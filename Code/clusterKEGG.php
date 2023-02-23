<?php
error_reporting(E_ALL); 
ini_set('display_errors','1');
set_time_limit(600);
$config_props = include("config.inc.php");
$db_host = $config_props[0];
$db_user = $config_props[1];
$db_pass = $config_props[2];
$db_name = $config_props[3];
$clusterProfiler_location = $config_props[5];

## sp that have no or low mapping to ncbi must be mapped to uniprot
$badNcbiMap = array('ddi', 'mpo', 'ppa');
$jsonArr = json_encode([]);
$jsonKEGG = json_encode([]);
$array = array();
$array2 = array();
$mydata = $_POST['mdata']; // Gene \t ident# dataframe
$referenceDb = $_POST['referenceDb']; // NCBI, Uniport, Ensembl
$species = $_POST['species']; // Species 3 letter code


function createIDString($dict)
{
    $idString = '';
    foreach ($dict as $key) {
        $idString .= "'" . $key . "', ";
    }
    return trim($idString, ", ");
}

function getResult($mysqli, $query)
{
    $result = false;
    $i = 0;
    while ($result == false) {
        $result = $mysqli->query($query);
        $i++;
    }
    return $result;
}

function createJSONArray($result)
{
    $arr = [];
    $i = 0;

    while ($row = $result->fetch_assoc()) {
        # Fetch mysql query rows as an array and convert to a JSON string
        $arr[$i] = createJSONArrayObj($row["id"]);
        $i++;
    }

    return $arr;
}

function createJSONArrayObj($row)
{
    return (array('ID' => $row)); 
}

// osi is not actually oryza sativa indica group, our db is wrong. oryza sativa indica group is not supported by KEGG
if ($species == "osi") {
    echo "osi";
}
// split tab delimited data to get array of gene ids and group ids for json encode
for ($n = 0; $n < count($mydata); $n++) {
    $geneList = explode("\r\n", $mydata[$n]);       //explode string by \n
    foreach ($geneList as $key => $value) {
        $parts = explode("\t", $value);
        if (array_key_exists($parts[0], $array) == false) {
            $array[$parts[0]] = $parts[0];
        }
    }
}
$mydatastr = $mydata[0];

$lines=explode("\r\n",$mydatastr);
$l = 0;
foreach ($lines as $item){
    $array2[$l] = explode("\t",$item);
    $l++;
}

$ids = createIDString($array);
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);


if ($referenceDb == 'Ensembl') {
    if (in_array($species, $badNcbiMap)) {
        $select_clause = "SELECT DISTINCT(genes_id_mapping.uniprot_id)";

        $query = $select_clause . " AS id
            FROM genes_id_mapping
            WHERE genes_id_mapping.ensembl_id in (" . $ids .") AND genes_id_mapping.uniprot_id IS NOT NULL";

        $result = getResult($mysqli, $query); 
        if ($result -> num_rows > 0) {
            $jsonarr = createJSONArray($result);
            $jsonStr = json_encode($jsonarr);
        }
    } else {
        $select_clause = "SELECT DISTINCT(genes_id_mapping.ncbi_id)";

        $query = $select_clause . " AS id
            FROM genes_id_mapping
            WHERE genes_id_mapping.ensembl_id in (" . $ids .") AND genes_id_mapping.ncbi_id IS NOT NULL";

        $result = getResult($mysqli, $query); 
        if ($result -> num_rows > 0) {
            $jsonarr = createJSONArray($result);
            $jsonStr = json_encode($jsonarr);
        }
    }

    
} else {
    $jsonStr = json_encode($array2); 
}

//$showarray = print_r($ids, true);
// Confirm inputs are correct
//$mydataw = print_r($mydata, true);
//$myfile = fopen("inoutcluster.txt", "w+"); //or die("Unable to open file!");
    
// Check here for full array, ref db, species, and jsonstring -- OKAY!
$command = "Rscript /var/www/html/v3/r/clusterProfilerKEGG.R" . ' ' . escapeshellarg($jsonStr) . ' ' . $referenceDb . ' ' . $species;
$jsonKEGG =  shell_exec($command); 
//$writein = $showarray . "\n jsonSTR: " . $jsonStr . "\njsonKEGG: " . $jsonKEGG;// . ' ' . $referenceDb . ' ' . $species . ' ' . $clusterProfiler_location . ' ' . $jsonStr;
//fwrite($myfile, $writein);
//fclose($myfile);
echo $jsonKEGG;

