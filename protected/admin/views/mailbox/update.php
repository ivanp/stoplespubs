<?php
$this->breadcrumbs=array(
	'Users'=>array('/members/index'),
	$model->user->email=>array('/members/view','id'=>$model->user->id),
	'Mailbox',
);

$this->menu=array(
	array('label'=>'Back to View User', 'url'=>array('/members/view', 'id'=>$model->user->id)),
);
?>

<h1>Update Mailbox <?php echo $model->user->email; ?></h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>