<?php

/**
 * This is the model class for table "process_log".
 *
 * The followings are the available columns in table 'process_log':
 * @property string $id
 * @property string $time
 * @property string $message
 * @property integer $mailbox_id
 *
 * The followings are the available model relations:
 * @property Mailbox $mailbox
 */
class ProcessLog extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return ProcessLog the static model class
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
		return 'process_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('mailbox_id', 'required'),
			array('mailbox_id', 'numerical', 'integerOnly'=>true),
			array('time, message', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, time, message, mailbox_id', 'safe', 'on'=>'search'),
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
			'time' => 'Time',
			'message' => 'Message',
			'mailbox_id' => 'Mailbox',
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
		$criteria->compare('time',$this->time,true);
		$criteria->compare('message',$this->message,true);
		$criteria->compare('mailbox_id',$this->mailbox_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}