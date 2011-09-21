<?php

class SiteController extends CController
{
	public $layout = '';

	/**
	 * Declares class-based actions.
	 */
	public function actions()
	{
		return array();
	}

	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		$error = Yii::app()->errorHandler->error;

	    if ($error)
	    {
	    	if (Yii::app()->request->isAjaxRequest)
			{
	    		echo $error['message'];
			}

	    	else
			{
	        	$this->render('error', $error);
			}
	    }
	}

	/**
	 * Display the web page
	 */
	public function actionIndex()
	{
		$modules = ModuleModel::instance()->find();
		$this->render('index', array('modules' => $modules));
	}
}