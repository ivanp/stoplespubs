<?php

/**
 * This is the model class for table "mailbox".
 *
 * The followings are the available columns in table 'mailbox':
 * @property integer $id
 * @property string $user_id
 * @property string $type
 * @property string $host
 * @property integer $port
 * @property string $username
 * @property string $password
 * @property string $ssl
 * @property integer $active
 * @property string $added_on
 * @property integer $pid
 * @property string $process_started
 * @property string $process_last_checkin
 *
 * The followings are the available model relations:
 * @property User $user
 * @property ProcessLog[] $processLogs
 */
class Mailbox extends CActiveRecord
{
	const DefaultPortPop3Plain = 110;
	const DefaultPortPop3Ssl = 995;
	const DefaultPortImapPlain = 143;
	
	const TypePop3 = 'pop3';
	const TypeImap = 'imap';
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return Mailbox the static model class
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
		return 'mailbox';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, type, host, port, username, password, active', 'required'),
			array('port, active, pid', 'numerical', 'integerOnly'=>true),
			array('user_id', 'length', 'max'=>10),
			array('type, ssl', 'length', 'max'=>4),
			array('host', 'length', 'max'=>255),
			array('username, password', 'length', 'max'=>80),
			array('added_on, process_started, process_last_checkin', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, type, host, port, username, password, ssl, active, added_on, pid, process_started, process_last_checkin', 'safe', 'on'=>'search'),
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
			'user' => array(self::BELONGS_TO, 'User', 'user_id'),
			'process_logs' => array(self::HAS_MANY, 'ProcessLog', 'mailbox_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'user_id' => 'User',
			'type' => 'Type',
			'host' => 'Host',
			'port' => 'Port',
			'username' => 'Username',
			'password' => 'Password',
			'ssl' => 'SSL',
			'active' => 'Active',
			'added_on' => 'Added On',
			'pid' => 'Pid',
			'process_started' => 'Process Started',
			'process_last_checkin' => 'Process Last Checkin',
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

		$criteria->compare('id',$this->id);
		$criteria->compare('user_id',$this->user_id,true);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('host',$this->host,true);
		$criteria->compare('port',$this->port);
		$criteria->compare('username',$this->username,true);
		$criteria->compare('password',$this->password,true);
		$criteria->compare('ssl',$this->ssl,true);
		$criteria->compare('active',$this->active);
		$criteria->compare('added_on',$this->added_on,true);
		$criteria->compare('pid',$this->pid);
		$criteria->compare('process_started',$this->process_started,true);
		$criteria->compare('process_last_checkin',$this->process_last_checkin,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}