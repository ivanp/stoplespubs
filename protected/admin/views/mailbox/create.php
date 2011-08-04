<?php
$this->breadcrumbs=array(
	'Mailboxes'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List Mailbox', 'url'=>array('index')),
	array('label'=>'Manage Mailbox', 'url'=>array('admin')),
);
?>

<h1>Create Mailbox</h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>