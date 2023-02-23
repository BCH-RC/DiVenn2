CREATE TABLE ids_with_symbols AS
    SELECT p.kegg_id,
        u.uniprot_id,
        n.ncbi_id,
        p.symbol
    FROM pathway p
        LEFT JOIN ncbi2kegg n ON p.kegg_id = n.kegg_id
        LEFT JOIN uniprot2kegg u ON p.kegg_id = u.kegg_id
    GROUP BY p.kegg_id,
        p.symbol,
        u.uniprot_id,
        n.ncbi_id;