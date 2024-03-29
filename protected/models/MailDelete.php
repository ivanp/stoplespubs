<?php

/**
 * This is the model class for table "mail_delete".
 *
 * The followings are the available columns in table 'mail_delete':
 * @property string $id
 * @property string $mailbox_id
 * @property integer $time
 * @property string $from
 * @property string $subject
 * @property string $header_id
 */
class MailDelete extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return MailDelete the static model class
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
		return 'mail_delete';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('mailbox_id, header_id', 'required'),
			array('time', 'numerical', 'integerOnly'=>true),
			array('mailbox_id, header_id', 'length', 'max'=>10),
			array('from', 'length', 'max'=>255),
			array('subject', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, mailbox_id, time, from, subject, header_id', 'safe', 'on'=>'search'),
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
			'mailbox_id' => 'Mailbox',
			'time' => 'Time',
			'from' => 'From',
			'subject' => 'Subject',
			'header_id' => 'Header',
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
		$criteria->compare('mailbox_id',$this->mailbox_id,true);
		$criteria->compare('time',$this->time);
		$criteria->compare('from',$this->from,true);
		$criteria->compare('subject',$this->subject,true);
		$criteria->compare('header_id',$this->header_id,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}