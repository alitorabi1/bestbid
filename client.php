<?php


require_once 'vendor/autoload.php';

$target_dir = "uploads/";
$max_file_size = 5 * 1024 * 1024;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('main');
$log->pushHandler(new StreamHandler('logs/everything.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));

///DB::$dbName = 'cp4724_bestbid';
//DB::$user = 'cp4724_bestbid';

//DB::$password = 'CtIeWH3iU0kx';
DB::$dbName ='bestbid';
DB::$user ='bestbid';


DB::$password = 'bDYeWvRqrfzL6wDe'; //Home
DB::$encoding = 'utf8'; // defaults to latin1 if omitted
//DB::$port='3333';
DB::$error_handler = 'sql_error_handler';
DB::$nonsql_error_handler = 'nonsql_error_handler';

// FIXME: add monolog

function nonsql_error_handler($params) {
    global $app, $log;
    $log->error("Database error: " . $params['error']);
    http_response_code(500);
    header('content-type: application/json');
    echo json_encode("Internal no server error");
    die;
}

function sql_error_handler($params) {
    global $app, $log;
    $log->error("SQL error: " . $params['error']);
    $log->error(" in query: " . $params['query']);
    http_response_code(500);
    header('content-type: application/json');
    echo json_encode("Internal server error11111111");
    die; // don't want to keep going if a query broke
}

$app = new \Slim\Slim(array(
    'view' => new \Slim\Views\Twig()
        ));

$view = $app->view();
$view->parserOptions = array(
    'debug' => true,
    'cache' => dirname(__FILE__) . '/cache'
);
$view->setTemplatesDirectory(dirname(__FILE__) . '/templates');


\Slim\Route::setDefaultConditions(array(
    'ID' => '\d+'
));

$app->response->headers->set('content-type', 'application/json');





$app->get('/category/:ID', function($ID) use ($app) {

    $record = DB::query("SELECT * FROM category WHERE mainCategoryID=%d", $ID);
    // 404 if record not found
    if (!$record) {
        $app->response->setStatus(404);
        echo json_encode("Record not found");
        return;
    }
    echo json_encode($record, JSON_PRETTY_PRINT);
});

$app->get('/maincategory_index', function()  use ($app){
    //$userID = getAuthUserID();
   // if (!$userID) return;
    $mainCategoryList = DB::query("SELECT * FROM maincategory ");
  //  echo json_encode($categoryList, JSON_PRETTY_PRINT);
 //  $mainCategoryList=array('name'=>'44444jfsjosjkkogtksdgk');
  $app->render('template.html.twig', array("mainCategoryList" => $mainCategoryList));
  //   $app->render('template.html.twig', array("BBname" => 'alalalalalalllalalla'));
});



$app->get('/maincategory', function() {
    //$userID = getAuthUserID();
   // if (!$userID) return;
    $categoryList = DB::query("SELECT * FROM maincategory ");
    echo json_encode($categoryList, JSON_PRETTY_PRINT);
});






$app->run();
