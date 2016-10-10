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

//DB::$dbName = 'cp4724_bestbid';
//DB::$user = 'cp4724_bestbid';
DB::$dbName = 'bestbid';
DB::$user = 'bestbid';
//DB::$password = '9uYCYW2r8xDQfZvJ';   //JAC
DB::$password = 'bDYeWvRqrfzL6wDe'; //Home
DB::$encoding = 'utf8'; // defaults to latin1 if omitted
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

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = array();
}

$app->get('/', function() use ($app) {
    $app->render('index.html.twig', array('sessionUser' => $_SESSION['user']));
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
    $app->render('register.html.twig');
});
// State 2: submission
$app->post('/register', function() use ($app, $log) {
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
        $app->render('register_success.html.twig');
    }
});

// State 1: first show
$app->get('/login', function() use ($app, $log) {
    $app->render('login.html.twig');
});
// State 2: submission
$app->post('/login', function() use ($app, $log) {
    $username = $app->request->post('username');
    $pass = $app->request->post('pass');
    $user = DB::queryFirstRow("SELECT * FROM users WHERE username=%s", $username);
    if (!$user) {
        $log->debug(sprintf("User failed for username %s from IP %s", $username, $_SERVER['REMOTE_ADDR']));
        $app->render('login.html.twig', array('loginFailed' => TRUE));
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
            $app->render('index.html.twig', array('sessionUser' => $_SESSION['user']));
        } else {
            $log->debug(sprintf("User failed for username %s from IP %s", $username, $_SERVER['REMOTE_ADDR']));
            $app->render('login.html.twig', array('loginFailed' => TRUE));
        }
    }
});

$app->get('/logout', function() use ($app, $log) {
    $_SESSION['user'] = array();
    $app->render('logout_success.html.twig');
});

$app->run();
