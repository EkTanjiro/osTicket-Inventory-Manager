<?php

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__file__).'/include');
return array(
    'bcombs18:inventory',
    'version' => '0.1',
    'name' => 'Inventory Manager',
    'author' => 'bcombs18',
    'description' => 'Inventory Asset Management',
    'url' => 'localhost',
    'plugin' => 'inventory.php:InventoryPlugin'
);