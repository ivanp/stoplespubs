<?php

/**
 * This is the model class for table "message_imap".
 *
 * The followings are the available columns in table 'message_imap':
 * @property string $id
 * @property integer $mailbox_id
 * @property string $message_id
 * @property integer $last_touch
 *
 * The followings are the available model relations:
 * @property Mailbox $mailbox
 */
class MessageImap extends CActiveRecord
{
	public function primaryKey()
	{
		return array('mailbox_id', 'message_id');
	}
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return MessagePop3 the static model class
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
		return 'message_imap';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('mailbox_id, message_id, last_touch', 'required'),
			array('mailbox_id, message_id, last_touch', 'numerical', 'integerOnly'=>true),
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
			'mailbox' => array(self::BELONGS_TO, 'Mailbox', 'mailbox_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'mailbox_id' => 'Mailbox',
			'message_id' => 'Message',
			'last_touch' => 'Last Touch',
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
		$criteria->compare('mailbox_id',$this->mailbox_id);
		$criteria->compare('message_id',$this->message_id,true);
		$criteria->compare('last_touch',$this->last_touch);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}