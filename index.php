<?php

session_start();

// enable on-demand class loader
require_once 'vendor/autoload.php';

require_once 'sessiontimeout.php';

require_once 'master.php';

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

    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $anticItem = DB::queryFirstRow("SELECT * FROM `itemsforsell` WHERE status='open' and minimumBid=(select max(minimumBid) as bid from itemsforsell WHERE status='open' order by ID desc limit 400)order by ID desc limit 400");
    $maxBid = DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $anticItem['ID']);
   // $topList = DB::query("SELECT * FROM `itemsforsell` WHERE status='open' order by ID desc LIMIT 4");
    $topList=DB::query("SELECT itemsforsell . * , MAX( bidAmount ) AS max, COUNT( * ) AS count FROM itemsforsell, bids WHERE STATUS =  'open' AND itemID = itemsforsell.ID group by itemID ORDER BY ID DESC LIMIT 4");
   // $bidTop = array();
    //foreach ($topList as $tlist) {
    //  $maxBidFour = DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $tlist['ID']);
      // array_push($bidTop, array('max' => $maxBidFour['max'], 'count' => $maxBidFour['count']));
        
        
        
        
//  }
    $app->render('index.html.twig', array('sessionUser' => $_SESSION['user'], 'mainCategoryList' => $mainCategoryList, 'topList' => $topList, 'anticItem' => $anticItem, 'maxBid' => $maxBid, 'bidTop' => $bidTop));

    // $app->render('index.html.twig', array('sessionUser' => $_SESSION['user']));
    //   $now = date("Y-m-d H:i:s");
       
     //   $itemsList = DB::query("SELECT * FROM itemsforsell where status='open' AND  bidEndTime<=%s ", $now);
        //--------------------------------------------------------------------------------------------
});
$app->get('/everyminute/', function() use ($app) {
    DB::$error_handler = FALSE;
    DB::$throw_exception_on_error = TRUE;
    try {
     $itemsList = DB::query("SELECT * FROM itemsforsell where status='open' AND  bidEndTime<=Now()");

        foreach ($itemsList as $item) {
            $bids = DB::queryFirstRow("SELECT * FROM `bids` WHERE itemId=%d and bidAmount = "
                    . "(select max(bidAmount)FROM `bids` WHERE itemId=%d)"
                    , $item['ID'], $item['ID']);

//send email
            //send to buyer who win bid 
            if ($bids) {
                $userBuyer = DB::queryFirstRow("SELECT * FROM users WHERE ID=%d", $bids['userID']);
                $userBuyer = $bids['email'];
                $subject = "You win the auction for " . $item['name'];
                $txt = "Hello You win the auction for" . $item['name'] . "that you bided for " . $bids['bidAmount'] . " and we pick up the amount in your credit";
                $headers = "From: bestbid@bestbid.ipd8.info";
                mail($to, $subject, $txt, $headers);


//send to seller 

                $userSeller = DB::queryFirstRow("SELECT * FROM users WHERE ID=%d", $item['userID']);
                $to = $userSeller['email'];
                $subject = "You sold t " . $item['name'];
                $txt = "Hello You  sold the auction for" . $item['name'] . "that you amount " . $bids['bidAmount'] . " and we increse the amount in your credit";
                $headers = "From: bestbid@bestbid.ipd8.info";
                mail($to, $subject, $txt, $headers);
//status='sold'
                DB::update('itemsforsell', array(
                    'status' => 'sold'
                        ), "ID=%d", $item['ID']);
//purchase table add
                DB::insert('purchases', array(
                    'itemID' => $item['ID'], 'buyerId' => $bids['userID'], 'amount' => $bids['bidAmount'], 'buyDate' => $now
                ));

//discount credit buyer increse credit seller
                DB::update('users', array(
                    'credit' => $userSeller['credit'] + $bids['bidAmount']
                        ), "ID=%d", $userSeller['ID']);
                DB::update('users', array(
                    'credit' => $userSeller['credit'] - $bids['bidAmount']
                        ), "ID=%d", $userBuyer['ID']);
            } else {
                DB::update('itemsforsell', array(
                    'status' => 'notReachedToSell'
                        ), "ID=%d", $item['ID']);
                $userSeller = DB::queryFirstRow("SELECT * FROM users WHERE ID=%d", $item['userID']);
                $to = $userSeller['email'];
                $subject = "Your  " . $item['name'] . "did not sold";
                $txt = "Hello You  did not sell the auction for" . $item['name'];
                $headers = "From: bestbid@bestbid.ipd8.info";
                mail($to, $subject, $txt, $headers);
            }
        }//end of for each


        DB::commit();
    } catch (MeekroDBException $e) {
        DB::rollback();
        sql_error_handler(array(
            'error' => $e->getMessage(),
            'query' => $e->getQuery()
        ));
    }
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

$fbUser = array();
if (isset($_SESSION['facebook_access_token'])) {
    $fbUser = $_SESSION['facebook_access_token'];
}

$twig = $app->view()->getEnvironment();
$twig->addGlobal('fbUser', $fbUser);
$twig->addGlobal('loginUrl', $loginUrl);
//$twig->addGlobal('logoutUrl', $logoutUrl);
//print_r($fbUser);
//print_r($_SESSION['fbmetadata']);
// State 1: first show
$app->get('/register', function() use ($app, $log) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $anticItem = DB::queryFirstRow("SELECT * FROM `itemsforsell` WHERE status='open' and minimumBid=(select max(minimumBid) as bid from itemsforsell WHERE status='open' order by ID desc limit 400)order by ID desc limit 400");
    $maxBid = DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $anticItem['ID']);
    $topList = DB::query("SELECT * FROM `itemsforsell` WHERE status='open' order by ID desc LIMIT 4");
    //$app->render('index.html.twig', array('mainCategoryList' => $mainCategoryList, 'topList' => $topList, 'anticItem' => $anticItem, 'maxBid' => $maxBid));
    $app->render('register.html.twig', array('mainCategoryList' => $mainCategoryList, 'topList' => $topList, 'anticItem' => $anticItem, 'maxBid' => $maxBid));
    // $app->render('register.html.twig', array('mainCategoryList' => $mainCategoryList));
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
        $mainCategoryList = DB::query('SELECT * FROM maincategory');
        $app->render('register.html.twig', array('mainCategoryList' => $mainCategoryList,
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
        $mainCategoryList = DB::query('SELECT * FROM maincategory');
        $app->render('register_success.html.twig', array('mainCategoryList' => $mainCategoryList));
    }
});

// State 1: first show
$app->get('/login', function() use ($app, $log) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $anticItem = DB::queryFirstRow("SELECT * FROM `itemsforsell` WHERE status='open' and minimumBid=(select max(minimumBid) as bid from itemsforsell WHERE status='open' order by ID desc limit 400)order by ID desc limit 400");
    $maxBid = DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $anticItem['ID']);
    $topList = DB::query("SELECT * FROM `itemsforsell` WHERE status='open' order by ID desc LIMIT 4");
    //$app->render('index.html.twig', array('mainCategoryList' => $mainCategoryList, 'topList' => $topList, 'anticItem' => $anticItem, 'maxBid' => $maxBid));
    $app->render('login.html.twig', array('mainCategoryList' => $mainCategoryList, 'topList' => $topList, 'anticItem' => $anticItem, 'maxBid' => $maxBid));
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
        // password MUST be compared in PHP because SQL is case-insenstive
        //if ($user['password'] == hash('sha256', $pass)) {
        if (crypt($pass, $user['password'])) {
            // LOGIN successful
            unset($user['password']);
            $_SESSION['user'] = $user;
            $userID = $user['ID'];
            $log->debug(sprintf("User %s logged in successfuly from IP %s", $userID, $_SERVER['REMOTE_ADDR']));
            $mainCategoryList = DB::query('SELECT * FROM maincategory');
//            $anticItem = DB::queryFirstRow("SELECT * FROM `itemsforsell` WHERE status='open' and minimumBid=(select max(minimumBid) as bid from itemsforsell WHERE status='open' order by ID desc limit 400)order by ID desc limit 400");
//            $maxBid = DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $anticItem['ID']);
//            $topList = DB::query("SELECT * FROM `itemsforsell` WHERE status='open' order by ID desc LIMIT 4");
            //$app->render('index.html.twig', array('mainCategoryList' => $mainCategoryList, 'topList' => $topList, 'anticItem' => $anticItem, 'maxBid' => $maxBid));
            if ($user['isAdmin'] === 'admin') {
                $mainCategoryList = DB::query('SELECT * FROM maincategory');
                $categoryList = DB::query('SELECT * FROM category');
                $saleList = DB::query('SELECT * FROM itemsforsell');
                $purchaseList = DB::query('SELECT * FROM purchases');
                $bidList = DB::query('SELECT * FROM bids');
                $users = DB::query('SELECT * FROM users');
                $app->render('admin.html.twig', array('sessionUser' => $_SESSION['user'],
                    'mainCategoryList' => $mainCategoryList, 'saleList' => $saleList,
                    'purchaseList' => $purchaseList, 'bidList' => $bidList, 'users' => $users,
                    'mainCategoryList' => $mainCategoryList, 'categoryList' => $categoryList
                ));
            } else {
                $saleList = DB::query('SELECT * FROM itemsforsell WHERE userID=%d', $userID);
                $purchaseList = DB::query('SELECT * 
FROM purchases, itemsforsell
WHERE buyerID=%d
AND itemsforsell.ID = itemID ', $userID);
                $bidList = DB::query('SELECT * FROM bids WHERE userID=%d', $userID);
                $app->render('userhome.html.twig', array('sessionUser' => $_SESSION['user'], 'mainCategoryList' => $mainCategoryList, 'saleList' => $saleList, 'purchaseList' => $purchaseList, 'bidList' => $bidList));
            }
        } else {
            $log->debug(sprintf("User failed for username %s from IP %s", $username, $_SERVER['REMOTE_ADDR']));
            $mainCategoryList = DB::query('SELECT * FROM maincategory');
            $app->render('login.html.twig', array('sessionUser' => $_SESSION['user'], 'loginFailed' => TRUE, 'mainCategoryList' => $mainCategoryList));
        }
    }
});

