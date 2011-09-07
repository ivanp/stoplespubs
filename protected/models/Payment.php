<?php

/**
 * This is the model class for table "payment".
 *
 * The followings are the available columns in table 'payment':
 * @property string $order_no
 * @property integer $time
 * @property string $user_id
 * @property integer $qty
 *
 * The followings are the available model relations:
 * @property User $user
 */
class Payment extends CActiveRecord
{
	const BaseUrl='https://usd.swreg.org/cgi-bin/s.cgi';
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return Payment the static model class
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
		return 'payment';
	}
	
	public function primaryKey() 
	{
		return 'order_no';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('order_no, time, user_id, qty', 'unsafe'),
			array('time, qty', 'numerical', 'integerOnly'=>true),
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
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'order_no' => 'Order No',
			'time' => 'Time',
			'user_id' => 'User',
			'qty' => 'Qty',
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

		$criteria->compare('order_no',$this->order_no,true);
		$criteria->compare('time',$this->time);
		$criteria->compare('user_id',$this->user_id,true);
		$criteria->compare('qty',$this->qty);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
	
	static public function createPaymentUrl(User $user)
	{
		$swreg=Yii::app()->params['SWREG.settings'];
		$url=Zend_Uri_Http::fromString(self::BaseUrl);
		$args=array(
			'd'=>$swreg['deliveryId'],
			'lnk'=>'http://stoplespubs.com/',
			'q'=>1,
			's'=>$swreg['shopId'],
			't'=>$user->id,
			'v'=>$swreg['variationId'],
			'x'=>1,
			'p'=>$swreg['productId']
		);
		$url->setQuery($args);
		return $url->__toString();
	}
}
//https://usd.swreg.org/cgi-bin/s.cgi?sw_key=f2217cdf69feec81&s=132573&lnk=http%3A//stoplespubs.com/&x=1&bb=1&p=132573-1&v=0&d=0&q=1&t=33&TestOrder=1