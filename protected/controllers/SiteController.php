<?php

class SiteController extends Controller
{
	/**
	 * @param CAction $action 
	 */
	public function beforeAction($action) 
	{
		$allowedActions=array('mailbox','logout');
		$webUser=Yii::app()->user;
		if ($webUser instanceof WebUser);
		if (!$webUser->isGuest && !in_array($action->getId(),$allowedActions))
		{
			$user=$webUser->getModel();
			if (!$user->hasMailbox())
			{
				$webUser->setFlash('global-notice', Yii::t('app', 'You need to setup Mailbox first'));
				$this->redirect(array('mailbox'));
			}
		}
		return parent::beforeAction($action);
	}
	/**
	 * Declares class-based actions.
	 */
	public function actions()
	{
		return array(
			// captcha action renders the CAPTCHA image displayed on the contact page
			'captcha'=>array(
				'class'=>'CCaptchaAction',
				'backColor'=>0xFFFFFF,
			),
			// page action renders "static" pages stored under 'protected/views/site/pages'
			// They can be accessed via: index.php?r=site/page&view=FileName
			'page'=>array(
				'class'=>'CViewAction',
			),
		);
	}
	
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index','error','page','contact','captcha','keygen'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('dashboard','logout','mailbox','imapsetting'),
				'users'=>array('@'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	public function actionIndex()
	{
		// If logged in, go to dashboard
		if (!Yii::app()->user->isGuest)
			$this->redirect(array('dashboard'));
		
		$this->layout='//layouts/column2';
		
		$login=new LoginForm;
		$register=new User('register');
		
		// if it is ajax validation request
		if(isset($_POST['ajax']))
		{
			switch ($_POST['ajax'])
			{
				case 'login-form':
					echo CActiveForm::validate($login);
					break;
				case 'register-form':
					echo CActiveForm::validate($register);
					break;
			}
			Yii::app()->end();
		}

		// collect user input data
		if(isset($_POST['LoginForm']))
		{
			$login->attributes=$_POST['LoginForm'];
			// validate user input and redirect to the previous page if valid
			if($login->validate() && $login->login())
				$this->redirect(Yii::app()->user->returnUrl);
		}
		elseif (isset($_POST['User']))
		{
			$transaction=Yii::app()->db->beginTransaction();
			try
			{
				$register->attributes=$_POST['User'];
				// Trial codes start
				$register->activeto=strtotime('+15 days');
				$register->billstatus=User::BillStatusTrial;
				// Trial codes end
				if($register->save())
				{
					$identity=new UserIdentity($register->email,$register->password_entered);
					Yii::app()->user->login($identity,86400); // 1 day
					if(!Yii::app()->user->isGuest)
					{
						$transaction->commit();
						$this->redirect(array('mailbox'));
					}
				}
			}
			catch(Exception $e)
			{
				$register->addError('_reg', $e->__toString());
			}
			$transaction->rollback();
		}
		
		// renders the view file 'protected/views/site/index.php'
		// using the default layout 'protected/views/layouts/main.php'
		$this->render('index',array('model_login'=>$login, 'model_register'=>$register));
	}
	
	public function actionMailbox()
	{
		$user=Yii::app()->user->getModel();
		if($user instanceof User);
		$hasMailbox=$user->hasMailbox();
		
		if($hasMailbox)
		{
			$mailbox=$user->mailbox;
		}
		else
		{
			$mailbox=new Mailbox();
			$mailbox->user_id=$user->id;
		}

		if(isset($_POST['User'],$_POST['Mailbox']))
		{
			$user->attributes=$_POST['User'];
			$mailbox->attributes=$_POST['Mailbox'];
			$transaction=Yii::app()->db->beginTransaction();
			try
			{
				// Try to connect first
				$save=$user->save() && $mailbox->save();
				$mailbox->openStorage();
				if($save)
				{
					$transaction->commit();
					Yii::app()->user->setFlash('global-success', Yii::t('app', 'Mailbox successfully connected'));
					if($mailbox->type==Mailbox::TypeImap)
						$this->redirect(array('imapsetting'));
					else
						$this->redirect(array('dashboard'));
				}
			}
			catch(Zend_Mail_Exception $e)
			{
				$mailbox->addError('_all',Yii::t('app', 'Cannot connect to specified mail server, please recheck your settings. Reason: <blockquote><pre>{reason}</pre></blockquote>', array('{reason}'=>$e->__toString())));
			}
			$transaction->rollback();
		}
		
		// Data to be passed to template
		$data=array();
		// Is it the first time user got here?
		if(empty($_POST) && !$hasMailbox)
		{
			// Get email domain
			list($localPart,$domainPart)=explode('@',$user->email);
			$mailDomain=MailDomain::model()->findByPk($domainPart);
			if ($mailDomain instanceof MailDomain)
			{
				$provider=$mailDomain->provider;
				$mailbox->type=$provider->type;
				$mailbox->host=$provider->hostname;
				$mailbox->port=$provider->port;
				$mailbox->ssl=$provider->ssl;
				
				switch($provider->username) 
				{
					case MailProvider::PlaceHolderEmailAddress:
						$mailbox->username=$user->email;
						break;
					case MailProvider::PlaceHolderEmailLocalPart:
					default:
						$mailbox->username=$localPart;
						break;
				}
				$data['provider']=$provider;
			}
		}
		
		$data['user']=$user;
		$data['mailbox']=$mailbox;
		$data['hasMailbox']=$hasMailbox;
		$this->render('mailbox',$data);
	}
	
	public function actionImapsetting()
	{
		$user=Yii::app()->user->getModel();
		$mailbox=$user->mailbox;
		if($mailbox->type!=Mailbox::TypeImap)
		{
			Yii::app()->user->setFlash('global-error','This page is only available for IMAP server type');
			$this->redirect(array('dashboard'));
		}
		
		if(isset($_POST['Mailbox']))
		{
			$mailbox->attributes=$_POST['Mailbox'];
			if($mailbox->save())
			{
				Yii::app()->user->setFlash('global-success', Yii::t('app', 'IMAP server settings saved succesfully'));
				$this->redirect(array('dashboard'));
			}
		}
		
		$folders=$mailbox->getImapFolders();
		if(empty($mailbox->imap_move_folder))
		{
			foreach($folders as $k=>$v)
			{
				if(preg_match('/trash/i',$k) || preg_match('/trash/i',$v))
				{
					$mailbox->imap_move_folder=$k;
					break;
				}
			}
		}
		
		if($mailbox instanceof Mailbox);
		$data=array(
			'mailbox'=>$mailbox,
			'folders'=>$folders
		);
		$this->render('imapsetting',$data);
	}

	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		if($error=Yii::app()->errorHandler->error)
		{
			if(Yii::app()->request->isAjaxRequest)
				echo $error['message'];
			else
				$this->render('error', $error);
		}
	}

