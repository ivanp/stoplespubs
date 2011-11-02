<?php

$this->pageTitle=Yii::app()->name; 

$this->breadcrumbs=array(
	'Report Summary',
);

$this->menu=array(
	array('label'=>'Mailbox Settings', 'url'=>array('mailbox')),
);

?>
<table border="1" width="100%">
	<caption>Summary for the last 12 months</caption>
		<tr>
			<th>Months</th>
			<?php foreach($months as $info) { ?>
			<td><?php echo CHtml::link($info['title'], $info['url'])?></td>
			<?php } ?>
			<th>Total</th>
		</tr>
		<tr>
			<th>Deleted</th>
			<?php foreach($months as $info) { ?>
				<td><?php echo CHtml::encode($info['count'])?></td>
			<?php } ?>
				<th><?php echo CHtml::encode($total)?></th>
		</tr>
</table>