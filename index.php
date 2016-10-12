<?php

session_start();

// enable on-demand class loader
require_once 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('main');
$log->pushHandler(new StreamHandler('logs/everything.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));

///DB::$dbName = 'cp4724_bestbid';
//DB::$user = 'cp4724_bestbid';
//DB::$password = 'CtIeWH3iU0kx';
DB::$dbName = 'bestbid';
DB::$user = 'bestbid';

DB::$password = 'bDYeWvRqrfzL6wDe'; //Home
DB::$encoding = 'utf8'; // defaults to latin1 if omitted
// DB::$host = '127.0.0.1'; // sometimes needed on Mac OSX
DB::$error_handler = 'sql_error_handler';
DB::$nonsql_error_handler = 'nonsql_error_handler';

//DB::$port='3333';

function nonsql_error_handler($params) {
    global $app, $log;
    $log->error("Database error: " . $params['error']);
    http_response_code(500);
    $app->render('error_internal.html.twig', array('mainCategoryList' => $mainCategoryList));
    die;
}

function sql_error_handler($params) {
    global $app, $log;
    $log->error("SQL error: " . $params['error']);
    $log->error(" in query: " . $params['query']);
    http_response_code(500);
    $app->render('error_internal.html.twig', array('mainCategoryList' => $mainCategoryList));
    die; // don't want to keep going if a query broke
}

// instantiate Slim - router in front controller (this file)
// Slim creation and setup
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

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = array();
}

$app->get('/', function() use ($app) {
    closeAllSellFinishTime();
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $anticItem = DB::queryFirstRow("SELECT * FROM `itemsforsell` WHERE status='open' and minimumBid=(select max(minimumBid) as bid from itemsforsell WHERE status='open' order by ID desc limit 400)order by ID desc limit 400");
    $maxBid = DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $anticItem['ID']);
    $topList = DB::query("SELECT * FROM `itemsforsell` WHERE status='open' order by ID desc LIMIT 4");
    $app->render('index.html.twig', array('sessionUser' => $_SESSION['user'], 'mainCategoryList' => $mainCategoryList, 'topList' => $topList, 'anticItem' => $anticItem, 'maxBid' => $maxBid));

    // $app->render('index.html.twig', array('sessionUser' => $_SESSION['user']));
});

$app->get('/userexists/:username', function($username) use ($app, $log) {
    $user = DB::queryFirstRow("SELECT * FROM users WHERE username=%s", $username);
    if ($user) {
        echo "<font size='2' color='red'>&nbsp;&nbsp;User already registered</font>";
    }
});

//facebook login
$fbID = "704374739718815";
$fbPass = "3cf65c2f0c77bde0f9019fc07d3c0471";
$fb = new Facebook\Facebook([
    'app_id' => $fbID,
    'app_secret' => $fbPass,
    'default_graph_version' => 'v2.5',
    'persistent_data_handler' => 'session'
        ]);

$helper = $fb->getRedirectLoginHelper();
$permissions = ['public_profile', 'email', 'user_location']; // optional

$loginUrl = $helper->getLoginUrl('http://bestbid.ipd8.info/fblogin.php', $permissions);
$logoutUrl = $helper->getLogoutUrl('http://bestbid.ipd8.info/fblogout.php', $permissions);

$fbUser = array();
if (isset($_SESSION['facebook_access_token'])) {
    $fbUser = $_SESSION['facebook_access_token'];
}

$twig = $app->view()->getEnvironment();
$twig->addGlobal('fbUser', $fbUser);
$twig->addGlobal('loginUrl', $loginUrl);
$twig->addGlobal('logoutUrl', $logoutUrl);

