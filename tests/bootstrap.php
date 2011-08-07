<?php
const TEST_ROOT = __DIR__;

// set paths
$path = array(
    realpath(TEST_ROOT . '/../library/'),
    realpath(TEST_ROOT . '/library'),
    get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $path));

// register autoloader's namespace
require('Zend/Loader/Autoloader.php');
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Mg_');

$resourceLoader = new Zend_Loader_Autoloader_Resource(array(
    'basePath'      => './application',
    'namespace'     => 'TestApp',
    'resourceTypes' => array(
        'dbtable' => array(
            'namespace' => 'Model_DbTable',
            'path'      => 'models/DbTable',
        ),
        'model' => array(
            'namespace' => 'Model',
            'path'      => 'models',
        ),
    ),
));

// clean up
unset($path, $autoloader, $resourceLoader);