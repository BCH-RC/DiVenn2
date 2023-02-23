# Run downloading gene ontologies

## Requirements

### - R (>= 3.2.1)

### - mygene R package
```
if (!require("BiocManager", quietly = TRUE))
    install.packages("BiocManager")

BiocManager::install("mygene")
```

## Usage

```angular2html
Rscript download_gene_ontologies.R species_file_path input_directory_path output_directory_path
```
## Arguments
- species_file_path:
    - example species list: 
  ```
  bta
  cfa
  dre
  ```
    - list of species codes
    - each record in new line
    - codes used to build input and output files path
- input_directory_path:
    - contains input files
    - input files are named: ``` speciescode_ncbi2kegg (eg. bta_ncbi2kegg)```
    - data downloaded from KEGG database API with command:
    ```http://rest.kegg.jp/conv/speciescode/ncbi-geneid```
- output_directory_path:
  - path to the directory where the results will be saved
  - results file naming convention: ```speciescode_go_results.tsv ```
    
## Description

### Script Steps

- Open species_list.txt file
- For each species:
    - open input file
    - read first column with **NCBI IDs**
    - get gene ontologies with mygene **getGenes** function based on **NCBI IDs**
    - parse results
    - save to results file in the following format:
```
gene_id	go_id	go_category	term
839580	GO:0006355	P	involved in regulation of transcription, DNA-templated
839580	GO:0006355	P	acts upstream of or within regulation of transcription, DNA-templated
839580	GO:0009414	P	acts upstream of or within response to water deprivation
```
