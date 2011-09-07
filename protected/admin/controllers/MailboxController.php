<?php

class MailboxController extends AdminController
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';

	/**
	 * @return array action filters
	 */
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
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('update'),
				'users'=>array('@'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the User ID
	 */
	public function actionUpdate($id)
	{
		$user=User::model()->findByPk($id);
		if($user===null)
			throw new CHttpException(404,'The requested page does not exist.');
		$mailbox=$user->mailbox;
		if($mailbox===null)
		{
			$mailbox=new Mailbox();
			$mailbox->user_id=$user->id;
		}

		// Uncomment the following line if AJAX validation is needed
		$this->performAjaxValidation($mailbox);

		if(isset($_POST['Mailbox']))
		{
			$mailbox->attributes=$_POST['Mailbox'];
			if($mailbox->save())
				$this->redirect(array('/members/view','id'=>$user->id));
		}

		$this->render('update',array(
			'model'=>$mailbox,
		));
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='mailbox-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
	
	public function actionTestImapMailbox()
	{
		
	}
}
