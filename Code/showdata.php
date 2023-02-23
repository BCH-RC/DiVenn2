<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$default_colors = $_SESSION['mycolor'];
$filenum = $_SESSION['filenum'];
$idsWithSymbols = $_SESSION['idsWithSymbols'];
$redraw = $_SESSION['redrawdata'];
$referenceDb = $_SESSION['referenceDb'];
$species = $_SESSION['species'];
$custom_data_passed_by_user = FALSE;
$cutoff = $_SESSION['cutoff'];
$cutofftype = $_SESSION['cutofftype'];
$mydata_cutoffed = $_SESSION['mydata_cutoffed'];
$genes_val = $_SESSION['genes_values'];
$raw_genes_val = $_SESSION['raw_genes_values'];
//$count_errors = $_SESSION['count_errors'];



for ($n = 0; $n < $filenum; $n++) {
    $mydata[$n] = $_SESSION['mydata_cutoffed'][$n];
    $row = explode("\r\n", $mydata[$n]); //explode string by \n
    $key = 0;
    foreach ($row as $value) {
        if ($key >= 0) {
            $parts = preg_split("/[\s,]+/", $value, -1, PREG_SPLIT_NO_EMPTY);
            if (count($parts) > 0) {
                if ($parts[0] != "-" && isset($parts[0]) && isset($parts[1]) && isset($parts[2])) {
                    $objectLinks[$n][$key] = (object)array("id" => $parts[0], "value" => $parts[1], "logfold" => $parts[2]);
                    $key += 1;
                } else if ($parts[0] != "-" && isset($parts[0]) && isset($parts[1])) {
                    $objectLinks[$n][$key] = (object)array("id" => $parts[0], "value" => $parts[1]);
                    $key += 1;
                } else if ($parts[0] == "-") {
                    $custom_data_passed_by_user = TRUE;
                }
            }
        }
    }
}
$objlinks = json_encode($objectLinks);

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['colorselector0'])) {
    for ($n = 0; $n < $filenum; $n++) {
        $_SESSION['mycolor'][$n] = $_POST['colorselector' . $n];   // when click the "changecolor" button reset the color value in session

    }
    $_SESSION['mycolor'][$filenum] = $_POST['colorselectorUp'];
    $_SESSION['mycolor'][$filenum + 1] = $_POST['colorselectorDown'];
    $_SESSION['mycolor'][$filenum + 2] = $_POST['colorselectorUpDown'];
    $_SESSION['mycolor'][$filenum + 3] = $_POST['colorselectorSum'];
    $_SESSION['mycolor'][$filenum + 4] = $_POST['colorselectorRedraw'];
}
// get color value from session
for ($n = 0; $n < $filenum; $n++) {
    $expname[$n] = "Exp_" . $_SESSION['exp_name'][$n];         //get the customer defined experiment name
    $mycolors[$n] = $_SESSION['mycolor'][$n];          // get the customer defined node color
}


if (isset($_SESSION['mycolor'][$filenum]) && $_SERVER['REQUEST_METHOD'] == "POST") {
    $mycolors[$filenum] = $_SESSION['mycolor'][$filenum];
    $mycolors[$filenum + 1] = $_SESSION['mycolor'][$filenum + 1];
    $mycolors[$filenum + 2] = $_SESSION['mycolor'][$filenum + 2];
    $mycolors[$filenum + 3] = $_SESSION['mycolor'][$filenum + 3];
    $mycolors[$filenum + 4] = $_SESSION['mycolor'][$filenum + 4];
}

$myshapes = [];
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['shapeselectorUp'])) {
    $_SESSION['myshape'][0] = $_POST['shapeselectorUp'];
    $_SESSION['myshape'][1] = $_POST['shapeselectorDown'];
    $_SESSION['myshape'][2] = $_POST['shapeselectorUpDown'];
    $_SESSION['myshape'][3] = $_POST['shapeselectorSum'];
    $_SESSION['myshape'][4] = $_POST['shapeselectorRedraw'];
}

if (isset($_SESSION['myshape'][0])) {
    $myshapes[0] = $_SESSION['myshape'][0];
    $myshapes[1] = $_SESSION['myshape'][1];
    $myshapes[2] = $_SESSION['myshape'][2];
    $myshapes[3] = $_SESSION['myshape'][3];
    $myshapes[4] = $_SESSION['myshape'][4];
}

?>
<table class="GG5EBMLCKI">
    <tbody>
    <tr>
        <td class="arrowsupdown">
        </td>
        <td class="arrowsupdown">
            <img id="moveUp" src="image/up-arrow.png" width="24" height="24" class="gwt-Image" alt="move up"
                 title="move up">
        </td>
    </tr>
    <tr>
        <td class="arrowsleftright">
            <img id="moveLeft" src="image/left-arrow.png" width="24" height="24" class="gwt-Image" alt="move left"
                 title="move left">
        </td>
        <td>
            <img id="reset" src="image/reset.png" width="24" height="24" class="gwt-Image" alt="reset" title="reset">
        </td>
        <td class="arrowsleftright">
            <img id="moveRight" src="image/right-arrow.png" width="24" height="24" class="gwt-Image" alt="move right"
                 title="move right">
        </td>
        <td class="zoominout">
            <img id="zoomOut" src="image/zoomOut2.png" width="24" height="24" class="gwt-Image" alt="zoom out"
                 title="zoom out" style="margin-left:15px">
        </td>
        <td class="zoominout">
            <img id="zoomIn" src="image/zoomIn2.png" width="24" height="24" class="gwt-Image" alt="zoom in"
                 title="zoom in">
        </td>
    </tr>
    <tr>
        <td class="arrowsupdown">
        </td>
        <td class="arrowsupdown">
            <img id="moveDown" src="image/down-arrow.png" width="24" height="24" class="gwt-Image" alt="move down"
                 title="move down">
        </td>
    </tr>
    </tbody>
</table>

<table class="LEGENDTABLE">
    <tbody>
    <tr class="legend">
        <td class="legendicons">
            <img src="image/reddot.jpg" class="ball_legend">
        </td>
        <td class="legenddescription">
            <span>Up-regulated</span>
        </td>
    </tr>
    <tr class="legend">
        <td class="legendicons">
            <img src="image/bluedot.jpg" class="ball_legend">
        </td>
        <td class="legenddescription">
            <span>Down-regulated</span>
        </td>
    </tr>
    <tr class="legend">
        <td class="legendicons">
            <img src="image/yellowdot.jpg" class="ball_legend">
        </td>
        <td class="legenddescription">
            <span>Up or down-regulated</span>
        </td>
    </tr>
    <tr class="legend">
        <td class="legendicons">
            <img src="image/redline.jpg" class="ball_legend">
        </td>
        <td class="legenddescription">
            <span>Up-regulated</span>
        </td>
    </tr>
    <tr class="legend">
        <td class="legendicons">
            <img src="image/blueline.png" class="ball_legend">
        </td>
        <td class="legenddescription">
            <span>Down-regulated</span>
        </td>
    </tr>
    </tbody>
</table>

<!--the table below is for change color button-->
<table class="changeColor" style="display:none">
    <tr>
        <td align="left">
            <form method="post" enctype="multipart/form-data" id="changeColorForm">
                <span id="colorchanger"></span>
                <button type="submit" name="changecolor"> change color</button>
            </form>
        </td>
    </tr>
</table>

<!--the table below is for change shape button-->
<table class="changeShape" style="display:none">
    <tr>
        <td align="left">
            <form method="post" enctype="multipart/form-data" id="changeShapeForm">
                <span id="shapechanger"></span>
                <button type="submit" name="changeshape"> change shape</button>
            </form>
        </td>
    </tr>
</table>

<!--the div below is SVG Container-->
<div class="d3container">
    <label id="test"></label>
    <section id="chart">
    </section>
</div>

<div class="modalspin">
</div>

<!-- Modal -->
<div id="myModal" class="modal fade" style="display: none;" aria-hidden="true">
    <div class="modal-dialog " style="width:85%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                <h3 id="myModalLabel">Gene Details</h3>
            </div>
            <div class="modal-body">
                <div id="commonInfo">
                </div>
                <div class="text-right pull-right" style="overflow: auto">
                    <table id="modal_table" class="table table-striped" tableindex="-1">
                        <tbody id="Gene_Info_Dialog">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

<!-- Node Modal -->
<div id="nodeModal" class="modal fade" style="display: none;" aria-hidden="true">
    <div class="modal-dialog " style="width:85%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                <h3 id="myModalLabel">Gene Details</h3>
            </div>
            <div class="modal-body">
                <div id="commonInfoN">
                </div>
                <div style="overflow: auto">
                    <table id="modal_table" class="table table-striped" tableindex="-1">
                        <tbody id="Gene_Info_DialogN">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

<!-- enrichModal -->
<div id="enrichModal" class="modal fade" style="display: none;" aria-hidden="true">
    <div class="modal-dialog" style="width:85%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                <h3 id="myModalLabel">ClusterProfiler GO Enrichment Results</h3>
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item waves-effect waves-light">
                        <a class="nav-link active" id="all-tab" data-toggle="tab" href="#all" role="tab" aria-controls="all" aria-selected="true">All</a>
                    </li>
                    <li class="nav-item waves-effect waves-light">
                        <a class="nav-link" id="BP-tab" data-toggle="tab" href="#BP" role="tab" aria-controls="BP" aria-selected="false">GO BP</a>
                    </li>
                    <li class="nav-item waves-effect waves-light">
                        <a class="nav-link" id="CC-tab" data-toggle="tab" href="#CC" role="tab" aria-controls="CC" aria-selected="false">GO CC</a>
                    </li>
                    <li class="nav-item waves-effect waves-light">
                        <a class="nav-link" id="MF-tab" data-toggle="tab" href="#MF" role="tab" aria-controls="MF" aria-selected="false">GO MF</a>
                    </li>
                    <li class="nav-item waves-effect waves-light">
                        <a class="nav-link" id="Bar-tab" data-toggle="tab" href="#Bar" role="tab" aria-controls="Bar" aria-selected="false">All Top 20 (Bar Plot)</a>
                    </li>
                    <!--<li class="nav-item waves-effect waves-light">
                        <a class="nav-link" id="Dot-tab" data-toggle="tab" href="#Dot" role="tab" aria-controls="Dot" aria-selected="false">Top 20 (Dot Plot)</a>
                    </li>-->
                    <li class="nav-item waves-effect waves-light">
                        <a class="nav-link" id="BPBar-tab" data-toggle="tab" href="#BPBar" role="tab" aria-controls="BPBar" aria-selected="false">BP Top 20 (Bar Plot)</a>
                    </li>
                    <li class="nav-item waves-effect waves-light">
                        <a class="nav-link" id="CCBar-tab" data-toggle="tab" href="#CCBar" role="tab" aria-controls="CCBar" aria-selected="false">CC Top 20 (Bar Plot)</a>
                    </li>
                    <li class="nav-item waves-effect waves-light">
                        <a class="nav-link" id="MFBar-tab" data-toggle="tab" href="#MFBar" role="tab" aria-controls="MFBar" aria-selected="false">MF Top 20 (Bar Plot)</a>
                    </li>
                </ul>
            </div>
            <div class="modal-body">
                <div class="col-md-12" > 
                    <button id="export" onclick="exportGOenrich()" classname="btn">Export GO Table</button>
                    <!--<button id="exportBar"  onclick="exportGOBar()" classname="btn">Export Bar Plot</button>
                    <button id="exportDot"  onclick="exportGODot()" classname="btn">Export Dot Plot</button>-->
                </div>
                <div id="commonInf">
                </div>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane active in" id="all" role="tabpanel" aria-labelledby="all-tab">
                        <div style="overflow: auto">
                        <table id="modal_table_all" class="table table-striped" tableindex="-1">
                            <tbody id="Gene_Info_Dialog_all">
                            </tbody>
                        </table>
                        </div>
                    </div>
                    <div class="tab-pane fade in" id="BP" role="tabpanel" aria-labelledby="BP-tab">
                        <div style="overflow: auto">
                        <table id="modal_table_BP" class="table table-striped" tableindex="-1">
                            <tbody id="Gene_Info_Dialog_BP">
                            </tbody>
                        </table>
                        </div>             
                    </div>
                    <div class="tab-pane fade in" id="CC" role="tabpanel" aria-labelledby="CC-tab">
                        <div style="overflow: auto">
                        <table id="modal_table_CC" class="table table-striped" tableindex="-1">
                            <tbody id="Gene_Info_Dialog_CC">
                            </tbody>
                        </table>
                        </div>               
                    </div>
                    <div class="tab-pane fade in" id="MF" role="tabpanel" aria-labelledby="MF-tab">
                        <div style="overflow: auto">
                        <table id="modal_table_MF" class="table table-striped" tableindex="-1">
                            <tbody id="Gene_Info_Dialog_MF">
                            </tbody>
                        </table>
                        </div>             
                    </div>
                   <!-- <div class="tab-pane fade in" id="Dot" role="tabpanel" aria-labelledby="Dot-tab">
                        <div style="overflow: auto">
                        <table id="modal_table_Dot" class="table table-striped" tableindex="-1">
                            <tbody id="Gene_Info_Dialog_Dot">
                            <div id="DotDiv"></div>
                            </tbody>
                        </table>
                        </div>             
                    </div>-->
                    <div class="tab-pane fade in" id="Bar" role="tabpanel" aria-labelledby="Bar-tab">
                        <div style="overflow: auto">
                        <table id="modal_table_Bar" class="table table-striped" tableindex="-1">
                            <tbody id="Gene_Info_Dialog_Bar">
                            <div id="controls">
                                <label for="scale-select">Select Color Scale: </label>
                                <select id="scale-select">
                                    <option value="RdBu">Red/Blue</option>
                                    <option value="YlGn">Yellow/Green</option>
                                    <option value="YlRd">Yellow/Red</option>
                                    <option value="BuRd">Blue/Red</option>
                                </select>
                            </div><span>
                            <div id="BarDiv" style="float:left; padding-right:20px"></div>
                            <div id="legendBar" style="float:left"></div></span>
                            </tbody>
                        </table>
                        </div>             
                    </div>
                    <div class="tab-pane fade in" id="BPBar" role="tabpanel" aria-labelledby="BPBar-tab">
                        <div style="overflow: auto">
                        <table id="modal_table_Bar" class="table table-striped" tableindex="-1">
                            <tbody id="Gene_Info_Dialog_Bar">
                            <div id="controls">
                                <label for="BPscale-select">Select Color Scale: </label>
                                <select id="BPscale-select">
                                    <option value="RdBu">Red/Blue</option>
                                    <option value="YlGn">Yellow/Green</option>
                                    <option value="YlRd">Yellow/Red</option>
                                    <option value="BuRd">Blue/Red</option>
                                </select>
                            </div>
                            <div id="BPBarDiv" style="float:left; padding-right:20px"></div>
                            <div id="BPlegendBar" style='float:left'></div>
                            </tbody>
                        </table>
                        </div>             
                    </div>
                    <div class="tab-pane fade in" id="CCBar" role="tabpanel" aria-labelledby="CCBar-tab">
                        <div style="overflow: auto">
                        <table id="modal_table_Bar" class="table table-striped" tableindex="-1">
                            <tbody id="Gene_Info_Dialog_Bar">
                            <div id="controls">
                                <label for="CCscale-select">Select Color Scale: </label>
                                <select id="CCscale-select">
                                    <option value="RdBu">Red/Blue</option>
                                    <option value="YlGn">Yellow/Green</option>
                                    <option value="YlRd">Yellow/Red</option>
                                    <option value="BuRd">Blue/Red</option>
                                </select>
                            </div>
                            <div id="CCBarDiv" style="float:left; padding-right:20px"></div>
                            <div id="CClegendBar" style="float:left"></div>
                            </tbody>
                        </table>
                        </div>             
                    </div>
                    <div class="tab-pane fade in" id="MFBar" role="tabpanel" aria-labelledby="MFBar-tab">
                        <div style="overflow: auto">
                        <table id="modal_table_Bar" class="table table-striped" tableindex="-1">
                            <tbody id="Gene_Info_Dialog_Bar">
                            <div id="controls">
                                <label for="MFscale-select">Select Color Scale: </label>
                                <select id="MFscale-select">
                                    <option value="RdBu">Red/Blue</option>
                                    <option value="YlGn">Yellow/Green</option>
                                    <option value="YlRd">Yellow/Red</option>
                                    <option value="BuRd">Blue/Red</option>
                                </select>
                            </div>
                            <div id="MFBarDiv" style="float:left; padding-right:20px"></div>
                            <div id="MFlegendBar" style="float:left"></div>
                            </tbody>
                        </table>
                        </div>             
                    </div>
              </div> <!-- tab-content -->
            </div> <!-- modal-body-->
            <div class="modal-footer">
                <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
            </div>     
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

<!-- KEGGModal -->
<div id="KEGGModal" class="modal fade" style="display: none;" aria-hidden="true">
    <div class="modal-dialog" style="width:85%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                <h3 id="myModalLabel">ClusterProfiler KEGG Pathway Enrichment Results</h3>
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item waves-effect waves-light">
                        <a class="nav-link active" id="KEGG-tab" data-toggle="tab" href="#KEGG" role="tab" aria-controls="KEGG" aria-selected="true">KEGG Pathways</a>
                    </li>
                    <li class="nav-item waves-effect waves-light">
                        <a class="nav-link" id="KEGGBar-tab" data-toggle="tab" href="#KEGGBar" role="tab" aria-controls="KEGGBar" aria-selected="false">KEGG Pathways Bar Plot (top 20)</a>
                    </li>
                </ul>
            </div>
            <div class="modal-body">
                <div id="commonIn">
                </div>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane active in" id="KEGG" role="tabpanel" aria-labelledby="KEGG-tab">
                        <div style="overflow: auto">
                        <table id="modal_table_KEGG" class="table table-striped" tableindex="-1">
                            <tbody id="KEGG_Info_Dialog">
                            </tbody>
                        </table>
                        </div>
                    </div> 
                    <div class="tab-pane fade in" id="KEGGBar" role="tabpanel" aria-labelledby="KEGGBar-tab">
                        <div style="overflow: auto">
                        <table id="modal_table_Bar" class="table table-striped" tableindex="-1">
                            <tbody id="KEGG_Info_Dialog_Bar">
                            <div id="controls">
                                <label for="KEGGscale-select">Select Color Scale: </label>
                                <select id="KEGGscale-select">
                                    <option value="RdBu">Red/Blue</option>
                                    <option value="YlGn">Yellow/Green</option>
                                    <option value="YlRd">Yellow/Red</option>
                                    <option value="BuRd">Blue/Red</option>
                                </select>
                            </div>
                            <div id="KEGGBarDiv" style="float:left; padding-right:20px"></div>
                            <div id="KEGGLegendBar" style="float:left"></div>
                            </tbody>
                        </table>
                        </div>             
                    </div>
                </div>   
            </div> <!-- modal-body-->
            <div class="modal-footer">
                <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
            </div>  <!-- modal footer -->   
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>


