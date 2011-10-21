<?php
$this->breadcrumbs=array(
	'Users'=>array('index'),
	'Create',
);

?>

<h1>Create User Account</h1>

<?php echo $this->renderPartial('_form', array('user'=>$user,'mailbox'=>$mailbox,'folders'=>array())); ?>