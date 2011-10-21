<?php
$this->pageTitle=Yii::app()->name; 

$this->breadcrumbs=array(
	'Dashboard',
);

$this->menu=array(
	array('label'=>'Mailbox Settings', 'url'=>array('mailbox')),
);

?>
<h1>Dashboard</h1>

<p>Payment status: <?php echo $status; ?></p>

<p><?php echo CHtml::link($pay_msg, $pay_url); ?></p>
