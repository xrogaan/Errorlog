<?php

chdir('../');
require 'errorlog.php';

$config = array(
    'writers' => array(
        'syslog' => array(
            // 'facility'    => '', //-> by default the user who's running php.
            // 'application' => '', //-> name of your application. By default ErrorLog
            
            /*
             * the formatter key is to customize the output for
             * specific type of reports.
             * Possible values are
             * 'ALL' -> means all types (exception, error, message)
             * ErrorLog::LOG_MESSAGE
             * ErrorLog::LOG_PHPERROR
             * ErrorLog::LOG_EXCEPTION
             */
            'formatter' => array( // 
                'ALL' => "%timestamp% %errorName% (%errorLevel%): %message%\n"
            ), 
        ),
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
