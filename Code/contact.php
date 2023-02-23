<!DOCTYPE html>
<html lang="en">
<title>Contact Us</title>
<?php
include_once("head.html");
?>
<body id="BodyTag">
<script type="text/javascript" src="main.js"></script>
<div class="container-fluid">
    <div class="panel panel-body">
        <div id="nav"></div>
        <h1>Contact Us</h1>
        <p> If you have questions or comments about our application, we would be pleased to hear from you.</p><br/>
        <p>Please contact our <a href="mailto:liang.sun@childrens.harvard.edu">site administrator</a></p><br/>
    </div>
</div>
<div id="footer"></div> <!-- to load partial page-->
<script>
    $(function () {
        $('#nav').load("new_navbar.html");
    });
</script>
</body>
</html>