$app->get('/userhome/:role', function($role) use ($app) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    if ($role === 'admin') {
        $categoryList = DB::query('SELECT c.name as name, m.name as maincategory FROM category c, maincategory m WHERE c.mainCategoryID=m.ID');
//        $saleList = DB::query('SELECT * FROM itemsforsell');
//        $purchaseList = DB::query('SELECT * FROM purchases');
//        $bidList = DB::query('SELECT * FROM bids');
        $saleList = DB::query('SELECT * FROM itemsforsell');
        $purchaseList = DB::query('SELECT p.amount as amount, p.buyDate as day, i.name as name FROM purchases p, itemsforsell i WHERE p.itemID=i.ID');
        $bidList = DB::query('SELECT b.bidAmount as amount, b.bidDate as day, i.name as name FROM bids b, itemsforsell i WHERE b.itemID=i.ID');
        $userList = DB::query('SELECT * FROM users');
        $app->render('admin.html.twig', array('sessionUser' => $_SESSION['user'],
            'mainCategoryList' => $mainCategoryList, 'saleList' => $saleList,
            'purchaseList' => $purchaseList, 'bidList' => $bidList, 'userList' => $userList,
            'mainCategoryList' => $mainCategoryList, 'categoryList' => $categoryList
        ));
    } else {
        $saleList = DB::query('SELECT * FROM itemsforsell WHERE userID=%d', $_SESSION['user']['ID']);
        $purchaseList = DB::query('SELECT p.amount as amount, p.buyDate as day, i.name as name FROM purchases p, itemsforsell i WHERE p.itemID=i.ID AND p.buyerID=%d', $_SESSION['user']['ID']);
        $bidList = DB::query('SELECT b.bidAmount as amount, b.bidDate as day, i.name as name FROM bids b, itemsforsell i WHERE b.itemID=i.ID AND b.userID=%d', $_SESSION['user']['ID']);
        $app->render('userhome.html.twig', array('sessionUser' => $_SESSION['user'], 'mainCategoryList' => $mainCategoryList, 'saleList' => $saleList, 'purchaseList' => $purchaseList, 'bidList' => $bidList));
    }
});

