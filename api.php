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

//DB::$dbName = 'cp4724_bestbid';
//DB::$user = 'cp4724_behnaz';
//DB::$host='';
//DB::$password = ';=F7M)k#yZg^';
DB::$dbName ='bestbid';
DB::$user ='bestbid';

DB::$password='r9pjLBpJnDqZ5ewv';//home
DB::$port='3333';
//DB::$password='bcrSjdTaCnAZR3sv';//college
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

$app = new \Slim\Slim();

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

$app->get('/maincategory_index', function() {
    //$userID = getAuthUserID();
   // if (!$userID) return;
    $categoryList = DB::query("SELECT * FROM maincategory ");
  //  echo json_encode($categoryList, JSON_PRETTY_PRINT);
    $app->render('template.html.twig', array("product" => $product));
});


$app->get('/maincategory', function() {
    //$userID = getAuthUserID();
   // if (!$userID) return;
    $categoryList = DB::query("SELECT * FROM maincategory ");
    echo json_encode($categoryList, JSON_PRETTY_PRINT);
});



$app->post('/itemsforsell', function() use ($app, $log) {
  
    
$body = $app->request->getBody();
    
    $record = json_decode($body, TRUE);
   
    
      $fileUpload = $record['itemPic'];
      // $image = 'path-to-your-picture/image.jpg';
      $image=$fileUpload ;
    $picture = base64_encode(file_get_contents($image));
    $record['itemPic']=$picture;
       //$check = getimagesize($fileUpload["tmp_name"]);
          
    /*   $file_name = preg_replace('/[^A-Za-z0-9\-]/', '_', $fileUpload['name']);
        $file_extension = explode('/', $check['mime'])[1];
        $target_file = $target_dir . date("Ymd-His-") . $file_name . '.' . $file_extension;
         echo "file will be named: " . $target_file;
        if (move_uploaded_file($fileUpload["tmp_name"], $target_file)) {
            echo "The file " . basename($fileUpload["name"]) . " has been uploaded.";
            $record['itemPic']=file_get_contents($target_file);
            
            print_r($record);
        } else {
            die("Fatal error: There was an server-side error handling the upload of your file.");
        }
    
      $log->debug("POST itemsforsell fiiileeee: " . $record);   
    
    
    
   // $record['itemPic']=$_FILES['itemPic'];
    // $log->debug("POST /: " .  $record['itemPic']);
   /* if (!isItemValid($record, $error, TRUE)) {
        $app->response->setStatus(400);
        $log->debug("POST /itemsforsell verification failed: " . $error);
        echo json_encode($error);
        //echo json_encode("Bad request - data validation failed");
        return;
    }*/
 DB::insert('itemsforsell', $record);

   // echo DB::insertId();
    // POST / INSERT is special - returns 201
    $app->response->setStatus(201);
});


$app->run();
