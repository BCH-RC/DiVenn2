<?php
$message = null;
$title_cutoff_12_Part1 = "The first column is gene IDs and the second column is gene regulation value.";
$title_cutoff_12_Part2 = " The second column must be '1' or '2'.";
$title_cutoff_12_Part3 = " we require users to use '1' to represent up-regulated genes and '2' to ";
$title_cutoff_12_Part4 = " represent down-regulated genes based on their own cut-off value of fold change.";
$title_cutoff_Any_Part1 = "If you use log2(fold change) cutoff value '1', genes with log2(fold changes)";
$title_cutoff_Any_Part2 = " lower than -1 will be considered as negative regulated, while ";
$title_cutoff_Any_Part3 = " those with log2 (fold changes) greater than 1 will be considered as positive-regulated.";

$extensions = ['csv' => ',', 'tsv' => "\t"];
$target_dir = "/var/www/html/v3/uploads/";


//include_once('redesign_data.php');

function initializeFilelist()
{
    if ($_FILES != null && $_FILES["file"] !== '')
        $_SESSION['filelist'] = $_FILES["file"];
}

function initializationSessionVarsAlways()
{
    $_SESSION['count_errors'] = -1;
    $_SESSION["exp_name"] = array();
    $_SESSION['genes_IDs'] = array();
    $_SESSION['genes_values'] = array(); //1: up-regulated, 2: down-regulated
    $_SESSION['raw_genes_values'] = array(); // raw log2fold change values 
    $_SESSION['idsWithSymbols'] = array();
    $_SESSION['messagelist'] = array();
    $_SESSION['mydata'] = array();
    $_SESSION['mydata_cutoffed'] = array();
    $_SESSION['remaining_data'] = array();
    $_SESSION['specieslist'] = array();
}

function initializationSessionVarsAtTheBeginningOfTheSession()
{
    if (!isset($_SESSION["filenum"])) {
        initializationSessionVarsAlways();
        $_SESSION['cutoff'] = 0;
        $_SESSION['cutofftype'] = "";
        $_SESSION['filelist'] = array();
        $_SESSION['filenum'] = -1;
        $_SESSION['mycolor'] = ["#a23388", "#ccffff", "#99cc33", "#ff9900", "#9966cc", "#0099cc", "#663300", "#39b5b8",
            "#550000", "#007323"];
        $_SESSION['referenceDb'] = "";
        $_SESSION['species'] = "";
        $_SESSION['uploadtype'] = "";
    }
}

function initializationSessionVarsAfterSubmit()
{
    if (empty($_POST) == false)
        if (isset($_POST['exp_num'])) {
            initializationSessionVarsAlways();
            $_SESSION['cutoff'] = (float)$_POST['cutoff'];
            $_SESSION['cutofftype'] = $_POST['cutofftype'];
            $_SESSION['filenum'] = $_POST['exp_num'];
            $_SESSION['referenceDb'] = $_POST['referenceDb'];
            $_SESSION['species'] = $_POST['species'];
            $_SESSION['uploadtype'] = $_POST['uploadtype'];
        }
}

function addErrorMessage($message)
{
    $_SESSION['messagelist'][] = $message;
}

function arrayContainsOnlyPassedValue($array, $value): bool
{
    for ($iterator = 0; $iterator < sizeof($array); $iterator++)
        if ($array[$iterator] !== $value)
            return false;
    return true;
}

function checkDBConnection(): bool
{
    $correctConnection = false;
    $mysqli = include("dbconnection.php");
    if ($mysqli->connect_error == null)
        $correctConnection = true;

    return $correctConnection;
}

function checkDBConnections(): bool
{
    if (checkDBConnection() == false)
        return false;

    return true;
}

