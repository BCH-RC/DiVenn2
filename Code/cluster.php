<?php
error_reporting(E_ALL); 
ini_set('display_errors','1');
ini_set('display_startup_errors', TRUE);
set_time_limit(600);
$config_props = include("config.inc.php");
$clusterProfiler_location = $config_props[4];

$jsonArr = json_encode([]);
$jsonGO = json_encode([]);
$array = array();
$array2 = array();
$ensSpCodes = array('cel', 'cfa', 'dre', 'dme', 'gga', 'hsa', 'mmu', 'rno', 'scs', 'spo', 'ath');
$uniSpCodes = array('cel', 'cfa', 'dre', 'dme', 'gga', 'hsa', 'mmu', 'rno', 'scs', 'spo', 'ssc', 'xtr');
$mydata = $_POST['mdata']; // Gene \t ident# dataframe
$referenceDb = $_POST['referenceDb']; // NCBI, Uniport, Ensembl
$species = $_POST['species']; // must be chosen on homepage for clusterprofiler to work
                                // We need to match name to Entrez database such as "org.Hs.eg.db"
                                // Species in in 3 letter code

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

    if ($referenceDb == 'Ensembl' && !in_array($species, $ensSpCodes)) {

            $select_clause = "SELECT DISTINCT(genes_id_mapping.ncbi_id)";

            $query = $select_clause . " AS id
                FROM genes_id_mapping
                WHERE genes_id_mapping.ensembl_id in (" . $ids .") AND genes_id_mapping.ncbi_id IS NOT NULL";
    
            $result = getResult($mysqli, $query); 
            if ($result -> num_rows > 0) {
                $jsonarr = createJSONArray($result);
                $jsonStr = json_encode($jsonarr);
            }
    } else if ($referenceDb == 'Uniprot' && !in_array($species, $uniSpCodes)) {
        $select_clause = "SELECT DISTINCT(genes_id_mapping.ncbi_id)";

        $query = $select_clause . " AS id
            FROM genes_id_mapping
            WHERE genes_id_mapping.uniprot_id in (" . $ids .") AND genes_id_mapping.ncbi_id IS NOT NULL";

        $result = getResult($mysqli, $query); 
        if ($result -> num_rows > 0) {
            $jsonarr = createJSONArray($result);
            $jsonStr = json_encode($jsonarr);
        }
    }
    else {
        $jsonStr = json_encode($array2); 
    }

    $showarray = print_r($ids, true);
    // Confirm inputs are correct
    //$mydataw = print_r($mydata, true);
    //$myfile = fopen("inoutcluster.txt", "w+"); // or die("Unable to open file!");
    
  ## If we have a successful mapping
    if (!empty($jsonStr)) {
        // Check here for full array, ref db, species, and jsonstring -- OKAY!
        $command = "Rscript /var/www/html/v3/r/clusterProfiler.R" . ' ' . escapeshellarg($jsonStr) . ' ' . $referenceDb . ' ' . $species;
   
        $jsonGO =  shell_exec($command); 
        
    } else {
        $jsonStr = "Empty";
        $jsonGO = "No Results";
    }
    
    //$writein = $showarray . "\n jsonSTR: " . $jsonStr . "\njsonGO: " . $jsonGO;# . "\nresultarr: " . $result;// . ' ' . $referenceDb . ' ' . $species . ' ' . $clusterProfiler_location . ' ' . $jsonStr;
    //fwrite($myfile, $writein);
    //fclose($myfile);
    echo $jsonGO; 


