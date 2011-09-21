<?php

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');

// This is the main Web application configuration. Any writable CWebApplication properties can be configured here.
return array(
	'basePath' => dirname(__FILE__) . DS . '..',
	'name' => 'SwarmCMS',

	// preloading 'log' component
	'preload' => array('log'),

	// autoloading model and component classes
	'import' => array(
		'application.classes.*',
		'application.models.*',
		'application.components.*',
	),

	'modules' => array(

		// uncomment the following to enable the Gii tool
		'gii' => array(
			'class' => 'system.gii.GiiModule',
			'password' => '12345',
			'ipFilters' => array('10.*.*.*','::1'),
		),
	),

	// application components
	'components' => array(
		'user' => array(
			'class' => 'CWebUser',
			'loginUrl' => 'login/index',
			'allowAutoLogin' => true,
		),

		'fileCache' => array(
			'class' => 'CFileCache'
		),

		// uncomment the following to enable URLs in path-format
		/*
		'urlManager' => array(
			'urlFormat' => 'path',
			'rules' => array(
				'<controller:\w+>/<id:\d+>' => '<controller>/view',
				'<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
				'<controller:\w+>/<action:\w+>' => '<controller>/<action>',
			),
		),
		*/

		'errorHandler' => array(
			// use 'site/error' action to display errors
			'errorAction' => 'site/error',
        	),

		'log' => array(
			'class' => 'CLogRouter',
			'routes' => array(
				array(
					'class' => 'CFileLogRoute',
				//	'levels' => 'error, warning',
				),

				// uncomment the following to show log messages on web pages
/*
				array(
					'class' => 'CWebLogRoute',
				),
*/
			),
		),

		'session' => array(
			'class' => 'HttpSession',
			'autoStart' => true
		),

		'request' => array(
			'class' => 'HttpRequest'
		),

	),

	// application-level parameters that can be accessed using Yii::app()->params['paramName']
	'params' => array(
		'css' => include('css.php'),
		'js' => include('js.php'),
		'docRoot' => dirname(dirname(dirname(__FILE__))),
		'browsers' => array(
			'firefox',
			'msie',
			'opera',
			'chrome',
			'safari',
			'mozilla',
			'seamonkey',
			'konqueror',
			'netscape',
			'gecko',
			'navigator',
			'mosaic',
			'lynx',
			'amaya',
			'omniweb',
			'avant',
			'camino',
			'flock',
			'aol',
		),

		'mongo' => array(
			'db' => 'swarm',
			'username' => 'root',
			'password' => 'Sup3rfly',
			'port' => 27017,
			'host' => 'localhost',
//			'replica' => array()
		)
	),
);
