<?php

class HttpRequest extends CHttpRequest
{
	public function getBrowser()
	{
		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		$rval = array();
		
		foreach(Yii::app()->params['browsers'] as $browser)
		{
			if (preg_match("#($browser)[/ ]?([0-9.]*)#", $agent, $match))
			{
				$rval['name'] = $match[1] ;
				$rval['version'] = $match[2] ;
				break ;
			}
		}
		
		return $rval;
	}
}

?>