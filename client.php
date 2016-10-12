<?php

session_start();
require_once 'vendor/autoload.php';

$target_dir = "uploads/";
$max_file_size = 5 * 1024 * 1024;

require_once 'master.php';

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