<script type="text/javascript">

    var defaultColors = <?php echo json_encode($default_colors); ?>;
    var mylinks =<?php echo $objlinks; ?>;  // get the data
    var filenum =<?php echo $filenum;?>;   //get how many files the customer uploaded
    var referenceDb = <?php echo json_encode($referenceDb);?>;
    var cutoff = <?php echo json_encode($cutoff);?>;
    var cutofftype = <?php echo json_encode($cutofftype);?>;
    var mydatacutoffed = <?php echo json_encode($mydata_cutoffed);?>;
    var genesval = <?php echo json_encode($genes_val);?>;
    var rawgenesval = <?php echo json_encode($raw_genes_val);?>;
    var idsWithSymbols = 'None';
    if (referenceDb !== "notselected" && species !== "notselected") {
        idsWithSymbols = <?php echo $idsWithSymbols; ?>;
    } 
    /*console.log("cutoff and cutoff type");
    console.log(cutoff);
    console.log(cutofftype);
    console.log(idsWithSymbols);
    console.log('herecutoff');
    console.log(mydatacutoffed);
    console.log("genesval");
    console.log(genesval);
    console.log("rawgenes");
    console.log(rawgenesval);*/
    var species =<?php echo json_encode($species);?>;
    var exp_name =<?php echo json_encode($expname);?>;   //get each name for experiment the customer uploaded
    var mycolor =<?php echo json_encode($mycolors); ?>;  //get array value of color

    if (mycolor.length == filenum) {
        mycolor[filenum] = '#FF0033';
        mycolor[filenum + 1] = '#0066cc';
        mycolor[filenum + 2] = '#E9F01D';
        mycolor[filenum + 3] = '#bfffc9';
        mycolor[filenum + 4] = '#ffffff';
    }
    var myshape =<?php echo json_encode($myshapes); ?>;
    if (myshape.length == 0) {
        myshape[0] = 1;
        myshape[1] = 1;
        myshape[2] = 1;
        myshape[3] = 1;
        myshape[4] = 2;
    }
    var mydata =    <?php echo json_encode($mydata); ?>;
    var redrawdata =<?php echo json_encode($redraw); ?>;
    /*var counterrors =<?php echo json_encode($count_errors); ?>;
    console.log("count errors");
    console.log(counterrors);
    console.log("redrawdata");
    console.log(redrawdata);
    console.log("mydata after");
    console.log(mydata);
    console.log("mylinks");
    console.log(mylinks);*/
    var UpDownDict = {};
    UpDownDict.Experiment = [0, 1, 2, 3, 4, 5, 6];
    //get links
    var links = [];
    var nodeMaxNum = 1000;
    var nodeMaxDefault = 5000;
    var customData = <?php echo json_encode($custom_data_passed_by_user); ?>;
    for (var n = 0; n < mylinks.length; n++) {
        if (mylinks[n].length > nodeMaxNum) {
            nodeMaxNum = mylinks[n].length;
            if (nodeMaxNum > nodeMaxDefault) {
                nodeMaxNum = nodeMaxDefault;
            }
        }
        UpDownDict.Experiment[n] = {};
        for (var i = 0; i < mylinks[n].length; i++) {
            var nodeId = mylinks[n][i].id.replace(/ /g, "_");
            nodeId = "id_" + nodeId
            //console.log(nodeId);

            links.push({
                'source': nodeId.replace(".", "-"),
                'target': exp_name[n].replace(/ /g, "_"),
                'experiment': exp_name[n],
                'value': +mylinks[n][i].value,                      //加号让string转换成数值 + change string to number
                'logfold': +mylinks[n][i].logfold
            })
            if (cutofftype == "Cutoff12") {
                UpDownDict.Experiment[n][mylinks[n][i].id.toLowerCase()] = mylinks[n][i].value;
            } else {
                UpDownDict.Experiment[n][mylinks[n][i].id.toLowerCase()] = mylinks[n][i].value;
                UpDownDict.Experiment[n][mylinks[n][i].id.toLowerCase()] = mylinks[n][i].logfold;
            }
            //UpDownDict.Experiment[n][mylinks[n][i].id.toLowerCase()] = mylinks[n][i].value;
            //UpDownDict.Experiment[n][mylinks[n][i].id.toLowerCase()] = mylinks[n][i].logfold;
        }

    }

    //get nodes
    var nodes = [];
    nodes.push({
        'id': links[0].source,
        'title': links[0].source,
        'group': links[0].value,
        'target': links[0].target,
        'redraw': function () {
            return redrawdata.indexOf(links[0].source) >= 0;
        }
    });
    /*console.log("updowndict");
    console.log(UpDownDict.Experiment[1]);
    console.log("links");
    console.log(links);*/
    var overlaps = {};
    var expGroup = {};
    expGroup[links[0].target] = 0;
    for (var i = 0; i < links.length; i++) {
        for (j = 0; j < nodes.length; j++) {
            if (links[i].source == nodes[j].id)              //判断所有的source是否存在wether source is exist
            {
                if (links[i].target in expGroup) {
                    expGroup[links[i].target]++;
                } else {
                    expGroup[links[i].target] = 1;
                }

                if (nodes[j].id in overlaps) {         // record overlap genes
                    overlaps[nodes[j].id] += "," + links[i].target;
                } else if (nodes[j].target != links[i].target) {
                    overlaps[nodes[j].id] = nodes[j].target + "," + links[i].target;
                }

                if (links[i].value == nodes[j].group) {    //if the node is already exist and the value is the same with the exist one then do nothing
                    break;
                } else if (links[i].value != nodes[j].group) {
                    nodes[j].group = 3;
                    break;
                }
            }                //if the node is already exist and the value is not the same in the new exp then change the value
        }
        if (j == nodes.length) {
            var nodeId = links[i].source.replace("id_", "").toLowerCase();
            nodeId = nodeId.replace("-", ".");
            nodes.push({
                'id': links[i].source,
                'title': links[i].source,
                'group': links[i].value,
                'target': links[i].target
            })
            if (redrawdata.indexOf(nodeId) >= 0) {
                nodes.push({
                    'redraw': true
                })
            }
            if (links[i].target in expGroup) expGroup[links[i].target]++;
            else expGroup[links[i].target] = 1;
        }
        for (var j = 0; j < nodes.length; j++) {
            if (links[i].target == nodes[j].id)                //判断所有的target是否存在 wether target node is exist
                break;
        }
        if (j == nodes.length) {
            nodes.push({
                'id': links[i].target,
                'title': links[i].experiment,
                'group': links[i].value
            })
        }
    }

    var overlapCount = {};
    for (var ids in overlaps) {
        if (overlaps[ids] in overlapCount) {
            overlapCount[overlaps[ids]].push(ids);
        } else {
            overlapCount[overlaps[ids]] = new Array();
            overlapCount[overlaps[ids]].push(ids);
        }
    }

    var grpGeneNum = {};						// count gene numbers
    for (var i = 0; i < nodes.length; i++) {
        if (nodes[i].id in exp_name) {
            continue;
        }
        if (overlaps[nodes[i].id] != null) {			// is overlapping node
            var grp = overlaps[nodes[i].id];
            if (!(grp in grpGeneNum)) {		// if this group is not created in grpGeneNum
                grpGeneNum[grp] = {};
                grpGeneNum[grp]['total'] = overlapCount[grp].length;
                grpGeneNum[grp]['up'] = 0;
                grpGeneNum[grp]['down'] = 0;
                grpGeneNum[grp]['updown'] = 0;
            }
            if (nodes[i].group == 1) {
                grpGeneNum[grp]['up']++;
            } else if (nodes[i].group == 2) {
                grpGeneNum[grp]['down']++;
            } else if (nodes[i].group == 3) {
                grpGeneNum[grp]['updown']++;
            }
        } else {									// not overlapping node
            var grp = nodes[i].target;
            if (!(grp in grpGeneNum)) {
                grpGeneNum[grp] = {};
                grpGeneNum[grp]['total'] = expGroup[grp];
                grpGeneNum[grp]['up'] = 0;
                grpGeneNum[grp]['down'] = 0;
                grpGeneNum[grp]['updown'] = 0;
                for (var grps in overlapCount) {
                    var group = grps.split(',');
                    for (var exp in group) {
                        if (group[exp] == nodes[i].target) {
                            grpGeneNum[grp]['total'] -= overlapCount[grps].length;
                        }
                    }
                }
            }
            if (nodes[i].group == 1) {
                grpGeneNum[grp]['up']++;
            } else if (nodes[i].group == 2) {
                grpGeneNum[grp]['down']++;
            } else if (nodes[i].group == 3) {
                grpGeneNum[grp]['updown']++;
            }
        }
    }

    var usrDatabase = {};
    if (customData) {
        mydataString = mydata.join(' ');
        usrDatabase = createUserDb(mydataString.split('\r\n'), usrDatabase);
    }

    function createUserDb(lines, givenData) {
        for (var i = 0; i < lines.length; i++) {
            var data = lines[i].split('\t');
            if (data[0] !== "-" && data[0] !== "") {
                var myId = data[0];
                if (myId in givenData) {
                    if (data[2] != null && data[2] !== "" && data[2] !== "-" && data[2] !== givenData[myId]['ncbi_id'])
                        givenData[myId]['ncbi_id'] += ' / ' + data[2];

                    if (data[3] != null && data[3] !== "" && data[3] !== "-" && data[3] !== givenData[myId]['uniprot_id'])
                        givenData[myId]['uniprot_id'] += ' / ' + data[3];

                    if (data[4] != null && data[4] !== "" && data[4] !== "-" && data[4] !== givenData[myId]['description'])
                        givenData[myId]['description'] += ' / ' + data[4];
                } else {
                    givenData[myId] = {};
                    givenData[myId]['ncbi_id'] = data[2];
                    givenData[myId]['uniprot_id'] = data[3];
                    givenData[myId]['description'] = data[4];
                    givenData[myId]['GO'] = [];
                }
            }
            var myGo = {};
            if (data[5] !== undefined)
                myGo['pathway'] = data[5];
            if (data[6] !== undefined)
                myGo['go_id'] = data[6];
            if (data[7] !== undefined)
                myGo['go_term'] = data[7];
            if (data[8] !== undefined)
                myGo['go_category'] = data[8];

            givenData[myId]['GO'].push(myGo);
        }
        return givenData;
    }

    //---------------------------------------------------------------------------------------
    //the contextMenu function
    // contextMenu references from website https://codepen.io/billdwhite/pen/VYGwaZ

    d3.contextMenu = function (menu, openCallback) {

        // create the div element that will hold the context menu
        d3.selectAll('.d3-context-menu').data([1])
            .enter()
            .append('div')
            .attr('class', 'd3-context-menu');

        // close menu
        d3.select('body').on('click.d3-context-menu', function () {
            d3.select('.d3-context-menu').style('display', 'none');
        });

        // this gets executed when a contextmenu event occurs
        return function (data, index) {
            var elm = this;

            d3.selectAll('.d3-context-menu').html('');
            var list = d3.selectAll('.d3-context-menu').append('ul');
            list.selectAll('li').data(menu).enter()
                .append('li')
                .html(function (d) {
                    if (species == 'notselected' || referenceDb == 'notselected') {
                        if (d.title.includes('label')) {
                            return d.title;
                        }
                    } else {
                        return d.title;
                    }
                })
                .on('click', function (d, i) {
                    d.action(elm, data, index);
                    d3.select('.d3-context-menu').style('display', 'none');
                });

            // the openCallback allows an action to fire before the menu is displayed
            // an example usage would be closing a tooltip
            if (openCallback) openCallback(data, index);

            // display context menu
            d3.select('.d3-context-menu')
                .style('position', 'absolute')
                .style('left', (d3.event.pageX + 2) + 'px')
                .style('top', (d3.event.pageY + 100) + 'px')
                .style('display', 'block');

            d3.event.preventDefault();    //prevent the system default menu
        };
    };

    //---------------------------------------------------------------------------------------
    //the contextMenu supporting functions

    function hideAllLabels() {
        for (var node_id = 0; node_id < nodes.length; node_id++) {
            if (exp_name.indexOf(nodes[node_id].title) === -1)
                hideLabel(nodes[node_id]);
        }
    }

    function showAllLabels() {
        for (var node_id = 0; node_id < nodes.length; node_id++) {
            if (nodes[node_id].target !== undefined)
                showLabel(nodes[node_id]);
        }
    }

    function showAllSymbols() {
        for (var node_id = 0; node_id < nodes.length; node_id++)
            showSymbol(nodes[node_id]);
    }


    function showSymbol(d) {
        var ident = d.id.toString().substring(3, d.id.toString().length); //id_AT12G34567...
        var symbol = getSymbol(ident);
        if (symbol != null)
            d3.select('#' + d.id).selectAll("text").style("display", "").text(symbol);
    }

    function showLabel(d) {
        var idChange = d.id.replace('id_', '');
        d3.select('#' + d.id).selectAll("text").style("display", "").text(idChange);
    }

    function hideLabel(d) {
        d3.select('#' + d.id).selectAll("text").style("display", "none");
    }

    function getSymbol(ident) {
        var symbol = null;
        if (usrDatabase[ident] !== undefined) symbol = usrDatabase[ident]['description'].split(';')[0];
        else {
            if (idsWithSymbols.length > 0 && idsWithSymbols !== "None") {
                var obj = idsWithSymbols.find(o => o.ID === ident);
                if (obj !== undefined)
                    symbol = obj.symbol;
            }
        }
        return symbol;
    }

    //---------------------------------------------------------------------------------------
    //the contextMenu options

    var menufull = [
        {
            title: 'Show label',
            action: function (elm, d, i) {
                showLabel(d);
            }
        },
        {
            title: 'Hide label',
            action: function (elm, d, i) {
                hideLabel(d);
            }
        },
        {
            title: 'Show all labels',
            action: function () {
                showAllLabels();
            }
        },
        {
            title: 'Hide all labels/symbols',
            action: function () {
                hideAllLabels();
            }
        },
        {
            title: 'Show gene symbol',
            action: function (elm, d, i) {
                showSymbol(d);
            }
        },
        {
            title: 'Hide gene symbol',
            action: function (elm, d, i) {
                hideLabel(d);
            }
        },
        {
            title: 'Show all symbols',
            action: function (elm, d, i) {
                showAllSymbols();
            }
        },
        {
            title: 'Gene detail',
            action: function (elm, d, i) {
                ShowNodeDetails(elm, d, i);
            }
        },
        {
            title: 'Gene group detail',
            action: function (elm, d, i) {
                ShowGroupDetails(elm, d, i);
            }
        }
    ]

    var menuless = [
        {
            title: 'Show label',
            action: function (elm, d, i) {
                showLabel(d);
            }
        },
        {
            title: 'Hide label',
            action: function (elm, d, i) {
                hideLabel(d);
            }
        },
        {
            title: 'Show all labels',
            action: function () {
                showAllLabels();
            }
        },
        {
            title: 'Hide all labels/symbols',
            action: function () {
                hideAllLabels();
            }
        }
    ]
    var menu = menufull;                  // set menu to choose between menu with or without gene titles
    if (species == 'notselected' || referenceDb == 'notselected'){      // Set menu to show menuless if species or db not selected
                menu = menuless;
    }

    function ShowNodeDetails(elm, d, i) {
        showNodeDetailsModal(d.id, d.target);
    }

    function showNodeDetailsModal(nodeId, nodeTarget) {
        var id = nodeId.replace('id_', '');
        id = id.replace("-", ".");
        nodeId = "id_" + id;
        $('.modal').modal('hide');

        if (overlaps[nodeId] != null)
            nodeTarget = overlaps[nodeId];
        else {
            for (var i = 0; i < nodes.length; i++)
                if (nodes[i].id == nodeId)
                    nodeTarget = nodes[i].target;
        }

        var tablecontainer = $('#Gene_Info_DialogN');
        var commonInfoContainer = $('#commonInfoN');
        tablecontainer.html('');
        commonInfoContainer.html('');
        $('#loadmore').remove();

        var th = $('<tr>');
        th.append('<th>Pathway</th>')
        th.append('<th>GO Information</th>')
        tablecontainer.append(th);

        var tabledata = '';
        if (!customData) {
            $("body").addClass('loading');
            $.post('mysql_query1.php', {GeneID: id, referenceDb: referenceDb, species: species}, function (result) {
                if (result === "0 results" || result === "[]")
                    tablecontainer.html('<h1>No Results</h1>')
                else {
                    var data = jQuery.parseJSON(result);                  //decode jason array

                    commonInfoContainer.append('<div class="row">' +
                        '<div class="col-sm-12 form-group"><label>ID:</label> ' + id +
                        '<button style="float:right" onclick="ShowGroupDetailsModal(\'' + nodeId + '\',\'' + nodeTarget +
                        '\')" classname="btn">Gene Group Details</button>' +
                        '</div><div class="col-sm-12 form-group"><label>UniprotD ID:</label> ' + data[0].uniprot_id +
                        '</div><div class="col-sm-12 form-group"><label>NCBI ID:</label> ' + data[0].ncbi_id +
                        '</div><div class="col-sm-12 form-group"><label>Description:</label> ' + data[0].description + '</div></div>');

                    
                        tabledata += '<tr>';
                    if (data[0].url == null) {
                        tabledata += '<td>' + "N/A" + '</td>';
                    }
                    else {
                        //Split url string by \n which is set in mysql_query1.php mysql select statement
                        var urllist = data[0].url.split("\n");
                        var pathdesclist = data[0].pathdesc.split("\n");
                        var urlstring = "<td>";
                        var length = urllist.length;

                        // create url string for each pathway and put all pathways in one cell
                        for (var j = 0; j < length; j++) {
                            urlstring += '<a href="';
                            urlstring += urllist[j]; 
                            urlstring += '" target="_blank">'; 
                            urlstring += pathdesclist[j]; 
                            urlstring += '</a>';
                            if (j == length - 1){
								urlstring += "";
							} else {
								urlstring += ' | ';
							}   
                        }
                        urlstring += "</td>";
                        tabledata += urlstring;
                    }
                    var cnt = 1;
					var gostring = "<td>";
					$.each(data,function(key,value){
						if(cnt ==1){
							cnt = 0;
						} 
                        if (value.go_id != null) {
                            var goidlist = value.go_id.split("\n");
						    console.log(goidlist);
                            var gotermlist = value.go_term.split("\n");
						    var gocatlist = value.go_category.split("\n");
                        
                            var length = goidlist.length;

                        // create url string for each pathway and put all pathways in one cell
                            for (var k = 0; k < length; k++) {
                                gostring += goidlist[k]; 
							    gostring += " | ";
                                gostring += gotermlist[k]; 
							    gostring += " | ";
                                gostring += gocatlist[k]; 
							    gostring += "<br>";
                            }}
                        });
						//console.log(gostring);
                        tabledata += gostring;
                        }
                        tablecontainer.append(tabledata);
                    }
                );
                
                $("body").removeClass('loading');
                }
           
            else {
            $("body").addClass('loading');
            if (!(id in usrDatabase)) {
                tablecontainer.html('<h1>No Results</h1>')
            } else {
                commonInfoContainer.append(
                    '<div class="row">' +
                    '<div class="col-sm-12 form-group"><label>ID:</label> ' + id +
                    '<button style="float:right" onclick="ShowGroupDetailsModal(\'' + nodeId + '\',\'' + nodeTarget
                    + '\')" classname="btn">Gene Group Details</button>' +
                    '</div><div class="col-sm-12 form-group"><label>UniprotD ID:</label> ' + usrDatabase[id]['uniprot_id'] +
                    '</div><div class="col-sm-12 form-group"><label>NCBI ID:</label> ' + usrDatabase[id]['ncbi_id'] +
                    '</div><div class="col-sm-12 form-group"><label>Description:</label> ' + usrDatabase[id]['description'] + '</div></div>');

                $.each(usrDatabase[id]['GO'], function (key, value) {
                    tabledata += '<tr>';
                    if (value['pathway'] !== undefined)
                        tabledata += '<td>' + value['pathway'] + '</td>';

                    if (value['go_id'] !== undefined)
                        tabledata += '<td>' + value['go_id'] + '</td>';

                    if (value['go_term'] !== undefined)
                        tabledata += '<td>' + value['go_term'] + '</td>';

                    if (value['go_category'] !== undefined)
                        tabledata += '<td>' + value['go_category'] + '</td>';
                    tabledata += '</tr>';
                });
                tablecontainer.append(tabledata);
            }
            $("body").removeClass('loading');
        }
        $('#nodeModal').modal('show');
    }

    function ShowGroupDetails(elm, d, i) {
        ShowGroupDetailsModal(d.id, d.target);
    }

    var groupgenes = [];			// global array for storing group gene list
    function ShowGroupDetailsModal(nodeId, nodeTarget) {
        var tablecontainer = $('#Gene_Info_Dialog');
        var commonInfoContainer = $('#commonInfo');
        tablecontainer.html('');
        commonInfoContainer.html('');
        $('#loadmore').remove();
        $('.modal').modal('hide');
        

        // export button
        commonInfoContainer.append('<button style="float:right; margin: 5px;" onclick="getGOenrichModal()" classname="btn btn-primary">GO enrichment</button>');
        commonInfoContainer.append('<button style="float:right; margin: 5px;" onclick="getKEGGModal()" classname="btn btn-primary">KEGG pathway enrichment</button> ');
            if (overlaps[nodeId] != null) {							// show overlaps between which experiments
            commonInfoContainer.append('<label id="commonInfoLabel">' + grpGeneNum[overlaps[nodeId]]['total'] +
                ' overlapping Genes between ' + overlaps[nodeId].replace(/,/g, ', ') + '</label> <button style="float:right; margin: 5px;" onclick="exportGeneDetail(\'' + nodeTarget + '\')" classname="btn btn-primary">Export Current Table</button>');
        } else {
            commonInfoContainer.append('<label id="commonInfoLabel">' + grpGeneNum[nodeTarget]['total'] +
                ' genes only in ' + nodeTarget + '</label> <button style="float:right; margin: 5px;" onclick="exportGeneDetail(\'' + nodeTarget + '\')" classname="btn btn-primary">Export Current Table</button>');
        }

        commonInfoContainer.append('<label id="geneCount" style="color:white" >0</label>')

        var th = $('<tr>');                       // write to table
        if (referenceDb == "Ensembl") {
            th.append('<th>Ensembl ID</th>')
            th.append('<th>UniprotD ID</th>')
            th.append('<th>NCBI ID</th>')
            th.append('<th>Description</th>')
            th.append('<th>Pathway</th>')
            th.append('<th>GO Information</th>')
        } else if (referenceDb == "NCBI") {
            th.append('<th>NCBI ID</th>')
            th.append('<th>UniprotD ID</th>')
            th.append('<th>Ensembl ID</th>')
            th.append('<th>Description</th>')
            th.append('<th>Pathway</th>')
            th.append('<th>GO Information</th>')
        } else {
            th.append('<th>UniprotD ID</th>')
            th.append('<th>NCBI ID</th>')
            th.append('<th>Ensembl ID</th>')
            th.append('<th>Description</th>')
            th.append('<th>Pathway</th>')
            th.append('<th>GO Information</th>')
        }
        tablecontainer.append(th);
        groupgenes = [];
        if (overlaps[nodeId] != null) {				// add gene to array
            for (var i in overlapCount[overlaps[nodeId]]) {					// overlapping genes
                var id = overlapCount[overlaps[nodeId]][i].replace('id_', '').replace("-", ".");
                groupgenes.push(id);
            }
        } else {
            for (var i = 0; i < nodes.length; i++) {				// no overlapping genes
                if (overlaps[nodes[i].id] != null) {
                    continue;
                } else if (nodes[i].target == nodeTarget) {
                    var id = nodes[i].id.replace('id_', '').replace("-", ".");
                    groupgenes.push(id);
                }
            }
        }
        groupgenes.sort();			// sort array

        loadmore(nodeTarget);				// load more function

        if (groupgenes.length > 3) {
            $('#myModal').find('.modal-body').append('<div id="loadmore" class="col-12-sm row" style="text-align: center;"><button class="btn btn-default loadmore" style="width: 200px;" onclick="loadmore()">Load More</button><button class="btn btn-default loadall" style="width: 200px;" onclick="loadall()">Load All</button></div>');

        }
        $("body").removeClass('loading');
        $('#myModal').modal('show');
    }

    function loadmore(nodeTarget) {						// load 15 more rows to group gene detail table
        $('#loadmore').hide();
        var start = Number(document.getElementById('geneCount').textContent);
        document.getElementById('geneCount').textContent = start + 3;
        var table = $('#Gene_Info_Dialog');
        for (var i = start; i < start + 3 && i < groupgenes.length; i++) {
            let id = groupgenes[i];
            if (customData) {
                if (id in usrDatabase) {
                    var tabledata = '<tr><td><a href="#" onclick="showNodeDetailsModal(\'' + id + '\',\'' + nodeTarget + '\')">' +
                        id + '</a></td><td>' + usrDatabase[id]['uniprot_id'] + '</td><td>' + usrDatabase[id]['ncbi_id'] +
                        '</td><td>' + usrDatabase[id]['description'] + '</td>';

                    var cnt = 1;
                    $.each(usrDatabase[id]['GO'], function (key, value) {
                        if (cnt == 1) {
                            cnt = 0;
                        } else {
                            tabledata += '<tr><td></td><td></td><td></td><td></td>'
                        }
                        tabledata += '<td>' + value['pathway'] + '</td>';
                        tabledata += '<td>' + value['go_id'] + '</td>';
                        tabledata += '<td>' + value['go_term'] + '</td>';
                        tabledata += '<td>' + value['go_category'] + '</td>';
                        tabledata += '</tr>';
                    });
                    table.append(tabledata);
                } else {
                    var tabledata = '<tr><td>' + id + '</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
                    table.append(tabledata);
                } 
                
            } else {
                $.post('mysql_query1.php', {GeneID: id, referenceDb: referenceDb, species: species}, function (result) {
			if (result == "0 results" || result == "[]") {	//|| result === "[]" -- took this out because it causes duplicates bug
                    var tabledata = '<tr><td>' + id + '</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
			        table.append(tabledata);
			} else {
				var data = jQuery.parseJSON(result);
				//console.log(data)
                if (referenceDb == "Ensembl") {
                    var tabledata = '<tr><td><a href="#" onclick="showNodeDetailsModal(\'' + data[0].ID + '\',\'' + nodeTarget + '\')">' +
			                data[0].ID + '</a></td><td>' + data[0].uniprot_id + '</td><td>' + data[0].ncbi_id +
                            '</td><td>' + data[0].description + '</td>' ;
                } else if (referenceDb == "NCBI") {
                    var tabledata = '<tr><td><a href="#" onclick="showNodeDetailsModal(\'' + data[0].ncbi_id + '\',\'' + nodeTarget + '\')">' +
			                data[0].ncbi_id + '</a></td><td>' + data[0].uniprot_id + '</td><td>' + data[0].ID +
                            '</td><td>' + data[0].description + '</td>' ;
                } else {
                    var tabledata = '<tr><td><a href="#" onclick="showNodeDetailsModal(\'' + data[0].uniprot_id + '\',\'' + nodeTarget + '\')">' +
			                data[0].uniprot_id + '</a></td><td>' + data[0].ncbi_id + '</td><td>' + data[0].ID +
                            '</td><td>' + data[0].description + '</td>' ;
                }
                
                if (data[0].url != null) {
                    if (data[0].url.trim() == '-') {
                        tabledata += '<td>' + data[0].pathway + '</td>';
                    }
                    else {
                        //Split url string by \n which is set in mysql_query1.php mysql select statement
                        var urllist = data[0].url.split("\n");
                        var pathdesclist = data[0].pathdesc.split("\n");
                        var urlstring = "<td>";
                        var length = urllist.length;

                        // create url string for each pathway and put all pathways in one cell
                        for (var j = 0; j < length; j++) {
                            urlstring += '<a href="';
                            urlstring += urllist[j]; 
                            urlstring += '" target="_blank">'; 
                            urlstring += pathdesclist[j]; 
                            urlstring += '</a>';
                            if (j == length - 1){
								urlstring += "";
							} else {
								urlstring += ' | ';
							}   
                        }
                        urlstring += "</td>";
                        tabledata += urlstring;
                    }
                }
                else {
                    tabledata += '<td>' + 'N/A' + '</td>';
                }

                var cnt = 1;
					var gostring = "<td>";
					$.each(data,function(key,value){
						if(cnt ==1){
							cnt = 0;
						} 
                        if (value.go_id != null) {
                            var goidlist = value.go_id.split("\n");
						    //console.log(goidlist);
                            var gotermlist = value.go_term.split("\n");
						    var gocatlist = value.go_category.split("\n");
                        
                            var length = goidlist.length;

                        // create url string for each pathway and put all pathways in one cell
                            for (var k = 0; k < length; k++) {
                                gostring += goidlist[k]; 
							    gostring += " | ";
                                gostring += gotermlist[k]; 
							    gostring += " | ";
                                gostring += gocatlist[k]; 
							    gostring += "<br>";
                            }}
                        });
						//console.log(gostring);
                        tabledata += gostring;
                        }
						
						//console.log(tabledata);
                        table.append(tabledata);
                    }
                );
            }
        }

        if (start <= groupgenes.length - 3) {
            $('#loadmore').show();
        }
        
    }

    function loadall(nodeTarget) {
        $('#loadmore').hide();
        var start = Number(document.getElementById('geneCount').textContent);
        while (start < groupgenes.length) {
            loadmore(nodeTarget);
            start += 3;
        }
    }

    function exportGeneDetail(nodeTarget) {			// download gene group detail list
        var rslt = '';
        var table = document.getElementById('modal_table');
        for (var i = 0, row; row = table.rows[i]; i++) {
            var gostr = row.cells[5].innerText;
			var trimstr = gostr.replace(/[\r\n]/gm, ' ');
			rslt += row.cells[0].innerText + '\t' + row.cells[1].innerText + '\t' + row.cells[2].innerText + '\t' + row.cells[3].innerText + '\t' + 
				row.cells[4].innerText + '\t' + trimstr + '\n'; //row.cells[6].innerText + '\t' + row.cells[7].innerText + '\t' + '\n';
        }

        var blob = new Blob([rslt], {type: 'text/plain'});
        if (window.navigator.msSaveOrOpenBlob) { // IE10+
            window.navigator.msSaveOrOpenBlob(blob, 'Gene_Group.txt');
        } else {
            var a = document.createElement("a");
            url = URL.createObjectURL(blob);
            a.href = url;
            a.download = 'Gene_Group.txt';
            document.body.appendChild(a);
            a.click();
            setTimeout(function () {
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }, 0);
        }
    }
