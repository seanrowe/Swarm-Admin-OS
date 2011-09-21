<?php

return array(
	'global' => array(
		array(
			'file' => 'js/jquery.min.js',
			'combine_only' => true
		),
		array(
			'file' => 'js/jquery-ui.min.js',
			'combine_only' => true
		),
	),

	'page' => array(
		'home/index' => array(
			array(
				'file' => 'js/index.js',
			)
		),
	)
);

?>