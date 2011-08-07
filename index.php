<?php

define('YII_DEBUG', true);
define('APPLICATION_MODE', 'user');
$webRoot=dirname(__FILE__);
require_once($webRoot.'/protected/vendors/yii/yii.php');
$app=Yii::createApplication('WebApplication', $webRoot.'/protected/config/main.php');
Yii::import("ext.yiiext.components.zendAutoloader.EZendAutoloader", true);
Yii::registerAutoloader(array("EZendAutoloader", "loadClass"));
$app->run();