////////////////////////////////////////////////////************WORKSPACE MARKER***************************************** */
    function getGOenrichModal() {			// download gene group detail list
        $('.modal').modal('hide');
        let tablecontainerall = $('#Gene_Info_Dialog_all');
        let tablecontainerBP = $('#Gene_Info_Dialog_BP');
        var tablecontainerCC = $('#Gene_Info_Dialog_CC');
        var tablecontainerMF = $('#Gene_Info_Dialog_MF');
        var tablecontainerDot = $('#Gene_Info_Dialog_Dot');
        var commonInfoContainer = $('#commonInf');
        commonInfoContainer.html('');
        //commonInfoContainer.append('<div class="col-md-12" ><label id="commonInfoLabe"></label> <button id="export" style="float:right" onclick="exportGOenrich()" classname="btn">Export Current GO Table</div>');
        commonInfoContainer.append('<label id="geneCoun" style="color:white" >0</label>')         // This line is integral for some reason
        $('#loadmore').remove();

        var th = '<tr>';
        th += '<th>Ontology</th>'
        th += '<th>ID</th>'
        th += '<th>Description</th>'
        th += '<th>Gene Ratio</th>'
        th += '<th>Bg Ratio</th>'
        th += '<th>P Value</th>'
        th += '<th>Adjusted P Value</th>'
        th += '<th>Q Value</th>'
        th += '<th>Entrez Gene ID</th>'
        th += '<th>Count</th>'
        th += '</tr>'
        tablecontainerall.append(th);
        tablecontainerBP.append(th);
        tablecontainerCC.append(th);
        tablecontainerMF.append(th);

        var tabledata = ''; 
        
        if (!customData) {
            $("body").addClass('loading');
            // Timer start for gene set size test           
            //var startTime = performance.now();
            $.post('cluster.php', {mdata: mydata, referenceDb: referenceDb, species: species}, function (result) { 
                
                    if (result === "Not Available" || result === "[]" || result == "No Results[]" || result == "No Results") {
                        tablecontainerall.html('<h1>No Results</h1>');
                        tablecontainerBP.html('<h1>No Results</h1>');
                        tablecontainerCC.html('<h1>No Results</h1>');
                        tablecontainerMF.html('<h1>No Results</h1>');
                        //console.log(result);
                        $("body").removeClass('loading');
                        $('#enrichModal').modal('show');
                        var endTime = performance.now();
                        //console.log(`Call to doSomething took ${endTime - startTime} milliseconds`);
                    } else {
                        // get arr of data from clusterProfiler
                        //console.log(result);
                        var data = jQuery.parseJSON(result);        //decode jason array
                        //console.log(data);
                        var tabledata = '';
                        var tabledataBP = '';
                        var tabledataCC = '';
                        var tabledataMF = '';
                        var tabledataBar = '';
                        var tabledataDot = '';
                        

                        $.each(data, function (key, value) {
                            tabledata += '<tr>';
                            //tabledata += '<td><a href="#" class="keggID" id="' + value.ID + '">' + value.ID + '</a></td>';
                            tabledata += '<td>' + value.ONTOLOGY + '</td>';
                            tabledata += '<td>' + value.ID + '</td>';
                            tabledata += '<td>' + value.Description + '</td>';
                            tabledata += '<td>' + value.GeneRatio + '</td>';
                            tabledata += '<td>' + value.BgRatio + '</td>';
                            tabledata += '<td>' + value.pvalue + '</td>';
                            tabledata += '<td>' + value.p_adjust + '</td>';
                            tabledata += '<td>' + value.qvalue + '</td>';
                            tabledata += '<td>' + value.geneID + '</td>';
                            tabledata += '<td>' + value.Count + '</td>';
                            tabledata += '</tr>';
                            if (value.ONTOLOGY == "BP") {
                                tabledataBP += '<tr>';
                                tabledataBP += '<td>' + value.ONTOLOGY + '</td>';
                                tabledataBP += '<td>' + value.ID + '</td>';
                                tabledataBP += '<td>' + value.Description + '</td>';
                                tabledataBP += '<td>' + value.GeneRatio + '</td>';
                                tabledataBP += '<td>' + value.BgRatio + '</td>';
                                tabledataBP += '<td>' + value.pvalue + '</td>';
                                tabledataBP += '<td>' + value.p_adjust + '</td>';
                                tabledataBP += '<td>' + value.qvalue + '</td>';
                                tabledataBP += '<td>' + value.geneID + '</td>';
                                tabledataBP += '<td>' + value.Count + '</td>';
                                tabledataBP += '</tr>';                                
                            } else if (value.ONTOLOGY == "CC") {
                                tabledataCC += '<tr>';
                                tabledataCC += '<td>' + value.ONTOLOGY + '</td>';
                                tabledataCC += '<td>' + value.ID + '</td>';
                                tabledataCC += '<td>' + value.Description + '</td>';
                                tabledataCC += '<td>' + value.GeneRatio + '</td>';
                                tabledataCC += '<td>' + value.BgRatio + '</td>';
                                tabledataCC += '<td>' + value.pvalue + '</td>';
                                tabledataCC += '<td>' + value.p_adjust + '</td>';
                                tabledataCC += '<td>' + value.qvalue + '</td>';
                                tabledataCC += '<td>' + value.geneID + '</td>';
                                tabledataCC += '<td>' + value.Count + '</td>';
                                tabledataCC += '</tr>';
                            } else {
                                tabledataMF += '<tr>';
                                tabledataMF += '<td>' + value.ONTOLOGY + '</td>';
                                tabledataMF += '<td>' + value.ID + '</td>';
                                tabledataMF += '<td>' + value.Description + '</td>';
                                tabledataMF += '<td>' + value.GeneRatio + '</td>';
                                tabledataMF += '<td>' + value.BgRatio + '</td>';
                                tabledataMF += '<td>' + value.pvalue + '</td>';
                                tabledataMF += '<td>' + value.p_adjust + '</td>';
                                tabledataMF += '<td>' + value.qvalue + '</td>';
                                tabledataMF += '<td>' + value.geneID + '</td>';
                                tabledataMF += '<td>' + value.Count + '</td>';
                                tabledataMF += '</tr>';
                            }
                    });
                    tablecontainerall.append(tabledata);
                    if (tabledata.includes("BP") == true) {
                        tablecontainerBP.append(tabledataBP);
                    } 
                    if (tabledata.includes("CC") == true) {
                        tablecontainerCC.append(tabledataCC);
                    } 
                    if (tabledata.includes("MF") == true) {
                        tablecontainerMF.append(tabledataMF);
                    }

                    makeD3BarPlot(data, "");
                    //makeD3DotPlot(data);
                    
                    $("body").removeClass('loading');
                    $('#enrichModal').modal('show');
                    //var endTime = performance.now();
                    //console.log(`Call to doSomething took ${endTime - startTime} milliseconds`);
                    // attach event listener to control
                    d3.select('#scale-select').on('change', function() {
                        var val = d3.select(this).node().value;
                        makeD3BarPlot(data, val);
                    });
                    d3.select('#BPscale-select').on('change', function() {
                        var val = d3.select(this).node().value;
                        makeD3BarPlot(data, val);
                    });
                    d3.select('#CCscale-select').on('change', function() {
                        var val = d3.select(this).node().value;
                        makeD3BarPlot(data, val);
                    });
                    d3.select('#MFscale-select').on('change', function() {
                        var val = d3.select(this).node().value;
                        makeD3BarPlot(data, val);
                    });
                    }
                });
                
                }
                
            };

    function exportGOenrich(){          // export ClusterProfiler output in table format
        var rslt = '';
        var table = document.getElementById('modal_table_all');
        for (var i = 0, row; row = table.rows[i]; i++) {
			rslt += row.cells[0].innerText + '\t' + row.cells[1].innerText + '\t' + row.cells[2].innerText + '\t' + row.cells[3].innerText + '\t' + 
				row.cells[4].innerText + '\t' + row.cells[5].innerText + '\t' + row.cells[6].innerText + '\t' + row.cells[7].innerText + '\t' + row.cells[8].innerText + '\t' + row.cells[9].innerText + '\n'; //row.cells[6].innerText + '\t' + row.cells[7].innerText + '\t' + '\n';
        }

        var blob = new Blob([rslt], {type: 'text/plain'});
        if (window.navigator.msSaveOrOpenBlob) { // IE10+
            window.navigator.msSaveOrOpenBlob(blob, 'GO_enrich.txt');
        } else {
            var a = document.createElement("a");
            url = URL.createObjectURL(blob);
            a.href = url;
            a.download = 'GO_enrich.txt';
            document.body.appendChild(a);
            a.click();
            setTimeout(function () {
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }, 0);
        }
    }

    function getKEGGModal() {			// get KEGG pathway list from clusterProfiler and display in a modal
        $('.modal').modal('hide');
        $("#modal_table_KEGG tr").remove();
        let tablecontainerall = $('#KEGG_Info_Dialog');
        var commonInfoContainer = $('#commonIn');
        commonInfoContainer.html('');
        commonInfoContainer.append('<div class="col-md-12" ><label id="commonInfoLabe"></label> <button id="exportKEGG" style="float:right" onclick="exportKEGGTable()" classname="btn">Export KEGG Table</div>');
        commonInfoContainer.append('<label id="geneCoun" style="color:white" >0</label>')         // This line is integral for some reason
        
        $('#loadmore').remove();
        
        // Make column labels
        var th = '<tr>';
        th += '<th>KEGG Pathway ID</th>'
        th += '<th>Description</th>'
        th += '<th>Gene Ratio</th>'
        th += '<th>Bg Ratio</th>'
        th += '<th>P Value</th>'
        th += '<th>Adjusted P Value</th>'
        th += '<th>Q Value</th>'
        th += '<th>Gene ID</th>'
        th += '<th>Count</th>'
        th += '</tr>'
        tablecontainerall.append(th);

        var tabledata = ''; 
        
        if (!customData) {
            $("body").addClass('loading');

            // Timer start for gene set size test           
            //var startTime = performance.now();
            $.post('clusterKEGG.php', {mdata: mydata, referenceDb: referenceDb, species: species}, function (result) { 
                    
                    if (result === "Not Available" || result === "[]" || result == "No Results[]" || result === "No Results") {
                        tablecontainerall.html('<h1>No Results</h1>');
                        $("body").removeClass('loading');
                        $('#KEGGModal').modal('show');
                        document.getElementById('exportKEGG').style.visibility='hidden';
                        //var endTime = performance.now();
                        //console.log(`Call to doSomething took ${endTime - startTime} milliseconds`);
                    } else if (result == "osi") {
                        tablecontainerall.html('<h1>Oryza sativa indica group not currently supported for KEGG pathway enrichment</h1>');
                        //console.log(result);
                        $("body").removeClass('loading');
                        $('#KEGGModal').modal('show');
                        document.getElementById('exportKEGG').style.visibility='hidden';
                    } else {
                        // get arr of data from clusterProfiler
                        var data = jQuery.parseJSON(result);        //decode jason array
                        var tabledata = '';

                        $.each(data, function (key, value) {
                            tabledata += '<tr>';
                            
                            tabledata += '<td>' + '<a href="' + "https://www.kegg.jp/pathway/" + value.ID + '" target="_blank">' + value.ID + '</a></td>'
                            //tabledata += '<td>' + value.ID + '</td>';
                            tabledata += '<td>' + value.Description + '</td>';
                            tabledata += '<td>' + value.GeneRatio + '</td>';
                            tabledata += '<td>' + value.BgRatio + '</td>';
                            tabledata += '<td>' + value.pvalue + '</td>';
                            tabledata += '<td>' + value.p_adjust + '</td>';
                            tabledata += '<td>' + value.qvalue + '</td>';
                            tabledata += '<td>' + value.geneID + '</td>';
                            tabledata += '<td>' + value.Count + '</td>';
                            tabledata += '</tr>';
                        });
                    tablecontainerall.append(tabledata);
                    makeD3BarPlotKEGG(data, "");

                    $("body").removeClass('loading');
                    $('#KEGGModal').modal('show');

                    d3.select('#KEGGscale-select').on('change', function() {
                        var val = d3.select(this).node().value;
                        makeD3BarPlotKEGG(data, val);
                    });
                    //var endTime = performance.now();
                    //console.log(`Call to doSomething took ${endTime - startTime} milliseconds`);
                    }
                });   
                }          
            };
    
    function exportKEGGTable() {
        var rslt = '';
        var table = document.getElementById('modal_table_KEGG');
        for (var i = 0, row; row = table.rows[i]; i++) {
			rslt += row.cells[0].innerText + '\t' + row.cells[1].innerText + '\t' + row.cells[2].innerText + '\t' + row.cells[3].innerText + '\t' + 
				row.cells[4].innerText + '\t' + row.cells[5].innerText + '\t' + row.cells[6].innerText + '\t' + row.cells[7].innerText + '\t' + row.cells[8].innerText + '\n' //row.cells[6].innerText + '\t' + row.cells[7].innerText + '\t' + '\n';
        }

        var blob = new Blob([rslt], {type: 'text/plain'});
        if (window.navigator.msSaveOrOpenBlob) { // IE10+
            window.navigator.msSaveOrOpenBlob(blob, 'KEGG_enrich.txt');
        } else {
            var a = document.createElement("a");
            url = URL.createObjectURL(blob);
            a.href = url;
            a.download = 'KEGG_enrich.txt';
            document.body.appendChild(a);
            a.click();
            setTimeout(function () {
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }, 0);
        }
    }

    function makeD3BarPlot(data, colorscale) {


        var dataArray = new Array();
        dataArray[0] = data
        dataArray[1] = data.filter(function(d) { return d.ONTOLOGY == "BP"; });
        dataArray[2] = data.filter(function(d) { return d.ONTOLOGY == "CC"; });
        dataArray[3] = data.filter(function(d) { return d.ONTOLOGY == "MF"; });
        var ontArray = new Array(); 
        ontArray[0] = "";
        ontArray[1] = "BP";
        ontArray[2] = "CC";
        ontArray[3] = "MF";
        for (let i in dataArray) {
            var ontologyData = dataArray[i];
            var ontology = ontArray[i]; 
            if (ontologyData.length === 0) {
                continue;
            }

            var ontologyDivStr = '#' + ontology + 'BarDiv';
            var ontologyBarStr = '#' + ontology + 'legendBar';
            // clear any existing d3 objects
            d3.selectAll(ontologyDivStr + ' svg').remove();
            //svg.selectAll("*").remove();

            var margin = {top: 20, right: 2, bottom: 100, left: 500},
            width = 700 - margin.right,
            height = 700 - margin.top - margin.bottom;

            // Sort data for each ontology and sort by p_adjust in ascending order
            var sortedData = ontologyData.sort(function(x, y){
            return d3.ascending(x.p_adjust, y.p_adjust);
            });
            //Filter for top 20 
            sortedData = sortedData.filter(function(d,i){
                return i < 20;
            });

            var maxVal = d3.max(sortedData, function(d) { return d.Count; } );
            var maxAdj = d3.max(sortedData, function(d) { return d.p_adjust; } );

            // if/else for each color scheme
            if (colorscale == "RdBu") {
            var legendscale = d3.scaleLinear()
                .domain([0,maxAdj])
                .range(['red', 'blue']);
            } else if (colorscale == "YlGn") {
            var legendscale = d3.scaleLinear()
                .domain([0,maxAdj])
                .range(['yellow', 'green']);
            } else if (colorscale == "YlRd") {
            var legendscale = d3.scaleLinear()
                .domain([0,maxAdj])
                .range(['yellow', 'red']);
            } else if (colorscale == "BuRd"){
            var legendscale = d3.scaleLinear()
                .domain([0,maxAdj])
                .range(['blue', 'red']);
            } else {
                var legendscale = d3.scaleLinear()
                .domain([0,maxAdj])
                .range(['red', 'blue']);
            }

            // append the svg object to the body of the tab for each tab
            var svg = d3.select(ontologyDivStr)
                .append("svg")
                .attr("width", width + margin.left + margin.right)
                .attr("height", height + margin.top + margin.bottom)
                .append("g")
                .attr("transform",
                "translate(" + margin.left + "," + margin.top + ")");

            // Initialize the X axis
            var x = d3.scaleLinear()
                .domain([0, maxVal])
                .range([ 0, width]);
            svg.append("g")
                .attr("transform", "translate(0," + height + ")")
                .call(d3.axisBottom(x).tickSizeOuter(0))//.tickFormat(function(d){if(d)return d}))
                .selectAll("text")
                .attr("transform", "translate(-10,0)rotate(-45)")
                .style("text-anchor", "end");
            // X Axis label; Gene Count
            svg.append("text")
                .attr("x", width/2)
                .attr("y", height + 40)
                .style("text-anchor", "middle")
                .text("Gene Count");

            // Initialize the Y axis
            var y = d3.scaleBand()
                .range([ 0, height ])
                .domain(sortedData.map(function(d) { return d.Description; }))
                .padding(.1);
            svg.append("g")
                .style("font", "14px times")
                .call(d3.axisLeft(y).tickSizeOuter(0));//.tickFormat(function(d){if(d)return d}))//.tickSizeInner(0))
            

            // Initialize the legend
            svg.selectAll("myRect")
                .data(sortedData)
                .enter()
                .append("rect")
                .attr("x", x(0) )
                .attr("y", function(d) { return y(d.Description); })
                .attr("width", function(d) { return x(d.Count); })
                .attr("height", y.bandwidth() )
                .attr("fill",  function(d){ return legendscale(d.p_adjust); })

                var legendheight = 200,
                legendwidth = 95,
                margin1 = {top: 50, right: 60, bottom: 10, left: 2};

                var canvas = d3.select(ontologyBarStr)
                    .style("height", legendheight + "px")
                    .style("width", legendwidth + "px")
                    .style("position", "relative")
                    .append("canvas")
                    .attr("height", legendheight - margin1.top - margin1.bottom)
                    .attr("width", 1)
                    .style("height", (legendheight - margin1.top - margin1.bottom) + "px")
                    .style("width", (legendwidth - margin1.left - margin1.right) + "px")
                    .style("border", "1px solid #000")
                    .style("position", "absolute")
                    .style("top", (margin1.top) + "px")
                    .style("left", (margin1.left) + "px")
                    .node();

                var ctx = canvas.getContext("2d");

                var legendscale1 = d3.scaleLinear()
                    .range([1, legendheight - margin1.top - margin1.bottom])
                    .domain(legendscale.domain());

                // image data hackery based on http://bl.ocks.org/mbostock/048d21cf747371b11884f75ad896e5a5
                var image = ctx.createImageData(1, legendheight);
                d3.range(legendheight).forEach(function(i) {
                var c = d3.rgb(legendscale(legendscale1.invert(i)));
                    image.data[4*i] = c.r;
                    image.data[4*i + 1] = c.g;
                    image.data[4*i + 2] = c.b;
                    image.data[4*i + 3] = 255;
                });
                ctx.putImageData(image, 0, 0);

                // set legend axis ticks
                var legendaxis = d3.axisRight()
                .scale(legendscale1)
                .tickSize(6)
                .ticks(4, "g")
                .tickSizeOuter(0)
                .tickSizeInner(0);

            var svg = d3.select(ontologyBarStr)
                .append("svg")
                .attr("height", (legendheight) + "px")
                .attr("width", (legendwidth) + "px")
                .style("position", "absolute")
                .style("left", "0px")
                .style("top", "0px")

            // set legend title
            svg.append("text")
                .attr("x", legendwidth/2 - 20)
                .attr("y", 35)
                .style("text-anchor", "middle")
                .text("p_adj");

            svg
                .append("g")
                .attr("class", "axis")
                .attr("transform", "translate(" + (legendwidth - margin1.left - margin1.right + 3) + "," + (margin1.top) + ")")
                .call(legendaxis)
    }
}

