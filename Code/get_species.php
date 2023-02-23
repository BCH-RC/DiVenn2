<?php
$config_props = include("config.inc.php");

$db_host = $config_props[0];
$db_user = $config_props[1];
$db_pass = $config_props[2];
$db_name = $config_props[3];
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
$query = "SELECT * FROM species ORDER BY full_name";
$species = $mysqli->query($query);
mysqli_close($mysqli);
return $species;