//print_r($fbUser);
//print_r($_SESSION['fbmetadata']);
// State 1: first show
$app->get('/register', function() use ($app, $log) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $app->render('register.html.twig', array('mainCategoryList' => $mainCategoryList));
});
// State 2: submission
$app->post('/register', function() use ($app, $log) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $username = $app->request->post('username');
    $email = $app->request->post('email');
    $address = $app->request->post('address');
    $codepostal = $app->request->post('codepostal');
    $state = $app->request->post('state');
    $country = $app->request->post('country');
    $pass1 = $app->request->post('pass1');
    $pass2 = $app->request->post('pass2');
    $valueList = array('username' => $username, 'email' => $email, 'address' => $address, 'codepostal' => $codepostal, 'state' => $state, 'country' => $country);
    // submission received - verify
    $errorList = array();
    if (!$email || !$codepostal || !$pass1 || !$pass2) {
        array_push($errorList, "Please complete all fields");
    }
    if (strlen($username) < 2) {
        array_push($errorList, "Username must be at least 2 characters long");
        unset($valueList['username']);
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
        array_push($errorList, "Email does not look like a valid email");
        unset($valueList['email']);
    } else {
        $user = DB::queryFirstRow("SELECT ID FROM users WHERE email=%s", $email);
        if ($user) {
            array_push($errorList, "User already registered");
            unset($valueList['email']);
        }
    }
    $ZIPREG = array(
        "US" => "^\d{5}([\-]?\d{4})?$",
        "UK" => "^(GIR|[A-Z]\d[A-Z\d]??|[A-Z]{2}\d[A-Z\d]??)[ ]??(\d[A-Z]{2})$",
        "DE" => "\b((?:0[1-46-9]\d{3})|(?:[1-357-9]\d{4})|(?:[4][0-24-9]\d{3})|(?:[6][013-9]\d{3}))\b",
        "CA" => "^([ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ])\ {0,1}(\d[ABCEGHJKLMNPRSTVWXYZ]\d)$",
        "FR" => "^(F-)?((2[A|B])|[0-9]{2})[0-9]{3}$",
        "IT" => "^(V-|I-)?[0-9]{5}$",
        "AU" => "^(0[289][0-9]{2})|([1345689][0-9]{3})|(2[0-8][0-9]{2})|(290[0-9])|(291[0-4])|(7[0-4][0-9]{2})|(7[8-9][0-9]{2})$",
        "IR" => "^[1-9]{1}[0-9]{5}$",
        "ES" => "^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$",
        "DK" => "^([D-d][K-k])?( |-)?[1-9]{1}[0-9]{3}$",
        "SE" => "^(s-|S-){0,1}[0-9]{3}\s?[0-9]{2}$",
        "BE" => "^[1-9]{1}[0-9]{3}$"
    );
    if (strlen($country) < 2 || !array_key_exists($country, $ZIPREG)) {
        array_push($errorList, "Please enter a country from the list");
    }
    if (strlen($address) < 10) {
        array_push($errorList, "Address must be at least 10 characters long");
    }
//    if ($ZIPREG[$country]) {
// 	if (!preg_match("/".$ZIPREG[$country]."/i",$codepostal) || strlen($codepostal) < 5){
//            array_push($errorList, "Postalcode for this country is not valid");
//	}
//    }
    if (strlen($state) < 2) {
        array_push($errorList, "State must be at least 2 characters long");
    }
    if (!preg_match('/[0-9;\'".,<>`~|!@#$%^&*()_+=-]/', $pass1) || (!preg_match('/[a-z]/', $pass1)) || (!preg_match('/[A-Z]/', $pass1)) || (strlen($pass1) < 8)) {
        array_push($errorList, "Password must be at least 8 characters " .
                "long, contain at least one upper case, one lower case, " .
                " one digit or special character");
    } else if ($pass1 != $pass2) {
        array_push($errorList, "Passwords don't match");
    }
    //
    if ($errorList) {
        // STATE 3: submission failed        
        $app->render('register.html.twig', array(
            'errorList' => $errorList, 'v' => $valueList
        ));
    } else {
        // STATE 2: submission successful
        DB::insert('users', array('username' => $username, 'email' => $email,
            'password' => password_hash($pass1, CRYPT_BLOWFISH),
            'address' => $address, 'codepostal' => $codepostal,
            'state' => $state, 'country' => $country
                // 'password' => hash('sha256', $pass1)
        ));
        $id = DB::insertId();
        $log->debug(sprintf("User %s created", $id));
        $app->render('register_success.html.twig', array('mainCategoryList' => $mainCategoryList));
    }
});

