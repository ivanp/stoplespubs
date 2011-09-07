<?php
$this->breadcrumbs=array(
	'Users'=>array('index'),
	$model->email,
);

$this->menu=array(
	array('label'=>'Update User', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete User', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Mailbox Settings', 'url'=>array('/mailbox/update', 'id'=>$model->id)),
);
?>

<h1>View User #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'email',
		'firstname',
		'lastname',
		'created:datetime',
		'updated:datetime',
		'lastlogin:datetime',
	),
)); 