function createMessageBasedOnFileError($n): string
{
    $message = "There is no files";
    if (isset($_FILES["file"]["error"])) {
        switch ($_FILES["file"]["error"][$n]) {
            case 0:
                $message = $_FILES["file"]["name"][$n] . " was uploaded successfully.";
                break;
            case 2:
                $message = $_FILES["file"]["name"][$n] . " is too big to upload.";
                break;
            case 4:
                $message = "No file selected.";
                break;
            default:
                $message = "Sorry, there was a problem uploading." . $_FILES["file"]["name"][$n];
                break;

        }
    }
    return $message;
}

function determineRegulation($gene_value, $cutoff): string
{
    if ((float)$gene_value > (float)$cutoff)
        return "1";
    else
        return "2";
}

function fileFailureCheck($error_number, $n, $message): bool
{
    if ($error_number > 0) {
        $field = $n + 1;
        $_SESSION['messagelist'][] = "Invalid data at line "
            . $error_number . " in input field " . $field . "! " . $message .
            " Please check it and submit again. \n Remember: separate the columns by tabulator ";
        return true;
    } elseif ($error_number < 0) {
        $_SESSION['messagelist'][] = "No data in experiment " . $_SESSION["exp_name"][$n] . "! Please check it and 
        submit again.";
        return true;
    }
    return false;
}

function getGeneIDs()
{
    $geneIDslist = [];
    for ($i = 0; $i < count($_SESSION['genes_IDs']); $i++) {
        for ($gene = 0; $gene < count($_SESSION['genes_IDs'][$i]); $gene++)
            $geneIDslist[] .= $_SESSION['genes_IDs'][$i][$gene];
    }
    return $geneIDslist;
}

function getIDsWithSymbolsForData($ids, $db)
{
    $_POST['GeneIDs'] = $ids;
    $_POST['referenceDb'] = $db;
    $idsWithSymbols = include('mysql_query_symbols.php');
    if ($idsWithSymbols !== []) {
        return $idsWithSymbols;
    }     
    return [];
}

function readContent($line, $separator)
{
    $content = null;

    try {
        $content = str_getcsv($line, separator: $separator);
    } catch (ValueError|TypeError $e) {
        addErrorMessage($e);
    }
    return $content;
}

function getFileContent($file_loc, $nr, $sep)
{
    $data = null;
    if (file_exists($file_loc)) {
        $csvcontent = file($file_loc);   //read file to string
        foreach ($csvcontent as $line) {
            $row = readContent($line, $sep);
            if ($row !== null)
                $data[$nr][] = $row;
        }
    }

    return $data;
}

function moveUploadedFileToTargetDir($temp_name, $sha_name, $dir, $extension)
{
    if (!move_uploaded_file($temp_name, sprintf($dir . '%s.%s', $sha_name, $extension)))
        return false;
    return true;
}

function validateAndMoveUploadedFileToTargetDir($dir, $nr, $file_extension)
{
    if ($_FILES["file"]["error"][$nr] == UPLOAD_ERR_OK) {
        $tmp_name = $_FILES["file"]["tmp_name"][$nr];
        $sha_filename = sha1_file($tmp_name);
        if (moveUploadedFileToTargetDir($tmp_name, $sha_filename, $dir, $file_extension)) {
            $name = basename($_FILES["file"]["name"][$nr]);
            move_uploaded_file($name, $dir . $name);
            $_SESSION['sha_filename'] = $sha_filename;
            return true;
        }
    } else {
        $message = createMessageBasedOnFileError($nr);
        addErrorMessage($message . "! Please check it and submit again. " . $_FILES["file"]["error"][$nr]);
    }
    return false;
}

function getOptions()
{
    return include('fill_species_dropdmenu.php');
}

function getSpecies()
{
    if (checkDBConnections() !== false) {
        $species_new = include('get_species.php');
        $_SESSION['specieslist'] = $species_new;
    }
}

