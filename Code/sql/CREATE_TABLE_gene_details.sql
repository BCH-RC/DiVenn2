USE divenn_db;
CREATE TABLE gene_details AS
SELECT p.kegg_id, p.symbol, p.gene_desc,p.pathway,p.path_url,g.go_id,g.go_term, g.go_category, u.uniprot_id, n.ncbi_id
FROM pathway p LEFT JOIN go g ON p.kegg_id = g.id
LEFT JOIN ncbi2kegg n ON p.kegg_id = n.kegg_id LEFT JOIN uniprot2kegg u ON p.kegg_id = u.kegg_id
GROUP BY p.kegg_id, p.symbol, p.gene_desc,p.pathway,p.path_url,g.go_id,g.go_term, g.go_category, u.uniprot_id, n.ncbi_id;
