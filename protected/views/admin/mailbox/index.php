<?php
$this->breadcrumbs=array(
	'Mailboxes',
);

$this->menu=array(
	array('label'=>'Create Mailbox', 'url'=>array('create')),
	array('label'=>'Manage Mailbox', 'url'=>array('admin')),
);
?>

<h1>Mailboxes</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
