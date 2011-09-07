<?php
return array(
	// application components
	'components'=>array(
		'db'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=stoplespubs',
			'emulatePrepare' => true,
			'username' => 'stoplespubs',
			'password' => 'stoplespubs',
			'charset' => 'utf8',
		)
	),
	// application-level parameters that can be accessed
	// using Yii::app()->params['paramName']
	'params'=>array(
		// this is used in contact page
		'adminEmail'=>'ivan@primaguna.com',
		'appKey'=>'$4$DLNvMUQF$XbKOkuj+O5QHzmOE8Nxs10l61k8$',
		'defaultTimezone'=>'Asia/Jakarta',
		'SWREG.settings'=>array(
				'shopId'=>'132573',
				'productId'=>'132573-1',
				'variationId'=>'0',
				'deliveryId'=>'0'
		)
	),	
);