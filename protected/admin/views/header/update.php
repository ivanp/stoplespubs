<?php
$this->breadcrumbs=array(
	'Headers'=>array('index'),
	$model->name=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Header', 'url'=>array('index')),
	array('label'=>'Create Header', 'url'=>array('create')),
	array('label'=>'View Header', 'url'=>array('view', 'id'=>$model->id)),
);
?>

<h1>Update Header <?php echo $model->id; ?></h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>