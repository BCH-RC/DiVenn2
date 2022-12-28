# DiVenn

**DiVenn** is an interactive and integrated web-based tool for comparing gene lists

## Resources

- [API Reference](https://github.com/BCH-RC/DiVenn2_Ardigen/blob/main/API.md)
- [Client's README](https://github.com/BCH-RC/DiVenn/blob/master/README.md)

## Introduction

Gene expression data generated from multiple biological states (mutant sample, double mutant sample and wild-type samples) are often compared via Venn diagram tools. It is of great interest to know the expression pattern between overlapping genes and their associated gene pathways or gene ontology terms. We developed DiVenn – a novel web-based tool that compares gene lists from multiple RNA-Seq experiments in a force directed graph, which shows the gene regulation levels for each gene and integrated KEGG pathway and gene ontology (GO) knowledge for the data visualization.

### DiVenn has three key features:

- Informative force-directed graph with gene expression levels to compare multiple data sets;
- Interactive visualization with biological annotations and integrated pathway and GO databases, which can be used to subset or highlight gene nodes to pathway or GO terms of interest in the graph;
- High resolution image and gene-associated information export.

_The current version is “2.0”._

The application is freely available at https://divenn.tch.harvard.edu/ (see Figure 1).

## Usage

- Create `config.inc.php` based on `config.example.php`.
- Start application using Docker Compose: `docker-compose up`
- Database create & restore: `./restore-db.sh`
