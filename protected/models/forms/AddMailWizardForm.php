<?php

class AddMailWizardForm extends CFormModel
{
	// Step 1
	public $email;
	public $password;
	
	// Step 2
	const TypeImap='imap';
	const TypePop3='pop3';
	public $type;
	public $hostname;
	public $port;
	public $ssl;
}