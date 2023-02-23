<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');
?>
<!DOCTYPE html>
<html lang="en">
<title>DiVenn 2.0</title>
<?php
include_once("head.html");
?>

<body id="BodyTag">
<script type="text/javascript" src="main.js"></script>
<div class="container-fluid">
    <div class="panel panel-body">
        <div id="nav"></div>
        <div id="welcome_divenn"></div>
        <?php
        include_once("main.php");
        ?>
    </div>
</div>
<div id="footer"></div> <!-- to load partial page-->
<script>
    $(function () {
        $('#nav').load("new_navbar.html");
        $('#welcome_divenn').load("welcome_divenn.html");
    });
</script>
</body>
</html>