function makeD3BarPlotKEGG(data, colorscale) {


    var KEGGDivStr = '#KEGGBarDiv';
    var KEGGBarStr = '#KEGGLegendBar';
    // clear any existing d3 objects
    d3.selectAll(KEGGDivStr + ' svg').remove();
    //svg.selectAll("*").remove();

    var margin = {top: 20, right: 2, bottom: 100, left: 500},
    width = 700 - margin.right,
    height = 700 - margin.top - margin.bottom;

    // Sort data for each ontology and sort by p_adjust in ascending order
    var sortedData = data.sort(function(x, y){
    return d3.ascending(x.p_adjust, y.p_adjust);
    });
    //Filter for top 20 
    sortedData = sortedData.filter(function(d,i){
        return i < 20;
    });

    var maxVal = d3.max(sortedData, function(d) { return d.Count; } );
    var maxAdj = d3.max(sortedData, function(d) { return d.p_adjust; } );

    // if/else for each color scheme
    if (colorscale == "RdBu") {
    var legendscale = d3.scaleLinear()
        .domain([0,maxAdj])
        .range(['red', 'blue']);
    } else if (colorscale == "YlGn") {
    var legendscale = d3.scaleLinear()
        .domain([0,maxAdj])
        .range(['yellow', 'green']);
    } else if (colorscale == "YlRd") {
    var legendscale = d3.scaleLinear()
        .domain([0,maxAdj])
        .range(['yellow', 'red']);
    } else if (colorscale == "BuRd"){
    var legendscale = d3.scaleLinear()
        .domain([0,maxAdj])
        .range(['blue', 'red']);
    } else {
        var legendscale = d3.scaleLinear()
        .domain([0,maxAdj])
        .range(['red', 'blue']);
    }

    // append the svg object to the body of the tab for each tab
    var svg = d3.select(KEGGDivStr)
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform",
        "translate(" + margin.left + "," + margin.top + ")");

    // Initialize the X axis
    var x = d3.scaleLinear()
        .domain([0, maxVal])
        .range([ 0, width]);
    svg.append("g")
        .attr("transform", "translate(0," + height + ")")
        .call(d3.axisBottom(x).tickSizeOuter(0))//.tickFormat(function(d){if(d)return d}))
        .selectAll("text")
        .attr("transform", "translate(-10,0)rotate(-45)")
        .style("text-anchor", "end");
    // X Axis label; Gene Count
    svg.append("text")
        .attr("x", width/2)
        .attr("y", height + 40)
        .style("text-anchor", "middle")
        .text("Gene Count");

    // Initialize the Y axis
    var y = d3.scaleBand()
        .range([ 0, height ])
        .domain(sortedData.map(function(d) { return d.Description; }))
        .padding(.1);
    svg.append("g")
        .style("font", "14px times")
        .call(d3.axisLeft(y).tickSizeOuter(0));//.tickFormat(function(d){if(d)return d}))//.tickSizeInner(0))
    

    // Initialize the legend
    svg.selectAll("myRect")
        .data(sortedData)
        .enter()
        .append("rect")
        .attr("x", x(0) )
        .attr("y", function(d) { return y(d.Description); })
        .attr("width", function(d) { return x(d.Count); })
        .attr("height", y.bandwidth() )
        .attr("fill",  function(d){ return legendscale(d.p_adjust); })

        var legendheight = 200,
        legendwidth = 95,
        margin1 = {top: 50, right: 60, bottom: 10, left: 2};

        var canvas = d3.select(KEGGBarStr)
            .style("height", legendheight + "px")
            .style("width", legendwidth + "px")
            .style("position", "relative")
            .append("canvas")
            .attr("height", legendheight - margin1.top - margin1.bottom)
            .attr("width", 1)
            .style("height", (legendheight - margin1.top - margin1.bottom) + "px")
            .style("width", (legendwidth - margin1.left - margin1.right) + "px")
            .style("border", "1px solid #000")
            .style("position", "absolute")
            .style("top", (margin1.top) + "px")
            .style("left", (margin1.left) + "px")
            .node();

        var ctx = canvas.getContext("2d");

        var legendscale1 = d3.scaleLinear()
            .range([1, legendheight - margin1.top - margin1.bottom])
            .domain(legendscale.domain());

        // image data hackery based on http://bl.ocks.org/mbostock/048d21cf747371b11884f75ad896e5a5
        var image = ctx.createImageData(1, legendheight);
        d3.range(legendheight).forEach(function(i) {
        var c = d3.rgb(legendscale(legendscale1.invert(i)));
            image.data[4*i] = c.r;
            image.data[4*i + 1] = c.g;
            image.data[4*i + 2] = c.b;
            image.data[4*i + 3] = 255;
        });
        ctx.putImageData(image, 0, 0);

        // set legend axis ticks
        var legendaxis = d3.axisRight()
        .scale(legendscale1)
        .tickSize(6)
        .ticks(4, "g")
        .tickSizeOuter(0)
        .tickSizeInner(0);

    var svg = d3.select(KEGGBarStr)
        .append("svg")
        .attr("height", (legendheight) + "px")
        .attr("width", (legendwidth) + "px")
        .style("position", "absolute")
        .style("left", "0px")
        .style("top", "0px")

    // set legend title
    svg.append("text")
        .attr("x", legendwidth/2 - 20)
        .attr("y", 35)
        .style("text-anchor", "middle")
        .text("p_adj");

    svg
        .append("g")
        .attr("class", "axis")
        .attr("transform", "translate(" + (legendwidth - margin1.left - margin1.right + 3) + "," + (margin1.top) + ")")
        .call(legendaxis)

    }

    


    /*function exportGOBar() {

    }*/

    /*function makeD3DotPlot(data) {
        src="https://d3js.org/d3-color.v1.min.js"
        src="https://d3js.org/d3-interpolate.v1.min.js"
        src="https://d3js.org/d3-scale-chromatic.v1.min.js"
        // set the dimensions and margins of the graph
        var margin = {top: 20, right: 30, bottom: 100, left: 500},
            width = 700 - margin.right,
            height = 700 - margin.top - margin.bottom;

        // append the svg object to the body of the page
        var svg = d3.select("#DotDiv")
            .append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g")
            .attr("transform",
            "translate(" + margin.left + "," + margin.top + ")");

        // Sort data for gene ratio
        var sortedData = data.sort(function(x, y){
            return d3.descending(x.GeneRatio, y.GeneRatio);
            })
        //Filter for top 20 gene ratio
        sortedData = sortedData.filter(function(d,i){
            return i < 20;
        });

        var maxVal = d3.max(sortedData, function(d) { return d.GeneRatio; } );
        var maxAdj = d3.max(sortedData, function(d) { return d.p_adjust; } );


  // Add X axis
  /*var x = d3.scaleLinear()
    .domain([0, 10000])
    .range([ 0, width ]);
  svg.append("g")
    .attr("transform", "translate(0," + height + ")")
    .call(d3.axisBottom(x));*/

        // add Y axis
        /*var y = d3.scaleBand()
            .range([ 0, height ])
            .domain(sortedData.map(function(d) { return d.Description; }))
            .padding(.1);
        svg.append("g")
            .style("font", "14px times")
            .call(d3.axisLeft(y))*/

  // Add Y axis
  /*var y = d3.scaleLinear()
    .domain([35, 90])
    .range([ height, 0]);
  svg.append("g")
    .call(d3.axisLeft(y));*/

        // Initialize the X axis
        /*var x = d3.scaleLinear()
            .domain([0, maxAdj])
            .range([ 0, width]);
        svg.append("g")
            .attr("transform", "translate(0," + height + ")")
            .call(d3.axisBottom(x))
            .selectAll("text")
            .attr("transform", "translate(-10,0)rotate(-45)")
            .style("text-anchor", "end");
        // X Axis label; Gene Ratio
        svg.append("text")
            .attr("x", width/2)
            .attr("y", height + 40)
            .style("text-anchor", "middle")
            .text("Gene Count");


        // Add a scale for bubble size
        var z = d3.scaleLinear()
            .domain([0, maxVal])
            .range([ 1, 15]);

        var myColor = d3.scaleLinear()
            .domain([0,0.05])
            .range(['red', 'blue']);


        // Add dots
        svg.append('g')
            .selectAll("dot")
            .data(sortedData)
            .enter()
            .append("circle")
            .attr("cx", function (d) { return x(d.p_adjust); } )
            .attr("cy", function (d) { return y(d.Description); } )
            .attr("r", function (d) { return z(d.GeneRatio); } )
            .attr("fill",  function(d){ return myColor(d.p_adjust); })
            .style("opacity", "0.7")
            .attr("stroke", "black")

            
    }

    function exportGODot() {

    }*/
    // up above is the contextMenu function
    //------------------------------------------------------------------------------------------------

    // cookie for recording show options
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    function eraseCookie(name) {
        document.cookie = name + '=; Max-Age=-99999999;';
    }

    window.onload = update();

    // hide and show lable function
    function lbstyle(val) {
        //var f=2;    //this is just a flag to judge whether it is a parent node
        if (val == 2) {
            d3.selectAll(".nodes").selectAll("text").style("display", "")    //show all lable
        } else if (val == 1) {
            for (i = 0; i < nodes.length; i++) {
                var f = 1;
                for (j = 0; j < exp_name.length; j++) {
                    if (nodes[i].title == exp_name[j]) {
                        f = 2;
                        d3.select("#" + nodes[i].id).selectAll("text").style("display", "");       //find all of the parent node ,show the name enven when hide the lable
                        break;
                    }
                }
                if (f == 1) {
                    d3.select("#" + nodes[i].id).selectAll("text").style("display", "none")
                }

            }
        }
    }

    function update() {
        d3.select('#colorchanger').selectAll('input').remove();// clear all the old create color element
        // create the colorselector element to change color in the graph
        for (var i = 0; i < filenum; i++) {
            d3.select('#colorchanger').append('input')
                .attr('class', 'jscolor{hash:true}')
                .attr('name', 'colorselector' + i)
                .attr('value', mycolor[i])
                .style('width', '60px')
        }
        d3.select('#colorchanger').append('input')
            .attr('class', 'jscolor{hash:true}')
            .attr('name', 'colorselectorUp')
            .attr('value', mycolor[filenum])
            .style('width', '60px')
        d3.select('#colorchanger').append('input')
            .attr('class', 'jscolor{hash:true}')
            .attr('name', 'colorselectorDown')
            .attr('value', mycolor[filenum + 1])
            .style('width', '60px')
        d3.select('#colorchanger').append('input')
            .attr('class', 'jscolor{hash:true}')
            .attr('name', 'colorselectorUpDown')
            .attr('value', mycolor[filenum + 2])
            .style('width', '60px')
        d3.select('#colorchanger').append('input')
            .attr('class', 'jscolor{hash:true}')
            .attr('name', 'colorselectorSum')
            .attr('value', mycolor[filenum + 3])
            .style('width', '60px')
        d3.select('#colorchanger').append('input')
            .attr('class', 'jscolor{hash:true}')
            .attr('name', 'colorselectorRedraw')
            .attr('value', mycolor[filenum + 4])
            .style('width', '60px')

        var active = d3.select(null);
        var palette = {
            "stroke-width": "1.5 px",
            "gray": "#999",
            "white": "#fff",
            "black": "#000000",
            "blue": "#1574A8",
            "lightblue": "#E5FFFF"                            //identify the basic color
        }

        // clear old shape element
        d3.select('#shapechanger').selectAll('input').remove();
        // create the shapeselector element to change shape in the graph
        d3.select('#shapechanger').append('input')
            .attr('name', 'shapeselectorUp')
            .attr('value', myshape[0])
            .style('width', '60px')
        d3.select('#shapechanger').append('input')
            .attr('name', 'shapeselectorDown')
            .attr('value', myshape[1])
            .style('width', '60px')
        d3.select('#shapechanger').append('input')
            .attr('name', 'shapeselectorUpDown')
            .attr('value', myshape[2])
            .style('width', '60px')
        d3.select('#shapechanger').append('input')
            .attr('name', 'shapeselectorSum')
            .attr('value', myshape[3])
            .style('width', '60px')
        d3.select('#shapechanger').append('input')
            .attr('name', 'shapeselectorRedraw')
            .attr('value', myshape[4])
            .style('width', '60px')

        // the code below is for zoom the graph
        var margin = 1
        var width = screen.width * margin;//1024;
        var height = screen.height * margin;//900;
        var ZoomScale = 1 //1-0.5*nodeMaxNum/nodeMaxDefault;

        var transform = d3.zoomIdentity.translate(0, 0).scale(1);
        var zoom = d3.zoom().on("zoom", zoomed).scaleExtent([0, 8]);
        var svg = d3.select("#chart")
            .append("svg")
            .attr("id", "svg")
            .attr("width", width)
            .attr("height", height)
            .style("left", 0)
            .style("top", 0)
            .attr("transform", "translate(0,0) scale(" + ZoomScale + ")")
            .call(zoom)
            .append("g")// this g can transform for zoom.
            .attr("id", "svgZoomContainer")

        // move and zoom functions
        //--------------------------------------------------


        var x = 0;
        var y = 0;
        //console.log("zoom:");
        //console.log(ZoomScale);

        var moveleftButton = document.getElementById("moveLeft");

        function zoomed() {
            svg.attr("transform", d3.event.transform)
            ZoomScale = d3.event.transform.k;
            x = d3.event.transform.x;
            y = d3.event.transform.y;
        }

        d3.select(moveleftButton).on("click", function () {
            x -= 5;
            y -= 0;
            d3.select("#svgZoomContainer").attr("transform", "translate(" + x + "," + y + ")scale(" + ZoomScale + ")");
        });

        $("#moveRight").on("click", function () {
            x += 5
            y += 0
            d3.select("#svgZoomContainer").attr("transform", "translate(" + x + "," + y + ")scale(" + ZoomScale + ")");
        });

        $("#moveUp").on("click", function () {
            x -= 0
            y -= 5
            d3.select("#svgZoomContainer").attr("transform", "translate(" + x + "," + y + ")scale(" + ZoomScale + ")");
        });
        $("#moveDown").on("click", function () {
            x += 0
            y += 5
            d3.select("#svgZoomContainer").attr("transform", "translate(" + x + "," + y + ")scale(" + ZoomScale + ")");
        });

        $("#reset").on("click", function () {
            x = 0;
            y = 0;
            ZoomScale = 1; // 1-0.5*nodeMaxNum/nodeMaxDefault;
            d3.select("#svgZoomContainer").attr("transform", "translate(" + x + "," + y + ")scale(" + ZoomScale + ")");
        });
        $("#zoomIn").on("click", function () {
            ZoomScale = 0.8 * ZoomScale;
            d3.select("#svgZoomContainer").attr("transform", "translate(" + x + "," + y + ")scale(" + ZoomScale + ")");
        });
        $("#zoomOut").on("click", function () {
            ZoomScale = 1.1 * ZoomScale;
            d3.select("#svgZoomContainer").attr("transform", "translate(" + x + "," + y + ")scale(" + ZoomScale + ")");
        });
        //---------------------------------------------

//定义颜色集  identify the colorset
        var color = d3.scaleOrdinal() // D3 Version 4            node的颜色  color of nodes
            .domain([1, 2, 3])
            .range(mycolor.slice(filenum));

        var color2 = d3.scaleOrdinal()
            .domain([0, 1, 2, 3, 4, 5, 6, 7])
            .range(mycolor.slice(0, filenum))

        var simulation = d3.forceSimulation()
            .force("link", d3.forceLink().id(function (d) {
                return d.id;
            }).distance(100))    //distance for the line length
            .force("charge", d3.forceManyBody())
            .force("center", d3.forceCenter(width * 0.5, height * 0.4));

//画链接线  draw link lines
        var link = svg.append("g")
            .attr("class", "links")
            .selectAll("line")
            .data(links)                       //获得link数据   get link
            .enter().append("line")//画线          draw line
            .attr("id", function (d) {
                return "link_" + d.source;
            })
            .attr("stroke-width", .8)
            .attr("stroke", palette.gray)      //线的颜色      color of line
            .attr("stroke-opacity", .6)        //线的透明度     opacity of line
            .on('mouseover', function (d) {
                d3.select(this)
                    .style('stroke', function (d) {
                        return color(d.value)
                    })
                    .attr("stroke-width", 1)
                    .attr("stroke-opacity", 1)
                    .enter().append("text")  //yge
                    .text(function (d) {
                        return d.x;
                    })
                    .attr("x", function (d) {
                        return x(d.x);
                    })
                    .attr("y", function (d) {
                        return y(d.y);
                    });

            })
            .on('mouseout', function () {
                d3.select(this)
                    .style('stroke', palette.gray)
                    .attr("stroke-width", .8)

            })

        var circleWidth = {};                   //记录圆点的半径的数组   r of circle
        for (var i = 0; i < nodes.length; i++) {
            var r = 6;                              //设置圆点的初始半径为5px
            for (var j = 0; j < links.length; j++) {
                if (nodes[i].id == links[j].target)        //遍历links数组，如果links数组中target值与nodes数组中id相同，给半径r增加0.5，计算每一个节点的权重
                {
                    if (r > 70) {
                        r = r + 0.0001;
                    } else if (r > 60) {
                        r = r + 0.001;
                    } else if (r > 40) {
                        r = r + 0.01;
                    } else if (r >= 20) {
                        r = r + 0.2;
                    } else if (r >= 10) {
                        r = r + 2;
                    } else {
                        r = r + 4;
                    }
                }

            }

            circleWidth[nodes[i].id] = r;                  //将r放入数组中

        }

        var node = svg.selectAll(".nodes")                         //定义node
            .data(nodes).enter()                         //链接node数据
            .append("g")                                 //追加g标签
            .attr("class", "nodes")                        //定义g的class=nodes
            .attr("id", function (d) {
                return d.id
            })

        var tooltip = d3.select('body').append("div")            // define div for tooltip
            .attr("class", "tooltip")
            .attr("id", "tooltip")
            .style("position", "absolute")
            .style("background-color", "#ffffff")
            .style("border-radius", "15px")
            .style("font-family", "sans-serif")
            .style("font-size", "2em")
            .style("opacity", 0);

        var nodedot = node.append("circle")
            .filter(function (d) {
                if (exp_name.indexOf(d.id) > -1) {
                    return true;
                } else if (redrawdata.length > 0 && d.redraw) {		// when redraw
                    if (document.getElementsByName('shapeselectorRedraw')[0].value == 1) {
                        return true;
                    } else {
                        return false;
                    }
                } else {				   // base on shape when not redraw
                    if (document.getElementsByName('shapeselectorUp')[0].value == '1' && d.group == 1) {
                        return true;
                    } else if (document.getElementsByName('shapeselectorDown')[0].value == 1 && d.group == 2) {
                        return true;
                    } else if (document.getElementsByName('shapeselectorUpDown')[0].value == 1 && d.group == 3) {
                        return true;
                    }
                }
            })
            .attr("r", function (d, i) {
                return circleWidth[d.id];
            })            //设置圆的半径根据每个节点子节点的多少确定圆点的大小
            .attr("fill", function (d) {
                for (i = 0; i < exp_name.length; i++) {
                    if (d.title == exp_name[i]) {
                        return color2(d.id);
                    }
                }
                if (d.redraw && document.getElementsByName('colorselectorRedraw')[0].value != '#ffffff' &&
                    document.getElementsByName('colorselectorRedraw')[0].value != 'FFFFFF') {
                    return document.getElementsByName('colorselectorRedraw')[0].value;
                }
                return color(d.group);
            })                        //圆点的填充颜色 fill color of the nodes
            .attr("stroke", palette.white)                             //设置圆点的外边线颜色
            .call(d3.drag()
                .on("start", function (d) {
                    dragstarted(d);
                })
                .on("drag", dragged)
                .on("end", dragended))
            .on('click', function (d) {           //添加鼠标单击事件 mouse click show the path
                var linecolor = $('#' + 'link_' + d.id).attr('stroke');
                if (linecolor === palette.gray) {
                    d3.selectAll('#' + 'link_' + d.id)
                        .attr("stroke-width", 1)
                        .attr("stroke-opacity", 1)
                        .attr("stroke", function (d) {
                            return color(d.value);
                        })
                        .style("stroke", function (d) {
                            return color(d.value);
                        });
                } else {
                    d3.selectAll('#' + 'link_' + d.id)
                        .attr("stroke-width", .8)
                        .attr("stroke-opacity", .6)
                        .attr("stroke", palette.gray)
                        .style("stroke", palette.gray);
                }
            })
            .on('dblclick', function (d) {           // dblclick to reback the attr of the line
                for (i = 0; i < exp_name.length; i++) {				// skip experiment node
                    if (d.id == exp_name[i]) {
                        return;
                    }
                }
                if (d3.select('#' + d.id).select('circle').attr('r') != '6') {		// spread group node
                    d3.select('#' + d.id).select('circle').attr('r', '6');
                    d3.select('#' + d.id).select('rect').style('display', 'none');
                    d3.select('#' + d.id).select('text').selectAll('tspan').remove();
                    d3.select('#' + d.id).select('text').text(d.id + ' ' + d.group).style('font-weight', '')
                        .style('font-size', '0.4em').style('display', 'none');
                    if (d.group == 1) { 						// set correct color
                        d3.select('#' + d.id).select('circle')
                            .attr('fill', document.getElementsByName('colorselectorUp')[0].value);
                    } else if (d.group == 2) {
                        d3.select('#' + d.id).select('circle')
                            .attr('fill', document.getElementsByName('colorselectorDown')[0].value);
                    } else if (d.group == 3) {
                        d3.select('#' + d.id).select('circle')
                            .attr('fill', document.getElementsByName('colorselectorUpDown')[0].value);
                    }
                    if (overlaps[d.id] != null) {						// show other node in this group
                        for (var i in overlapCount[overlaps[d.id]]) {
                            $('#' + overlapCount[overlaps[d.id]][i]).show();
                            d3.select('#' + overlapCount[overlaps[d.id]][i]).select('rect').style('display', 'none');
                            d3.select('#' + overlapCount[overlaps[d.id]][i]).select('circle').style('display', '');
                            d3.select('#svgZoomContainer').selectAll('#link_' + overlapCount[overlaps[d.id]][i]).style('display', '');
                        }
                    } else {
                        for (var i in nodes) {
                            if (overlaps[nodes[i].id] == null && nodes[i].target == d.target) {
                                $('#' + nodes[i].id).show();
                                d3.select('#' + nodes[i].id).select('rect').style('display', 'none');
                                d3.select('#' + nodes[i].id).select('circle').style('display', '');
                                d3.select('#svgZoomContainer').selectAll('#link_' + nodes[i].id).style('display', '');
                            }
                        }
                    }
                }
            })
            .on('mouseover', function (d) {               // when mouseover change the circle outline color to black
                d3.select(this)
                    .style('stroke', palette.black)
                    .enter().append("text")  //yge
                    .text(function (d) {
                        return d.x;
                    })
                    .attr("x", function (d) {
                        return x(d.x);
                    })
                    .attr("y", function (d) {
                        return y(d.y);
                    })
                d3.select("#tooltip")            // show tooltip
                    .transition()
                    .duration(200)
                    .style("opacity", 0.9)
                d3.select("#tooltip").html(function () {
                    for (var exp in expGroup) {
                        if (exp == d.id) {
                            return;
                        }
                    }
                    if (overlaps[d.id] != null) {
                        return "&nbsp;<b>" + grpGeneNum[overlaps[d.id]]['total'] + "</b> overlapping genes&nbsp;";
                    }
                    return "&nbsp;<b>" + grpGeneNum[d.target]['total'] + "</b> genes&nbsp;";
                })
            })
            .on('mouseout', function (d) {                 // when mouse move out rechange the circle outline color to white
                d3.select(this)
                    .style('stroke', palette.white);
                d3.select("#tooltip")            // hide tooltip
                    .transition()
                    .duration(500)
                    .style("opacity", 0);
            })
            .on('mousemove', function (d) {				// set tooltip position
                d3.select('#tooltip')
                    .style('left', (d3.event.pageX + 10) + 'px')
                    .style('top', (d3.event.pageY - 70) + 'px')
            })
            .on('contextmenu', d3.contextMenu(menu));   //鼠标右键弹出菜单  mouse right click pop menu

        var nodetri = node.append("polygon")
            .filter(function (d) {
                if (exp_name.indexOf(d.id) > -1) {              // when redraw
                    return false;
                } else if (redrawdata.length > 0 && d.redraw) {               // when redraw
                    return document.getElementsByName('shapeselectorRedraw')[0].value == 3;
                } else {                            // base on shape when not redraw
                    if (document.getElementsByName('shapeselectorUp')[0].value == 3 && d.group == 1) {
                        return true;
                    } else if (document.getElementsByName('shapeselectorDown')[0].value == 3 && d.group == 2) {
                        return true;
                    } else if (document.getElementsByName('shapeselectorUpDown')[0].value == 3 && d.group == 3) {
                        return true;
                    }
                }
            })
            .attr("fill", function (d) {
                for (i = 0; i < exp_name.length; i++) {
                    if (d.title == exp_name[i]) {
                        return color2(d.id);
                    }
                }
                if (d.redraw && document.getElementsByName('colorselectorRedraw')[0].value != '#ffffff' &&
                    document.getElementsByName('colorselectorRedraw')[0].value != 'FFFFFF') {
                    return document.getElementsByName('colorselectorRedraw')[0].value;
                }
                return color(d.group);
            })
            .attr("stroke", palette.white)
            .call(d3.drag()
                .on("start", function (d) {
                    dragstarted(d);
                })
                .on("drag", dragged)
                .on("end", dragended))
            .on('click', function (d) {
                var linecolor = $('#' + 'link_' + d.id).attr('stroke');
                if (linecolor === palette.gray) {
                    d3.selectAll('#' + 'link_' + d.id)
                        .attr('stroke', function (d) {
                            return color(d.value);
                        })
                        .attr("stroke-width", 1)
                        .attr("stroke-opacity", 1)
                } else {
                    d3.selectAll('#' + 'link_' + d.id)
                        .attr("stroke-width", .8)
                        .attr("stroke", palette.gray)
                        .attr("stroke-opacity", .6)
                }
            })
            .on('dblclick', function (d) {
                for (i = 0; i < exp_name.length; i++) {                         // skip experiment node
                    if (d.id == exp_name[i]) {
                        return;
                    }
                }
                if (d3.select('#' + d.id).select('circle').attr('r') != '6') {     // spread group node
                    d3.select('#' + d.id).select('circle').attr('r', '6');
                    d3.select('#' + d.id).select('rect').style('display', 'none');
                    d3.select('#' + d.id).select('text').selectAll('tspan').remove();
                    d3.select('#' + d.id).select('text').text(d.id + ' ' + d.group).style('font-weight', '')
                        .style('font-size', '0.4em').style('display', 'none');
                    if (d.group == 1) {  				// set correct color
                        d3.select('#' + d.id).select('circle')
                            .attr('fill', document.getElementsByName('colorselectorUp')[0].value);
                    } else if (d.group == 2) {
                        d3.select('#' + d.id).select('circle')
                            .attr('fill', document.getElementsByName('colorselectorDown')[0].value);
                    } else if (d.group == 3) {
                        d3.select('#' + d.id).select('circle')
                            .attr('fill', document.getElementsByName('colorselectorUpDown')[0].value);
                    }
                    if (overlaps[d.id] != null) {	// show other node in this group
                        for (var i in overlapCount[overlaps[d.id]]) {
                            $('#' + overlapCount[overlaps[d.id]][i]).show();
                            d3.select('#' + overlapCount[overlaps[d.id]][i]).select('rect').style('display', 'none');
                            d3.select('#' + overlapCount[overlaps[d.id]][i]).select('circle').style('display', '');
                            d3.select('#svgZoomContainer').selectAll('#link_' + overlapCount[overlaps[d.id]][i]).style('display', '');
                        }
                    } else {
                        for (var i in nodes) {
                            if (overlaps[nodes[i].id] == null && nodes[i].target == d.target) {
                                $('#' + nodes[i].id).show();
                                d3.select('#' + nodes[i].id).select('rect').style('display', 'none');
                                d3.select('#' + nodes[i].id).select('circle').style('display', '');
                                d3.select('#svgZoomContainer').selectAll('#link_' + nodes[i].id).style('display', '');
                            }
                        }
                    }
                }
            })
            .on('mouseover', function (d) {               // when mouseover change the circle outline to black
                d3.select(this)
                    .style('stroke', palette.black)
                    .enter().append("text")  //yge
                    .text(function (d) {
                        return d.x;
                    })
                    .attr("x", function (d) {
                        return x(d.x);
                    })
                    .attr("y", function (d) {
                        return y(d.y);
                    })
                d3.select("#tooltip")                   // show tooltip
                    .transition()
                    .duration(200)
                    .style("opacity", 0.9)
                d3.select("#tooltip").html(function () {
                    for (var exp in expGroup) {
                        if (exp == d.id) {
                            return;
                        }
                    }
                    if (overlaps[d.id] != null) {
                        return "&nbsp;<b>" + grpGeneNum[overlaps[d.id]]['total'] + "</b> overlapping genes&nbsp;";
                    }
                    return "&nbsp;<b>" + grpGeneNum[d.target]['total'] + "</b> genes&nbsp;";
                })
            })
            .on('mouseout', function (d) {            // when mouse move out rechange the circle outline color to white
                d3.select(this)
                    .style('stroke', palette.white);
                d3.select("#tooltip")                   // hide tooltip
                    .transition()
                    .duration(500)
                    .style("opacity", 0);
            })
            .on('mousemove', function (d) {                           // set tooltip position
                d3.select('#tooltip')
                    .style('left', (d3.event.pageX + 10) + 'px')
                    .style('top', (d3.event.pageY - 70) + 'px')
            })
            .on('contextmenu', d3.contextMenu(menu));   //mouse right click pop menu

        var nodestar = node.append("polygon")
            .filter(function (d) {
                if (exp_name.indexOf(d.id) > -1) {              // when redraw
                    return false;
                } else if (redrawdata.length > 0 && d.redraw) {               // when redraw
                    if (document.getElementsByName('shapeselectorRedraw')[0].value == 4) {
                        return true;
                    } else {
                        return false;
                    }
                } else {                            // base on shape when not redraw
                    if (document.getElementsByName('shapeselectorUp')[0].value == 4 && d.group == 1) {
                        return true;
                    } else if (document.getElementsByName('shapeselectorDown')[0].value == 4 && d.group == 2) {
                        return true;
                    } else if (document.getElementsByName('shapeselectorUpDown')[0].value == 4 && d.group == 3) {
                        return true;
                    }
                }
            })
            .attr("fill", function (d) {
                for (i = 0; i < exp_name.length; i++) {
                    if (d.title == exp_name[i]) {
                        return color2(d.id);
                    }
                }
                if (d.redraw && document.getElementsByName('colorselectorRedraw')[0].value != '#ffffff' &&
                    document.getElementsByName('colorselectorRedraw')[0].value != 'FFFFFF') {
                    return document.getElementsByName('colorselectorRedraw')[0].value;
                }
                return color(d.group);
            })
            .attr("stroke", palette.white)
            .call(d3.drag()
                .on("start", function (d) {
                    dragstarted(d);
                })
                .on("drag", dragged)
                .on("end", dragended))
            .on('click', function (d) {
                var linecolor = $('#' + 'link_' + d.id).attr('stroke');
                if (linecolor == palette.gray) {
                    d3.selectAll('#' + 'link_' + d.id)
                        .attr('stroke', function (d) {
                            return color(d.value);
                        })
                        .attr("stroke-width", 1)
                        .attr("stroke-opacity", 1)
                } else {
                    d3.selectAll('#' + 'link_' + d.id)
                        .attr("stroke-width", .8)
                        .attr("stroke", palette.gray)
                        .attr("stroke-opacity", .6)
                }
            })
            .on('dblclick', function (d) {
                for (i = 0; i < exp_name.length; i++) {                         // skip experiment node
                    if (d.id == exp_name[i]) {
                        return;
                    }
                }
                if (d3.select('#' + d.id).select('circle').attr('r') != '6') {     // spread group node
                    d3.select('#' + d.id).select('circle').attr('r', '6');
                    d3.select('#' + d.id).select('rect').style('display', 'none');
                    d3.select('#' + d.id).select('text').selectAll('tspan').remove();
                    d3.select('#' + d.id).select('text').text(d.id + ' ' + d.group).style('font-weight', '')
                        .style('font-size', '0.4em').style('display', 'none');
                    if (d.group == 1) {                               // set correct color
                        d3.select('#' + d.id).select('circle')
                            .attr('fill', document.getElementsByName('colorselectorUp')[0].value);
                    } else if (d.group == 2) {
                        d3.select('#' + d.id).select('circle')
                            .attr('fill', document.getElementsByName('colorselectorDown')[0].value);
                    } else if (d.group == 3) {
                        d3.select('#' + d.id).select('circle')
                            .attr('fill', document.getElementsByName('colorselectorUpDown')[0].value);
                    }
                    if (overlaps[d.id] != null) {     // show other node in this group
                        for (var i in overlapCount[overlaps[d.id]]) {
                            $('#' + overlapCount[overlaps[d.id]][i]).show();
                            d3.select('#' + overlapCount[overlaps[d.id]][i]).select('rect').style('display', 'none');
                            d3.select('#' + overlapCount[overlaps[d.id]][i]).select('circle').style('display', '');
                            d3.select('#svgZoomContainer').selectAll('#link_' + overlapCount[overlaps[d.id]][i]).style('display', '');
                        }
                    } else {
                        for (var i in nodes) {
                            if (overlaps[nodes[i].id] == null && nodes[i].target == d.target) {
                                $('#' + nodes[i].id).show();
                                d3.select('#' + nodes[i].id).select('rect').style('display', 'none');
                                d3.select('#' + nodes[i].id).select('circle').style('display', '');
                                d3.select('#svgZoomContainer').selectAll('#link_' + nodes[i].id).style('display', '');
                            }
                        }
                    }
                }
            })
            .on('mouseover', function (d) {               // when mouseover change the circle outline to black
                d3.select(this)
                    .style('stroke', palette.black)
                    .enter().append("text")  //yge
                    .text(function (d) {
                        return d.x;
                    })
                    .attr("x", function (d) {
                        return x(d.x);
                    })
                    .attr("y", function (d) {
                        return y(d.y);
                    })
                d3.select("#tooltip")                   // show tooltip
                    .transition()
                    .duration(200)
                    .style("opacity", 0.9)
                d3.select("#tooltip").html(function () {
                    for (var exp in expGroup) {
                        if (exp == d.id) {
                            return;
                        }
                    }
                    if (overlaps[d.id] != null) {
                        return "&nbsp;<b>" + grpGeneNum[overlaps[d.id]]['total'] + "</b> overlapping genes&nbsp;";
                    }
                    return "&nbsp;<b>" + grpGeneNum[d.target]['total'] + "</b> genes&nbsp;";
                })
            })
            .on('mouseout', function (d) {            // when mouse move out rechange the circle outline color to white
                d3.select(this)
                    .style('stroke', palette.white);
                d3.select("#tooltip")                   // hide tooltip
                    .transition()
                    .duration(500)
                    .style("opacity", 0);
            })
            .on('mousemove', function (d) {                           // set tooltip position
                d3.select('#tooltip')
                    .style('left', (d3.event.pageX + 10) + 'px')
                    .style('top', (d3.event.pageY - 70) + 'px')
            })
            .on('contextmenu', d3.contextMenu(menu));   //mouse right click pop menu

        var nodehex = node.append("polygon")
            .filter(function (d) {
                if (exp_name.indexOf(d.id) > -1) {              // whenredraw
                    return false;
                } else if (redrawdata.length > 0 && d.redraw) {               // when redraw
                    if (document.getElementsByName('shapeselectorRedraw')[0].value == 5) {
                        return true;
                    } else {
                        return false;
                    }
                } else {                            // base on shape when not redraw
                    if (document.getElementsByName('shapeselectorUp')[0].value == 5 && d.group == 1) {
                        return true;
                    } else if (document.getElementsByName('shapeselectorDown')[0].value == 5 && d.group == 2) {
                        return true;
                    } else if (document.getElementsByName('shapeselectorUpDown')[0].value == 5 && d.group == 3) {
                        return true;
                    }
                }
            })
            .attr("fill", function (d) {
                for (i = 0; i < exp_name.length; i++) {
                    if (d.title == exp_name[i]) {
                        return color2(d.id);
                    }
                }
                if (d.redraw && document.getElementsByName('colorselectorRedraw')[0].value != '#ffffff' &&
                    document.getElementsByName('colorselectorRedraw')[0].value != 'FFFFFF') {
                    return document.getElementsByName('colorselectorRedraw')[0].value;
                }
                return color(d.group);
            })
            .attr("stroke", palette.white)
            .call(d3.drag()
                .on("start", function (d) {
                    dragstarted(d);
                })
                .on("drag", dragged)
                .on("end", dragended))
            .on('click', function (d) {
                var linecolor = $('#' + 'link_' + d.id).attr('stroke');
                if (linecolor == palette.gray) {
                    d3.selectAll('#' + 'link_' + d.id)
                        .attr('stroke', function (d) {
                            return color(d.value);
                        })
                        .attr("stroke-width", 1)
                        .attr("stroke-opacity", 1)
                } else {
                    d3.selectAll('#' + 'link_' + d.id)
                        .attr("stroke-width", .8)
                        .attr("stroke", palette.gray)
                        .attr("stroke-opacity", .6)
                }
            })
            .on('dblclick', function (d) {
                for (i = 0; i < exp_name.length; i++) {                         // skip experiment node
                    if (d.id == exp_name[i]) {
                        return;
                    }
                }
                if (d3.select('#' + d.id).select('circle').attr('r') != '6') {     // spread group node
                    d3.select('#' + d.id).select('circle').attr('r', '6');
                    d3.select('#' + d.id).select('rect').style('display', 'none');
                    d3.select('#' + d.id).select('text').selectAll('tspan').remove();
                    d3.select('#' + d.id).select('text').text(d.id + ' ' + d.group).style('font-weight', '')
                        .style('font-size', '0.4em').style('display', 'none');
                    if (d.group == 1) {                               // set correct color
                        d3.select('#' + d.id).select('circle')
                            .attr('fill', document.getElementsByName('colorselectorUp')[0].value);
                    } else if (d.group == 2) {
                        d3.select('#' + d.id).select('circle')
                            .attr('fill', document.getElementsByName('colorselectorDown')[0].value);
                    } else if (d.group == 3) {
                        d3.select('#' + d.id).select('circle')
                            .attr('fill', document.getElementsByName('colorselectorUpDown')[0].value);
                    }
                    if (overlaps[d.id] != null) {     // show other node in this group
                        for (var i in overlapCount[overlaps[d.id]]) {
                            $('#' + overlapCount[overlaps[d.id]][i]).show();
                            d3.select('#' + overlapCount[overlaps[d.id]][i]).select('rect').style('display', 'none');
                            d3.select('#' + overlapCount[overlaps[d.id]][i]).select('circle').style('display', '');
                            d3.select('#svgZoomContainer').selectAll('#link_' + overlapCount[overlaps[d.id]][i]).style('display', '');
                        }
                    } else {
                        for (var i in nodes) {
                            if (overlaps[nodes[i].id] == null && nodes[i].target == d.target) {
                                $('#' + nodes[i].id).show();
                                d3.select('#' + nodes[i].id).select('rect').style('display', 'none');
                                d3.select('#' + nodes[i].id).select('circle').style('display', '');
                                d3.select('#svgZoomContainer').selectAll('#link_' + nodes[i].id).style('display', '');
                            }
                        }
                    }
                }
            })
            .on('mouseover', function (d) {               // when mouseover change the circle outline to black
                d3.select(this)
                    .style('stroke', palette.black)
                    .enter().append("text")  //yge
                    .text(function (d) {
                        return d.x;
                    })
                    .attr("x", function (d) {
                        return x(d.x);
                    })
                    .attr("y", function (d) {
                        return y(d.y);
                    })
                d3.select("#tooltip")                   // show tooltip
                    .transition()
                    .duration(200)
                    .style("opacity", 0.9)
                d3.select("#tooltip").html(function () {
                    for (var exp in expGroup) {
                        if (exp == d.id) {
                            return;
                        }
                    }
                    if (overlaps[d.id] != null) {
                        return "&nbsp;<b>" + grpGeneNum[overlaps[d.id]]['total'] + "</b> overlapping genes&nbsp;";
                    }
                    return "&nbsp;<b>" + grpGeneNum[d.target]['total'] + "</b> genes&nbsp;";
                })
            })
            .on('mouseout', function (d) {            // when mouse move out rechange the circle outline color to white
                d3.select(this)
                    .style('stroke', palette.white);
                d3.select("#tooltip")                   // hide tooltip
                    .transition()
                    .duration(500)
                    .style("opacity", 0);
            })
            .on('mousemove', function (d) {                           // set tooltip position
                d3.select('#tooltip')
                    .style('left', (d3.event.pageX + 10) + 'px')
                    .style('top', (d3.event.pageY - 70) + 'px')
            })
            .on('contextmenu', d3.contextMenu(menu));   //mouse right click pop menu

        var nodeRect = node.append("rect")
            .filter(function (d) {
                if (exp_name.indexOf(d.id) > -1) {
                    return false;
                } else if (redrawdata.length > 0 && d.redraw) {
                    if (document.getElementsByName('shapeselectorRedraw')[0].value == 2) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    if (document.getElementsByName('shapeselectorUp')[0].value == 2 && d.group == 1) {
                        return true;
                    } else if (document.getElementsByName('shapeselectorDown')[0].value == 2 && d.group == 2) {
                        return true;
                    } else if (document.getElementsByName('shapeselectorUpDown')[0].value == 2 && d.group == 3) {
                        return true;
                    }
                }
            })
            .attr("width", 12)
            .attr("height", 12)
            .attr("fill", function (d) {
                for (i = 0; i < exp_name.length; i++) {
                    if (d.title == exp_name[i]) {
                        return color2(d.id);
                    }
                }
                if (d.redraw && document.getElementsByName('colorselectorRedraw')[0].value != '#ffffff' &&
                    document.getElementsByName('colorselectorRedraw')[0].value != 'FFFFFF') {
                    return document.getElementsByName('colorselectorRedraw')[0].value;
                }
                return color(d.group);
            })                    //圆点的填充颜色 fill color of the nodes
            .attr("stroke", palette.white)                         //设置圆点的外边线颜色
            .call(d3.drag()
                .on("start", dragstarted)
                .on("drag", dragged)
                .on("end", dragended))
            .on('click', function (d) {           	     //添加鼠标单击事件 mouse click show the path
                d3.selectAll('#' + 'link_' + d.id)
                    .style('stroke', function (d) {
                        return color(d.value)
                    })
                    .attr("stroke-width", 1)
                    .attr("stroke-opacity", 1)
            })
            .on('dblclick', function (d) {                  // dblclick to reback the attr of the line
                d3.selectAll('#' + 'link_' + d.id)
                    .style("stroke", palette.gray)    //color of the line
                    .attr("stroke-width", .8)        // width of the line
                    .attr("stroke-opacity", .6)      //opacity of the line
            })
            .on('mouseover', function (d) {               // when mouseover change the circle outline color to black
                d3.select(this)
                    .style('stroke', palette.black)
                    .enter().append("text")  //yge
                    .text(function (d) {
                        return d.x;
                    })
                    .attr("x", function (d) {
                        return x(d.x);
                    })
                    .attr("y", function (d) {
                        return y(d.y);
                    });
            })
            .on('mouseout', function () {                 // when mouse move out rechange the circle outline color to white
                d3.select(this)
                    .style('stroke', palette.white)
            })
            .on('contextmenu', d3.contextMenu(menu));   //鼠标右键弹出菜单  mouse right click pop menu

        var nodetext;
        nodetext = node.append("text")                       //为每个节点添加文本
            .text(function (d) {				// return node id
                for (i = 0; i < exp_name.length; i++) {
                    if (d.title === exp_name[i]) {
                        return d.title.substring(4);
                    }
                }
                return d.title
            })
            .style("font-size", function (d) {
                for (i = 0; i < exp_name.length; i++) {
                    if (d.title === exp_name[i]) {
                        return '1em';
                    }
                }
                return '0.4em';
            })
            .style("font-family", "sans-serif")                 //设置字体
            .style("fill", palette.black)
            .on('click', function () {
                d3.select(this).style("display", "none")              //鼠标单击将该节点的标签隐藏
            })

        var lbflag = 1;
        lbstyle(lbflag);     //call lbstyle function to show all label or hide all
        simulation
            .nodes(nodes)
            .on("tick", ticked);

        simulation.force("link")
            .links(links);

        function ticked() {
            link
                .attr("x1", function (d) {
                    return d.source.x;
                })       //链接线的起始坐标
                .attr("y1", function (d) {
                    return d.source.y;
                })
                .attr("x2", function (d) {
                    return d.target.x;
                })
                .attr("y2", function (d) {
                    return d.target.y;
                });              //连接线的终止坐标

            nodedot
                .attr("cx", function (d) {
                    return d.x;
                })                  //圆点的圆心坐标
                .attr("cy", function (d) {
                    return d.y;
                });

            nodetri
                .attr("points", function (d) {
                    return (d.x).toString() + "," + (Number(d.y) - 7).toString() + " " +
                        (Number(d.x) - 7).toString() + "," + (Number(d.y) + 7).toString() + " " +
                        (Number(d.x) + 7).toString() + "," + (Number(d.y) + 7).toString();
                });

            nodestar
                .attr("points", function (d) {
                    return (d.x).toString() + "," + (Number(d.y) - 7).toString() + " " +
                        (Number(d.x) + 3).toString() + "," + (Number(d.y) - 3).toString() + " " +
                        (Number(d.x) + 7).toString() + "," + (Number(d.y) - 3).toString() + " " +
                        (Number(d.x) + 4).toString() + "," + (Number(d.y) + 2).toString() + " " +
                        (Number(d.x) + 5).toString() + "," + (Number(d.y) + 7).toString() + " " +
                        (d.x).toString() + "," + (Number(d.y) + 3).toString() + " " +
                        (Number(d.x) - 5).toString() + "," + (Number(d.y) + 7).toString() + " " +
                        (Number(d.x) - 4).toString() + "," + (Number(d.y) + 2).toString() + " " +
                        (Number(d.x) - 7).toString() + "," + (Number(d.y) - 3).toString() + " " +
                        (Number(d.x) - 3).toString() + "," + (Number(d.y) - 3).toString();
                });

            nodehex
                .attr("points", function (d) {
                    return (d.x).toString() + "," + (Number(d.y) - 7).toString() + " " +
                        (Number(d.x) + 6).toString() + "," + (Number(d.y) - 3.5).toString() + " " +
                        (Number(d.x) + 6).toString() + "," + (Number(d.y) + 3.5).toString() + " " +
                        (d.x).toString() + "," + (Number(d.y) + 7).toString() + " " +
                        (Number(d.x) - 6).toString() + "," + (Number(d.y) + 3.5).toString() + " " +
                        (Number(d.x) - 6).toString() + "," + (Number(d.y) - 3.5).toString();
                });

            nodeRect
                .attr("x", function (d) {
                    return d.x - 6;
                })
                .attr("y", function (d) {
                    return d.y - 6;
                });

            nodetext
                .attr("x", function (d) {
                    for (i = 0; i < exp_name.length; i++) {
                        if (d.title === exp_name[i]) {
                            var font = getCookie('changeFontSize')
                            return d.x - (Number(font) * (exp_name[i].length - 4) * 3.5);
                        }
                    }
                    var size = d3.select('#' + d.id).select('text').style('font-size');
                    if (size == '0.4em') {
                        return d.x + 8;
                    } else {
                        d3.select('#' + d.id).select('text').select('.upNum').attr('y', d.y - 10).attr('x', d.x - 35);
                        d3.select('#' + d.id).select('text').select('.downNum').attr('y', d.y + 5).attr('x', d.x - 35);
                        d3.select('#' + d.id).select('text').select('.updownNum').attr('y', d.y + 20).attr('x', d.x - 35);
                        return d.x - 35;
                    }
                })                   //文本的坐标
                .attr("y", function (d) {
                    return d.y + 3
                })
        }


        function dragstarted(d) {
            if (!d3.event.active) simulation.alphaTarget(0.8).restart();
            d.fx = d.x;
            d.fy = d.y;

        }

        function dragged(d) {
            d3.selectAll('.genenumber').remove();
            d.fx = d3.event.x;
            d.fy = d3.event.y;
        }

        function dragended(d) {
            if (!d3.event.active) simulation.alphaTarget(0);
            d.fx = d3.event.x;
            d.fy = d3.event.y;
        }

        lbstyle(getCookie('lbstyle'));                  // set recorded showing options
        changeFontSize(getCookie('changeFontSize'));
        if (getCookie('geneNumbers') == 2) {
            geneNumbers(getCookie('geneNumbers'));
        }
        if (!(redrawdata.length > 0) && getCookie('SumNodes') == 2) {
            SumNodes(getCookie('SumNodes'));
        }
    }

    //----------------------------------------------------------GUI---------------------------------------------------------------   
    var FizzyText = function () {

        this.LabelStyle = '1';
        this.genenum = '1';
        this.sumnodes = '1';
        this.Show_Detail = function () {
            ShowPathwayDetailTable();
        };
        this.Show_GoDetail = function () {
            ShowGoDetailTable();
        };
        this.Data_Table = function () {
            ShowDataTable();
        }
        this.Save_AS_SVG = function () {
            save('svg');
        };
        this.Save_AS_PNG = function () {
            save('png');
        }
        this.Save_AS_JPG = function () {
            save('jpg');
        }
        this.Save_On_Line = function () {
            saveonline();
        }

        this.color0 = mycolor[0] || defaultColors[0]; // CSS string
        this.color1 = mycolor[1] || defaultColors[1]; // RGB array
        this.color2 = mycolor[2] || defaultColors[2];
        this.color3 = mycolor[3] || defaultColors[3];
        this.color4 = mycolor[4] || defaultColors[4];
        this.color5 = mycolor[5] || defaultColors[5];
        this.color6 = mycolor[6] || defaultColors[6];
        this.color7 = mycolor[7] || defaultColors[7];
        this.color8 = mycolor[8] || defaultColors[8];
        this.color9 = mycolor[9] || defaultColors[9];
        this.colorUp = mycolor[filenum] || '#FF0033';
        this.colorDown = mycolor[filenum + 1] || '#0066cc';
        this.colorUpDown = mycolor[filenum + 2] || '#E9F01D';
        this.colorSum = mycolor[filenum + 3] || '#bfffc9';
        this.colorRedraw = mycolor[filenum + 4] || '#ffffff';
        this.Change_Color = function () {
            ChangeNodeColor();
        }

        this.upShape = myshape[0] || '1';
        this.downShape = myshape[1] || '1';
        this.udShape = myshape[2] || '1';
        this.groupShape = myshape[3] || '1';
        this.rdShape = myshape[4] || '2';
        this.Change_Shape = function () {
            ChangeNodeShape();
        }

        this.Node_Font_Size = '1';
    };
    window.onload = function () {
        var text = new FizzyText();
        var gui = new dat.GUI({width: 360, name: 'datGuiControlPanel'});    // define the gui and set width as 360


        var f1 = gui.addFolder('Graph Control');   //add folder for gui
        var mylabel = f1.add(text, 'LabelStyle', {HideLabel: 1, ShowLabel: 2});   //the children of the folder
        mylabel.onChange(function (value) {
            lbstyle(value);
            setCookie('lbstyle', value, 1);
        });  // add event

        var genenum = f1.add(text, 'genenum', {Hide: 1, Show: 2}).name('Gene Numbers');	// show overlapping gene numbers
        genenum.onChange(function (value) {
            geneNumbers(value)
        });

        if (redrawdata.length == 0) {
            var sumnode = f1.add(text, 'sumnodes', {No: 1, Yes: 2}).name('Summarize Group Statistics');
            sumnode.onChange(function (value) {
                SumNodes(value)
            });
        }

        var f2 = gui.addFolder('Color');
        for (var i = 0; i < filenum; i++) {
            f2.addColor(text, 'color' + i).name(exp_name[i].substring(4))
                .onFinishChange(function (value) {
                    updateColorSelector()
                });
        }
        f2.addColor(text, 'colorUp').name('Up-regulated')
            .onFinishChange(function (value) {
                updateColorSelector()
            });
        f2.addColor(text, 'colorDown').name('Down-regulated')
            .onFinishChange(function (value) {
                updateColorSelector()
            });
        f2.addColor(text, 'colorUpDown').name('Up/down-regulated')
            .onFinishChange(function (value) {
                updateColorSelector()
            });
        f2.addColor(text, 'colorSum').name('Node Group')
            .onFinishChange(function (value) {
                updateColorSelector()
            });
        f2.addColor(text, 'colorRedraw').name('Redraw')
            .onFinishChange(function (value) {
                updateColorSelector()
            });
        f2.add(text, 'Change_Color').name('Change Color');

        var f3 = gui.addFolder('Shape');
        var upshape = f3.add(text, 'upShape', {
            Circle: 1,
            Rectangle: 2,
            Triangle: 3,
            Star: 4,
            Hexagon: 5
        }).name('Up-regulated');
        upshape.onChange(function (value) {
            updateShapeSelector('shapeselectorUp', value)
        });
        var downshape = f3.add(text, 'downShape', {
            Circle: 1,
            Rectangle: 2,
            Triangle: 3,
            Star: 4,
            Hexagon: 5
        }).name('Down-regulated');
        downshape.onChange(function (value) {
            updateShapeSelector('shapeselectorDown', value)
        });
        var udshape = f3.add(text, 'udShape', {
            Circle: 1,
            Rectangle: 2,
            Triangle: 3,
            Star: 4,
            Hexagon: 5
        }).name('Up/down-regulated');
        udshape.onChange(function (value) {
            updateShapeSelector('shapeselectorUpDown', value)
        });
        var groupshape = f3.add(text, 'groupShape', {
            Circle: 1,
            Rectangle: 2,
            Triangle: 3,
            Star: 4,
            Hexagon: 5
        }).name('Node Group');
        groupshape.onChange(function (value) {
            updateShapeSelector('shapeselectorSum', value)
        });
        var redrawshape = f3.add(text, 'rdShape', {
            Circle: 1,
            Rectangle: 2,
            Triangle: 3,
            Star: 4,
            Hexagon: 5
        }).name('Redraw');
        redrawshape.onChange(function (value) {
            updateShapeSelector('shapeselectorRedraw', value)
        });
        f3.add(text, 'Change_Shape').name('Change Shape');

        var f4 = gui.addFolder('Font Size');
        var fontSize = f4.add(text, 'Node_Font_Size', {
            1: 1,
            2: 2,
            3: 3,
            4: 4,
            5: 5,
            6: 6,
            7: 7,
            8: 8
        }).name('Experiment Node');
        fontSize.onChange(function (value) {
            changeFontSize(value)
        });

        if (species !== "notselected" ) {
            if (referenceDb !== "notselected") {
                var f5 = gui.addFolder('All Gene Details');
                f5.add(text, 'Show_Detail').name('Pathway Details');
                f5.add(text, 'Show_GoDetail').name('Gene Ontology Details');
            }
            
        }

        var f6 = gui.addFolder('SAVE');
        f6.add(text, 'Save_AS_SVG').name('Save as SVG File');
        f6.add(text, 'Save_AS_PNG').name('Save as PNG File');
        f6.add(text, 'Save_AS_JPG').name('Save as JPG File');

    };

    function geneNumbers(value) {			// show overlapping gene numbers
        setCookie('geneNumbers', value, 1);
        d3.select('#svgZoomContainer').selectAll('.genenumber').remove();
        if (value == '1') {
            return;
        }
        var flg = {};
        var dxy = null;
        if (getCookie('gnXY') != null) {
            dxy = getCookie('gnXY').split('|')
        }
        ;
        var xyRcd = '';
        for (i = 0; i < nodes.length; i++) {
            var id = nodes[i].id;
            var rst = 0;
            if (id in overlaps) {
                if (overlaps[id] in flg) {
                    continue;
                }
                rst = grpGeneNum[overlaps[id]]['total'];
                flg[overlaps[id]] = 1;
            } else {
                if (id in expGroup) {
                    continue;
                }
                if (nodes[i].target in flg) {
                    continue;
                }
                rst = grpGeneNum[nodes[i].target]['total'];
                flg[nodes[i].target] = 1;
            }

            var loc = [0, 0];
            if (typeof dxy != "undefined" && dxy != null && dxy.length != null && dxy.length > 0) {
                loc = dxy[0].split('_');
                dxy.shift();
            }
            var dx = d3.select('#' + nodes[i].id).selectAll("circle").attr('cx') || loc[0];
            var dy = d3.select('#' + nodes[i].id).selectAll("circle").attr('cy') || loc[1];
            var gn = d3.select('#svgZoomContainer').append('g')
                .attr('class', 'genenumber');
            xyRcd += dx + '_' + dy + '|';

            gn.append('rect')
                .attr('x', dx - (rst.toString().length * 3.5) - 10)
                .attr('y', dy - 66)
                .attr('width', rst.toString().length * 10 + 30)
                .attr('height', '30')
                .style('position', 'absolute')
                .attr('fill', '#ffffff')
                .style('opacity', '0.9')
                .attr('rx', '10')
                .attr('ry', '10');
            gn.append('text')
                .attr('x', dx - (rst.toString().length * 3.5))
                .attr('y', dy - 40)
                .style('position', 'absolute')
                .style('font-size', '2em')
                .style('font-family', 'sans-serif')
                .attr('fill', '#52504f')
                .text(rst);
        }
        setCookie('gnXY', xyRcd, 1);
    }

    function unSum(sumShape, myNode) {				// change Sum nodes back to original
        d3.select('#' + myNode.id).select('circle').style('display', '');
        d3.select('#' + myNode.id).select('rect').style('display', '');
        d3.select('#' + myNode.id).selectAll('polygon').style('display', '');

        var originalShape = '';
        if (myNode.group == 1) {
            var originalShape = document.getElementsByName('shapeselectorUp')[0].value;
        } else if (myNode.group == 2) {
            var originalShape = document.getElementsByName('shapeselectorDown')[0].value;
        } else if (myNode.group == 3) {
            var originalShape = document.getElementsByName('shapeselectorUpDown')[0].value;
        }

        switch (sumShape) {
            case '1':
                if (originalShape == 1) {
                    d3.select('#' + myNode.id).select('circle')
                        .attr('r', 6)
                        .attr('fill', function () {
                            if (myNode.group == 1) {
                                return document.getElementsByName('colorselectorUp')[0].value;
                            } else if (myNode.group == 2) {
                                return document.getElementsByName('colorselectorDown')[0].value;
                            } else if (myNode.group == 3) {
                                return document.getElementsByName('colorselectorUpDown')[0].value;
                            }
                        });
                } else {
                    d3.select('#' + nodes[i].id).select('circle').style('display', 'none');
                }
                break;
            case '2':
                if (originalShape == 2) {
                    d3.select('#' + myNode.id).select('rect')
                        .style('display', '')
                        .attr('fill', function () {
                            if (myNode.group == 1) {
                                return document.getElementsByName('colorselectorUp')[0].value;
                            } else if (myNode.group == 2) {
                                return document.getElementsByName('colorselectorDown')[0].value;
                            } else if (myNode.group == 3) {
                                return document.getElementsByName('colorselectorUpDown')[0].value;
                            }
                        })
                        .attr('width', 12)
                        .attr('height', 12)
                        .attr('x', myNode.x - 6)
                        .attr('y', myNode.y - 6);
                } else {
                    d3.select('#' + myNode.id).select('rect').style('display', 'none');
                }
                break;
            case '3':
            case '4':
            case '5':
                if (originalShape == 3) {
                    d3.select('#' + myNode.id).select('polygon')
                        .style('display', '')
                        .attr('fill', function () {
                            if (myNode.group == 1) {
                                return document.getElementsByName('colorselectorUp')[0].value;
                            } else if (myNode.group == 2) {
                                return document.getElementsByName('colorselectorDown')[0].value;
                            } else if (myNode.group == 3) {
                                return document.getElementsByName('colorselectorUpDown')[0].value;
                            }
                        })
                        .attr("points", function (d) {
                            return (myNode.x).toString() + "," + (Number(myNode.y) - 7).toString() + " " +
                                (Number(myNode.x) - 7).toString() + "," + (Number(myNode.y) + 7).toString() + " " +
                                (Number(myNode.x) + 7).toString() + "," + (Number(myNode.y) + 7).toString();
                        });
                } else {
                    d3.select('#' + myNode.id).select('polygon').style('display', 'none');
                }
                break;
        }

        d3.select('#' + myNode.id).select('text')
            .style('font-size', '0.4em')
            .style('fill', 'rgb(0, 0, 0)')
            .style('display', 'none')
            .style("font-weight", '')
            .attr('x', myNode.x + 8)
            .attr('y', myNode.y + 3)
            .text(myNode.id);
    }

    function SumNodes(value) {			// summarize each group of nodes to one node, show numbers of up-regulated&down-regulated
        setCookie('SumNodes', value, 1);
        // get selected shape of sum node
        var sumShape = document.getElementsByName('shapeselectorSum')[0].value;
        var sumColor = document.getElementsByName('colorselectorSum')[0].value;

        if (value == 1) {				// if not summarize
            $('.nodes').show();		// show all nodes and links
            $('line').show();
            for (i = 0; i < nodes.length; i++) {
                if (nodes[i].id.substring(0, 3) != 'id\_') {
                    continue;
                }		// ignore exp nodes
                switch (sumShape) {
                    case '1':
                        if (d3.select('#' + nodes[i].id).select('circle').attr('r') > 40) {
                            unSum(sumShape, nodes[i]);
                        }
                        break;
                    case '2':
                        if (d3.select('#' + nodes[i].id).select('rect').attr('width') > 40) {
                            unSum(sumShape, nodes[i]);
                        }
                        break;
                    case '3':
                    case '4':
                    case '5':
                        if (d3.select('#' + nodes[i].id).select('polygon').attr('points') != null &&
                            d3.select('#' + nodes[i].id).select('polygon').attr('points').split(' ')[0].split(',')[1] < nodes[i].y - 40) {
                            unSum(sumShape, nodes[i]);
                        }
                        break;
                }
            }
            return;
        }
        // when select to summarize node
        var sumnodes = {};
        for (i = 0; i < nodes.length; i++) {
            if (nodes[i].id.substring(0, 3) != 'id\_') {
                continue;
            }		// ignore experiment node

            var grp = '';				     // identity current node's group & node number
            if (overlaps[nodes[i].id] != null) {
                grp = overlaps[nodes[i].id];
            } else {
                grp = nodes[i].target;
            }

            if (grp in sumnodes) {			// if this group already showing a summarizing node
                d3.select('#' + nodes[i].id).style('display', 'none');
                d3.selectAll('#link_' + nodes[i].id).style('display', 'none');
            } else {
                sumnodes[grp] = 1;
                // get color for up/down/updown
                var ucolor = document.getElementsByName('colorselectorUp')[0].value;
                var dcolor = document.getElementsByName('colorselectorDown')[0].value;
                var udcolor = document.getElementsByName('colorselectorUpDown')[0].value;

                var num = grpGeneNum[grp]['total'];			// get gene numbers
                var up = grpGeneNum[grp]['up'] || 0;
                var down = grpGeneNum[grp]['down'] || 0;
                var updown = grpGeneNum[grp]['updown'] || 0;

                var radius = 0;				// bigger the num, bigger the circle
                if (num < 20) {
                    radius = num * 0.5 + 40
                } else if (num < 40) {
                    radius = num * 0.35 + 50;
                } else if (num < 80) {
                    radius = num * 0.1 + 64;
                } else if (num < 160) {
                    radius = num * 0.02 + 72;
                } else {
                    radius = num * 0.001 + 75.2;
                }

                d3.select('#' + nodes[i].id).select('circle').style('display', 'none');
                d3.select('#' + nodes[i].id).select('rect').style('display', 'none');
                d3.select('#' + nodes[i].id).selectAll('polygon').style('display', 'none');

                var txt = d3.select('#' + nodes[i].id).select('text')
                    .style('display', '')
                    .style('font-size', '1em')
                    .style("font-weight", 'bold')
                    .text('')
                txt.append('tspan')
                    .attr('x', nodes[i].x - 35)
                    .attr('y', nodes[i].y - 10)
                    .style('fill', ucolor)
                    .attr('class', 'upNum')
                    .text('Up: \u00A0\u00A0\u00A0\u00A0\u00A0\u00A0\u00A0' + up);
                txt.append('tspan')
                    .attr('x', nodes[i].x - 35)
                    .attr('y', nodes[i].y + 5)
                    .style('fill', dcolor)
                    .attr('class', 'downNum')
                    .text('Down:\u00A0\u00A0\u00A0' + down);
                txt.append('tspan')
                    .attr('x', nodes[i].x - 35)
                    .attr('y', nodes[i].y + 20)
                    .style('fill', udcolor)
                    .attr('class', 'updownNum')
                    .text('U/D: \u00A0\u00A0\u00A0\u00A0\u00A0\u00A0' + updown);

                switch (sumShape) {			// expand shape size based on shape selection
                    case '1':
                        d3.select('#' + nodes[i].id).select('circle')
                            .style('display', '')
                            .attr('fill', sumColor)
                            .attr('r', radius)
                            .attr('cx', nodes[i].x)
                            .attr('cy', nodes[i].y);
                        break;
                    case '2':
                        d3.select('#' + nodes[i].id).select('rect')
                            .style('display', '')
                            .attr('fill', sumColor)
                            .attr('width', 2 * radius)
                            .attr('height', 2 * radius)
                            .attr('x', nodes[i].x - radius)
                            .attr('y', nodes[i].y - radius);
                        break;
                    case '3':
                        d3.select('#' + nodes[i].id).select('polygon')
                            .style('display', '')
                            .attr('fill', sumColor)
                            .attr("points", function (d) {
                                return (nodes[i].x).toString() + "," +
                                    (Number(nodes[i].y) - (radius) * 1.15).toString() + " " +
                                    (Number(nodes[i].x) - (radius) * 1.15).toString() + "," +
                                    (Number(nodes[i].y) + (radius) * 1.15).toString() + " " +
                                    (Number(nodes[i].x) + (radius) * 1.15).toString() + "," +
                                    (Number(nodes[i].y) + (radius) * 1.15).toString();
                            });
                        break;
                    case '4':
                        d3.select('#' + nodes[i].id).select('polygon')
                            .style('display', '')
                            .attr('fill', sumColor)
                            .attr("points", function (d) {
                                return (nodes[i].x).toString() + "," +
                                    (Number(nodes[i].y) - (radius) * 1.15).toString() + " " +
                                    (Number(nodes[i].x) + (radius) * 0.5).toString() + "," +
                                    (Number(nodes[i].y) - (radius) * 0.5).toString() + " " +
                                    (Number(nodes[i].x) + (radius) * 1.15).toString() + "," +
                                    (Number(nodes[i].y) - (radius) * 0.5).toString() + " " +
                                    (Number(nodes[i].x) + (radius) * 0.65).toString() + "," +
                                    (Number(nodes[i].y) + (radius) * 0.35).toString() + " " +
                                    (Number(nodes[i].x) + (radius) * 0.85).toString() + "," +
                                    (Number(nodes[i].y) + (radius) * 1.15).toString() + " " +
                                    (nodes[i].x).toString() + "," +
                                    (Number(nodes[i].y) + (radius) * 0.6).toString() + " " +
                                    (Number(nodes[i].x) - (radius) * 0.85).toString() + "," +
                                    (Number(nodes[i].y) + (radius) * 1.15).toString() + " " +
                                    (Number(nodes[i].x) - (radius) * 0.65).toString() + "," +
                                    (Number(nodes[i].y) + (radius) * 0.35).toString() + " " +
                                    (Number(nodes[i].x) - (radius) * 1.15).toString() + "," +
                                    (Number(nodes[i].y) - (radius) * 0.5).toString() + " " +
                                    (Number(nodes[i].x) - (radius) * 0.5).toString() + "," +
                                    (Number(nodes[i].y) - (radius) * 0.5).toString();
                            });
                        break;
                    case '5':
                        d3.select('#' + nodes[i].id).select('polygon')
                            .style('display', '')
                            .attr('fill', sumColor)
                            .attr("points", function (d) {
                                return (nodes[i].x).toString() + "," +
                                    (Number(nodes[i].y) - (radius) * 1.15).toString() + " " +
                                    (Number(nodes[i].x) + radius).toString() + "," +
                                    (Number(nodes[i].y) - (radius) * 0.6).toString() + " " +
                                    (Number(nodes[i].x) + radius).toString() + "," +
                                    (Number(nodes[i].y) + (radius) * 0.6).toString() + " " +
                                    (nodes[i].x).toString() + "," +
                                    (Number(nodes[i].y) + (radius) * 1.15).toString() + " " +
                                    (Number(nodes[i].x) - radius).toString() + "," +
                                    (Number(nodes[i].y) + (radius) * 0.6).toString() + " " +
                                    (Number(nodes[i].x) - radius).toString() + "," +
                                    (Number(nodes[i].y) - (radius) * 0.6).toString();
                            });
                        break;
                }
            }
        }
    }

    // save svg graph to svg or png
    function download(source, filename, type) {
        var file = new Blob([source], {type: type});
        if (window.navigator.msSaveOrOpenBlob) { // IE10+
            window.navigator.msSaveOrOpenBlob(file, filename);
        } else { // Others
            if (type == 'svg') {
                var a = document.createElement("a");
                url = URL.createObjectURL(file);
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                setTimeout(function () {
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                }, 0);
            } else if (type === 'png' || type === 'jpg') {
                var url = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(source);
                var image = new Image;
                image.src = url;

                var canvas = document.createElement("canvas");
                canvas.width = screen.width;  //1024;
                canvas.height = screen.height;//900;
                var context = canvas.getContext("2d");
                context.fillStyle = "#FFF";
                context.fillRect(0, 0, canvas.width, canvas.height);

                image.onload = function () {
                    context.drawImage(image, 0, 0);
                    var a = document.createElement("a");
                    a.download = filename;
                    if (type === 'png')
                        a.href = canvas.toDataURL("image/png");
                    else
                        a.href = canvas.toDataURL("image/jpeg");

                    a.click();
                }
            }
        }
    }

    function save(type) {
        var filename = '';
        //get svg element.
        var svg = document.getElementById("svg");
        //get svg source.
        var serializer = new XMLSerializer();
        var source = serializer.serializeToString(svg);
        var systime = new Date().toLocaleTimeString();

        if (type === 'svg' || type === 'png')
            filename = type.toString().toUpperCase() + '_' + md5(systime) + '.' + type.toString();

        else if (type === 'jpg') filename = 'JPG_' + md5(systime) + '.jpeg';

        download(source, filename, type);
    }

    function saveonline() {
        window.open("https://image.online-convert.com/convert-to-png");
    }

    function ChangeNodeColor() {
        document.getElementById("changeColorForm").submit();
    }

    function updateColorSelector() {
        var elements = document.querySelectorAll("input[type=text]")
        for (var i = 0; i < exp_name.length; i++) {
            document.getElementsByName('colorselector' + i)[0].value = elements[i].value;
        }

        document.getElementsByName('colorselectorUp')[0].value = elements[exp_name.length].value;
        document.getElementsByName('colorselectorDown')[0].value = elements[exp_name.length + 1].value;
        document.getElementsByName('colorselectorUpDown')[0].value = elements[exp_name.length + 2].value;
        document.getElementsByName('colorselectorSum')[0].value = elements[exp_name.length + 3].value;
        document.getElementsByName('colorselectorRedraw')[0].value = elements[exp_name.length + 4].value;
    }


    function changeFontSize(val) {
        setCookie('changeFontSize', val, 1);
        for (j = 0; j < exp_name.length; j++) {
            var el = d3.select("#" + exp_name[j]).selectAll("text");
            el.style("font-size", val + "em")
            el.attr("x", function (d) {
                var len = d.id.length;
                return d.x - (val * 3.5 * len);
            })
                .attr("y", function (d) {
                    return d.y + (val * 3);
                })
        }
    }

    function ChangeNodeShape() {
        document.getElementById("changeShapeForm").submit();
    }

    function updateShapeSelector(type, value) {
        document.getElementsByName(type)[0].value = value;
    }

    function ShowPathwayDetailTable() {
        if (!customData) {
            $("body").addClass('loading');
            $.post('mysql_query2.php', {mdata: mydata, referenceDb: referenceDb, species: species}, function (result) {
                if (!$("#panelGoTable").hasClass("hidden")) {
                    $('#panelGoTable').find('table').DataTable().clear().destroy();
                    var tablecontainer = $('#Gene_Go_Info');
                    tablecontainer.html('');
                    $("#panelGoTable").addClass("hidden"); //hide the go table, only show one table

                }
                if ($("#panelPathwayTable").hasClass("hidden")) {
                    $("#panelPathwayTable").removeClass("hidden");
                } else {
                    $('#panelPathwayTable').find('table').DataTable().clear().destroy();
                }

                var tablecontainer = $('#Gene_Pathway_Info');
                tablecontainer.html('');

                var tableheader = $('#Gene_PathWay_Header');
                tableheader.html('');
                var headerdata = '<tr><th>ID</th>';
                headerdata += '<th>Description</th>';
                headerdata += '<th>Pathway</th>';
                //console.log(species);
                $.map(exp_name, function (exp) {
                    headerdata += '<td>' + exp + '</td>';
                })
                headerdata += '<th style="width:90px;"><input type="checkbox" class="Gene_Pathway_SelectAll"></input></th></tr>';
                tableheader.append(headerdata);

                if (result === "0 results" || result === "[]" || result === "No Result[]") {
                    tablecontainer.html('<h1>No Results</h1>');
                } else {
                    // get data from database
                    //console.log(species);
                    var data = jQuery.parseJSON(result);        //decode jason array
                    //console.log(result);
                    var tabledata = '';
                    //console.log(data);
                    //console.log(species);
                    $.each(data, function (key, value) {
                    tabledata += '<tr>';
                    tabledata += '<td><a href="#" class="keggID" id="' + value.ID + '">' + value.ID + '</a></td>';
                    tabledata += '<td>' + value.description + '</td>';

                    if (value.urls != null) {
                        if (value.urls.trim() == '-') {
                            tabledata += '<td>' + value.pathway + '</td>';
                        }
                        else {
                            //Split url string by \n which is set in mysql_query1.php mysql select statement
                            var urllist = value.urls.split("\n");
                            var pathdesclist = value.pathway.split("\n");
                            var urlstring = "<td>";
                            var length = urllist.length;
                            //console.log(length);

                        // create url string for each pathway and put all pathways in one cell
                            for (var j = 0; j < length; j++) {
                                urlstring += '<a href="';
                                urlstring += urllist[j]; 
                                urlstring += '" target="_blank">'; 
                                urlstring += pathdesclist[j]; 
                                urlstring += '</a>';
                                if (j == length - 1){
							    	urlstring += "";
							    } else {
							    	urlstring += ' | ';
							    }   
                            }
                            urlstring += "</td>";
                            tabledata += urlstring;
                        }
                    }
                    else {
                        tabledata += '<td>' + 'N/A' + '</td>';
                    }
                        //HERE LOG FOLD CHANGE numbers should be here instead of 1 and 2 or n/a
                        $.map(exp_name, function (exp, index) {
                            if (UpDownDict.Experiment[index][value.ID.toLowerCase()] === null || UpDownDict.Experiment[index][value.ID.toLowerCase()] === undefined)
                                if (UpDownDict.Experiment[index][value.ID.toLowerCase()] === null || UpDownDict.Experiment[index][value.ID.toLowerCase()] === undefined)
                                    tabledata += '<td>n/a</td>';
                                else
                                    tabledata += '<td>' + UpDownDict.Experiment[index][value.ID.toLowerCase()] + '</td>';
                            else
                                tabledata += '<td>' + UpDownDict.Experiment[index][value.ID.toLowerCase()] + '</td>';
                        })
                        tabledata += '<td><input class="chkbox" type="checkbox" value="' + value.ID + '"></input></td>';
                        tabledata += '</tr>';
                });
                    tablecontainer.append(tabledata);
                    $('#panelPathwayTable').find('table').DataTable({
                        "aLengthMenu": [[10, 20, 50, 100, 200, 500, -1], [10, 20, 50, 100, 200, 500, "All"]],
                        "pageLength": 50
                    });
                }
                $("body").removeClass('loading');
            });
        } else {
            $body = $("body");
            $body.addClass("loading");

            if (!$("#panelGoTable").hasClass("hidden")) {
                $('#panelGoTable').find('table').DataTable().clear().destroy();
                var tablecontainer = $('#Gene_Go_Info');
                tablecontainer.html('');
                $("#panelGoTable").addClass("hidden"); //hide the go table, only show one table

            }
            if ($("#panelPathwayTable").hasClass("hidden")) {
                $("#panelPathwayTable").removeClass("hidden");
            } else {
                $('#panelPathwayTable').find('table').DataTable().clear().destroy();
            }
            var tablecontainer = $('#Gene_Pathway_Info');
            tablecontainer.html('');

            var tableheader = $('#Gene_PathWay_Header');
            tableheader.html('');
            var headerdata = '<tr><th>ID</th>';
            headerdata += '<th>Description</th>';
            headerdata += '<th>Pathway</th>';
            $.map(exp_name, function (exp) {
                headerdata += '<td>' + exp + '</td>';
            })
            headerdata += '<th style="width:90px;"><input type="checkbox" class="Gene_Pathway_SelectAll"></input></th></tr>';
            tableheader.append(headerdata);

            var tabledata = '';
            for (var key in usrDatabase) {
                for (var myGo in usrDatabase[key]['GO']) {
                    tabledata += '<tr>';
                    tabledata += '<td><a href="#" class="keggID" id="' + key + '">' + key + '</a></td>';
                    tabledata += '<td>' + usrDatabase[key]['description'] + '</td>';
                    tabledata += '<td>' + usrDatabase[key]['GO'][myGo]['pathway'] + '</td>';

                    $.map(exp_name, function (exp, index) {
                        if (UpDownDict.Experiment[index][key.toLowerCase()] === null || UpDownDict.Experiment[index][key.toLowerCase()] === undefined)
                            if (UpDownDict.Experiment[index][key.toLowerCase()] === null || UpDownDict.Experiment[index][key.toLowerCase()] === undefined)
                                tabledata += '<td>n/a</td>';
                            else
                                tabledata += '<td>' + UpDownDict.Experiment[index][key.toLowerCase()] + '</td>';
                        else
                            tabledata += '<td>' + UpDownDict.Experiment[index][key.toLowerCase()] + '</td>';
                    })


                    tabledata += '<td><input class="chkbox" type="checkbox" value="' + key + '"></input></td>';
                    tabledata += '</tr>';
                }
            }
            tablecontainer.append(tabledata);

            $("body").removeClass('loading');
        }
        setTimeout(function () {
            $('#Gene_PathWay_Header').focus();
        }, 5000);
        setTimeout(function () {
            $('#Gene_PathWay_Header').focus();
        }, 20000);
    }

    function ShowGoDetailTable() {						// show Gene GO details
        if (!customData) {
            $("body").addClass('loading');
            $.post('mysql_query3.php', {mdata: mydata, referenceDb: referenceDb, species: species}, function (result) {
                if (!$("#panelPathwayTable").hasClass("hidden")) {
                    $('#panelPathwayTable').find('table').DataTable().clear().destroy();
                    var tablecontainer = $('#Gene_Pathway_Info');
                    tablecontainer.html('');
                    $("#panelPathwayTable").addClass("hidden"); //hide, only show one table
                }
                if ($("#panelGoTable").hasClass("hidden")) {
                    $("#panelGoTable").removeClass("hidden");
                } else {
                    $('#panelGoTable').find('table').DataTable().clear().destroy();
                }

                var tablecontainer = $('#Gene_Go_Info');
                tablecontainer.html('');
                var tableheader = $('#Gene_Ontology_Header');
                tableheader.html('');
                var headerdata = '<tr>';
                headerdata += '<th>ID</th>';
                headerdata += '<th>GO ID</th>';
                headerdata += '<th>GO term</th>';
                headerdata += '<th>GO category</th>';
                $.map(exp_name, function (exp) {
                    headerdata += '<td>' + exp + '</td>';
                });
                headerdata += '<th style="width:90px;"><input type="checkbox" class="Gene_Go_SelectAll"></input></th></tr>';
                tableheader.append(headerdata);

                if (result === "0 results" || result === []) {
                    tablecontainer.html('<h1>No Results</h1>');
                } else {
                    var data = jQuery.parseJSON(result);                  //decode jason array
                    var tabledata = '';
                    $.each(data, function (key, value) {
                        tabledata += '<tr>';
                        tabledata += '<td><a href="#" class="keggID" id="' + value.ID + '">' + value.ID + '</a></td>';
                        tabledata += '<td>' + value.go_id + '</td>';
                        tabledata += '<td>' + value.go_term + '</td>';
                        tabledata += '<td>' + value.go_category + '</td>';
                        $.map(exp_name, function (exp, index) {
                            if (UpDownDict.Experiment[index][value.ID.toLowerCase()] === null || UpDownDict.Experiment[index][value.ID.toLowerCase()] === undefined)
                                tabledata += '<td>n/a</td>';
                            else
                                tabledata += '<td>' + UpDownDict.Experiment[index][value.ID.toLowerCase()] + '</td>';
                        })

                        tabledata += '<td><input class="chkbox" type="checkbox" value="' + value.ID + '"></input></td>';
                        tabledata += '</tr>';
                    });
                    tablecontainer.append(tabledata);
                    
                    var tabl = $('#panelGoTable').find('table').DataTable({
                        "aLengthMenu": [[10, 20, 50, 100, 200, 500, -1], [10, 20, 50, 100, 200, 500, "All"]],
                        "pageLength": 50,
                        
                    });
                }
                $("body").removeClass('loading');
            });
        } else {
            $("body").addClass('loading');
            if (!$("#panelPathwayTable").hasClass("hidden")) {
                $('#panelPathwayTable').find('table').DataTable().clear().destroy();
                var tablecontainer = $('#Gene_Pathway_Info');
                tablecontainer.html('');
                $("#panelPathwayTable").addClass("hidden"); //hide, only show one table

            }
            if ($("#panelGoTable").hasClass("hidden")) {
                $("#panelGoTable").removeClass("hidden");
            } else {
                $('#panelGoTable').find('table').DataTable().clear().destroy();
            }

            var tablecontainer = $('#Gene_Go_Info');
            tablecontainer.html('');
            var tableheader = $('#Gene_Ontology_Header');
            tableheader.html('');
            var headerdata = '<tr>';
            headerdata += '<th>ID</th>';
            headerdata += '<th>GO ID</th>';
            headerdata += '<th>GO term</th>';
            headerdata += '<th>GO category</th>';
            $.map(exp_name, function (exp) {
                headerdata += '<td>' + exp + '</td>';
            });
            headerdata += '<th style="width:90px;"><input type="checkbox" class="Gene_Go_SelectAll"></input></th></tr>';
            tableheader.append(headerdata);

            var tabledata = '';
            for (var key in usrDatabase) {
                for (var myGo in usrDatabase[key]['GO']) {
                    tabledata += '<tr>';
                    tabledata += '<td><a href="#" class="keggID" id="' + key + '">' + key + '</a></td>';
                    tabledata += '<td>' + usrDatabase[key]['GO'][myGo]['go_id'] + '</td>';
                    tabledata += '<td>' + usrDatabase[key]['GO'][myGo]['go_term'] + '</td>';
                    tabledata += '<td>' + usrDatabase[key]['GO'][myGo]['go_category'] + '</td>';
                    $.map(exp_name, function (exp, index) {
                        if (UpDownDict.Experiment[index][key.toLowerCase()] === null || UpDownDict.Experiment[index][key.toLowerCase()] === undefined)
                            tabledata += '<td>n/a</td>';
                        else
                            tabledata += '<td>' + UpDownDict.Experiment[index][key.toLowerCase()] + '</td>';
                    })

                    tabledata += '<td><input class="chkbox" type="checkbox" value="' + key + '"></input></td>';
                    tabledata += '</tr>';
                }
            }
            tablecontainer.append(tabledata);
            $('#panelGoTable').find('table').DataTable({
                "aLengthMenu": [[10, 20, 50, 100, 200, 500, -1], [10, 20, 50, 100, 200, 500, "All"]],
                "pageLength": 50
            });
            
            $("body").removeClass('loading');
        }
        setTimeout(function () {
            $('#Gene_Ontology_Header').focus();
        }, 5000);
        setTimeout(function () {
            $('#Gene_Ontology_Header').focus();
        }, 20000);
    }

    function ShowDataTable() {
        window.open('datatable2.html', '_blank', false)
    }

    

    $(document).on('click', "input.Gene_Pathway_SelectAll", function (e) {
        $('#Gene_Pathway_Table :checkbox').prop('checked', this.checked);
    });

    $("#Gene_Pathway_Table :checkbox").click(function () {
        $("#Gene_Pathway_SelectAll").prop("checked", $("#Gene_Pathway_Table tbody :checkbox:not(:checked)").length === 0);
    });

    $("#Gene_Pathway_Table").on('click', '.chkbox', function (e) {
        var $chkboxes = $('.chkbox');
        if (!lastChecked) {
            lastChecked = this;
            return;
        }

        if (e.shiftKey) {
            var start = $chkboxes.index(this);
            var end = $chkboxes.index(lastChecked);

            $chkboxes.slice(Math.min(start, end), Math.max(start, end) + 1).prop('checked', lastChecked.checked);

        }

        lastChecked = this;
    });

    $(document).on('click', "input.Gene_Go_SelectAll", function (e) {
        $('#Gene_Ontology_Table :checkbox').prop('checked', this.checked);
    });

    $("#Gene_Ontology_Table :checkbox").click(function () {
        $("#Gene_Go_SelectAll").prop("checked", $("#Gene_Ontology_Table tbody :checkbox:not(:checked)").length === 0);
    });


    $("#Gene_Ontology_Table").on('click', '.chkbox', function (e) {
        var $chkboxes = $('.chkbox');
        if (!lastChecked) {
            lastChecked = this;
            return;
        }

        if (e.shiftKey) {
            var start = $chkboxes.index(this);
            var end = $chkboxes.index(lastChecked);

            $chkboxes.slice(Math.min(start, end), Math.max(start, end) + 1).prop('checked', lastChecked.checked);

        }

        lastChecked = this;
    });
    var lastChecked = null;

    $(document).on('click', "a.keggID", function (e) {
        e.preventDefault();

        var id = $(this).attr('id');

        showNodeDetailsModal(id)
    });

    // Show export table for enrich GO only when "all" tab is shown and hide button if not 
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href") // activated tab
        if (target != "#all") {
            document.getElementById('export').style.visibility='hidden';
        } 
        else if (target == "#all") {
            document.getElementById('export').style.visibility='visible';
        }
    });
    //console.log("here");
    function Redraw_Pathway() {
        var checkedValue = $('#OnlyDrawSelectedPathway').is(":checked");
        var selected = getSelected("Pathway");
        $.redirect('redraw_query.php', {ids: selected.join(","), redrawselected: checkedValue})
    }
    $("#Redraw_Pathway").click(function (e) {
        e.preventDefault();
        var checkedValue = $('#OnlyDrawSelectedPathway').is(":checked");
        var selected = getSelected("Pathway");

        $.redirect('redraw_query.php', {ids: selected.join(","), redrawselected: checkedValue})
    });

    function Redraw_Ontology() {
        var checkedValue = $('#OnlyDrawSelectedOntology').is(":checked");
        var selected = getSelected("Ontology");
        /*console.log(selected);
        console.log(checkedValue);
        console.log("button worked");*/
        $.redirect('run.php', {ids: selected.join(","), redrawselected: checkedValue})
    }

    $("#Redraw_Ontology").click(function (e) {
        e.preventDefault();
        var checkedValue = $('#OnlyDrawSelectedOntology').is(":checked");
        var selected = getSelected("Ontology");
        /*console.log(selected);
        console.log(checkedValue);
        console.log("button worked");*/
        $.redirect('run.php', {ids: selected.join(","), redrawselected: checkedValue})
    });

    function getSelected(type) {
        if (type === "Ontology") {
            return $('#Gene_Ontology_Table tbody input:checked').map(function () {
                return $(this).attr('value').toLowerCase();
            }).get();
        } else {
            return $('#Gene_Pathway_Table tbody input:checked').map(function () {
                return $(this).attr('value').toLowerCase();
            }).get();
        }

    }
    /*$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href") // activated tab
        if (target != "#Bar") {
            document.getElementById('exportBar').style.visibility='hidden';
        } 
        else if (target == "#Bar") {
            document.getElementById('exportBar').style.visibility='visible';
        }
    });
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href") // activated tab
        if (target != "#Dot") {
            document.getElementById('exportDot').style.visibility='hidden';
        } 
        else if (target == "#Dot") {
            document.getElementById('exportDot').style.visibility='visible';
        }
    });*/

     

    

</script>
