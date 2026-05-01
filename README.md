# **DiVenn 2**

**An Interactive and integrated web-based visualization and enrichment tool for comparing gene lists for bulk and single-cell RNA-seq data**

🔗 **Launch DiVenn 2**: https://divenn.tch.harvard.edu/v2

📄 **Original publication**: [Front. Genet. 2019 – DiVenn](https://www.frontiersin.org/journals/genetics/articles/10.3389/fgene.2019.00421/full)

🎥 **Tutorial video**: [Watch on YouTube](https://www.youtube.com/watch?v=OypczjArKoo)

---

<div align="center">
  <img src="./images/DiVenn2.1_Flowchart-April_15_2026_v5.png" alt="DiVenn 2 Flow chart" width="600px"/>
  <p><em>Figure 1: DiVenn 2 Flow chart</em></p>
</div>

---

## Table of Contents
- [Overview](#overview)
- [Introduction](#introduction)
- [Key Features](#key-features)
- [Input & Data Preparation](#input--data-preparation)
  - [Classic Analysis](#classic-analysis)
  - [Single-cell RNA-seq Analysis](#single-cell-rna-seq-analysis)
    - [Calculating DEG on-the-fly](#calculating-deg-on-the-fly-based-on-annotations)
    - [H5AD file with precomputed DEG](#h5ad-file-with-precomputed-deg)
- [Visualization & Interaction](#visualization--interaction)
- [Enrichment Analysis](#enrichment-analysis)
- [scRNAseq analysis and visualization](#scrnaseq-analysis-and-visualization)
- [Export Options](#export-options)
- [Citation](#citation)

## Overview
DiVenn 2 is a major upgrade to the original [DiVenn platform](https://www.frontiersin.org/journals/genetics/articles/10.3389/fgene.2019.00421/full), 
developed to support comprehensive and customizable comparison of gene lists from **bulk** level omics data and **single-cell RNA-seq (scRNA-seq)** datasets.
This release brings enhanced visualization, expanded species and ID support, built-in GO/KEGG enrichment tools, AI interpretation of enrichment results, and scRNAseq data analysis and visualization all through a simple, interactive web interface.

## Introduction
Gene expression data from different biological states - such as mutant, double mutant, and wild-type samples - are commonly compared using Venn diagram tools. These comparisons help identify shared and unique genes
between conditions and gain insights into their biological roles, especially through associated pathways and gene ontology (GO) terms.

To address the limitations of static Venn diagrams and to better explore these relationships, we originally developed [DiVenn](https://divenn.tch.harvard.edu/v1), an interactive web-based tool
that visualizes gene list overlaps using force-directed graphs enriched with integrated biological annotations. 
The platform was widely adopted for its ability to provide expression context and functional annotation through connected GO and KEGG pathway data.

Building on that foundation, **DiVenn 2** is a major upgrade to the original version. This release introduces new functionalities designed to support **bulk and scRNA-seq** workflows with greater customization, scalability, and analytic depth.

## Key Features
  
-   Comparison of up to **15 gene sets** simultaneously.
-   Supports both **bulk** and **scRNA-seq** inputs.
-   Interactive **force-directed network graphs** for dynamic visulaization.
-   Integrated **GO/KEGG pathway enrichment analysis** via the `clusterProfiler` R package.
-   High-resolution plot and interactive interactive exports.
-   Support **27 species**, including lesser-studied organisms. 
-   Accepts multiple gene ID types: **NCBI/Entrez, Ensembl, UniProt, Gene Symbol and Plant-specific ID types**.
-   Built-in scripts and Docker pipelines for scRNA-seq data preprocessing.

DiVenn 2 is freely available at <https://divenn.tch.harvard.edu/v2>.

---

## Input & Data Preparation

### Classic Analysis
DiVenn 2 accepts two input format for classic analysis: 

- **Two-column tab-delimited files**: 
  - First column: Gene IDs
  - Second column: Gene regulation values (1 for up-regulated, 2 for down-regulated genes)
 
- **Gene expression data**: The first column is gene IDs and the second column is gene regulation values. The gene regulation value should be obtained 
from differentially expressed (DE) genes. Users can select the cut-off value of fold change (for example, two-fold change) to define their DE genes. 
To simplify this gene regulation value, we require users to use “1” to represent up-regulated genes and “2” to represent down-regulated genes based 
on their own cut-off value of fold change. Additional columns can be added to include custom annotations, which could be useful for unsupported sepcies.

👉 [Sample Files](https://divenn.tch.harvard.edu/v2/data.php)

#### Interface Instructions
1. Select the `Classic Analysis` tab on the DiVenn homepage.  
2. Choose your species (requited for pathway/GO enrichment).
3. Select input ID type and number of experiments (up to 15).
4. Upload files for each experiment.
5. Click `Submit` to visualize.

<div align="left">
  <img src="./images/classic_loadData_202604.jpg" alt="Classic load Data"  width="100%"/>
  <p><em>Figure 2: Data input in classic analysis mode</em></p>
</div>

### Single-cell RNA-seq Analysis

An annotated `.h5ad` (H5 AnnData) file of single-cell data is accepted as the input. If users have a `.rds` file from the Seurat pipeline, we provide a Docker pipeline to preprocess and convert the data. DiVenn can perform differentially expressed gene analysis with default methods and parameters in Seurat and Scanpy. Users can use the Docker pipeline described below to adjust the parameters.

**Note**: Only Chrome and FireFox are supported for processing `.h5ad` files in the browser. Due to techinical limitations, Chrome can only work with files smaller than 2GB. If you encounter problems with large files (for example larger than 5GB), please consider using our Docker pipeline for preprocessing.

#### Docker Pipeline
- Accept `.rds` (Seurat) or `.h5ad` (Scanpy) files.
- Performs DEG analysis and generates a `.h5ad` file with computed DEGs.
- [Docker workflow details](./scRNAseq_preprocessing/docker)

<div align="left">
  <img src="./images/Flowchart-DEGprep.png" alt="Docker workflow for DEG preprocessing"  width="100%"/>
  <p><em>Figure 3: Workflow of the Docker pipeline for DEG preprocessing</em></p>
</div>

#### Interface Instructions
1. Select the `scRNAseq Analysis` tab.
2. Choose your species.
3. Choose the ID type.
4. Click the `H5AD` button to a separate page.

<div align="left">
  <img src="./images/scRNAseq_loadData_202604.jpg" alt="scRNA data input" width="100%"/>
  <p><em>Figure 4: Interface of scRNAseq data input</em></p>
</div>

In the new page, users can load a `.h5ad` file. DiVenn will detect whether the file contains DEG results either from the Docker pipeline or Scanpy. Users can select DEG lists for visualization and comparison in DiVenn.

#### Calculating DEG on-the-fly based on annotations
If no DEG results are found, users can select annotations from the file to calculate DEGs on the fly. Users should first choose the annotation of comparison conditions (e.g. disease and control) and then the cell subsets to compare in (e.g. cell types). The selected annotations will be used in the next step to select the comprisons. For example, condition 1 vs condition 2 in selected cell subsets, respectively. Multiple selection is supported with pressing the Shift key. More pairs of conditions can be added by clicking `Add Condition`. After clicking `Calcualte DEG`, the significant DEG lists will be shown similar to the precomputed results.

<div align="left">
  <img src="./images/scRNAseq_select_conditions_202604.jpg" alt="scRNA comparison selection" width="100%"/>
  <p><em>Figure 6: Interface to select DEG comparisons</em></p>
</div>

#### H5AD file with precomputed DEG 
If the input `.h5ad` file contains precomputed DEGs, either from the Docker pipeline or Scanpy analysis, DiVenn will extract the differential gene lists and users can directly select them to visulize on DiVenn graph. 
<div align="left">
  <img src="./images/scRNAseq_precomp_DEG.jpg" alt="scRNA data DEG lists" width="100%"/>
  <p><em>Figure 5: Interface to select precomputed DEG lists from scRNAseq data</em></p>
</div>


<div align="left">
  <img src="./images/scRNAseq_directed_graph.jpg" alt="scRNA Force Directed Graph" height="50%" width="100%"/>
  <p><em>Figure 7: scRNA Force Directed Graph</em></p>
</div>


#### Notes
- Use your own comparison names (e.g. `WT_vs_KO`), but **do not start names with a number**
- You can choose from gene ID types from Ensembl, Uniprot, gene symbol, NCBI/Entrez and plant specific IDs from Phytozome database
- You can upload up to 15 experiment data sets for comparison 
- Choose between 27 supported species from a drop-down menu

---

## Visualization & Interaction

### Force-Directed Graph
- Scrolling with the mouse wheel on the graph will zoom into/out of the graph.
- Left-clicking will highlight edges (expression patterns). 
- Double-clicking the same node will hide the connecting edge colors.
- Right-clicking a node will show five function options: show or hide one or all node labels, show all gene associated pathways, or GO terms.
- Right-clicking nodes can show the gene IDs of interest (See figure 8)

<div align="left">
  <img src="./images/scRNAseq_directed_graph_geneInfo.jpg" alt="Right-click functions" width="100%"/>
  <p><em>Figure 8: Right-click functions</em></p>
</div>

### Customization
- Adjust font size, color, and node shape (See figure 9)
- Summarize groups and collapse nodes
- Filter by condition, GO term, or pathway

<div align="left">
  <img src="./images/DiVenn_graph_customization.jpg" alt="Customize Appearance" width="100%"/>
  <p><em>Figure 9: Customize Appearance</em></p>
</div>

### Gene Information
Access detailed gene information by right-clicking nodes and select `Gene detail` (See figure 10)

<div align="left">
  <img src="./images/geneInfo.jpg" alt="Gene Info"  width="100%"/>
  <p><em>Figure 10: Gene Info</em></p>
</div>

---

## Enrichment Analysis

### KEGG pathway and GO terms
If users need to check the KEGG pathway or GO terms of a group of genes (for example, regulated genes in group Z versus group D in cell type D), they can choose the `Gene group detail` option after right clicking the node (See figure 11).

<div align="left">
  <img src="./images/scRNAseq_geneDetails.jpg" alt="Gene Pathway" width="100%"/>
  <p><em>Figure 11: Genes in a group with KEGG and GO annotations</em></p>
</div>

### GO Enrichment
To perform GO enrichment for this set of genes, users need to click `GO enrichment` tab. It uses `clusterProfiler` R package to perform GO enrichment.

User also can switch different GO enrichment results namely Biological Process (BP), Molecular Function (MF), and Cellular Component (CC). In the tab of each GO category, result table (figure 12), bar chart (figure 13), tree map plot (figure 14), and AI interpretation (figure 15) can be viewed.

Users can select GO terms in the table and update all the visualization and AI interpretation results. Multiple select with pressing the Shift key is supported. Users can sort the table or filter for gene set description or genes.

<div align="left">
  <img src="./images/GO_table_202604.jpg" alt="GO result table" width="100%"/>
  <p><em>Figure 12: GO result table</em></p>
</div>

By default, bar chart shows up to 20 terms. The GO terms to show can be adjusted from the result table.
<div align="left">
  <img src="./images/GO_barchart_202604.jpg" alt="GO Barplot" width="100%"/>
  <p><em>Figure 13: GO Barplot</em></p>
</div>

Tree map summaries the GO terms based on GO hierarchy when more than 10 terms are selected.
<div align="left">
  <img src="./images/GO_treemap_202604.jpg" alt="GO treemap" width="100%"/>
  <p><em>Figure 14: GO tree map</em></p>
</div>

The enrichment results are sent to Google's Gemma model for interpretation. Users can add experimental background to help improve the interpretation.
<div align="left">
  <img src="./images/GO_AI_202604.jpg" alt="GO AI interpretation" width="100%"/>
  <p><em>Figure 15: GO AI interpretation</em></p>
</div>

#### KEGG Enrichment
Similar to GO enrichment, user can perform KEGG pathway analysis by selecting the `KEGG pathway enrichment` and generate the same visualization, AI interpretation, and result table.

Users can select KEGG pathways and update all the visualization and AI interpretation results. Multiple select with pressing the Shift key is supported.
<div align="left">
  <img src="./images/KEGG_table_202604.jpg" alt="KEGG result table" width="100%"/>
  <p><em>Figure 16: KEGG result table</em></p>
</div>

<div align="left">
  <img src="./images/KEGG_bar_202604.jpg" alt="KEGG Barplot"  width="100%"/>
  <p><em>Figure 17: KEGG Barplot</em></p>
</div>

Tree map summaries KEGG pathways based on KEGG BRITE database when more than 10 pathways are selected.
<div align="left">
  <img src="./images/KEGG_treemap_202604.jpg" alt="KEGG treemap" width="100%"/>
  <p><em>Figure 18: KEGG tree map</em></p>
</div>

The enrichment results are sent to Google's Gemma model for interpretation. Users can add experimental background to help improve the interpretation.
<div align="left">
  <img src="./images/KEGG_AI_202604.jpg" alt="KEGG AI interpretation" width="100%"/>
  <p><em>Figure 19: KEGG AI interpretation</em></p>
</div>


---

## scRNAseq analysis and visualization
When the input `.h5ad` file of scRNAseq data contains UMAP and t-SNE coordinates stored in standard `X_UMAP` and `X_TSNE` slots, DiVenn can visualize expression of individual genes on the dimension reduction plot (often called feature plot). When right clicking on a gene node in the Divenn graph, there is a `Feature plot` menu option that will open a new page.

Users can color the cells by annotations in the file and search for genes to get feature plots. The color for annotations can be changed by clicking on the color boxes before the annotation labels. Cell groups can be hidden by unselecting from the annotation list.

<div align="left">
  <img src="./images/featurePlot_202604.jpg" alt="Feature plot" width="100%"/>
  <p><em>Figure 20: Feature plot</em></p>
</div>

From the `Gene group detail` window, users can also navigate the UMAP/t-SNE page by clicking the `UMAP/t-SNE` button. The `addModuleScore` algorithm from the Seurat package will be used to calculate the module score for this gene group (overlapping between multiple comparisons or unique to a comparison) and use the score to color the dimension reduction plot.

From the result table of GO and KEGG enrichment results, users can also view the feature plot of each overlapping gene in the pathway and calculate the module score of the input genes in the pathway (Figure 12 and 16).

<div align="left">
  <img src="./images/moduleScorePlot_202604.jpg" alt="Module score plot" width="100%"/>
  <p><em>Figure 21: UMAP plot colored by module score</em></p>
</div>

---

## Export Options

You can export:

- Network diagrams
- Bar plots
- Enrichment tables (.csv)
- Gene/group details reports
- Feature plot and module score plot on UMAP or t-SNE

---

## Citation

Please cite the original DiVenn publication if you use this tool:

> **Sun et al.** *DiVenn: An Interactive and Integrated Web-Based Visualization Tool for Comparing Gene Lists*. Front. Genet. 2019.  
> [https://doi.org/10.3389/fgene.2019.00421](https://doi.org/10.3389/fgene.2019.00421)

---

## Contact & Contributions

DiVenn is developed and maintained by the **Research Computing Bioinformatics Team at Boston Children Hospital**.  
For issues or feature requests, [open an issue](https://github.com/BCH-RC/DiVenn2/issues) or reach out through the homepage.

