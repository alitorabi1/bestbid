<?php

require_once 'vendor/autoload.php';
require_once 'master.php';
session_start();

//facebook login
$fbID = '704374739718815';
$fbPass = '3cf65c2f0c77bde0f9019fc07d3c0471';
$fb = new Facebook\Facebook([
    'app_id' => $fbID,
    'app_secret' => $fbPass,
    'default_graph_version' => 'v2.5',
    'persistent_data_handler' => 'session'
        ]);

$helper = $fb->getRedirectLoginHelper();

try {
    $accessToken = $helper->getAccessToken();
} catch (Facebook\Exceptions\FacebookResponseException $e) {
    // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    // When validation fails or other local issues
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}

if (!isset($accessToken)) {
    if ($helper->getError()) {
        header('HTTP/1.0 401 Unauthorized');
        echo "Error: " . $helper->getError() . "\n";
        echo "Error Code: " . $helper->getErrorCode() . "\n";
        echo "Error Reason: " . $helper->getErrorReason() . "\n";
        echo "Error Description: " . $helper->getErrorDescription() . "\n";
    } else {
        header('HTTP/1.0 400 Bad Request');
        echo 'Error: Bad request';
    }
    // exit;
}

// Logged in
$oAuth2Client = $fb->getOAuth2Client();
$tokenMetadata = $oAuth2Client->debugToken($accessToken);
$tokenMetadata->validateAppId($fbID);
$tokenMetadata->validateExpiration();

if (!$accessToken->isLongLived()) {
    // Exchanges a short-lived access token for a long-lived one
    try {
        $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
        exit;
    }
    echo '<h3>Long-lived</h3>';
}

$fb->setDefaultAccessToken($accessToken);

try {
    $response = $fb->get('/me?locale=en_US&fields=id,name,email,gender,first_name,last_name,location');
    $userNode = $response->getGraphUser();

    // Submission successful
    $user = DB::queryFirstRow("SELECT * FROM users WHERE username=%s", $userNode->getId());
    if (!$user) {
        DB::insert('users', array('username' => $userNode->getId(), 'email' => $userNode->getEmail(), 'fbUID' => 'yes'));
        $id = DB::insertId();
        $log->debug(sprintf("Facebook user %s created", $id));
    }
} catch (Facebook\Exceptions\FacebookResponseException $e) {
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}


$fbUser = array(
    'name' => $userNode->getName(),
    'username' => $userNode->getName(),
    'email'=> $userNode->getEmail(),
    'gender' => $userNode->getGender(),
    'ID' => $id,
    'location' => $userNode->getLocation(),
    'isAdmin' => "user"
);

//$user = DB::queryFirstRow("SELECT * FROM users WHERE username=%s", $fbUser.username);

$_SESSION['facebook_access_token'] = $fbUser;
$_SESSION['user'] = $fbUser;
        
header("Location: /");  
