<?php

$config_props = include("config.inc.php");
$db_host = $config_props[0];
$db_user = $config_props[1];
$db_pass = $config_props[2];
$db_name = $config_props[3];
$fisher_test_location = $config_props[4];

function createJSONArray($result)
{
    $arr = [];
    if ($result !== null) {
        $i = 0;
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $row) {
            $arr[$i] = createJSONArrayObj($row['ID'], $row['symbol']);
            $i++;
        }
    }
    return json_encode($arr);
}

function createJSONArrayObj($id, $symbol)
{
    return array('ID' => $id, 'symbol' => $symbol);
}

function createSanitizedIDs($ids, $mysqli)
{
    return implode(',', array_map(fn($s) => sprintf("'%s'", $mysqli->real_escape_string($s)), $ids));
}

function getSymbolsWithIDS($mysqli, $query)
{
    return mysqli_query($mysqli, $query);
}

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
$geneIDsArray = $_POST['GeneIDs'];
$jsonArr = [];
$query = '';
$referenceDb = $_POST['referenceDb'];
$result = null;
$sanitized_ids = createSanitizedIDs($geneIDsArray, $mysqli);

if ($referenceDb == "Ensembl")
    $query = "SELECT DISTINCT(genes.symbol) AS symbol, genes.ensembl_id AS ID
        FROM genes
        WHERE genes.ensembl_id IN ($sanitized_ids);";
elseif ($referenceDb == "Uniprot")
    $query = "SELECT DISTINCT(genes.symbol) AS symbol, genes_id_mapping.uniprot_id AS ID
        FROM genes, genes_id_mapping
        WHERE genes.ensembl_id = genes_id_mapping.ensembl_id AND genes_id_mapping.uniprot_id IN ($sanitized_ids);";
else
    $query = "SELECT DISTINCT(genes.symbol) AS symbol, genes_id_mapping.ncbi_id AS ID
        FROM genes, genes_id_mapping
        WHERE genes.ensembl_id = genes_id_mapping.ensembl_id AND genes_id_mapping.ncbi_id IN ($sanitized_ids);";

$result = getSymbolsWithIDS($mysqli, $query);
if ($result !== false)
    $jsonArr = createJSONArray($result);

mysqli_close($mysqli);

return $jsonArr;
