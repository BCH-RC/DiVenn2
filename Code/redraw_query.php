<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', TRUE);
session_start();
$geneIDs = $_POST["ids"];
$redrawSelected = $_POST["redrawselected"];

$IDs = explode(",", $geneIDs);
if ($redrawSelected == "true") {

    $mydata = $_SESSION['mydata_cutoffed'];
    $datasize = count($mydata);
    echo "datasize: " . $datasize . "<br>";
    for ($n = 0; $n < $datasize; $n++) {
        $im_list = explode("\r\n", $mydata[$n]);       //explode string by \n
        $newdata[$n] = "";
        foreach ($im_list as $key => $value) {
            $parts = explode("\t", $value);
            foreach ($IDs as $k => $val) {
                if (strtolower($parts[0]) == strtolower($val)) {
                    $newdata[$n] = $newdata[$n] . $value . "\r\n";
                    break;
                }
            }
        }
        $_SESSION['mydata_cutoffed'][$n] = test_input($newdata[$n]);
    }
    $_SESSION['redrawdata'] = [];
} else {
    $_SESSION['redrawdata'] = $IDs;
}

echo "<script>window.location.href='drawing.php';</script>";

function test_input($data): string
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = str_replace('/', '', $data);
    $data=htmlspecialchars($data);
    return $data;
}
