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
 * @property string $ssl
 * @property integer $active
 * @property string $added_on
 * @property integer $pid
 * @property string $process_started
 * @property string $process_last_checkin
 * @property string $imap_action
 * @property string $imap_move_folder
 *
 * The followings are the available model relations:
 * @property User $user
 * @property ProcessLog[] $processLogs
 */
class Mailbox extends CActiveRecord
{
	const DefaultPortPop3Plain=110;
	const DefaultPortPop3Ssl=995;
	const DefaultPortImapPlain=143;
	
	const TypePop3='pop3';
	const TypeImap='imap';
	
	const ImapActionMove='move';
	const ImapActionMark='mark';
	const ImapActionDelete='delete';
	
	/**
	 * @var Zend_Mail_Storage_Abstract
	 */
	private $_storage;
	
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
			array('user_id', 'unsafe'), // cannot be changed!
			array('user_id', 'required', 'on'=>'insert'), // only can be set on insert
			array('user_id', 'exist', 'className'=>'User', 'attributeName'=>'id', 'on'=>'insert'),
			array('user_id, active, pid', 'numerical', 'integerOnly'=>true),
			array('host', 'length', 'max'=>255),
			array('port', 'numerical', 'min'=>1, 'max'=>65535, 'integerOnly'=>true),
			array('username', 'length', 'max'=>255),
			array('active, added_on, process_started, process_last_checkin', 'unsafe'),
			array('type', 'in', 'range'=>array('pop3', 'imap')),
			array('ssl', 'default', 'value'=>'none'), // Default to none
			array('ssl', 'in', 'range'=>array('none', 'tls', 'ssl')),
			
			array('imap_action', 'default', 'value'=>'delete'), // Default to delete mail
			array('imap_action', 'in', 'range'=>array('move','mark','delete'), ),
			array('imap_move_folder', 'length', 'max'=>255),
			array('imap_action', 'requiredOnImap'),
				
			array('type, host, port, username, ssl', 'required', 'on'=>'insert,update'),
		);
	}
	
	public function requiredOnImap($attribute,$params)
	{
		if ($this->type==self::TypeImap)
		{
			$validator=new CRequiredValidator();
			$validator->attributes=array('imap_action','imap_move_folder');
			// imap_action is required when on IMAP
			$validator->validate($this, array('imap_action'));
			// imap_move_folder needs to be filled when 'move' is chosen
			if ($this->imap_action==self::ImapActionMove)
				$validator->validate($this, array('imap_move_folder'));
		}
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
			'processLogs' => array(self::HAS_MANY, 'ProcessLog', 'mailbox_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'user_id' => 'Username',
			'type' => 'Type',
			'host' => 'Mail server hostname',
			'port' => 'Mail server port',
			'username' => 'Username',
			'ssl' => 'SSL',
			'active' => 'Active',
			'added_on' => 'Added On',
			'pid' => 'PID',
			'process_started' => 'Process Started',
			'process_last_checkin' => 'Process Last Checkin',
			'imap_action' => 'Delete Action',
			'imap_move_folder' => 'Move Message To Folder'
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
		$criteria->compare('email',$this->email,true);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('host',$this->host,true);
		$criteria->compare('port',$this->port);
		$criteria->compare('username',$this->username,true);
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
	
	/**
	 * @return Zend_Mail_Storage_Abstract 
	 */
	public function openStorage()
	{
		$user=$this->user;
		if($user instanceof User);
		if (!isset($this->_storage)) {
			switch ($this->ssl) {
				case 'ssl':
					$ssl='SSL';
					break;
				case 'tls':
					$ssl='TLS';
					break;
				default:
					$ssl=false;
			}
			switch($this->type) {
				case 'imap':
					$mail=new EZend_Mail_Storage_Imap(array(
						'host'=>$this->host,
						'user'=>$this->username,
						'password'=>$user->decrypt(),
						'ssl'=>$ssl
					));
					break;
				case 'pop3':
					$mail=new EZend_Mail_Storage_Pop3(array(
						'host'=>$this->host,
						'user'=>$this->username,
						'password'=>$user->decrypt(),
						'ssl'=>$ssl
					));
					break;
			}
			$this->_storage=$mail;
		}
		return $this->_storage;
	}
	
	public function getImapFolders()
	{
		if ($this->type==self::TypeImap)
		{
			$this->openStorage();
			$folders=array();
			$iterator=new RecursiveIteratorIterator($this->_storage->getFolders(), RecursiveIteratorIterator::SELF_FIRST);
			foreach ($iterator as $localName => $folder) 
			{
				if ($folder->isSelectable()) 
					$folders[$folder->__toString()]=$localName;
			}
			return $folders;
		}
		else
			return null;
	}
	
	public function closeStorage()
	{
		unset($this->_storage);
	}
}