<?php

// ensure we get report on all possible php errors
error_reporting(-1);

define('YII_ENABLE_ERROR_HANDLER', false);
define('YII_DEBUG', true);

define('RUNTIME_DIR', __DIR__ . '/runtime');

$_SERVER['SCRIPT_NAME'] = '/' . __DIR__;
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../vendor/ziiframework/zii/src/Yii.php');

Yii::setAlias('@yiiunit/integrations', __DIR__);
Yii::setAlias('@yii/integrations', dirname(__DIR__) . '/src');
