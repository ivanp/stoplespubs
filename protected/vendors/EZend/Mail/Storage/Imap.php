<?php

class EZend_Mail_Storage_Imap extends Zend_Mail_Storage_Imap
{
	public function searchRecent()
	{
		return $this->_protocol->search(array(Zend_Mail_Storage::FLAG_RECENT));
	}
	
	public function getServerCapability()
	{
		return $this->_protocol->capability();
	}
	
	public function noop()
	{
		return $this->_protocol->noop();
	}
	
	public function expunge()
	{
		if (!$this->_protocol->expunge()) {
			throw new Zend_Mail_Storage_Exception('message marked as deleted, but could not expunge');
		}
	}
	
	public function moveMsgExt($id, $folder)
	{
		if (!$this->_protocol->store(array(Zend_Mail_Storage::FLAG_DELETED), $id, null, '+')) {
			throw new Zend_Mail_Storage_Exception('cannot set deleted flag');
		}
		$this->copyMessage($id, $folder);
	}
}