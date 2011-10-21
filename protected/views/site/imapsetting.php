<?php
$this->breadcrumbs=array(
	'Dashboard'=>array('dashboard'),
	'Mailbox Settings'=>array('mailbox'),
	'IMAP Server Settings'
);
?>

<h1>IMAP Server Settings</h1>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'mailbox-form',
	'enableAjaxValidation'=>false,
	'enableClientValidation'=>true,
	'clientOptions'=>array(
		'validateOnSubmit'=>true,
	)
)); 
if ($form instanceof CActiveForm); 
if ($mailbox instanceof Mailbox);
?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($mailbox); ?>
	
	<div class="row radiobuttons">
		<?php
		$data=array(
			'delete'=>'Immediately remove mail from server',
			'mark'=>'Just mark mail as deleted (recommended for GMail)',
			'move'=>'Move mail to an IMAP folder: ',
		);
		?>
		<?php echo $form->labelEx($mailbox,'imap_action'); ?>
		<?php echo $form->radioButtonList($mailbox,'imap_action',$data,array('class'=>'imap_action','labelOptions'=>array('class'=>'inline'))) ?>
		<?php echo $form->error($mailbox,'imap_action'); ?>
	</div>
	
	<div class="row imap_move_folder">
		<?php echo $form->labelEx($mailbox,'imap_move_folder'); ?>
		<?php echo $form->dropDownList($mailbox,'imap_move_folder',$folders) ?>
		<?php echo $form->error($mailbox,'imap_move_folder'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Save settings'); ?>
	</div>

<script type="text/javascript">
	jQuery(function() {
		var form=$('#mailbox-form');
		var select_container=form.find("div.row.imap_move_folder");
		var label=form.find('input[type=radio][value=move]').next();
		select_container.find("select").appendTo(label);
		select_container.remove();
	});
</script>
	
	
<?php $this->endWidget(); ?>

</div><!-- form -->