function setRegulation($number)
{
    if (isset($_SESSION['cutoff'])) {
        $_SESSION['raw_genes_values'] === $_SESSION['genes_values'];
        for ($id = 0; $id < count($_SESSION['genes_IDs'][$number]); $id++) {
            $value = determineRegulation(abs($_SESSION['genes_values'][$number][$id]), (float)$_SESSION['cutoff']);
            $_SESSION['genes_values'][$number][$id] = $value;
        }
    }
}

function validateCutoff(): bool
{
    if ($_SESSION['cutoff'] !== -1) {
        $value = trim($_SESSION['cutoff']);
        if ($value !== "") {
            if (is_numeric($value))
                if ((float)$value >= 0.0)
                    return true;
        }
        return false;
    } else
        return true;
}

function joinRowDataIntoOneLine($row)
{
    return implode("\t", $row);
}

function validation($data, $number, $mode): int|string
{
    if ($data != null) {
        $data_without_crlf_characters = "";
        $formattedData = "";
        $gen_counter = -1;

        if ($mode == 'file') {
            for ($i = 0; $i < count($data[$number]); $i++)
                $data_without_crlf_characters .= joinRowDataIntoOneLine($data[$number][$i]) . "\r\n";

            $data = $data_without_crlf_characters;
        }

        if (strlen(trim($data)) == 0) return -1;
        $data_without_crlf_characters = preg_split("/\r\n|\n|\r/", $data);

        foreach ($data_without_crlf_characters as $key => $line) {
            if (strlen(trim($line)) > 0) {
                $parts = preg_split("/\t/", $line, -1, PREG_SPLIT_NO_EMPTY);

                if ($parts[0] == "-") {
                    $remainingData = implode("\t", $parts) . "\r\n";
                    $formattedData .= $remainingData;
                    $_SESSION['remaining_data'][$number][$gen_counter] .= $remainingData;
                    continue;
                }

                if (count($parts) >= 2) {
                    ++$gen_counter;
                    $remainingData = array();
                    $formattedData .= implode("\t", $parts) . "\r\n";

                    $_SESSION['genes_IDs'][$number][$gen_counter] = $parts[0];
                    $_SESSION['genes_values'][$number][$gen_counter] = $parts[1];
                    $_SESSION['raw_genes_values'][$number][$gen_counter] = $parts[1];
                    $_SESSION['remaining_data'][$number][$gen_counter] = '';
                    for ($part = 2; $part < count($parts); $part++) {
                        $remainingData[] = $parts[$part];
                    }
                    if (count($remainingData) > 0) {
                        $_SESSION['remaining_data'][$number][$gen_counter] .=
                            implode("\t", $remainingData) . "\r\n";
                    }
                } else if (count($parts) > 0)
                    return $key + 1;

                if (in_array($parts[0], $_SESSION['genes_IDs'])) return $key + 1;
            }
        }
        $formattedData = htmlspecialchars($formattedData);
        $_SESSION['mydata'][$number] = substr($formattedData, 0, -2);

        return 0;
    } else return 1;
}

function getCutoffMode()
{
    return $_SESSION['cutofftype'];
}

function createMyDataCutoffed($cutoffMode)
{
    for ($number = 0; $number < $_SESSION['filenum']; $number++) {
        if ($cutoffMode == "Cutoff12") $_SESSION['mydata_cutoffed'][$number] = $_SESSION['mydata'][$number];
        else {
            $_SESSION['mydata_cutoffed'][$number] = '';
            for ($gen_id = 0; $gen_id < count($_SESSION['genes_IDs'][$number]); $gen_id++) {
                if ($_SESSION['genes_IDs'][$number][$gen_id] != null) {
                    $_SESSION['mydata_cutoffed'][$number] .= $_SESSION['genes_IDs'][$number][$gen_id] . "\t" .
                        $_SESSION['genes_values'][$number][$gen_id] . "\t" .
                        $_SESSION['raw_genes_values'][$number][$gen_id] . "\r\n";
                }
            }
            $_SESSION['mydata_cutoffed'][$number] = htmlspecialchars($_SESSION['mydata_cutoffed'][$number]);
            $_SESSION['mydata_cutoffed'][$number] = substr($_SESSION['mydata_cutoffed'][$number], 0, -2);
        }
    }
}

