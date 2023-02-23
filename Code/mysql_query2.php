<?php

$config_props = include("config.inc.php");
$db_host = $config_props[0];
$db_user = $config_props[1];
$db_pass = $config_props[2];
$db_name = $config_props[3];


function createIDString($dict)
{
    $idString = '';
    foreach ($dict as $key) {
        $idString .= "'" . $key . "', ";
    }
    return trim($idString, ", ");
}

function createJSONArrayObj($id, $descr, $pathway, $url)
{
    return (array('ID' => $id, 'description' => $descr, 'pathway' => $pathway,
        'urls' => $url));
}

function createJSONArray($result)
{
    $arr = [];
    if ($result !== null) {
        $i = 0;

        $rows = $result->fetch_all(MYSQLI_ASSOC);

        foreach ($rows as $row) {
            if ($row["pathway"] != "-") {
                $arr[$i] = createJSONArrayObj($row['id'], $row['gene_desc'], $row['pathway'],
                $row['urls']);
                $i++;
            }
            # Fetch mysql query rows as an array and convert to a JSON string
            
        }
    } 
    return json_encode($arr);
}

function getResult($mysqli, $query)
{
    $result = false;
    while ($result == false) {
        $result = $mysqli->query($query);
    }
    return $result;
}

function getSanitizedGeneID($mysqli, $geneID)
{
    return $mysqli->real_escape_string($geneID);
}

$and_clause = '';
$array = array();
$jsonArr = json_encode([]);
$mydata = $_POST['mdata'];
$pathtotal = 0;
$referenceDb = $_POST['referenceDb'];

$select_clause = '';
$species = $_POST['species'];
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

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

$ids = createIDString($array);

if ($referenceDb == "Ensembl") {
    $select_clause = "SELECT DISTINCT(genes.ensembl_id)";
    $where_clause = "genes.ensembl_id in (" . $ids . ") Group By id, genes.gene_desc";
} elseif ($referenceDb == "Uniprot") {
    $select_clause = "SELECT DISTINCT(genes.ensembl_id)";
    $where_clause = "genes_id_mapping.uniprot_id in (" . $ids . ") Group By id, genes.gene_desc";
} else {
    $select_clause = "SELECT DISTINCT(genes.ensembl_id)";
    $where_clause = "genes_id_mapping.ncbi_id in (" . $ids . ") Group By id, genes.gene_desc";
}

if ($species !== 'notselected')
    $and_clause = " AND species.short_name = '" . $species . "' ;";

$query = $select_clause . " AS id, genes.gene_desc, group_concat(distinct pathways.path_desc SEPARATOR '\\n') as pathway, group_concat(distinct pathways.path_url SEPARATOR '\\n') as urls
              FROM genes
              INNER JOIN genes_id_mapping ON genes_id_mapping.ensembl_id = genes.ensembl_id
              INNER JOIN pathways_gene_mapping ON pathways_gene_mapping.gene_id = genes.ensembl_id
              INNER JOIN pathways ON pathways_gene_mapping.pathway_id = pathways.pathway_id 
              INNER JOIN species ON pathways.species_id = species.species_id
              WHERE " . $where_clause;

if (strlen($and_clause) > 0)
    $query .= $and_clause;
//Write to inoutcluster.txt for debugging
//$showarray = print_r($ids, true);
//$mydataw = print_r($query, true);
//$myfile = fopen("inoutcluster.txt", "w+"); //or die("Unable to open file!");


$result = getResult($mysqli, $query);


if ($result->num_rows > 0) {
    $jsonArr = createJSONArray($result);
    //$jsonStr = json_encode($jsonArr);
/*    $writein = $jsonArr . "\n results: " . $mydataw;
    fwrite($myfile, $writein);
    fclose($myfile);*/
} else {
    $i = "No Result";
    /*$writein = $i . "\n results: " . $mydataw;
    fwrite($myfile, $writein);
    fclose($myfile);*/

    echo $i;
}

mysqli_close($mysqli);


echo $jsonArr;
