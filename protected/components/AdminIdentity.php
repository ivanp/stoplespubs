<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class AdminIdentity extends CUserIdentity
{
//	/**
//	 * Authenticates a user.
//	 * The example implementation makes sure if the username and password
//	 * are both 'demo'.
//	 * In practical applications, this should be changed to authenticate
//	 * against some persistent user identity storage (e.g. database).
//	 * @return boolean whether authentication succeeds.
//	 */
//	public function authenticate()
//	{
//		$user=AdminUser::model()->find('username=?', array($this->username));
//		if (!($user instanceof AdminUser))
//			$this->errorCode=self::ERROR_USERNAME_INVALID;
//		else if($user->password!==AdminUser::hashPassword($this->password))
//			$this->errorCode=self::ERROR_PASSWORD_INVALID;
//		else
//			$this->errorCode=self::ERROR_NONE;
//		return !$this->errorCode;
//	}
	
	/**
	 * @var AdminUser
	 */
	private $_user;
	
	public function __construct($username,$password)
	{
		$this->_user=AdminUser::model()->find('username=?', array($username));
		parent::__construct($username,$password);
	}
	/**
	 * Authenticates a user.
	 * The example implementation makes sure if the username and password
	 * are both 'demo'.
	 * In practical applications, this should be changed to authenticate
	 * against some persistent user identity storage (e.g. database).
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		if (!($this->_user instanceof AdminUser))
			$this->errorCode=self::ERROR_USERNAME_INVALID;
		else if($this->_user->password!==AdminUser::hashPassword($this->password))
			$this->errorCode=self::ERROR_PASSWORD_INVALID;
		else
			$this->errorCode=self::ERROR_NONE;
		return !$this->errorCode;
	}
	
	public function getId()
	{
		return ($this->_user instanceof AdminUser) ? $this->_user->id : null;
	}
}