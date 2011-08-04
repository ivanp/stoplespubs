<?php
$this->breadcrumbs=array(
	'Mailboxes'=>array('index'),
	$model->id=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Mailbox', 'url'=>array('index')),
	array('label'=>'Create Mailbox', 'url'=>array('create')),
	array('label'=>'View Mailbox', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage Mailbox', 'url'=>array('admin')),
);
?>

<h1>Update Mailbox <?php echo $model->id; ?></h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>