// State 1: first show
$app->get('/login', function() use ($app, $log) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $app->render('login.html.twig', array('mainCategoryList' => $mainCategoryList));
});
// State 2: submission
$app->post('/login', function() use ($app, $log) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $username = $app->request->post('username');
    $pass = $app->request->post('pass');
    $user = DB::queryFirstRow("SELECT * FROM users WHERE username=%s", $username);
    if (!$user) {
        $log->debug(sprintf("User failed for username %s from IP %s", $username, $_SERVER['REMOTE_ADDR']));
        $app->render('login.html.twig', array('loginFailed' => TRUE, 'mainCategoryList' => $mainCategoryList));
    } else {
//echo "<pre>\n";
//echo "\$_FILES:\n";
//print_r($user);
//print_r($pass);
//print_r(!crypt($pass, $user['password']));
        // password MUST be compared in PHP because SQL is case-insenstive
        //if ($user['password'] == hash('sha256', $pass)) {
        if (crypt($pass, $user['password'])) {
            // LOGIN successful
            unset($user['password']);
            $_SESSION['user'] = $user;
            $log->debug(sprintf("User %s logged in successfuly from IP %s", $user['ID'], $_SERVER['REMOTE_ADDR']));
            $app->render('index.html.twig', array('sessionUser' => $_SESSION['user'], 'mainCategoryList' => $mainCategoryList));
        } else {
            $log->debug(sprintf("User failed for username %s from IP %s", $username, $_SERVER['REMOTE_ADDR']));
            $app->render('login.html.twig', array('loginFailed' => TRUE, 'mainCategoryList' => $mainCategoryList));
        }
    }
});

$app->get('/logout', function() use ($app, $log) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $_SESSION['user'] = array();
    $app->render('index.html.twig', array('mainCategoryList' => $mainCategoryList));
});

$app->get('/selllist/:ID', function($ID) use ($app) {
    closeAllSellFinishTime();
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
//if (isset($_GET["page"])) { $page  = $_GET["page"]; } else { $page=1; }; 
//$start_from = ($page-1) * $results_per_page;
//// LIMIT $start_from, ".$results_per_page;
//ORDER BY id DESC LIMIT {$start},{$limit}
    $sellList = DB::query("SELECT * FROM itemsforsell WHERE status='open' AND categoryID=%d  ORDER BY ID desc ", $ID);

//$sql = "SELECT * FROM ".$datatable." ORDER BY ID ASC LIMIT $start_from, ".$results_per_page;
    // 404 if record not found
    //  if (!$sellList) {
    //     $app->response->setStatus(404);
    //    echo json_encode("Record not found");
    //    return;
    //}
    // echo json_encode($record, JSON_PRETTY_PRINT);
    // print_r($sellList);
     $maxBid=DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $ID);
    $app->render('sel.html.twig', array('sessionUser' => $_SESSION['user'], 'sellList' => $sellList, 'mainCategoryList' => $mainCategoryList,'maxBid' => $maxBid));
});
//viewsellitem/{{mList.ID}}
$app->get('/viewsellitem/:ID', function($ID) use ($app) {
    closeAllSellFinishTime();
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $item = DB::queryFirstRow("SELECT * FROM itemsforsell WHERE status='open' AND ID=%d", $ID);
    $maxBid=DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $ID);
   
   
   // $app->render('viewitem.html.twig', array('sessionUser' => $_SESSION['user'], 'item' => $item, 'mainCategoryList' => $mainCategoryList));
     $app->render('viewitem.html.twig', array('sessionUser' => $_SESSION['user'], 'item' => $item,'maxBid' => $maxBid,'mainCategoryList' => $mainCategoryList));
});

