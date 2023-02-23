SELECT DISTINCT(genes_id_mapping.ncbi_id) AS id, genes.gene_desc, group_concat(distinct pathways.path_desc SEPARATOR '\\n') as pathway, group_concat(distinct pathways.path_url SEPARATOR '\\n') as urls 
              FROM genes
              INNER JOIN genes_id_mapping ON genes_id_mapping.ensembl_id = genes.ensembl_id
              INNER JOIN pathways_gene_mapping ON pathways_gene_mapping.gene_id = genes.ensembl_id
              INNER JOIN pathways ON pathways_gene_mapping.pathway_id = pathways.pathway_id 
              INNER JOIN species ON pathways.species_id = species.species_id
              WHERE genes_id_mapping.ncbi_id in ("839523", "839106", "838517") Group By id, genes.gene_desc