<?php

require_once 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('main');
$log->pushHandler(new StreamHandler('logs/everything.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));

DB::$dbName = 'cp4724_bestbid';
DB::$user = 'cp4724_behnaz';
DB::$host='';
DB::$password = ';=F7M)k#yZg^';
DB::$error_handler = 'sql_error_handler';
DB::$nonsql_error_handler = 'nonsql_error_handler';

// FIXME: add monolog

function nonsql_error_handler($params) {
    global $app, $log;
    $log->error("Database error: " . $params['error']);
    http_response_code(500);
    header('content-type: application/json');
    echo json_encode("Internal server error");
    die;
}

function sql_error_handler($params) {
    global $app, $log;
    $log->error("SQL error: " . $params['error']);
    $log->error(" in query: " . $params['query']);
    http_response_code(500);
    header('content-type: application/json');
    echo json_encode("Internal server error");
    die; // don't want to keep going if a query broke
}

$app = new \Slim\Slim();

\Slim\Route::setDefaultConditions(array(
    'ID' => '\d+'
));

$app->response->headers->set('content-type', 'application/json');
$app->post('/sell', function() use ($app, $log) {
    $body = $app->request->getBody();
    $record = json_decode($body, TRUE);
  
  /*  if (!isTodoItemValid($record, $error, TRUE)) {
        $app->response->setStatus(400);
        $log->debug("POST /todoitems verification failed: " . $error);
        echo json_encode($error);
        //echo json_encode("Bad request - data validation failed");
        return;
    }*/
    DB::insert('itemsforsell', $record);
    echo DB::insertId();
    // POST / INSERT is special - returns 201
    $app->response->setStatus(201);
});

$app->get('/category', function() {
    //$userID = getAuthUserID();
   // if (!$userID) return;
    $categoryList = DB::query("SELECT * FROM category ");
    echo json_encode($categoryList, JSON_PRETTY_PRINT);
});

$app->run();
