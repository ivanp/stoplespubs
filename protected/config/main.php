<?php

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');

// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return CMap::mergeArray(
	// Database configuration
	require(dirname(__FILE__).'/common.php'),
	array(
		'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
		'name'=>'My Web Application',

		// preloading 'log' component
		'preload'=>array('log'),

		// autoloading model and component classes
		'import'=>array(
			'application.models.*',
			'application.models.forms.*',
			'application.components.*',
			'application.vendors.*'
		),

		'modules'=>array(
			// uncomment the following to enable the Gii tool
			'gii'=>array(
				'class'=>'system.gii.GiiModule',
				'password'=>'kraken',
				// If removed, Gii defaults to localhost only. Edit carefully to taste.
				'ipFilters'=>array('127.0.0.1','::1'),
			),
		),

		// application components
		'components'=>array(
			'session'=>array(
				'sessionName'=>'stoplesuser'
			),
			'urlManager'=>array(
				'showScriptName' => false, // remove index.php in URL
				'urlFormat'=>'path',
			),
			'user'=>array(
				'class'=>'WebUser',
				// enable cookie-based authentication
				'allowAutoLogin'=>true,
				'loginUrl' => array('/site/index')
			),
			// uncomment the following to enable URLs in path-format
			/*
			'urlManager'=>array(
				'urlFormat'=>'path',
				'rules'=>array(
					'<controller:\w+>/<id:\d+>'=>'<controller>/view',
					'<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
					'<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
				),
			),
			*/
			'errorHandler'=>array(
				// use 'site/error' action to display errors
							'errorAction'=>'site/error',
					),
			'log'=>array(
				'class'=>'CLogRouter',
				'routes'=>array(
					array(
						'class'=>'CFileLogRoute',
						'levels'=>'error, warning',
					),
					// uncomment the following to show log messages on web pages
					/*
					array(
						'class'=>'CWebLogRoute',
					),
					*/
				),
			),
		),

		// application-level parameters that can be accessed
		// using Yii::app()->params['paramName']
	)
);