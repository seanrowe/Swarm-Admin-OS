<?php

/**
 *  This array sets up the css files for the site.  Files under 'global' are loaded for
 *  all pages, in the order that they are set up here.  Files under 'page' are only
 *  loaded for those pages where /?r=controller/action are equal to the page given
 *
 *  Example:
 *  	'example/url' => array(
 *  		// Required. The name of the css file.  Should always be in the css folder
 *  		'file' => 'css/file_name.csss
 *
 *  		// Optional. If true, will only combine the file and not minify it.  This
 *  		// is useful for files that are already minified, or too big
 *  		// to efficiently run through the minify parser
 *  		'combine_only' => false // defaults to false.  if true, will
 *
 *  		// Optional.  If given, then the file will only be loaded if the given
 *  		// request value exists and is equal to the given value.  You can put
 *  		// in as many parameters here as you wish
 *  		'request' => array(
 *  			'request_key' => 'request_value',
 *  			'request_key2' => 'request_value2',
 *  			...
 *  			'request_key_n' => 'request_value_n'
 *  		)
 *
 *  		// Optional.  If present, the css file will only be loaded if the browser
 *  		// is (in this case) firefox.  The names of the browsers are set in the
 *  		// Yii::app()->params array.  If the version is present, it will only load
 *  		// for the browser with that version.
 *  		'browser' => array(
 *  			'name' => 'firefox'
 *  			'version' => '10'
 *  		)
 *  	)
 * @var unknown_type
 */
return array(
	'global' => array(
		array(
			'file' => 'css/reset.css'
		),

		array(
			'file' => 'css/jquery-ui.css'
		),

		array(
			'file' => 'css/common.css'
		)
	),

	'page' => array(
		'home/index' => array(
			array(
				'file' => 'css/index.css',
			)
		)
	)
);
?>