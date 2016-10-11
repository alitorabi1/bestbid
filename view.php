<?php

session_start();

// enable on-demand class loader
require_once 'vendor/autoload.php';
DB::$dbName ='bestbid';
DB::$user ='bestbid';

//DB::$password='r9pjLBpJnDqZ5ewv';//home
//DB::$port='3333';
//DB::$password='bDYeWvRqrfzL6wDe';//college
DB::$password = 'bDYeWvRqrfzL6wDe'; //Home
DB::$encoding = 'utf8'; // defaults to latin1 if omitted
DB::$port='3333';
if (!isset($_GET['id'])) {
    die("You must provide id of image you want to view");
}

$ID=$_GET['id'];
$item = DB::queryFirstRow("SELECT * FROM itemsforsell WHERE ID=%d", $ID);
    header("Content-type: " . $item['mimeType']);
   


$length = strlen($item['itemPic']);
header("Content-Length: $length");

echo $item['itemPic'];