<!DOCTYPE html>
<html>
<title>Sample Data</title>
<?php
include_once("head.html");
?>
<body id="BodyTag">
<script type="text/javascript" src="main.js"></script>
<div class="container-fluid">
    <div class="panel-body">
        <div id="nav"></div>
        <h1>DiVenn Sample Data</h1>
        <div class="col-sm-12">To test DiVenn using our sample data, you can either:
            <ul>
                <li>Download the following sample files by right-clicking the sample buttons below and uploading them to
                    DiVenn.
                </li>
                OR
                <li>Click the sample buttons below then copy and paste the data to DiVenn.</li>
            </ul>
        </div>
        <div class="col-sm-12">
            <ol>
            <li><b>Species ID Examples and Reference</b></li>
                <a href="/v3/data/Species_ID_types_examples_web.htm" target="_blank" id="code" type="submit" class="btn btn-success"><span
                            class="glyphicon glyphicon-download"></span> ID Table</a><br/><br/>
                <li>Arabidopsis KEGG IDs with expression values:</li>
                <a href="data/DiVenn_sample_data_c.txt" target="_blank" id="code" type="submit" class="btn btn-success">
                    <span class="glyphicon glyphicon-download"></span> Sample1</a><br/><br/>
                <a href="data/DiVenn_sample_data_cf.txt" target="_blank" id="code" type="submit"
                   class="btn btn-success">
                    <span class="glyphicon glyphicon-download"></span> Sample2</a><br/><br/>
                <a href="data/DiVenn_sample_data_f.txt" target="_blank" id="code" type="submit" class="btn btn-success">
                    <span class="glyphicon glyphicon-download"></span> Sample3</a><br/><br/>
                <li>Chicken NCBI IDs with expression values:</li>
                <a href="data/entrez_1.txt" target="_blank" id="code" type="submit" class="btn btn-success"><span
                            class="glyphicon glyphicon-download"></span> Sample1</a><br/><br/>
                <a href="data/entrez_2.txt" target="_blank" id="code" type="submit" class="btn btn-success"><span
                            class="glyphicon glyphicon-download"></span> Sample2</a><br/><br/>
                <a href="data/entrez_3.txt" target="_blank" id="code" type="submit" class="btn btn-success"><span
                            class="glyphicon glyphicon-download"></span> Sample3</a><br/><br/>
                <li>Chicken Uniprot IDs with expression values:</li>
                <a href="data/uniprot_1.txt" target="_blank" id="code" type="submit" class="btn btn-success"><span
                            class="glyphicon glyphicon-download"></span> Sample1</a><br/><br/>
                <a href="data/uniprot_2.txt" target="_blank" id="code" type="submit" class="btn btn-success"><span
                            class="glyphicon glyphicon-download"></span> Sample2</a><br/><br/>
                <a href="data/uniprot_3.txt" target="_blank" id="code" type="submit" class="btn btn-success"><span
                            class="glyphicon glyphicon-download"></span> Sample3</a><br/><br/>
                <li>Customized Data: The columns are "Gene ID, Expression Value, Uniprot ID, NCBI ID, Description,
                    Pathway, GO ID, GO Term, GO
                    Category, P Value" , and they are separated by tab.
                </li>
                <a href="data/expDb0.txt" target="_blank" id="code" type="submit" class="btn btn-success"><span
                            class="glyphicon glyphicon-download"></span> Sample1</a><br/><br/>
                <a href="data/expDb1.txt" target="_blank" id="code" type="submit" class="btn btn-success"><span
                            class="glyphicon glyphicon-download"></span> Sample2</a><br/><br/>
                <a href="data/expDb2.txt" target="_blank" id="code" type="submit" class="btn btn-success"><span
                            class="glyphicon glyphicon-download"></span> Sample3</a><br/><br/>
            </ol>
        </div>
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