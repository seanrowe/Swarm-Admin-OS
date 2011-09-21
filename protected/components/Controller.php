<?php

class Controller extends CController
{
	/**
	 * @var string the default layout for the controller view. Defaults to '//layouts/column1',
	 * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
	 */
	public $layout = '';


	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
	    if ($error = Yii::app()->errorHandler->error)
	    {
	    	if (Yii::app()->request->isAjaxRequest)
	    	{
	    		$response = new AjaxResponse();
	    		$response->success = false;
	    		$response->message = $error['message'];
	    		$response->params = CJavaScript::jsonEncode($error);
	    		$response->execute();
	    		return;
	    	}

        	$this->render('error', $error);
	    }
	}

}