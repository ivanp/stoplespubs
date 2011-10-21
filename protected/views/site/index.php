<?php 
$this->pageTitle=Yii::app()->name; 

if (Yii::app()->user->isGuest)
{
	$this->portlets[] = array(
		'title' => 'Login',
		'content' => $this->renderPartial('portlets/login', array('model'=>$model_login), true)
	);
}
?>

<h1>Signup to <i><?php echo CHtml::encode(Yii::app()->name); ?></i></h1>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'register-form',
	'enableAjaxValidation'=>false,
	'enableClientValidation'=>true,
	'clientOptions'=>array(
		'validateOnSubmit'=>true,
	)
));
if ($form instanceof CActiveForm);
?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model_register); ?>

	<div class="row">
		<?php echo $form->labelEx($model_register,'email'); ?>
		<?php echo $form->textField($model_register,'email',array('size'=>40,'maxlength'=>255)); ?>
		<?php echo $form->error($model_register,'email'); ?>
	</div>
	
	<div class="row">
		<?php echo $form->labelEx($model_register,'password_entered'); ?>
		<?php echo $form->passwordField($model_register,'password_entered',array('size'=>40,'maxlength'=>255)); ?>
		<?php echo $form->error($model_register,'password_entered'); ?>
	</div>
	
	<div class="row">
		<?php echo $form->labelEx($model_register,'firstname'); ?>
		<?php echo $form->textField($model_register,'firstname',array('size'=>40,'maxlength'=>80)); ?>
		<?php echo $form->error($model_register,'firstname'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model_register,'lastname'); ?>
		<?php echo $form->textField($model_register,'lastname',array('size'=>40,'maxlength'=>80)); ?>
		<?php echo $form->error($model_register,'lastname'); ?>
	</div>
	
	<div class="row">
		<?php echo $form->checkBox($model_register,'is_accept_cos',array('uncheckValue'=>'0','value'=>'1')); ?> <?php echo $form->labelEx($model_register,'is_accept_cos',array('style'=>'display: inline')); ?>
		<?php echo $form->error($model_register,'is_accept_cos'); ?>
	</div>
	
	<div class="row buttons">
		<?php echo CHtml::submitButton('Sign Up'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->