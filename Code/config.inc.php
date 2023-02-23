<?php
$app_root_folder = "v3";

$db_host = "localhost";
$db_name = "divenn_newdb";
$db_user = "divennuser";
$db_pass = "ch213537@BCH";

$fisher_test_location = "/var/www/html/" . $app_root_folder . "/r/fisher_test.R";
$clusterProfiler_location = "/var/www/html/" . $app_root_folder . "/r/clusterProfiler.R";
$clusterKEGG_location = "/var/www/html/" . $app_root_folder . "/r/clusterProfilerKEGG.R";

return [$db_host, $db_user, $db_pass, $db_name, $clusterProfiler_location, $clusterKEGG_location];
