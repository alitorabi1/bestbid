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



// Facebook Login block

/* INCLUSION OF LIBRARY FILES*/
	require_once('lib/Facebook/FacebookSession.php');
	require_once('lib/Facebook/FacebookRequest.php');
	require_once('lib/Facebook/FacebookResponse.php');
	require_once('lib/Facebook/FacebookSDKException.php');
	require_once('lib/Facebook/FacebookRequestException.php');
	require_once('lib/Facebook/FacebookRedirectLoginHelper.php');
	require_once('lib/Facebook/FacebookAuthorizationException.php');
	require_once('lib/Facebook/GraphObject.php');
	require_once('lib/Facebook/GraphUser.php');
	require_once('lib/Facebook/GraphSessionInfo.php');
	require_once('lib/Facebook/Entities/AccessToken.php');
	require_once('lib/Facebook/HttpClients/FacebookCurl.php');
	require_once('lib/Facebook/HttpClients/FacebookHttpable.php');
	require_once('lib/Facebook/HttpClients/FacebookCurlHttpClient.php');

/* USE NAMESPACES */
	
	use Facebook\FacebookSession;
	use Facebook\FacebookRedirectLoginHelper;
	use Facebook\FacebookRequest;
	use Facebook\FacebookResponse;
	use Facebook\FacebookSDKException;
	use Facebook\FacebookRequestException;
	use Facebook\FacebookAuthorizationException;
	use Facebook\GraphObject;
	use Facebook\GraphUser;
	use Facebook\GraphSessionInfo;
	use Facebook\FacebookHttpable;
	use Facebook\FacebookCurlHttpClient;
	use Facebook\FacebookCurl;

/*PROCESS*/

	//check if users wants to logout
	 if(isset($_REQUEST['logout'])){
	 	unset($_SESSION['fb_token']);
	 }
	
	//2.Use app id,secret and redirect url 
	$app_id = '704374739718815';
	$app_secret = '3cf65c2f0c77bde0f9019fc07d3c0471';
	$redirect_url='http://bestbid.ipd8.info/';

	//3.Initialize application, create helper object and get fb sess
	 FacebookSession::setDefaultApplication($app_id,$app_secret);
	 $helper = new FacebookRedirectLoginHelper($redirect_url);
	 $sess = $helper->getSessionFromRedirect();

	//check if facebook session exists
	if(isset($_SESSION['fb_token'])){
	 	$sess = new FacebookSession($_SESSION['fb_token']);
	}

	//4. if fb sess exists echo name 
	 	if(isset($sess)){
	 		//store the token in the php session
	 		$_SESSION['fb_token']=$sess->getToken();
	 		//create request object,execute and capture response
	 		$request = new FacebookRequest($sess,'GET','/me');
			// from response get graph object
			$response = $request->execute();
			$graph = $response->getGraphObject(GraphUser::classname());
			// use graph object methods to get user details
			$name = $graph->getName();
			$id = $graph->getId();
			$image = 'https://graph.facebook.com/'.$id.'/picture?width=300';
			$email = $graph->getProperty('email');
			echo "hi $name <br>";
			echo "your email is $email <br><Br>";
			echo "<img src='$image' /><br><br>";
			echo "<a href='/logout'><button>Logout</button></a>";
	 	}else{
			//else echo login
	 		echo '<a href="'.$helper->getLoginUrl(array('email')).'" >Login with facebook</a>';
	 	}

// Facebook Login block




DB::$dbName = 'slimshop';
DB::$user = 'slimshop';
DB::$password = 'Sn6rABvHDNAxVujJ';
// DB::$host = '127.0.0.1'; // sometimes needed on Mac OSX
DB::$error_handler = 'sql_error_handler';
DB::$nonsql_error_handler = 'nonsql_error_handler';

function nonsql_error_handler($params) {
    global $app, $log;
    $log->error("Database error: " . $params['error']);
    http_response_code(500);
    $app->render('error_internal.html.twig');
    die;
}

function sql_error_handler($params) {
    global $app, $log;
    $log->error("SQL error: " . $params['error']);
    $log->error(" in query: " . $params['query']);
    http_response_code(500);
    $app->render('error_internal.html.twig');
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

/*
\Slim\Route::setDefaultConditions(array(
    'id' => '\d+'
)); */

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = array();
}

$app->get('/', function() use ($app) {    
    $app->render('index.html.twig',
            array('sessionUser' => $_SESSION['user']));
});

$app->get('/emailexists/:email', function($email) use ($app, $log) {
    $user = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
    if ($user) {
        echo "Email already registered";
    }
});

// State 1: first show
$app->get('/register', function() use ($app, $log) {
    $app->render('register.html.twig');
});
// State 2: submission
$app->post('/register', function() use ($app, $log) {
    $name = $app->request->post('name');
    $email = $app->request->post('email');
    $pass1 = $app->request->post('pass1');
    $pass2 = $app->request->post('pass2');
    $valueList = array ('name' => $name, 'email' => $email);
    // submission received - verify
    $errorList = array();
    if (strlen($name) < 4) {
        array_push($errorList, "Name must be at least 4 characters long");
        unset($valueList['name']);
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
        array_push($errorList, "Email does not look like a valid email");
        unset($valueList['email']);
    } else {
        $user = DB::queryFirstRow("SELECT ID FROM users WHERE email=%s", $email);        
        if ($user) {
            array_push($errorList, "Email already registered");
            unset($valueList['email']);
        }
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
        DB::insert('users', array(
            'name' => $name, 'email' => $email,
            'password' => password_hash($pass1, CRYPT_BLOWFISH)
            // 'password' => hash('sha256', $pass1)
        ));
        $id = DB::insertId();
        $log->debug(sprintf("User %s created", $id));
        $app->render('register_success.html.twig');
    }
});

// State 1: first show
$app->get('/login', function() use ($app, $log) {
    $app->render('login.html.twig');
});
// State 2: submission
$app->post('/login', function() use ($app, $log) {
    $email = $app->request->post('email');
    $pass = $app->request->post('pass');
    $user = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);    
    if (!$user) {
        $log->debug(sprintf("User failed for email %s from IP %s",
                    $email, $_SERVER['REMOTE_ADDR']));
        $app->render('login.html.twig', array('loginFailed' => TRUE));
    } else {
        // password MUST be compared in PHP because SQL is case-insenstive
        //if ($user['password'] == hash('sha256', $pass)) {
        if (password_verify($pass, $user['password'])) {
            // LOGIN successful
            unset($user['password']);
            $_SESSION['user'] = $user;
            $log->debug(sprintf("User %s logged in successfuly from IP %s",
                    $user['ID'], $_SERVER['REMOTE_ADDR']));
            $app->render('login_success.html.twig');
        } else {
            $log->debug(sprintf("User failed for email %s from IP %s",
                    $email, $_SERVER['REMOTE_ADDR']));
            $app->render('login.html.twig', array('loginFailed' => TRUE));            
        }
    }
});

$app->get('/logout', function() use ($app, $log) {
    $_SESSION['user'] = array();
    $app->render('logout_success.html.twig');
});

$app->run();