$app->post('/itemsforsell', function() use ($app, $log) {
    //  $body = $app->request->getBody();
    //$record = json_decode($body, TRUE);
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $fileToUpload = $_FILES['itemPic'];
    $record1 = array();
    $record1['userID'] = $_SESSION['user']['ID'];

    $record1['categoryID'] = $_POST['categoryList'];
    if ($fileToUpload['error'] == 0) {

        $record1['mimeType'] = $fileToUpload['type'];
        $record1['itemPic'] = file_get_contents($fileToUpload['tmp_name']);
    }
    $bidStartTime11 = $_POST['bidStartTime'];
    $bidStartDate11 = $_POST['bidStartDate'];
    $d1 = explode(' ', $bidStartDate11);
    $bidStartTime1 = $d1[0] . " " . $bidStartTime11;
    $bidEndTime11 = $_POST['bidEndTime'];
    $bidEndDate11 = $_POST['bidEndDate'];
    $d2 = explode(' ', $bidEndDate11);
    $bidEndTime1 = $d2[0] . " " . $bidEndTime11;
    $record1['bidType'] = $_POST['bidType'];
    //  $record1['bidType']=$record['bidType']; 
    $record1['name'] = $_POST['name'];
    $record1['minimumBid'] = $_POST['minimumBid'];
    $record1['bidEndTime'] = $bidEndTime1;
    $record1['bidStartTime'] = $bidStartTime1;
    //    $record1['bidEndTime']= $_POST['bidEndTime'];
    //     $record1['bidStartTime']= $_POST['bidStartTime'];
    DB::insert('itemsforsell', $record1);
    echo DB::insertId();
   // $app->render('index.html.twig', array('mainCategoryList' => $mainCategoryList));
     $sellList = DB::query("SELECT * FROM itemsforsell WHERE status='open' AND userID=%d   ", $_SESSION['user']['ID']);
    
    //  $maxBid=DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $itemID);
  
    $maxBid=array();
    
    
    $app->render('sel.html.twig', array('sessionUser' => $_SESSION['user'], 'sellList' => $sellList, 'mainCategoryList' => $mainCategoryList,'maxBid' => $maxBid));

     
    // POST / INSERT is special - returns 201
    $app->response->setStatus(201);
});

$app->post('/bids', function() use ($app, $log) {
    //  $body = $app->request->getBody();
    //  $record = json_decode($body, TRUE);
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $userID = $app->request->post('userID');
    $bidDate = date("Y-m-d H:i:s");
    $bidAmount = $app->request->post('bidAmount');
    $itemID = $app->request->post('itemID');
    $record = array('userID' => $userID, 'bidDate' => $bidDate, 'bidAmount' => $bidAmount, 'itemID' => $itemID);
    DB::insert('bids', $record);
    echo DB::insertId();
    // POST / INSERT is special - returns 201
    $app->response->setStatus(201);
    // $log->debug(sprintf("bids %s created"));
    $sellList = DB::query("SELECT * FROM itemsforsell WHERE status='open' AND ID=%d   ", $itemID);
    
      $maxBid=DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $itemID);
  
    
    
    
    $app->render('sel.html.twig', array('sessionUser' => $_SESSION['user'], 'sellList' => $sellList, 'mainCategoryList' => $mainCategoryList,'maxBid' => $maxBid));
});

/////////////////////////////////////////////////////////////
function closeAllSellFinishTime() {
    $now = date("Y-m-d H:i:s");
    /* $tempDate = explode('-', $now['dueDate']);
      $year=$tempDate[0];
      $month=$tempDate[1];
      $tt=explode(' ',tempDate[2]);
      $day=$tt[0];
      $hh=explode(':',tt[1]);
      $hour=$hh[0];
      $minute=$hh[1]; */

    //close all open sells if time is end
    $record['status'] = 'notReachedToSell';

    DB::update('itemsforsell', $record, "bidEndTime<=%s", $now);
    // echo json_encode(TRUE); // s
}

$app->get('/searchall/:des', function($des) use ($app) {
    closeAllSellFinishTime();
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $sellList = DB::query("SELECT * FROM itemsforsell WHERE status='open' AND  ( name LIKE  %ss  OR  description LIKE  %ss)", $des, $des);
    // LIKE @ProductName OR Barcode  LIKE @Barcode
      $maxBid=DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $ID);
    $app->render('sel.html.twig', array('sessionUser' => $_SESSION['user'], 'sellList' => $sellList, 'mainCategoryList' => $mainCategoryList));
    //   $app->render('sel.html.twig', array('sellList' => $sellList));
});
$app->get('/topfour/', function() use ($app) {
    closeAllSellFinishTime();
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $app->render('login.html.twig', array('mainCategoryList' => $mainCategoryList));
    // LIKE @ProductName OR Barcode  LIKE @Barcode
    $app->render('sel.html.twig', array('sessionUser' => $_SESSION['user'], 'sellList' => $sellList, 'mainCategoryList' => $mainCategoryList));
    //   $app->render('sel.html.twig', array('sellList' => $sellList));
});

$app->run();
