<?php

class Formatter extends CFormatter
{
	public function formatDate($time)
	{
		$zdate=new Zend_Date($time, Zend_Date::TIMESTAMP);
		return sprintf('%s %s', $zdate->get(Zend_Date::DATE_FULL), $zdate->get(Zend_Date::TIME_MEDIUM));
	}
}