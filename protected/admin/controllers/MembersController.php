<?php

class MembersController extends AdminController
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$user=new User();
		$mailbox=new Mailbox();
		$mailbox->user_id=$user->id;
		
		$save_user=$save_mailbox=false;
		if(isset($_POST['User']))
		{
			$user->attributes=$_POST['User'];
			$save_user=true;
		}
		if(isset($_POST['Mailbox']))
		{
			$mailbox->attributes=$_POST['Mailbox'];
			$save_mailbox=true;
		}

		if($save_user && $save_mailbox)
		{
			$transaction=Yii::app()->db->beginTransaction();
			try
			{
				$save=$user->save() && $mailbox->save();
				// Try to connect first
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
		$data=array(
			'folders'=>array(),
			'provider'=>null,
		);
		// Is it the first time user got here?
		if(isset($user->email))
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
		$data['folders']=array();
//		$data['hasMailbox']=$hasMailbox;
		$this->render('create',$data);
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['User']))
		{
			$user->attributes=$_POST['User'];
			$user->setScenario('admin_update');
			if($user->save())
				$this->redirect(array('view','id'=>$user->id));
		}

		$this->render('update',array(
			'user'=>$model,
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		if(Yii::app()->request->isPostRequest)
		{
			// we only allow deletion via POST request
			$this->loadModel($id)->delete();

			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if(!isset($_GET['ajax']))
				$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
//	{
//		$dataProvider=new CActiveDataProvider('User');
//		$this->render('index',array(
//			'dataProvider'=>$dataProvider,
//		));
//	}
//
//	/**
//	 * Manages all models.
//	 */
//	public function actionAdmin()
	{
		$model=new User('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['User']))
			$model->attributes=$_GET['User'];

		$this->render('index',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id)
	{
		$model=User::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='user-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