$app->get('/edit/:ID', function($ID) use ($app) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $app->render('underconstruction.html.twig', array('sessionUser' => $_SESSION['user'], 'mainCategoryList' => $mainCategoryList));
});

$app->get('/delete/:ID', function($ID) use ($app) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $app->render('underconstruction.html.twig', array('sessionUser' => $_SESSION['user'], 'mainCategoryList' => $mainCategoryList));
});

$app->get('/adduser', function() use ($app) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $app->render('underconstruction.html.twig', array('sessionUser' => $_SESSION['user'], 'mainCategoryList' => $mainCategoryList));
});

$app->get('/addmaincategory', function() use ($app) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $app->render('underconstruction.html.twig', array('sessionUser' => $_SESSION['user'], 'mainCategoryList' => $mainCategoryList));
});

$app->get('/addcategory', function() use ($app) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $app->render('underconstruction.html.twig', array('sessionUser' => $_SESSION['user'], 'mainCategoryList' => $mainCategoryList));
});

$app->get('/logout', function() use ($app, $log) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $_SESSION['facebook_access_token'] = NULL;
    $_SESSION['user'] = NULL;
    // $app->render('index.html.twig', array('mainCategoryList' => $mainCategoryList));


    $anticItem = DB::queryFirstRow("SELECT * FROM `itemsforsell` WHERE status='open' and minimumBid=(select max(minimumBid) as bid from itemsforsell WHERE status='open' order by ID desc limit 400)order by ID desc limit 400");
    $maxBid = DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $anticItem['ID']);
    $topList = DB::query("SELECT * FROM `itemsforsell` WHERE status='open' order by ID desc LIMIT 4");
    $app->render('index.html.twig', array('mainCategoryList' => $mainCategoryList, 'topList' => $topList, 'anticItem' => $anticItem, 'maxBid' => $maxBid));
});

