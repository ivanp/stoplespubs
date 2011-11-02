<?php

class ReportController extends Controller
{
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow', // allow authenticated user to access report pages
				'users'=>array('@'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}
	
	public function actionIndex()
	{
		$months=array();
		$now=Zend_Date::now();
		$total=0;
		for($i=0;$i<12;$i++) 
		{
			// Check this month
			$t1=clone $now;
			$t1->set('1',Zend_Date::DAY);
			$t1->set('00:00:00',Zend_Date::TIMES);
			$t1->subMonth($i);

			$t2=clone $t1;
			$t2->addMonth(1);
			if($t2 instanceof Zend_Date);
			$t2->subSecond(1);
			
			$count=(int)MailDelete::model()->count('time BETWEEN :t1 AND :t2',array(':t1'=>$t1->get(Zend_Date::TIMESTAMP),':t2'=>$t2->get(Zend_Date::TIMESTAMP)));
			
			$title=sprintf('%s %d',$t1->get(Zend_Date::MONTH_NAME),$t1->get(Zend_Date::YEAR));
			array_unshift($months,array(
				'title'=>$title,
				'count'=>$count,
				'url'=>$this->createUrl('daily',array('t'=>$t1->get(Zend_Date::TIMESTAMP)))
			));
			$total+=$count;
		}
		$this->render('index',array('months'=>$months,'total'=>$total));
	}
	
	public function actionDaily()
	{
		if(!isset($_GET['t']))
			throw new CHttpException(400);
		$t=CPropertyValue::ensureInteger($_GET['t']);
		
		$days=array();
		$month=new Zend_Date($t,Zend_Date::TIMESTAMP);
		$month_num=$month->getMonth();
		$total=0;
		for($i=1;$i<=31;$i++)
		{
			$t1=clone $month;
			if(!isset($first_day))
				$first_day=$t1;
			if($t1 instanceof Zend_Date);
			$t1->set('00:00:00',Zend_Date::TIMES);
			$t1->set($i,Zend_Date::DAY);
			if($t1->getMonth()!=$month_num)
				break;

			$t2=clone $t1;
			$t2->set('23:59:59',Zend_Date::TIMES);
			
			$count=(int)MailDelete::model()->count('time BETWEEN :t1 AND :t2',array(':t1'=>$t1->get(Zend_Date::TIMESTAMP),':t2'=>$t2->get(Zend_Date::TIMESTAMP)));
			
			$days[]=array(
				'day'=>$i,
				'weekday'=>(int)$t1->get(Zend_Date::WEEKDAY_DIGIT),
				'count'=>$count
			);
			$total+=$count;
		}
		$last_day=$t2;
		$month_str=sprintf('%s %s',$month->get(Zend_Date::MONTH_NAME),$month->get(Zend_Date::YEAR));
		
		$first_day->subSecond(1);
		$prev=array(
			'url'=>$this->createUrl('daily',array('t'=>$first_day->getTimestamp())),
			'title'=>sprintf('%s %s',$first_day->get(Zend_Date::MONTH_NAME),$first_day->get(Zend_Date::YEAR))
		);
		$last_day->addSecond(1);
		$next=array(
			'url'=>$this->createUrl('daily',array('t'=>$last_day->getTimestamp())),
			'title'=>sprintf('%s %s',$last_day->get(Zend_Date::MONTH_NAME),$last_day->get(Zend_Date::YEAR))
		);
		
		$this->render('daily',array(
			'prev'=>$prev,
			'next'=>$next,
			'days'=>$days,
			'total'=>$total,
			'month_str'=>$month_str,
			'weekyear'=>(int)$t1->get(Zend_Date::WEEK)
		));
	}
}