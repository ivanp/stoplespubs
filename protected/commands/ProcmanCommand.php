<?php

class ProcmanCommand extends CConsoleCommand 
{
	/**
	 * No use
	 */
	public function actionIndex()
	{
	}
	
	/**
	 * Start mailbox process
	 * 
	 * @param int $mailbox_id 
	 */
	public function actionStart($mailbox_id)
	{
	}
	
	/**
	 * Stop mailbox process gracefully
	 * 
	 * @param int $mailbox_id 
	 */
	public function actionStop($mailbox_id)
	{
	}

	/**
	 * Kill process
	 * 
	 * @param int $mailbox_id 
	 */
	public function actionKill($mailbox_id)
	{
	}
	
	/**
	 * Set mailbox process to paused state
	 * 
	 * @param int $mailbox_id 
	 */
	public function actionPause($mailbox_id)
	{
	}
	
	/**
	 * To start new processes, restarting died processes
	 */
	public function actionCheck()
	{
	}
}