	/**
	 * Displays the contact page
	 */
	public function actionContact()
	{
		$model=new ContactForm;
		if(isset($_POST['ContactForm']))
		{
			$model->attributes=$_POST['ContactForm'];
			if($model->validate())
			{
				$headers="From: {$model->email}\r\nReply-To: {$model->email}";
				mail(Yii::app()->params['adminEmail'],$model->subject,$model->body,$headers);
				Yii::app()->user->setFlash('contact','Thank you for contacting us. We will respond to you as soon as possible.');
				$this->refresh();
			}
		}
		$this->render('contact',array('model'=>$model));
	}

	/**
	 * Displays the login page
	 */
//	public function actionLogin()
//	{
//		$model=new LoginForm;
//
//		// if it is ajax validation request
//		if(isset($_POST['ajax']) && $_POST['ajax']==='login-form')
//		{
//			echo CActiveForm::validate($model);
//			Yii::app()->end();
//		}
//
//		// collect user input data
//		if(isset($_POST['LoginForm']))
//		{
//			$model->attributes=$_POST['LoginForm'];
//			// validate user input and redirect to the previous page if valid
//			if($model->validate() && $model->login())
//				$this->redirect(Yii::app()->user->returnUrl);
//		}
//		// display the login form
//		$this->render('login',array('model'=>$model));
//		
//		
//	}
	
//	public function actionRegister()
//	{
//		$model=new User;
//
//		// Uncomment the following line if AJAX validation is needed
//		// $this->performAjaxValidation($model);
//
//		if(isset($_POST['User']))
//		{
//			$model->attributes=$_POST['User'];
//			if($model->save())
//				$this->redirect(array('view','id'=>$model->id));
//		}
//
//		$this->render('create',array(
//			'model'=>$model,
//		));
//	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout()
	{
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
	}
	