$app->post('/addcredit', function() use ($app, $log) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $creditAmount = $app->request->post('creditAmount');
    $userID = $app->request->post('userID');
    DB::update('users', array("credit" => $creditAmount), "ID=%d", $userID);
    $_SESSION['user']['credit'] = $creditAmount;
    $app->render('index.html.twig', array('sessionUser' => $_SESSION['user'], 'mainCategoryList' => $mainCategoryList));
});

$app->get('/selllist/:ID', function($ID) use ($app) {

    $mainCategoryList = DB::query('SELECT * FROM maincategory');
//if (isset($_GET["page"])) { $page  = $_GET["page"]; } else { $page=1; }; 
//$start_from = ($page-1) * $results_per_page;
//// LIMIT $start_from, ".$results_per_page;
//ORDER BY id DESC LIMIT {$start},{$limit}
  //  $sellList = DB::query("SELECT * FROM itemsforsell WHERE status='open' AND categoryID=%d  ORDER BY ID desc ", $ID);
    $sellList = DB::query("SELECT itemsforsell. * , MAX( bidAmount ) AS max FROM itemsforsell, bids WHERE STATUS =  'open' AND categoryID=%d AND itemID = itemsforsell.ID GROUP BY itemID ", $ID);
    
    

//$sql = "SELECT * FROM ".$datatable." ORDER BY ID ASC LIMIT $start_from, ".$results_per_page;
    // 404 if record not found
    //  if (!$sellList) {
    //     $app->response->setStatus(404);
    //    echo json_encode("Record not found");
    //    return;
    //}
    // echo json_encode($record, JSON_PRETTY_PRINT);
    // print_r($sellList);
    $maxBid = DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $ID);
    $app->render('sel.html.twig', array('sessionUser' => $_SESSION['user'], 'sellList' => $sellList, 'mainCategoryList' => $mainCategoryList, 'maxBid' => $maxBid));
});
//wewsellitem/{{mList.ID}}
$app->get('/viewsellitem/:ID', function($ID) use ($app) {

    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $item = DB::queryFirstRow("SELECT * FROM itemsforsell WHERE status='open' AND ID=%d", $ID);
    $maxBid = DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $ID);


    // $app->render('viewitem.html.twig', array('sessionUser' => $_SESSION['user'], 'item' => $item, 'mainCategoryList' => $mainCategoryList));
    $app->render('viewitem.html.twig', array('sessionUser' => $_SESSION['user'], 'item' => $item, 'maxBid' => $maxBid, 'mainCategoryList' => $mainCategoryList));
});

