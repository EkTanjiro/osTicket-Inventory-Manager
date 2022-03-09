<?php

namespace controller;

require_once(INCLUDE_DIR . 'class.staff.php');

use Format;
use model\AssetForm;
use Mpdf\Tag\P;
use User;

class Asset {
    function addAsset() {
        global $thisstaff;

        $info = array();

        if (!\AuthenticationBackend::getSearchDirectories())
            $info['lookup'] = 'local';

        if ($_POST) {
            if (!$thisstaff->hasPerm(User::PERM_CREATE))
                \Http::response(403, 'Permission Denied');

            $info['title'] = __('Add New User');
            $form = \model\AssetForm::getAssetForm()->getForm($_POST);
            if (($asset = \model\Asset::fromForm($form)))
                \Http::response(201, $asset->to_json(), 'application/json');

            $info['error'] = sprintf('%s - %s', __('Error adding asset'), __('Please try again!'));
        }

        return self::_lookupform($form, $info);
    }

    function editAsset($id) {
        global $thisstaff;

        if(!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_EDIT))
            Http::response(403, 'Permission Denied');
        elseif(!($asset = \model\Asset::lookup($id)))
            Http::response(404, 'Unknown user');

        $info = array(
            'title' => sprintf(__('Update %s'), Format::htmlchars($asset->getHostname()))
        );
        $forms = $asset->getForms();

        include(INVENTORY_VIEWS_DIR . 'asset.tmpl.php');
    }

    function updateAsset($id) {
        global $thisstaff;

        if(!$thisstaff)
            \Http::response(403, 'Login Required');
        elseif (!$thisstaff->hasPerm(User::PERM_EDIT))
            \Http::response(403, 'Permission Denied');
        elseif(!($asset = \model\Asset::lookup($id)))
            \Http::response(404, 'Unknown asset');

        $errors = array();
        $form = AssetForm::getAssetForm()->getForm($_POST);

        if ($asset->updateInfo($_POST, $errors, true) && !$errors)
            \Http::response(201, $asset->to_json(),  'application/json');

        $forms = $asset->getForms();
        include(INVENTORY_VIEWS_DIR . 'asset.tmpl.php');
    }

    function getAsset($id=false) {

        if(($asset=\model\Asset::lookup(($id) ? $id : $_REQUEST['id'])))
            Http::response(201, $asset->to_json(), 'application/json');

        $info = array('error' => sprintf(__('%s: Unknown or invalid ID.'), _N('asset', 'assets', 1)));

        return self::_lookupform(null, $info);
    }

    function preview($id) {
        global $thisstaff;

        if(!$thisstaff)
            \Http::response(403, 'Login Required');
        elseif(!($asset = \model\Asset::lookup($id)))
            \Http::response(404, 'Unknown asset');

        $info = array(
            'title' => '',
            'assetedit' => sprintf('#asset/%d/edit', $asset->getId()),
        );
        ob_start();
        echo sprintf('<div style="width:650px; padding: 2px 2px 0 5px;"
                id="u%d">', $asset->getId());
        include(INVENTORY_VIEWS_DIR . 'asset.tmpl.php');
        echo '</div>';
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;

    }

    static function _lookupform($form=null, $info=array()) {
        global $thisstaff;

        if (!$info or !$info['title']) {
            if ($thisstaff->hasPerm(User::PERM_CREATE))
                $info += array('title' => __('Lookup or create an asset'));
            else
                $info += array('title' => __('Lookup an asset'));
        }

        ob_start();
        include(INVENTORY_VIEWS_DIR . 'addAsset.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;
    }

    function handle() {
        require_once 'model\assets.php';
    }

}
