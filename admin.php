<?php

define('YII_DEBUG', true);
define('APPLICATION_MODE', 'admin');
$webRoot=dirname(__FILE__);
require_once($webRoot.'/protected/vendors/yii/yii.php');
require_once($webRoot.'/protected/components/WebApplication.php');
$app=Yii::createApplication('WebApplication', $webRoot.'/protected/config/admin.php');
Yii::import("ext.yiiext.components.zendAutoloader.EZendAutoloader", true);
Yii::registerAutoloader(array("EZendAutoloader", "loadClass"));
$app->run();
