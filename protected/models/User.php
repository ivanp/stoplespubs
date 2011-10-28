<?php

/**
 * This is the model class for table "user".
 *
 * The followings are the available columns in table 'user':
 * @property string $id
 * @property string $password
 * @property string $email
 * @property string $firstname
 * @property string $lastname
 * @property string $updated
 * @property string $created
 * @property string $lastlogin
 * @property int $activeto
 * @property string $billstatus
 *
 * The followings are the available model relations:
 * @property Mailbox[] $mailboxes
 */
class User extends CActiveRecord
{
	const BillStatusInactive='inactive';
	const BillStatusTrial='trial';
	const BillStatusPaid='paid';
	
	public $password_entered;
	public $password_repeat;
//	public $is_password_modified=false;
	public $is_accept_cos;
	
	public $is_email_modified=false;
	
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
			array('email', 'length', 'max'=>255),
			array('firstname, lastname', 'length', 'max'=>80),
			array('email', 'email'),
			array('email', 'unique'),
			array('password, updated, created, lastlogin, activeto', 'unsafe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, email, firstname, lastname, updated, created, lastlogin', 'safe', 'on'=>'search'),
			
			// Registration
			array('password_entered', 'default', 'value'=>$this->getIsNewRecord() ? '' : $this->decrypt()),
			array('email, password_entered, firstname, lastname', 'required', 'on'=>'register'), 
			array('password_entered', 'length', 'min'=>5, 'on'=>'register'),
			array('is_accept_cos', 'compare', 'compareValue'=>'1', 'on'=>'register', 'message'=>'You need to agree with our Conditions of Service'),
		
			// Password change
			array('password_entered', 'compare', 'compareAttribute'=>'password_repeat', 'on'=>'change_password'),
			array('password_entered, password_repeat', 'required', 'on'=>'change_password'),
			array('password_entered', 'length', 'min'=>5, 'on'=>'change_password'),
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
			'payments' => array(self::HAS_MANY, 'Payment', 'user_id')
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'password' => 'Password',
			'email' => 'Email address',
			'firstname' => 'First name',
			'lastname' => 'Last name',
			'updated' => 'Updated',
			'created' => 'Created',
			'lastlogin' => 'Last login',
			'password_entered' => 'Mailbox password',
			'password_repeat' => 'Re-enter password',
			'is_accept_cos' => 'Accept our Conditions of Service'
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
		$criteria->compare('password',$this->password,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('firstname',$this->firstname,true);
		$criteria->compare('lastname',$this->lastname,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
	
	protected function beforeSave()
	{
		if(parent::beforeSave())
		{
			$isNewRecord=$this->getIsNewRecord();
			if(!$isNewRecord && $this->is_email_modified && empty($this->password_entered))
				$this->password_entered=$this->decrypt();
			if (isset($this->password_entered))
			{
				if (strlen($this->password_entered))
					$this->password=$this->encrypt($this->password_entered);
				else
					unset($this->password); // Don't change the password
			}
			if ($isNewRecord)
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
	
	public function __set($name, $value)
	{
		if ($name=='email')
			$this->is_email_modified=true;
		parent::__set($name, $value);
	}
	
	private function _key()
	{
		return crypt($this->email, Yii::app()->params['appKey']);
	}
	
	public function encrypt($str)
	{
		$str=serialize($str);
		$key = $this->_key();
		$block = mcrypt_get_block_size('des', 'ecb');
		$pad = $block - (strlen($str) % $block);
		$str .= str_repeat(chr($pad), $pad);

		return base64_encode(mcrypt_encrypt(MCRYPT_TWOFISH, $key, $str, MCRYPT_MODE_ECB));
	}

	public function decrypt()
	{   
		$key = $this->_key();
		$str = mcrypt_decrypt(MCRYPT_TWOFISH, $key, base64_decode($this->password), MCRYPT_MODE_ECB);

		$block = mcrypt_get_block_size('des', 'ecb');
		$pad = ord($str[($len = strlen($str)) - 1]);
		$str = substr($str, 0, strlen($str) - $pad);
		return unserialize(trim($str));
	}
	
	public function createPaymentUrl()
	{
		return Payment::createPaymentUrl($this);
	}
}