<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require_once __DIR__ . '/TestCase.php';

if (!defined('YII_ENABLE_ERROR_HANDLER')) {
    define('YII_ENABLE_ERROR_HANDLER', false);
}
if (!defined('YII_DEBUG')) {
    define('YII_DEBUG', true);
}
if (!defined('YII_ENV')) {
    define('YII_ENV', 'test');
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../web/index.php';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['HTTPS'] = false;