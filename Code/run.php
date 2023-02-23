<?php
// This script is used to display errors within a php script and should ONLY be used in place of other .php scripts in the showdata.php script.
// Input the name of the script that you want to test for errors in the "include();" line
// This is a simple run script that is required to test for errors thrown within the php script you are testing
// Useful for if you suspect there to be a syntax or other error in your .php script, but can't get an error thrown in the console log.
// MAKE SURE to only use in testing environment, not production. 

error_reporting(E_ALL); 
ini_set('display_errors','1');
ini_set('display_startup_errors', TRUE);
include("redraw_query.php");
?>