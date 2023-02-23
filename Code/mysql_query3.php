<?php

$config_props = include("config.inc.php");
$db_host = $config_props[0];
$db_user = $config_props[1];
$db_pass = $config_props[2];
$db_name = $config_props[3];
$fisher_test_location = $config_props[4];

function createIDString($dict)
{
    $idString = '';
    foreach ($dict as $key) {
        $idString .= "'" . $key . "', ";
    }
    return trim($idString, ", ");
}

function createJSONArray($result, $total)
{
    $arr = [];
    $i = 0;
    $goDict = [];
    
    while ($row = $result->fetch_assoc()) {
        if ($row["go_term"] != "-")
            $goDict = determineGOs($row, $goDict);
    }

    mysqli_data_seek($result, 0);
    while ($row = $result->fetch_assoc()) {

        $arr[$i] = createJSONArrayObj($row );
        $i++;
    }

    return json_encode($arr);
}

function createJSONArrayObj($row)
{
    return (array('ID' => $row['id'], 'go_id' => $row['go_id'], 'go_term' => $row['go_term'],
        'go_category' => $row['go_category'])); 
}

function determineInGO($row, $dict)
{
    return $dict[$row['go_term']] - 1;
}

function determineNotInGO($total, $inGO)
{
    return $total - $inGO;
}

function determineGOs($row, $dict)
{
    if (array_key_exists($row['go_term'], $dict) == false)
        $dict[$row['go_term']] = 1;
    else
        $dict[$row['go_term']]++;

    return $dict;
}

function determinePValue($command, $row, $pValueDict)
{
    if (array_key_exists($row['go_term'], $pValueDict) == false)
        $pvalue = shell_exec($command);
    else
        $pvalue = $pValueDict[$row['go_term']];

    return $pvalue;
}

function updatePValueDict($value, $row, $pValueDict)
{
    if (array_key_exists($row['go_term'], $pValueDict) == false)
        $pValueDict[$row['go_term']] = $value;
    return $pValueDict;
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

$and_clause = '';
$idDict = [];
$jsonArr = json_encode([]);
$mydata = $_POST["mdata"];
$pathtotal = 0;
$referenceDb = $_POST['referenceDb'];
$species = $_POST['species'];

for ($n = 0; $n < count($mydata); $n++) {
    $geneList = explode("\r\n", $mydata[$n]);       //explode string by \n
    foreach ($geneList as $key => $value) {
        $parts = explode("\t", $value);
        if (array_key_exists($parts[0], $idDict) == false) {
            $idDict[$parts[0]] = $parts[0];
            $pathtotal = $pathtotal + 1;
        }
    }
}

$matched_code = preg_match('/^([a-z]){3}$/', $species); //PHP must(!) have delimiters in the head and tail.

$ids = createIDString($idDict);
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($referenceDb == "Ensembl") {
    $select_clause = "SELECT DISTINCT(go_gene_mapping.ensembl_id)";
    $where_clause = "go_gene_mapping.ensembl_id in ($ids)";
} elseif ($referenceDb == "Uniprot") {
    $select_clause = "SELECT DISTINCT(genes_id_mapping.uniprot_id)";
    $where_clause = "genes_id_mapping.uniprot_id in ($ids)";
} else {
    $select_clause = "SELECT DISTINCT(genes_id_mapping.ncbi_id)";
    $where_clause = "genes_id_mapping.ncbi_id in ($ids)";
}

if ($species !== 'notselected')
    $and_clause = " AND species.short_name = '" . $species . "';";

$query = $select_clause . " AS id,
		 gene_ontologies.go_id, gene_ontologies.go_term, gene_ontologies.go_category,
         go_gene_mapping.in_go, go_gene_mapping.not_in_go
         FROM gene_ontologies
         INNER JOIN go_gene_mapping ON go_gene_mapping.go_id = gene_ontologies.go_id
         INNER JOIN genes_id_mapping ON go_gene_mapping.ensembl_id = go_gene_mapping.ensembl_id
         LEFT JOIN genes ON genes.ensembl_id = go_gene_mapping.ensembl_id
                   AND genes.ensembl_id = genes_id_mapping.ensembl_id
         INNER JOIN species ON species.species_id = genes.species_id
         WHERE " . $where_clause;

if (strlen($and_clause) > 0)
    $query .= $and_clause;

if ($matched_code == 1) {
    $result = getResult($mysqli, $query);
    if ($result->num_rows > 0)
        $jsonArr = createJSONArray($result, $pathtotal);
}


mysqli_close($mysqli);

echo $jsonArr;