	public function actionDashboard()
	{
		$this->layout='//layouts/column2';
		$user=Yii::app()->user->getModel();
		
		$expiry1=strtotime('-1 day');
		$expiry2=strtotime('-1 month');
		
		if($user instanceof User);
		
		switch($user->billstatus)
		{
			case User::BillStatusInactive:
				$status=Yii::t('app', '<strong>Inactive</strong>');
				$pay_msg=Yii::t('app', 'Click here to pay');
				break;
			case User::BillStatusPaid:
				if($user->activeto===null || $user->activeto < $expiry1)
				{
					$status=Yii::t('app', '<strong>Inactive</strong>');
					$pay_msg=Yii::t('app', 'Click here to pay');
				}
				elseif($user->activeto > $expiry2 && $user->activeto < $expiry1)
				{
					$dt=Zend_Date::now();
					$dt->sub($expiry2, Zend_Date::TIMESTAMP);
					$days=$dt->get(Zend_Date::DAY);
					$status=Yii::t('app', '<strong>Paid</strong> Expiring in {days} days', array('{days}'=>$days));
					$pay_msg=Yii::t('app', 'Click here to extend your subscription');
				}
				else
				{
					$status=Yii::t('app', '<strong>Paid</strong>');
					$pay_msg=Yii::t('app', 'Click here to extend your subscription');
				}
				break;
			case User::BillStatusTrial:
				if($user->activeto===null || $user->activeto < $expiry1)
				{
					$status=Yii::t('app', '<strong>Inactive</strong> (expired trial)');
					$pay_msg=Yii::t('app', 'Click here to pay');
				}
				else
				{
					$dt=new Zend_Date($user->activeto, Zend_Date::TIMESTAMP);
					$dt->sub(Zend_Date::now());
					// Doesn't count today
					$dt->sub(1,Zend_Date::DAY);
					$days=$dt->get(Zend_Date::DAY);
					$status=Yii::t('app', '<strong>Trial</strong> {days} days left', array('{days}'=>$days));
					$pay_msg=Yii::t('app', 'Click here to pay subscription');
				}
				break;
		}
		
		$data=array(
			'status'=>$status,
			'pay_msg'=>$pay_msg,
			'user'=>$user,
			'pay_url'=>$user->createPaymentUrl()
		);
		$this->render('dashboard',$data);
	}
	
	public function actionKeygen()
	{
		header('Content-type: text/plain');
		
		$user_id=CPropertyValue::ensureInteger($_GET['user_text']);
		if(!$user_id)
			return;
		$user=User::model()->findByPk($user_id);
		if($user===null)
			return;
		
		$order_no=CPropertyValue::ensureString($_GET['o_no']);
		$qty=CPropertyValue::ensureInteger($_GET['qty']);
		
		// Begin transaction
		$transaction=Yii::app()->db->beginTransaction();
		
		try
		{
			$payment=new Payment();
			$payment->user_id=$user->id;
			$payment->order_no=$order_no;
			$payment->qty=$qty;
			$payment->time=time();
			$payment->save();

			$now=Zend_Date::now();
			
			if($user->activeto===null)
			{
				$dt=$now;
			}
			else
			{
				$dt=new Zend_Date($user->activeto, Zend_date::TIMESTAMP);
				if($now->compare($dt)==-1)
				{
					$dt=$now;
				}
			}
			$dt->addYear($qty);
			$user->activeto=$dt->getTimestamp();
			$user->mailbox->active=1;
			$user->mailbox->save();
			$user->save();
			$transaction->commit();
		}
		catch(Exception $e)
		{
			$transaction->rollback();
		}
		
		return '<softshop></softshop>';
	}
}