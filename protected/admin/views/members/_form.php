<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'mailbox-form',
	'enableAjaxValidation'=>false,
	'enableClientValidation'=>true,
	'clientOptions'=>array(
		'validateOnSubmit'=>true,
	)
)); 
//if ($form instanceof CActiveForm); 
//if ($user instanceof User);
//if ($mailbox instanceof Mailbox);
//if ($provider instanceof MailProvider);


?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary(array($user, $mailbox)); ?>
	
	<div class="row">
		<?php echo $form->labelEx($user,'email'); ?>
		<?php echo $form->textField($user,'email',array('size'=>40,'maxlength'=>255)); ?>
		<?php echo $form->error($user,'email'); ?>
	</div>
	
	<div class="row">
		<?php echo $form->labelEx($user,'password_entered'); ?>
		<?php echo $form->passwordField($user,'password_entered',array('size'=>40,'maxlength'=>255)); ?>
		<?php echo $form->error($user,'password_entered'); ?>
		<br/>Leave password empty to leave it unchanged.
	</div>
	
	<div class="row">
		<?php echo $form->labelEx($user,'firstname'); ?>
		<?php echo $form->textField($user,'firstname',array('size'=>40,'maxlength'=>80)); ?>
		<?php echo $form->error($user,'firstname'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($user,'lastname'); ?>
		<?php echo $form->textField($user,'lastname',array('size'=>40,'maxlength'=>80)); ?>
		<?php echo $form->error($user,'lastname'); ?>
	</div>
	
	<?php
		if($user instanceof User);
		if($user->getIsNewRecord() && !isset($_POST['Mailbox']))
	?>
	<?php if(isset($provider)): ?>
	<div class="flash-success">
		<table border="1">
			<caption>We have detected settings for your e-mail. You can safely ignore all the fields below and click Save.</caption>
			<thead>
				<tr>
					<th>Provider</th>
					<th>Type</th>
					<th>Hostname:Port</th>
					<th>SSL</th>
					<th>Username</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo CHtml::encode($provider->name); ?></td>
					<td><?php echo CHtml::encode(strtoupper($provider->type)); ?></td>
					<td><?php echo CHtml::encode($provider->hostname).':'.$provider->port; ?></td>
					<td><?php echo CHtml::encode(strtoupper($provider->ssl)); ?></td>
					<td><?php echo CHtml::encode($mailbox->username); ?></td>
				</tr>
			</tbody>
		</table>
	</div>
	<?php elseif (empty($_POST)): ?>
	<div class="flash-notice">
		We can't find the right settings for your e-mail, please provide your mail server's settings in the fields below. If you are unsure about what to fill here, please contact your mail server administrator.
	</div>
	<?php endif; ?>

	<div class="row">
		<?php
			$types=array(
				'imap'=>'IMAP4',
				'pop3'=>'POP3'
			);
		?>
		<?php echo $form->labelEx($mailbox,'type'); ?>
		<?php echo $form->dropDownList($mailbox,'type',$types) ?>
		<?php echo $form->error($mailbox,'type'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($mailbox,'host'); ?>
		<?php echo $form->textField($mailbox,'host',array('size'=>40,'maxlength'=>255)); ?>
		<?php echo $form->error($mailbox,'host'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($mailbox,'port'); ?>
		<?php echo $form->textField($mailbox,'port',array('size'=>5,'maxlength'=>5)); ?>
		<?php echo $form->error($mailbox,'port'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($mailbox,'username'); ?>
		<?php echo $form->textField($mailbox,'username',array('size'=>40,'maxlength'=>80)); ?>
		<?php echo $form->error($mailbox,'username'); ?>
	</div>

	<div class="row">
		<?php
		$data=array(
			'none'=>'None',
			'ssl'=>'SSL/TLS',
			'tls'=>'STARTTLS'
		);
		?>
		<?php echo $form->labelEx($mailbox,'ssl'); ?>
		<?php echo $form->dropDownList($mailbox,'ssl',$data) ?>
		<?php echo $form->error($mailbox,'ssl'); ?>
	</div>
	
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
		<?php echo CHtml::submitButton('Save this settings',array('name'=>'submit','value'=>'save')); ?>
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