<?php

class WebApplication extends CWebApplication
{
	public function init()
	{
		parent::init();
		date_default_timezone_set($this->params['defaultTimezone']);
	}
}