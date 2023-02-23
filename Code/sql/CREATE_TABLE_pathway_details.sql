CREATE TABLE pathway_details AS
SELECT DISTINCT(p.kegg_id), p.gene_desc, p.pathway, p.path_url, in_path, not_in_path
FROM pathway p LEFT JOIN ncbi2kegg n ON p.kegg_id = n.kegg_id LEFT JOIN uniprot2kegg u ON p.kegg_id=u.kegg_id
