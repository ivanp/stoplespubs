<?php

/**
 * This is the model class for table "admin".
 *
 * The followings are the available columns in table 'admin':
 * @property string $id
 * @property string $username
 * @property string $password
 * @property integer $active
 * @property integer $created
 * @property integer $lastlogin
 * @property string $email
 */
class AdminUser extends CActiveRecord
{
	const HashAlgorithm='sha256';
	
	public $password_repeat;
	
	public $password_modified=false;
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return AdminUser the static model class
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
		return 'admin';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('username, password, active, email', 'required'),
			array('active, created, lastlogin', 'numerical', 'integerOnly'=>true),
			array('username', 'length', 'max'=>80),
			array('password, email', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, username, password, active, created, lastlogin, email', 'safe', 'on'=>'search'),
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
			'active' => 'Active',
			'created' => 'Created',
			'lastlogin' => 'Lastlogin',
			'email' => 'Email',
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
		$criteria->compare('active',$this->active);
		$criteria->compare('created',$this->created);
		$criteria->compare('lastlogin',$this->lastlogin);
		$criteria->compare('email',$this->email,true);

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
				$this->created = time();
			return true;
		}
		else
			return false;
	}
	
	public function __set($name, $value)
	{
		if ($name=='password')
			$this->password_modified=true;
		parent::__set($name, $value);
	}
}