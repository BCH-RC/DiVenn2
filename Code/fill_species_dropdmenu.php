<?php

$initial_options = '';
$result = $_SESSION['specieslist'];
if (mysqli_num_rows($result) > 0) {
    while ($row = $result->fetch_assoc()) {
        $common_name = '';
        if (strlen($row["common_name"]) > 0)
            $common_name = '(' . $row["common_name"] .')';
        $initial_options .= '<option value="' . $row["short_name"] . '">' . $row["full_name"] . ' '. $common_name .'</option>';
    }
}

return $initial_options;
