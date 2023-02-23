<!DOCTYPE html>
<html>
<title>About</title>
<?php
include_once("head.html");
?>
<body id="BodyTag">
<script type="text/javascript" src="main.js"></script>
<div class="container-fluid">
    <div class="panel-body">
        <div id="nav"></div>
        <h1>About</h1>
        <p> DiVenn is an interactive and integrated web-based tool for comparing gene lists. The current version is
            “2.0”.</p><br/>
        <h2>Introduction</h2>
        <p>Gene expression data generated from multiple biological states (mutant sample, double mutant sample and
            wild-type samples) are often
            compared via Venn diagram tools. It is of great interest to know the expression pattern between overlapping
            genes and their associated
            gene pathways or gene ontology terms. We developed DiVenn – a novel web-based tool that compares gene lists
            from multiple RNA-Seq
            experiments in a force directed graph, which shows the gene regulation levels for each gene and integrated
            KEGG pathway and gene
            ontology (GO) knowledge for the data visualization. </p><br/>
        <h2>Three Key Features</h2>
        <ul>
            <li>Informative force-directed graph with gene expression levels to compare multiple data sets;</li>
            <li>Interactive visualization with biological annotations and integrated pathway and GO databases, which can
                be used to subset or
                highlight gene nodes to pathway or GO terms of interest in the graph;
            </li>
            <li>High resolution image and gene-associated information export.</li>
        </ul>
        <br/>
        <h2>Tutorial</h2>
        <p>Click <a href="https://www.youtube.com/watch?v=A7Ldx24e9UU&feature=youtu.be" target="_blank">below</a> to
            watch a tutorial video, or
            view <a href="https://github.com/noble-research-institute/DiVenn">Source Files</a> for more information.
        <p>
            <?php
            $url = 'https://www.youtube.com/watch?v=A7Ldx24e9UU&feature=youtu.be';
            preg_match('/[\\?\\&]v=([^\\?\\&]+)/', $url, $matches);
            $id = $matches[1];
            $width = '800px';
            $height = '450px';
            ?>
            <iframe id="ytplayer" type="text/html" width="<?php echo $width ?>" height="<?php echo $height ?>"
                    src="https://www.youtube.com/embed/<?php echo $id ?>?rel=0&showinfo=0&color=white&iv_load_policy=3"
                    frameborder="0" allowfullscreen></iframe>
        </p>
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
