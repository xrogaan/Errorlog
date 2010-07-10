<?php

chdir('../');
require 'errorlog.php';

$config = array(
    'writer' => array(
        'mail' => array(
            'from_email' => 'webmaster@example.com',                             // Most likely *your* email
            'recipients' => array('mail1@example.com','mail2@example.com'),      // The ones where the mails will go
        
            // the next parameters to use are those from the PEAR_Mail package
            // object &factory ( string $backend , array $params = array() )
            // see http://pear.php.net/manual/en/package.mail.mail.factory.php
            'backend' => 'mail',    // or 'sendmail', or 'smtp'
            'params'  => ''
        )
    ),
    'dump_session_data' => false,  // do not need session info in the syslog
    'logLevel' => ErrorLog::DEBUG, // Log all messages whit severities < ErrorLog::DEBUG
);

$error = ErrorLog::getInstance($config);
$error->registerExceptionHandler();


$error->warn('This is an informal message', ErrorLog::INFO);
$error->warn('This is a critical message', ErrorLog::CRIT);

function test() {
    throw new Exception('This is an exception');
}
test();