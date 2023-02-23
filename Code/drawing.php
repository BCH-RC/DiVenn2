<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
include_once("head.html");
?>
<!DOCTYPE html>
<html lang="en">
<title>DiVenn 2.0</title>
<noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-573XNWX"
            height="0" width="0" style="display:none;visibility:hidden"></iframe>
</noscript>
<!-- End Google Tag Manager (noscript) -->

<div class="l-flex">
    <div class="panel-body">
        <?php
        include_once("showdata.php");
        ?>
        <div id="pathway_table"></div>
    </div>
</div>
<footer>
    <script>
        $(function () {
            $('#pathway_table').load("gene_pathway_table.html");
        })
    </script>
</footer>
</html>
