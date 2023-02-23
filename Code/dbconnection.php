<?php
$db_host = null;
$db_name = null;
$db_user = null;
$db_pass = null;
include("config.inc.php");

$mysqli = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    $message = 'Connection error: ';
    error_log($message . $mysqli->connect_errno);
    die($message . $mysqli->connect_errno);
} else
    mysqli_close($mysqli);

return $mysqli;
