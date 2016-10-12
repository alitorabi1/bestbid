<?php

session_start();

// enable on-demand class loader
require_once 'vendor/autoload.php';
require_once 'master.php';
session_start();
if (!isset($_GET['id'])) {
    die("You must provide id of image you want to view");
}

$ID=$_GET['id'];
$item = DB::queryFirstRow("SELECT * FROM itemsforsell WHERE ID=%d", $ID);
    header("Content-type: " . $item['mimeType']);
   


$length = strlen($item['itemPic']);
header("Content-Length: $length");

echo $item['itemPic'];