initializationSessionVarsAtTheBeginningOfTheSession();
getSpecies();
$mysqli = include('dbconnection.php');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST["submit"])) {
        initializationSessionVarsAfterSubmit();
        $count_errors = 0;
        $message = null;
        $mydata = null;

        if (validateCutoff() != false) {
            if (($_SESSION['filenum']) > 0)
                initializeFilelist();
        } else {
            $message = "Please input correct cutoff value! Entered value";
            addErrorMessage($message . ": " . $_POST['cutoff']);
            ++$count_errors;
        }

        if ($count_errors <= 0) {
            switch ($_SESSION['uploadtype']) {
                case "UploadFile":
                    for ($nr = 0; $nr < $_SESSION['filenum']; $nr++) {
                        $_SESSION['redrawdata'] = [];

                        $file_extension = explode('.', $_FILES['file']['name'][$nr])[1];
                        if (!in_array($file_extension, array_keys($extensions)))
                            addErrorMessage("Wrong file extension!: " . $_FILES["file"]["error"][$nr]);

                        if (validateAndMoveUploadedFileToTargetDir($target_dir, $nr, $file_extension) == false)
                            ++$count_errors;
                        else {
                            try {
                                $file_loc = $target_dir . implode('.', [$_SESSION['sha_filename'], $file_extension]);
                                $mydata = getFileContent($file_loc, $nr, $extensions[$file_extension]);
                                if ($mydata == null) {
                                    addErrorMessage("null: " . $nr);
                                    $count_errors++;
                                }
                            } catch (Exception|ValueError $e) {
                                $message = createMessageBasedOnFileError($e);
                                addErrorMessage($message . ": " . $nr);
                                $count_errors++;
                            }

                            if ($count_errors <= 0) {
                                if (isset($_POST['exp_num'])) {
                                    $_SESSION['mycolor'][$nr] = '#' . trim($_POST["colorselector" . $nr], '#');
                                    $_SESSION["exp_name"][$nr] = preg_replace('/\s+/', '',
                                        $_POST['expname'][$nr]);
                                }
                                if (validation($mydata, $nr, 'file') > 0)
                                    $count_errors++;
                            }
                        }
                        setRegulation($nr);
                    }
                    break;

                case "UploadText":
                    $errornumber = -2;
                    for ($nr = 0; $nr < $_SESSION['filenum']; $nr++) {
                        $_SESSION['redrawdata'] = [];
                        if (isset($_POST['exp_num'])) {
                            $_SESSION['mycolor'][$nr] = '#' . trim($_POST["colorselector" . $nr], '#');
                            $_SESSION['exp_name'][$nr] = preg_replace('/\s+/', '', $_POST['expname'][$nr]);
                            $mydata = "txtquery" . $nr;
                            $errornumber = validation($_POST[$mydata], $nr, 'text');
                        }
                        if (fileFailureCheck($errornumber, $nr, $message) === true) $count_errors++;
                        else setRegulation($nr);
                    }
                    break;

                default:
                    echo "<script>console.log('something wrong');</script>";
                    break;
            }
        }

        if ($count_errors <= 0) {
            createMyDataCutoffed($_SESSION['cutofftype']);
            $ref_db = $_SESSION['referenceDb'];
            
            if ($ref_db !== 'notselected'){
                $_SESSION['idsWithSymbols'] = getIDsWithSymbolsForData(getGeneIDs(), $ref_db);
            } else {
                $_SESSION['idsWithSymbols'] = "None";
            }
 
            if (arrayContainsOnlyPassedValue($_SESSION['mydata'], '') === false) {
                echo "<script>window.location.href='drawing.php';</script>";
            }
            
            else {
                $_SESSION['mydata'] = array();
                addErrorMessage('Amount of data: 0. Correct parameters!');
                ++$count_errors;
            }
        }
        $_SESSION['count_errors'] = $count_errors;
    }
}
?>