$app->get('/itemsforsell', function() use ($app, $log) {
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $app->render('addsell.html.twig', array('sessionUser' => $_SESSION['user'], 'mainCategoryList' => $mainCategoryList));
});

$app->post('/itemsforsell', function() use ($app, $log) {
    //  $body = $app->request->getBody();
    //$record = json_decode($body, TRUE);
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $fileToUpload = $_FILES['itemPic'];
    $record1 = array();
    $record1['categoryID'] = $_POST['categoryList'];
    $errorList = array();
    if (!($_SESSION['user'])) {
        array_push($errorList, "you must login in our system");
        $app->render('addsell.html.twig', array('mainCategoryList' => $mainCategoryList, 'errorList' => $errorList,'sessionUser' => $_SESSION['user']));
    } else {



        $record1['userID'] = $_SESSION['user']['ID'];
        $errorList = array();
        if ($fileToUpload['error'] == 0) {

            $record1['mimeType'] = $fileToUpload['type'];
            $record1['itemPic'] = file_get_contents($fileToUpload['tmp_name']);
        } else {
            array_push($errorList, "image file must be provided");
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
        $record1['description'] = $_POST['description'];
        $record1['minimumBid'] = $_POST['minimumBid'];
        $record1['bidEndTime'] = $bidEndTime1;
        $record1['bidStartTime'] = $bidStartTime1;


        if ($bidEndTime1 <= $bidStartTime1) {
            array_push($errorList, "bid  end time must be less than start time");
        }
        if (!is_numeric($record1['minimumBid']) || $record1['minimumBid'] <= 0) {
            array_push($errorList, "minimum bid amount is zero or negative ");
        }
        if (strlen($record1['name']) <= 2) {
            array_push($errorList, "name  must be at least 2 characters long  ");
        }
        $valueList = $record1;
        if ($errorList!=array()) {
              echo $errorList;
            $app->render('addsell.html.twig', array('mainCategoryList' => $mainCategoryList, 'errorList' => $errorList,'sessionUser' => $_SESSION['user']));
        } else {
            DB::insert('itemsforsell', $record1);
            $id1 = DB::insertId();
            $log->debug("item add for sell with ID=" . $id1);
            // $app->render('index.html.twig', array('mainCategoryList' => $mainCategoryList));
            $sellList = DB::query("SELECT * FROM itemsforsell WHERE status='open' AND userID=%d   ", $_SESSION['user']['ID']);

            //  $maxBid=DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $itemID);

            $maxBid = array();


            $app->render('sel.html.twig', array('sessionUser' => $_SESSION['user'], 'sellList' => $sellList, 'mainCategoryList' => $mainCategoryList, 'maxBid' => $maxBid));


            // POST / INSERT is special - returns 201
            $app->response->setStatus(201);
        }
    }
});

$app->post('/bids', function() use ($app, $log) {
    //  $body = $app->request->getBody();
    //  $record = json_decode($body, TRUE);
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $userID = $app->request->post('userID');
    $userCredit = DB::queryFirstField('SELECT credit FROM users where ID=%d', $userID);
    $bidDate = date("Y-m-d H:i:s");
    $bidAmount = $app->request->post('bidAmount');
    $itemID = $app->request->post('itemID');
    if (!$bidAmount || $bidAmount <= 0) {
        $maxBid = DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $itemID);
        $item = DB::queryFirstRow('SELECT *  FROM itemsforsell where ID=%d', $itemID);
        $error = 'the amount must be provided';
        $app->render('viewitem.html.twig', array('sessionUser' => $_SESSION['user'], 'item' => $item, 'maxBid' => $maxBid, 'mainCategoryList' => $mainCategoryList, 'error' => $error));
        return;
    }
    if ($userCredit < $bidAmount) {
        $error = 'your credit is lower than this amount';
        $maxBid = DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $itemID);
        $item = DB::queryFirstRow('SELECT *  FROM itemsforsell where ID=%d', $itemID);
        $app->render('viewitem.html.twig', array('sessionUser' => $_SESSION['user'], 'item' => $item, 'maxBid' => $maxBid, 'mainCategoryList' => $mainCategoryList, 'error' => $error));
        return;
    }
    $record = array('userID' => $userID, 'bidDate' => $bidDate, 'bidAmount' => $bidAmount, 'itemID' => $itemID);
    DB::insert('bids', $record);
    echo DB::insertId();
    // POST / INSERT is special - returns 201
    $app->response->setStatus(201);
    // $log->debug(sprintf("bids %s created"));
    $sellList = DB::query("SELECT * FROM itemsforsell WHERE status='open' AND ID=%d   ", $itemID);

    $maxBid = DB::queryFirstRow("SELECT MAX(bidAmount) as max,count(*) as count FROM bids WHERE itemID=%d", $itemID);




    $app->render('sel.html.twig', array('sessionUser' => $_SESSION['user'], 'sellList' => $sellList, 'mainCategoryList' => $mainCategoryList, 'maxBid' => $maxBid));
});

