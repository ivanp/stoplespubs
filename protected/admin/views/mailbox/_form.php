<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'mailbox-form',
	'enableAjaxValidation'=>false,
)); 
if ($form instanceof CActiveForm); if ($model instanceof Mailbox);
?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>
	
	<div class="row">
		<?php
			$types=array(
				'imap'=>'IMAP4',
				'pop3'=>'POP3'
			);
		?>
		<?php echo $form->labelEx($model,'type'); ?>
		<?php echo $form->dropDownList($model,'type',$types) ?>
		<?php echo $form->error($model,'type'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'host'); ?>
		<?php echo $form->textField($model,'host',array('size'=>40,'maxlength'=>255)); ?>
		<?php echo $form->error($model,'host'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'port'); ?>
		<?php echo $form->textField($model,'port',array('size'=>5,'maxlength'=>5)); ?>
		<?php echo $form->error($model,'port'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'username'); ?>
		<?php echo $form->textField($model,'username',array('size'=>40,'maxlength'=>80)); ?>
		<?php echo $form->error($model,'username'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'password'); ?>
		<?php echo $form->passwordField($model,'password',array('size'=>40,'maxlength'=>80,'value'=>'')); ?>
		<?php echo $form->error($model,'password'); ?>
	</div>

	<div class="row">
		<?php
		$data=array(
			'none'=>'None',
			'ssl'=>'SSL',
			'tls'=>'TLS'
		);
		?>
		<?php echo $form->labelEx($model,'ssl'); ?>
		<?php echo $form->dropDownList($model,'ssl',$data) ?>
		<?php echo $form->error($model,'ssl'); ?>
	</div>

	<div class="row radiobuttons">
		<?php
		$data=array(
			'move'=>'Move mail to an IMAP folder (e.g. Trash)',
			'mark'=>'Just mark mail as deleted (recommended for GMail)',
			'delete'=>'Remove mail from server'
		);
		?>
		<?php echo $form->labelEx($model,'imap_action'); ?>
		<?php echo $form->radioButtonList($model,'imap_action',$data,array('labelOptions'=>array('class'=>'inline'))) ?>
		<?php echo $form->error($model,'imap_action'); ?>
	</div>
	
	<div class="row">
		<?php
		$data=array();
		?>
		<?php echo $form->labelEx($model,'imap_move_folder'); ?>
		<?php echo $form->dropDownList($model,'imap_move_folder',$data) ?>
		<?php echo $form->error($model,'imap_move_folder'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'active'); ?>
		<?php echo $form->checkBox($model,'active',array('value'=>1,'uncheckValue'=>0)) ?>
		<?php echo $form->error($model,'active'); ?>
	</div>
	
	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->