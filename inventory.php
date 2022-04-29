<?php

require_once(INCLUDE_DIR.'class.plugin.php');
require_once(INCLUDE_DIR.'class.signal.php');
require_once(INCLUDE_DIR.'class.app.php');
require_once(INCLUDE_DIR.'class.dispatcher.php');
require_once(INCLUDE_DIR.'class.osticket.php');
require_once(INCLUDE_DIR.'class.import.php');
require_once('config.php');

const INVENTORY_TABLE = TABLE_PREFIX . 'inventory_asset';

define ( 'OST_WEB_ROOT', osTicket::get_root_path ( __DIR__ ) );

const INVENTORY_WEB_ROOT = OST_WEB_ROOT . 'scp/dispatcher.php/inventory/';

const OST_ROOT = INCLUDE_DIR . '../';
const INVENTORY_PLUGIN_ROOT = __DIR__ . '/';
const INVENTORY_INCLUDE_DIR = INVENTORY_PLUGIN_ROOT . 'include/';
const INVENTORY_MODEL_DIR = INVENTORY_INCLUDE_DIR . 'model/';
const INVENTORY_CONTROLLER_DIR = INVENTORY_INCLUDE_DIR . 'controller/';

const INVENTORY_ASSETS_DIR = INVENTORY_PLUGIN_ROOT . 'assets/';
const INVENTORY_VENDOR_DIR = INVENTORY_PLUGIN_ROOT . 'vendor/';
const INVENTORY_VIEWS_DIR = INVENTORY_PLUGIN_ROOT . 'views/';

require_once INVENTORY_VENDOR_DIR.'autoload.php';
spl_autoload_register(array(
    'InventoryPlugin',
    'autoload'
));

class InventoryPlugin extends Plugin {

    var $config_class = 'InventoryConfig';

    public static function autoload($className) {
        $className = ltrim ( $className, '\\' );
        $fileName = '';
        $namespace = '';
        if ($lastNsPos = strrpos ( $className, '\\' )) {
            $namespace = substr ( $className, 0, $lastNsPos );
            $className = substr ( $className, $lastNsPos + 1 );
            $fileName = str_replace ( '\\', DIRECTORY_SEPARATOR, $namespace ) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace ( '_', DIRECTORY_SEPARATOR, $className ) . '.php';
        $fileName = 'include/' . $fileName;

        if (file_exists ( INVENTORY_PLUGIN_ROOT . $fileName )) {
            require $fileName;
        }
    }

    public function bootstrap() {
        if($this->firstRun()) {
            if(!$this->configureFirstRun()) {
                return false;
            }
        }

        $config = $this->getConfig();

        if($config->get('inventory_backend_enable')) {
            $this->createStaffMenu();
        }

        Signal::connect( 'apps.scp', array(
            'InventoryPlugin',
            'callbackDispatch'
        ));
    }

    static public function callbackDispatch($object, $data) {
        $media_url = url ( '^/inventory.*assets/',
            patterns ( 'controller\MediaController',
                url_get ( '^(?P<url>.*)$', 'defaultAction' )
            )
        );

        $dashboard_url = url ( '^/inventory.*dashboard',
            patterns ( 'controller\Dashboard',
                url_get('^/retired', 'viewRetired'),
                url_get ( '^/active', 'viewAction' ),
                url('/handle', 'handle')
            )
        );

        $asset_url = url ( '^/inventory.*asset',
            patterns( 'controller\Asset',
                url_get('^/(?P<id>\d+)$', 'getAsset'),
                url_post('^/(?P<id>\d+)$', 'updateAsset'),
                url_get('^/(?P<id>\d+)/edit$', 'editAsset'),
                url_get('^/(?P<id>\d+)/delete$', 'delete'),
                url_post('^/(?P<id>\d+)/delete$', 'delete'),
                url_get('^/(?P<id>\d+)/preview$', 'preview'),
                url_get('^/(?P<id>\d+)/user$', 'viewUser'),
                url_get('^/(?P<id>\d+)/change-user$', 'changeUserForm'),
                url_post('^/(?P<id>\d+)/note$', 'createNote'),
                url_get('^/(?P<id>\d+)/retire$', 'retire'),
                url_post('^/(?P<id>\d+)/retire$', 'retire'),
                url_get('^/(?P<id>\d+)/activate$', 'activate'),
                url_post('^/(?P<id>\d+)/activate$', 'activate'),
                url_get('^/lookup/form$', 'lookup'),
                url_post('^/lookup/form$', 'addAsset'),
                url('^/search',
                    patterns('controller\Search',
                        url_get('^$', 'getAdvancedSearchDialog'),
                        url_post('^$', 'doSearch'),
                        url_get('^/(?P<id>\d+)$', 'editSearch'),
                        url_get('^/adhoc,(?P<key>[\w=/+]+)$', 'getAdvancedSearchDialog'),
                        url_get('^/create$', 'createSearch'),
                        url_post('^/(?P<id>\d+)/save$', 'saveSearch'),
                        url_post('^/save$', 'saveSearch'),
                        url_delete('^/(?P<id>\d+)$', 'deleteSearch'),
                        url_get('^/field/(?P<id>[\w_!:]+)$', 'addField'),
                        url('^/column/edit/(?P<id>\d+)$', 'editColumn'),
                        url('^/sort/edit/(?P<id>\d+)$', 'editSort'),
                        url_post('^(?P<id>\d+)/delete$', 'deleteQueues'),
                        url_post('^(?P<id>\d+)/disable$', 'disableQueues'),
                        url_post('^(?P<id>\d+)/enable$', 'undisableQueues')
                    )),
                url('^/queue', patterns('controller\Search',
                    url('^(?P<id>\d+/)?preview$', 'previewQueue'),
                    url_get('^(?P<id>\d+)$', 'getQueue'),
                    url_get('^addColumn$', 'addColumn'),
                    url_get('^condition/add$', 'addCondition'),
                    url_get('^condition/addProperty$', 'addConditionProperty'),
                    url_get('^counts$', 'collectQueueCounts'),
                    url('^/(?P<id>\d+)/delete$', 'deleteQueue')
                )),
                url('/add', 'addAsset'),
                url('/handle', 'handle')
            )
        );

        $import_url = url('^/inventory.*import',
            patterns('controller\Import',
                url('/bulk', 'importAssets'),
                url('/handle', 'handle')
            )
        );

        $object->append ( $media_url );
        $object->append ( $import_url );
        $object->append ( $asset_url );
        $object->append ( $dashboard_url );
    }

    function createStaffMenu() {
        $app = new Application();
        $app->registerStaffApp('Inventory', INVENTORY_WEB_ROOT.'asset/handle');
    }

    function firstRun() {
        $sql = 'SHOW TABLES LIKE \'' . INVENTORY_TABLE . '\'';
        $res = db_query($sql);
        return (db_num_rows($res) == 0);
    }

    function configureFirstRun() {
        if(!$this->createDBTables()) {
            echo "First run configuration error. " . "Unable to create database tables!";
            return false;
        }

        if(!$this->executeFileCopy()) {
            echo "First run configuration error. " . "Unable to copy necessary files!";
            return false;
        }

        return true;
    }

    function createDBTables() {
        $installer = new \util\InventoryInstaller();
        return  $installer->install();
    }

    function executeFileCopy() {
        if(!copy(INVENTORY_PLUGIN_ROOT."dispatcher.php", OST_ROOT."scp/dispatcher.php")) {
            return false;
        }
    }

    function pre_uninstall(&$errors) {
        $installer = new \util\InventoryInstaller();
        return $installer->remove();
    }
}