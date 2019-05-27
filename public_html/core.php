<?php

define('APP_PATH', __DIR__ . '/../app/');
define('ROOT_PATH', dirname(realpath(APP_PATH)) . DIRECTORY_SEPARATOR);
define('VENDOR_PATH', __DIR__ . '/../vendor/');
define('SITE_PATH', __DIR__);
define('APP_DEBUG', 1);

define('ADDON_PATH', APP_PATH.'addons');
define('API_PATH', APP_PATH.'api');

define('SYS_NAME', '品用CMS');
define('SYS_VERSION', '2.1 building');

define('RUNTIME_PATH',ROOT_PATH.'#r/');

define('COM_RES','/z/common');
define('RES','/z/'.BIND_MODULE);
define('IMG',RES.'/img');
define('JS',RES.'/js');

?>