<form action="" method="post" enctype="multipart/form-data" id="species_form">
    <div class="row">
        <div class="col-md-8">
            <div class="col-md-7 form-group">
                <h2>Submit Data</h2>

                <label>Species (Optional)</label>
                <select id='species' name='species' class="form-control">
                    <option value='notselected' selected>&lt;please select&gt;</option>
                    <?php echo getOptions(); ?>
                </select>

                <br>

                <label>Choose Gene ID Type (Optional)</label>
                <select id='referenceDb' name='referenceDb' class="form-control">
                    <option value='notselected' selected>&lt;please select&gt;</option>
                    <option value="Ensembl">Ensembl</option>
                    <option value="Uniprot">Uniprot</option>
                    <option value="NCBI">NCBI</option>
                    <!--<option value="Symbol">Symbol</option>-->
                </select>
            </div>

            <div class="col-md-7 form-group">
                <label>Choose Upload Type</label><br>
                <input type="radio" name="uploadtype" id="UploadText" value="UploadText"
                       onchange='switchUploadMode()' checked><label
                        for="UploadText" style="font-weight:normal;margin-left:5px">Paste Text Data</label>
                <input type="radio" name="uploadtype" id="UploadFile" value="UploadFile"
                       onclick='switchUploadMode()'><label
                        for="UploadFile" style="font-weight:normal;margin-left:5px">Upload File</label>
            </div>

            <div class="col-md-7 form-group">
                <label>Choose Cutoff Type</label><br>
                <input type="radio" name="cutofftype" id="Cutoff12" value="Cutoff12"
                       onchange='switchCutoffType()' checked><label
                        for="Cutoff12" style="font-weight:normal;margin-left:5px">Default value</label>
                <button type="button" class="btn btn-sm btn-outline-info" data-toggle="popover" title="Default value"
                        data-placement="top"
                        data-content=<?php echo json_encode($title_cutoff_12_Part1 . $title_cutoff_12_Part2 .
                    $title_cutoff_12_Part3 . $title_cutoff_12_Part4); ?>>Info
                </button>
                <input type="radio" name="cutofftype" id="CutoffAny" value="CutoffAny"
                       onclick='switchCutoffType()'><label for="CutoffAny" style="font-weight:normal;margin-left:5px">
                    Custom log2(fold change) cutoff value</label>
                <button type="button" class="btn btn-sm btn-outline-info" data-toggle="popover"
                        title="Custom log2(fold change) cutoff value" data-placement="top"
                        data-content=<?php echo json_encode($title_cutoff_Any_Part1 . $title_cutoff_Any_Part2 .
                    $title_cutoff_Any_Part3); ?>>Info
                </button>
            </div>
            <div class="col-md-7 form-group" id="cutoff_div" style="display: none">
                <label for="cutoff">Cut-off value (Required)</label><br>
                <div class="ui-widget">
                    <input id='cutoff' name="cutoff" class="form-control input-lg" type="number" step="0.01" min="0.00"
                           placeholder="0.0"
                           onclick="cutoffChanged()" onchange="cutoffChanged()">
                </div>
                <span id="info"></span>
            </div>

            <div class="col-md-7 form-group">
                <label>Number of Experiments </label><br>
                <select id='exp_num' name='exp_num' class="form-control" onchange="changeExperimentCount()">
                    <option value="0" selected="">&lt;please select&gt;</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                    <option value="10">10</option>
                </select>
                <button id="autoLoadButton" class="btn btn-primary hidden" style="margin-top:10px"
                        onclick="onClickAutoLoadButton()">
                    Load Sample Data
                </button>
                <button id="autoLoadDbButton" class="btn btn-primary hidden" style="margin-top:10px"
                        onclick="onClickAutoLoadDbButton()">
                    Load Sample With Custom Data
                </button>
            </div>
        </div>
    </div>
    <div id="UploadData"></div>
    <div class="form-group">
        <input type="submit" name="submit" id="submit" value="Submit" class="btn btn-success"
               disabled="disabled">
    </div>
