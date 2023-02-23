<?php
$app_root_folder = "/var/www/html";

$db_host = "mysql";
$db_name = "divenn";
$db_user = "root";
$db_pass = "divenn";

$fisher_test_location = $app_root_folder . "/r/fisher_test.R";

return [$db_host, $db_user, $db_pass, $db_name, $fisher_test_location];
