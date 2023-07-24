<?php 
if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }
/**
 *---------------------------------------------------------------
 * Autoloader / Compser
 *---------------------------------------------------------------
 *
 * We need to access our dependencies & autloader..
 */
require __DIR__ . DS . 'vendor' . DS . 'autoload.php';

/**
 * Set the timezone
 */
date_default_timezone_set('Europe/Zurich');

/**
 *---------------------------------------------------------------
 * Paths
 *---------------------------------------------------------------
 *
 * Setup paths needed in the application
 */
define('VISU_PATH_ROOT',         __DIR__);
define('VISU_PATH_CACHE',        VISU_PATH_ROOT . DS . 'var' . DS . 'cache');
define('VISU_PATH_STORE',        VISU_PATH_ROOT . DS . 'var' . DS . 'storage');
define('VISU_PATH_RESOURCES',    VISU_PATH_ROOT . DS . 'resources');
define('VISU_PATH_LEVELS',       VISU_PATH_RESOURCES . DS . 'levels');
define('VISU_PATH_RES_TERRAIN',  VISU_PATH_RESOURCES . DS . 'terrain');
define('VISU_PATH_APPCONFIG',    VISU_PATH_ROOT . DS . 'app');

/**
 *---------------------------------------------------------------
 * VISU
 *---------------------------------------------------------------
 *
 * Load the visu bootstrap file, which will create and return the
 * application container.
 */
$container = require __DIR__ . DS . 'vendor' . DS . 'phpgl' . DS . 'visu' . DS . 'bootstrap.php';

// this is the place to do custom things just after VISU initalized.
// you can also hook into `bootstrap.pre` and `bootstrap.pre` events.
// ...

// forward the container
return $container;