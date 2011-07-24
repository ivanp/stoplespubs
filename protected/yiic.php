<?php
/**
 * Most of the files were copied from Yii's yiic.php with modification 
 * to support Zend Framework.
 */

// change the following paths if necessary
$config=dirname(__FILE__).'/config/console.php';

defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

defined('YII_DEBUG') or define('YII_DEBUG',true);

require_once(dirname(__FILE__).'/vendors/yii/yii.php');

if(isset($config))
{
	$app=Yii::createConsoleApplication($config);
	$app->commandRunner->addCommands(YII_PATH.'/cli/commands');
	$env=@getenv('YII_CONSOLE_COMMANDS');
	if(!empty($env))
		$app->commandRunner->addCommands($env);
}
else
	$app=Yii::createConsoleApplication(array('basePath'=>dirname(__FILE__).'/cli'));

// Zend stuff
Yii::import("ext.yiiext.components.zendAutoloader.EZendAutoloader", true);
Yii::registerAutoloader(array("EZendAutoloader", "loadClass"));

// Run app
$app->run();