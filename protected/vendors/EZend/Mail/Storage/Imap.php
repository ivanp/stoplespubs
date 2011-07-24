<?php

class EZend_Mail_Storage_Imap extends Zend_Mail_Storage_Imap
{
	public function searchRecent()
	{
		return $this->_protocol->search(array('NEW'));
	}
}