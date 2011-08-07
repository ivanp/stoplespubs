<?php

/**
 * This is the model class for table "user".
 *
 * The followings are the available columns in table 'user':
 * @property string $id
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string $firstname
 * @property string $lastname
 * @property string $updated
 * @property string $created
 * @property string $lastlogin
 * @property string $level
 *
 * The followings are the available model relations:
 * @property Mailbox[] $mailboxes
 */
class User extends CActiveRecord
{
	const HashAlgorithm='sha256';
	
	public $password_repeat;
	public $password_modified=false;
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return User the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('username, password, email', 'length', 'max'=>255),
			array('firstname, lastname', 'length', 'max'=>80),
			array('firstname, lastname', 'required', 'on'=>'register,update'),
			array('username', 'unique'),
			array('email', 'email'),
			array('email', 'unique'),
			array('updated, created, lastlogin', 'unsafe'),
			array('password', 'length', 'min'=>4, 'on'=>'update'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, username, password, email, firstname, lastname, updated, created, lastlogin', 'safe', 'on'=>'search'),
			array('password', 'compare', 'compareAttribute'=>'password_repeat', 'on'=>'register,update'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'mailbox' => array(self::HAS_ONE, 'Mailbox', 'user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'username' => 'Username',
			'password' => 'Password',
			'email' => 'Email',
			'firstname' => 'Firstname',
			'lastname' => 'Lastname',
			'updated' => 'Updated',
			'created' => 'Created',
			'lastlogin' => 'Lastlogin',
			'level' => 'Level',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id,true);
		$criteria->compare('username',$this->username,true);
		$criteria->compare('password',$this->password,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('firstname',$this->firstname,true);
		$criteria->compare('lastname',$this->lastname,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
	
	static public function hashPassword($password)
	{
		return hash_hmac(self::HashAlgorithm, $password, Yii::app()->params['appKey']);
	}
	
	protected function beforeSave()
	{
		if(parent::beforeSave())
		{
			if (isset($this->password) && $this->password_modified)
			{
				if (strlen($this->password))
					$this->password=self::hashPassword($this->password);
				else
					unset($this->password);
			}
			if ($this->getIsNewRecord())
				$this->created=time();
			else
				$this->updated=time();
			return true;
		}
		else
			return false;
	}
	
	public function getDisplayName()
	{
		$name=ucwords(strtolower(trim($this->firstname.' '.$this->lastname)));
		if (!strlen($name)) 
			$name=$this->email;
		return $name;
	}
	
	public function hasMailbox()
	{
		return ($this->mailbox instanceof Mailbox);
	}
	
	/**
	 * @return Zend_Date
	 */
	public function getCreated()
	{
		$date=new Zend_Date();
		$date->setTimestamp($this->created);
		return $date;
	}
	
	public function getUpdated()
	{
		$date=new Zend_Date();
		$date->setTimestamp($this->updated);
		return $date;
	}
}