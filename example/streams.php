<?php

chdir('../');
require 'errorlog.php';

$config = array(
	'writers' => array(
		'streams' => array(
			'file' => 'example/streams.log',
			'mode' => 'a',
		)	
	),
	'dump_session_data' => true,
	'logLevel' => ErrorLog::DEBUG,
);

$error = ErrorLog::getInstance($config);

$error->log('test');
