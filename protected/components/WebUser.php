<?php

class WebUser extends CWebUser
{
	private $_user;
	
	protected function getModelName()
	{
		return 'User';
	}
	
	/**
	 * @return User
	 */
	public function getModel()
	{
		if (!$this->getIsGuest() && !isset($this->_user))
		{
			$model=call_user_func_array(array($this->getModelName(), 'model'), array());
			$this->_user=$model->findByPk($this->getId());
			if($this->_user===null)
			{
				// Session says i'm logged in but can't find that user in the database
				// automatically logout in this kind of event
				Yii::app()->user->logout();
				Yii::app()->getRequest()->redirect(Yii::app()->getHomeUrl());
			}
		}
		return $this->_user;
	}
	
	protected function afterLogin($fromCookie)
	{
		if(!$fromCookie)
		{
			$user=$this->getModel();
			if($user!==null)
			{
				$user->lastlogin=time();
				$user->save();
			}
		}
		parent::afterLogin($fromCookie);
	}
}