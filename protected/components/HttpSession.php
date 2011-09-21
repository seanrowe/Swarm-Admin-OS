<?php

class HttpSession extends CHttpSession
{
	public function regenerateSessionId()
	{
		session_regenerate_id();
	}
	
	public function setTimeout($timeout = null)
	{
		if (null === $timeout)
		{
			$timeout = ini_get('session.gc_maxlifetime') -1;
		}
		
		parent::setTimeout($timeout);
	}
}

?>