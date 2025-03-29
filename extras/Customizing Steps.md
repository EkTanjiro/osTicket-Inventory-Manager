# **Customizing osTicket Navigation to Use Plugin Registration for Inventory Manager**
This guide explains how to update the osTicket navigation so that the “Applications” tab is replaced by “Inventory Manager” and points to the Inventory Manager plugin’s URL using plugin registration. The guide uses a constant (INVENTORY_WEB_ROOT) so that you can easily change the base URL without hardcoding your domain.

## **Prerequisites**
- **osTicket Installation:** Ensure you have a working osTicket instance.

- **Inventory Manager Plugin Installed:** The Inventory Manager plugin should be installed and registered.
The plugin typically registers its staff app using a line like this in /include/plugins/inventory-manager/inventory.php:

```php
$app->registerStaffApp('Inventory Manager', INVENTORY_WEB_ROOT.'asset/handle');
```
- **Backup:** Always back up your installation files and database before making changes.

## Steps
### 1. Backup the Navigation File
Before modifying any core files, back up the navigation file. For example, run:

```bash
cp /var/www/osticket/include/class.nav.php /var/www/osticket/include/class.nav.php.bak
```
### 2. Locate the Navigation Tab Definition
Open the file /var/www/osticket/include/class.nav.php in your preferred text editor. Look for the section defining the navigation tabs. You should find lines similar to:

```php
$this->tabs['apps'] = array('desc'=>__('Applications'), 'href'=>'apps.php', 'title'=>__('Applications'));
$tabs['apps']    = array('desc'=>__('Applications'), 'href'=>'apps.php', 'title'=>__('Applications'));
```
### 3. Update the Tab to Use the Plugin Registration URL
Replace the existing definition with the following code. This change sets the tab description to “Inventory Manager” and uses the plugin registration URL based on the INVENTORY_WEB_ROOT constant:

```php
$this->tabs['apps'] = array(
    'desc'  => __('Inventory Manager'),
    'href'  => INVENTORY_WEB_ROOT.'asset/handle',
    'title' => __('Inventory Manager')
);
$tabs['apps'] = array(
    'desc'  => __('Inventory Manager'),
    'href'  => INVENTORY_WEB_ROOT.'asset/handle',
    'title' => __('Inventory Manager')
);
```
**Notes:**

- **Generic URL:** The code uses INVENTORY_WEB_ROOT.'asset/handle' to construct the URL. This constant should be defined within your Inventory Manager plugin configuration.

- **Defining INVENTORY_WEB_ROOT:** If not already defined, you can define it (for example, in a configuration file) as a relative or absolute URL. For example:

```php
define('INVENTORY_WEB_ROOT', '/scp/dispatcher.php/inventory/');
```
This way, the URL becomes generic and independent of a specific domain.

## 4. Save and Test
- **Save** the changes to class.nav.php.

- **Clear Cache:** If your osTicket installation uses caching, clear the cache.

- **Test the Navigation:** Log in to the admin panel. The “Applications” tab should now display as “Inventory Manager.” When clicked, it should navigate to the URL constructed from INVENTORY_WEB_ROOT (for example, /scp/dispatcher.php/inventory/asset/handle).

## 5. Commit and Upload to GitHub
After verifying that the changes work as expected, commit the changes with a clear commit message. For example:

```bash
git add include/class.nav.php
git commit -m "Update navigation: Change Applications tab to Inventory Manager using INVENTORY_WEB_ROOT"
git push origin your-branch-name
```
## Future Updates
- **Maintainability:** Document this customization in your project’s README or changelog so that future maintainers are aware of the changes.

- **Core Updates:** If you update osTicket to a new version, reapply this change if the core navigation file is updated.

- **Override via Themes/Plugins:** If osTicket later supports overriding core navigation via themes or custom plugins, consider migrating this customization to avoid core file modifications.
---

This guide provides a generic approach to updating the navigation for the Inventory Manager plugin without hardcoding a specific domain name. The use of the INVENTORY_WEB_ROOT constant makes it easy to change the base URL as needed.