/////////////////////////////////////////////////////////////


$app->get('/searchall', function() use ($app, $log) {
    //$app->get('/searchall/:des', function($des) use ($app) { 

    $des = $app->request->get('itemSearch');
    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $sellList = DB::query("SELECT * FROM itemsforsell WHERE  status='open' AND ( name LIKE  %ss  OR  description LIKE  %ss)", $des, $des);

    if ($sellList) {

        $log->debug(sprintf("User %s created", $des));
        $app->render('sel.html.twig', array('sessionUser' => $_SESSION['user'], 'sellList' => $sellList, 'mainCategoryList' => $mainCategoryList));
    } else {
        $log->debug(sprintf("User %s created", $des));
        $app->render('sel.html.twig', array('sessionUser' => $_SESSION['user'], 'mainCategoryList' => $mainCategoryList));
    }
    //   $app->render('sel.html.twig', array('sellList' => $sellList));
});
$app->get('/topfour/', function() use ($app) {

    $mainCategoryList = DB::query('SELECT * FROM maincategory');
    $app->render('login.html.twig', array('mainCategoryList' => $mainCategoryList));
    // LIKE @ProductName OR Barcode  LIKE @Barcode
    $app->render('sel.html.twig', array('sessionUser' => $_SESSION['user'], 'sellList' => $sellList, 'mainCategoryList' => $mainCategoryList));
    //   $app->render('sel.html.twig', array('sellList' => $sellList));
});

$app->run();
