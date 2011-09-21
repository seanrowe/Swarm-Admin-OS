<?php

/**
 * Controller class for the js/css minifier
 * @author Sean Rowe
 *
 */
class MinifyController extends Controller
{
	/**
	 * Executes the minifier for javascript
	 * @throws Exception If the page attribute has not been set
	 */
	public function actionJs()
	{
		$params = $this->getActionParams();
		if (false === isset($params['page']))
		{
			throw new Exception('Page attribute not set');
		}

		header('Content-type: application/x-javascript');
		echo Minify::instance()->run('js', $params['page']);
	}

	/**
	 * Executes the minifier for css
	 * @throws Exception If the page attribute has not been set
	 */
	public function actionCss()
	{
		$params = $this->getActionParams();
		if (false === isset($params['page']))
		{
			throw new Exception('Page attribute not set');
		}

		header('Content-type: text/css');
		echo Minify::instance()->run('css', $params['page']);
	}
}

?>