</form>


<div id="myModal" class="modal fade" role="dialog" aria-hidden="true" style="display: none">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                <h3 id="myModalLabel">Information</h3>
            </div>
            <div class="modal-body"></div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

<script type="text/javascript">

    var count_errors = -1;
    var cutoff = 0;
    var cutofftype = null;
    var dbtype = null;
    var errors = [];
    var exp_name = null;
    var filelist = null;
    var filenum = -1;
    var mycolor = [];
    var mydata = [];
    var referenceDb = null;
    var species = null;
    var species_list = null;
    var uploadtype = null;
    count_errors = <?php if (isset($_SESSION['count_errors']) !== false) echo json_encode($_SESSION['count_errors']);
                   else echo json_encode(-1); ?>;
    cutoff =       <?php if (isset($_SESSION['cutoff']) !== false) echo json_encode($_SESSION['cutoff']);
                   else echo json_encode(0); ?>;
    cutofftype =   <?php if (isset($_SESSION['cutofftype']) !== false ) echo json_encode($_SESSION['cutofftype']);
                   else echo json_encode(""); ?>;   //Cutoff12 or CutoffAny
    errors =       <?php if (isset($_SESSION['messagelist']) !== false) echo json_encode($_SESSION['messagelist']);
                   else echo json_encode(array()); ?>;
    exp_name =     <?php echo json_encode($_SESSION['exp_name']); ?>;   //each experiment's name uploaded
    filelist =     <?php if (isset($_SESSION['filelist']) !== false) echo json_encode($_SESSION['filelist']);
                   else echo json_encode(array()); ?>;
    filenum =      <?php echo json_encode($_SESSION['filenum']); ?>;   //number of files/datasets the customer uploaded
    mycolor =      <?php if (isset($_SESSION['mycolor']) !== false) echo json_encode($_SESSION['mycolor']);
                   else echo json_encode(array()); ?>;  //get array value of color
    mydata =       <?php if (isset($_SESSION['mydata']) !== false) echo json_encode($_SESSION['mydata']);
                    else echo json_encode(array()); ?>; //data sent to server
    referenceDb =   <?php if (isset($_SESSION['referenceDb'])) echo json_encode($_SESSION['referenceDb']);
                    else echo json_encode("notselected");?>; //Ensembl, Uniprot or NCBI
    species =       <?php echo json_encode($_SESSION['species']); ?>;
    uploadtype =    <?php echo json_encode($_SESSION['uploadtype']); ?>;   //UploadFile or UploadText


    if (cutoff > 0) $("#cutoff").val(cutoff);
    if (cutofftype === "CutoffAny") $("#CutoffAny").prop("checked", true);
    else $("#Cutoff12").prop("checked", true);
    if (filenum > 0) $('#exp_num').val(filenum);		// fill saved data last time
    if (referenceDb !== "") $('#referenceDb').val(referenceDb);
    if (species !== "") $('#species').val(species);
    if (uploadtype === "UploadFile") $("#UploadFile").prop("checked", true);
    else $("#UploadText").prop("checked", true);

    if (count_errors > 0) {
        showErrors(errors);
        deactivateSubmitButton();
    }

    function changeExperimentCount() {
        deactivateSubmitButton();
        updateFileUploadContent(mycolor);
    }

    function switchUploadMode() {
        deactivateSubmitButton();
        updateFileUploadContent(mycolor);
    }

    function switchCutoffType() {
        switchCutoffMode(cutofftype);
        clearUploadTextInputs();
    }

    // open a new window in the web browser
    window.onload = updateFileUploadContent(mycolor, parseInt(filenum), mydata);

    $(function () {
        $('[data-toggle="popover"]').popover()
    })

</script>
