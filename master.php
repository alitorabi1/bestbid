<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('main');
$log->pushHandler(new StreamHandler('logs/everything.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));

if ($_SERVER['SERVER_NAME'] == 'localhost') {
    DB::$dbName = 'bestbid';
    DB::$user = 'bestbid';
    DB::$password = 'bDYeWvRqrfzL6wDe'; //Home
} else { // hosted on external server
    DB::$dbName = 'cp4724_bestbid';
    DB::$user = 'cp4724_bestbid';
    DB::$password = 'CtIeWH3iU0kx';
}

DB::$encoding = 'utf8'; // defaults to latin1 if omitted
// DB::$host = '127.0.0.1'; // sometimes needed on Mac OSX
DB::$error_handler = 'sql_error_handler';
DB::$nonsql_error_handler = 'nonsql_error_handler';

//DB::$port='3333';

function nonsql_error_handler($params) {
    global $app, $log;
    $log->error("Database error: " . $params['error']);
    http_response_code(500);
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $app->render('error_internal.html.twig', array('mainCategoryList' => $mainCategoryList));
    die;
}

function sql_error_handler($params) {
    global $app, $log;
    $log->error("SQL error: " . $params['error']);
    $log->error(" in query: " . $params['query']);
    http_response_code(500);
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $app->render('error_internal.html.twig', array('mainCategoryList' => $mainCategoryList));
    die; // don't want to keep going if a query broke
}


