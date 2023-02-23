<?php
//error_reporting(E_ALL); 
//ini_set('display_errors','1');
$config_props = include_once("config.inc.php");
$db_host = $config_props[0];
$db_user = $config_props[1];
$db_pass = $config_props[2];
$db_name = $config_props[3];
$fisher_test_location = $config_props[4];

function createJSONArrayObj($id, $descr, $pathway, $pathdesc, $url, $go_id, $go_term, $go_cat, $uniprot, $ncbi_id)
{
    return (array('ID' => $id, 'description' => $descr, 'pathway' => $pathway, 'pathdesc' => $pathdesc,
        'url' => $url, 'go_id' => $go_id, 'go_term' => $go_term,
        'go_category' => $go_cat, 'uniprot_id' => $uniprot, 'ncbi_id' => $ncbi_id));
}

function createJSONArray($result)
{
    $arr = [];
    if ($result !== null) {
        $i = 0;
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        
        foreach ($rows as $row) {
            if ($row['id'] != null && $row['pathway'] != null)
                $arr[$i] = createJSONArrayObj($row['id'], $row['gene_desc'], $row['pathway'], $row['pathdesc'],
                    $row['urls'], $row['go_ids'], $row['go_terms'], $row['go_cats'], $row['uniprot'],
                    $row['ncbi_id']);
            else
                $arr[$i] = createJSONArrayObj($row['id'], $row['gene_desc'], null, null, null,
                    $row['go_ids'], $row['go_terms'], $row['go_cats'], $row['uniprot'],
                    $row['ncbi_id']);
            $i++;
        }
    }
    return json_encode($arr);
}

function getGeneDetails($mysqli, $query)
{
    return $mysqli->query($query);
}

function getResult($mysqli, $queries)
{
    $result = false;
    $i = 0;
    while ($result == false && $i < count($queries)) {
        $result = getGeneDetails($mysqli, $queries[$i]);
        $i++;
    }
    return $result;
}

function getSanitizedGeneID($mysqli, $geneID)
{
    return $mysqli->real_escape_string($geneID);
}

$geneID = $_POST["GeneID"];
$jsonArr = json_encode([]);
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
$referenceDb = $_POST['referenceDb'];
$sanitizedGeneID = getSanitizedGeneID($mysqli, $geneID);
$where_clause = '';
$and_clause = '';

if ($referenceDb == "Ensembl") {
    $where_clause = "WHERE genes_id_mapping.ensembl_id = '" . $sanitizedGeneID . "'Group By id, genes.gene_desc,
    genes_id_mapping.ncbi_id, gene_ontologies.go_id, gene_ontologies.go_term, gene_ontologies.go_category ;"; // "';"
} elseif ($referenceDb == "Uniprot") {
    $where_clause = "WHERE genes_id_mapping.uniprot_id = '" . $sanitizedGeneID . "'Group By id, genes.gene_desc,
    genes_id_mapping.ncbi_id, gene_ontologies.go_id, gene_ontologies.go_term, gene_ontologies.go_category ;";
} else {
    $where_clause = "WHERE genes_id_mapping.ncbi_id = '" . $sanitizedGeneID . "'Group By id, genes.gene_desc,
    genes_id_mapping.ncbi_id, gene_ontologies.go_id, gene_ontologies.go_term, gene_ontologies.go_category ;";
}
//
$queries = [
    0 => "SELECT DISTINCT(genes.ensembl_id) AS id, genes.gene_desc,
               group_concat(distinct pathways.pathway_id SEPARATOR ',\n') as pathway, group_concat(distinct pathways.path_url SEPARATOR '\n') as urls, group_concat(distinct pathways.path_desc SEPARATOR '\n') as pathdesc,
              group_concat(distinct gene_ontologies.go_id SEPARATOR ',\n') as go_ids, group_concat(gene_ontologies.go_term SEPARATOR '\n') as go_terms, group_concat(distinct gene_ontologies.go_category) as go_cats,
              group_concat(distinct genes_id_mapping.uniprot_id SEPARATOR ',\n') as uniprot, genes_id_mapping.ncbi_id
              FROM genes
              left JOIN go_gene_mapping ON go_gene_mapping.ensembl_id = genes.ensembl_id
              left JOIN pathways_gene_mapping ON pathways_gene_mapping.gene_id = genes.ensembl_id
              left JOIN genes_id_mapping ON genes_id_mapping.ensembl_id = genes.ensembl_id
              left JOIN pathways ON pathways_gene_mapping.pathway_id = pathways.pathway_id
              left JOIN gene_ontologies ON gene_ontologies.go_id = go_gene_mapping.go_id "       
        . $where_clause,
    1 => "SELECT DISTINCT(go_gene_mapping.ensembl_id) AS id,
              gene_ontologies.go_id, gene_ontologies.go_term, gene_ontologies.go_category,
              genes_id_mapping.uniprot_id, genes_id_mapping.ncbi_id
              FROM gene_ontologies
              INNER JOIN go_gene_mapping ON go_gene_mapping.go_id = gene_ontologies.go_id
              INNER JOIN genes_id_mapping ON genes_id_mapping.ensembl_id = go_gene_mapping.ensembl_id "
        . $and_clause
];

$result = getResult($mysqli, $queries);
if ($result->num_rows > 0)
    $jsonArr = createJSONArray($result);
mysqli_close($mysqli);

echo $jsonArr;
