## **DiVenn 2.0**

**An Interactive and integrated web-based visualization and enrichment tool for comparing gene lists for bulk and single-cell RNA-seq data**

🔗 **Launch DiVenn 2.0**: https://divenn.tch.harvard.edu/v2

📄 **Original publication**: [Front. Genet. 2019 – DiVenn](https://www.frontiersin.org/journals/genetics/articles/10.3389/fgene.2019.00421/full)

🎥 **Tutorial video**: [Watch on YouTube](https://www.youtube.com/watch?v=OypczjArKoo)

---

<div align="center">
  <img src="./new_tutorial_imgs/DiVenn2_flowchart_v3.PNG" alt="DiVenn 2.0 Flow chart" height="50%" width="600"/>
  <p><em></em></p>
</div>

<div align="left">
  <img src="./new_tutorial_imgs/homepage3_0.PNG" alt="DiVenn 2.0 Web Interface" width="100%"/>
  <p><em>Figure 1: DiVenn 2.0 home page interface. Users can upload DEG files and select between classic bulk RNA-seq or scRNA-seq modes for visualization and enrichment.</em></p>
</div>

---

### Table of Contents
- [Overview](#Overview)
- [Introduction](#Introduction)
- [Key Features](#Key-Features)
- [Input & Data Preparation](#Input-&-Data-Preparation)
  - [Classic Analysis](#Classic_Analysis)
  - [Single-cell RNA-seq Analysis](#Single-cell-RNA-seq-Analysis)
- [Visualization & Interaction](#[Visualization-&-Interaction)
- [Enrichment Analysis](#Enrichment-Analysis)
- [Species and ID Mapping](#Species-and-ID-Mapping)
- [Export Options](#Export-Options)
- [Citation](#Citation)

### Overview
DiVenn 2.0 is a major upgrade to the original [DiVenn platform](https://www.frontiersin.org/journals/genetics/articles/10.3389/fgene.2019.00421/full), 
developed to support comprehensive and customizable comparison of gene lists from **bulk** and **single-cell RNA-seq (scRNA-seq)** datasets.
This release brings enhanced visualization, expanded species and ID support, and built-in GO/KEGG enrichment tools, all through a simple, interactive web interface.

### Introduction
Gene expression data from different biological states—such as mutant,double mutant, and wild-type samples—are commonly compared using Venn diagram tools. These comparisons help identify shared and unique genes
between conditions and gain insights into their biological roles, especially through associated pathways and gene ontology (GO) terms.

To address the limitations of static Venn diagrams and to better explore these relationships, we originally developed [DiVenn](https://divenn.tch.harvard.edu), an interactive web-based tool
that visualizes gene list overlaps using force-directed graphs enriched with integrated biological annotations. 
The platform was widely adopted for its ability to provide expression context and functional annotation through connected GO and KEGG pathway data.

Building on that foundation, **DiVenn 2.0** is a major upgrade to the original version. This release introduces new functionalities designed to support **bulk and scRNA-seq** workflows with greater customization, scalability, and analytic depth.

#### Key Features:
  
-   Comparison of up to **15 gene sets** simultaneously.
-   Supports both **bulk** and **scRNA-seq** inputs.
-   Interactive **force-directed network graphs** for dynamic visulaization.
-   Integrated **GO/KEGG pathway enrichment analysis** via `clusterProfiler` R package.
-   High-resolution plot and interactive interactive exports.
-   Support **27 species**, including lesser-studied organisms. 
-   Accepts multiple gene ID types: **NCBI/Entrez, Ensembl, UniProt, and Gene Symbol**.
-   Built-in scripts and Docker pipelines for scRNA-seq DEG preparation.

DiVenn 2.0 is freely available at <https://divenn-dev.tch.harvard.edu/v3_yl/index.php>.

---

### Input & Data Preparation

#### Classic Analysis
DiVenn 2.0 accepts two input format for classic analysis: 

- **Two-column tab-delimited files**: 
  - First column: Gene IDs
  - Second column: Gene regulation values (1 for up-regulated, 2 for down-regulated genes)
 
- **Gene expression data**: The first column is gene IDs and the second column is gene regulation value. The gene regulation value should be obtained 
from differentially expressed (DE) genes. Users can select the cut-off value of fold change (for example, two-fold change) to define their DE genes. 
To simplify this gene regulation value, we require users to use “1” to represent up-regulated genes and “2” to represent down-regulated genes based 
on their own cut-off value of fold change. If users need to link their genes to the KEGG pathway (Kanehisa and Goto, 2000) or GO database, 
27 model species are supported in DiVenn. Currently, three types of gene IDs : KEGG, Uniprot (UniProt, 2008) and NCBI (Benson, et al., 2018), 
are accepted for pathway analysis. All agriGO (Du, et al., 2010; Tian, et al., 2017) supported IDs are supported for GO analysis by 
DiVenn ([View table] or download in [Excel]). 

👉 [Sample Files](https://divenn.tch.harvard.edu/v2/data.php)

##### Interface Instructions
1. Select the `Classic Analysis` tab on the DiVenn homepage.  
2. Choose your species (requited for pathway enrichment).
3. Select input type and number of  experiments (up to 15).
4. Upload files for each experiment.
5. Click `Submit` to visualize.

<div align="left">
  <img src="./new_tutorial_imgs/classic_loadData_2.PNG" alt="Classic load Data" height="50%" width="100%"/>
  <p><em>Figure 2: Classic load Data</em></p>
</div>

#### Single-cell RNA-seq Input

Single-cell data must first be preprocessed using provided Docker pipeline.

##### Input Format
CSV file with required columns: `Condition_1`, `Condition_2`, `CellType`, `Gene`, `Reg_direct`. [Example Data](https://github.com/BCH-RC/DiVenn2/tree/main/scRNAseq_preprocessing/TestData))

##### Docker Pipeline
- Accept `.rds` (Seurat) or `.h5ad` (Scanpy) files.
- Performs DEG analysis and generates DiVenn-compatible CSV files.
- [Docker workflow details](https://github.com/BCH-RC/DiVenn2/tree/main/scRNAseq_preprocessing/docker)

##### Interface Instructions
1. Select the `scRNAseq Analysis` tab.
2. Choose your species.
3. Upload processed `.csv` file  
4. Select comparison conditions and cell type(s). 
5. Click `Submit` to visualize.

<div align="left">
  <img src="./new_tutorial_imgs/scRNAseq_conditionSelection.PNG" alt="scRNA Condition Select" height="50%" width="100%"/>
  <p><em>Figure 3: scRNA Condition Select</em></p>
</div>

<div align="left">
  <img src="./new_tutorial_imgs/scRNAseq_directed_graph.PNG" alt="scRNA Force Directed Graph" height="50%" width="100%"/>
  <p><em>Figure 4: scRNA Force Directed Graph</em></p>
</div>

##### Notes
- Use your own comparison names (e.g. `WT_vs_KO`), but **do not start names with a number**
- You can choose from four gene ID types (Ensembl, Uniprot, gene symbol and NCBI/Entrez. (see [ID Mapping](#species-and-id-mapping))
- You can upload up to 15 experiment data sets for comparison 
- Choose between 27 supported species from a drop-down menu

---

### Visualization & Interaction

#### Force-Directed Graph
- Scrolling with the mouse wheel on the graph will zoom into/out of the graph.
- Left-clicking will highlight edges (expression patterns). 
- Double-clicking the same node will hide the connecting edge colors.
- Right-clicking a node will show five function options: show or hide one or all node labels, show all gene associated pathways, or GO terms.
- Right-clicking nodes can show the gene IDs of interest (See figure 5)

<div align="left">
  <img src="./new_tutorial_imgs/scRNAseq_directed_graph_geneInfo.PNG" alt="Right-click functions"height="50%" width="100%"/>
  <p><em>Figure 5: Right-click functions</em></p>
</div>

#### Customization
- Adjust font size, color, and node shape (See figure 6)
- Summarize groups and collapse nodes
- Filter by condition, GO term, or pathway

<div align="left">
  <img src="./new_tutorial_imgs/shape_font_size2.PNG" alt="Customize Appearance" height="50%" width="100%"/>
  <p><em>Figure 6: Customize Appearance</em></p>
</div>

#### Gene Information
Access detailed gene information by right-clicking nodes and select `Gene detail` (See figure 7)

<div align="left">
  <img src="./new_tutorial_imgs/geneInfo.PNG" alt="Gene Info" height="50%" width="100%"/>
  <p><em>Figure 7: Gene Info</em></p>
</div>

---

### Enrichment Analysis

#### KEGG pathway and GO terms
If users need to check the KEGG pathway or GO terms of a group of genes (examples regulated genes in group Z versus group D in cell type D), they can choose the `Gene group detail` option after right clicking the node (See figure 8).

<div align="left">
  <img src="./new_tutorial_imgs/scRNAseq_geneDetails.PNG" alt="Gene Pathway" height="50%" width="100%"/>
  <p><em>Figure 8: Gene Pathway</em></p>
</div>

#### 2. GO Enrichment
To perform GO enrichment for this set of genes, users need to click `GO enrichment` tab. It uses `clusterProfiler` R package and perform GO enrichment.
User also can choose different GO enrichment such as Biological Process (BP), Molecular Function (MF), and Cellular Component (CC). (See figures 8 and 9)

<div align="left">
  <img src="./new_tutorial_imgs/GO_enrich_3.PNG" alt="GO Table" height="50%" width="100%"/>
  <p><em>Figure 8: GO Table</em></p>
</div>

<div align="left">
  <img src="./new_tutorial_imgs/GO_barplot_2.PNG" alt="GO Barplot" height="50%" width="100%"/>
  <p><em>Figure 9: GO Barplot</em></p>
</div>

#### 3. KEGG Enrichment
Similar to GO enrichment, user can perform KEGG pathway analysis by selecting the `KEGG pathway enrichment` and generate an interactive table and bar plots.

<div align="left">
  <img src="./new_tutorial_imgs/scRNAseq_KEGG_table.PNG" alt="KEGG Table" height="50%" width="100%"/>
  <p><em>Figure 11: KEGG Table</em></p>
</div>

<div align="left">
  <img src="./new_tutorial_imgs/scRNAseq_KEGG_plot.PNG" alt="KEGG Barplot" height="50%" width="100%"/>
  <p><em>Figure 10: KEGG Barplot</em></p>
</div>

---

### Species & ID Mapping

- Extensive species support (278 species).
- Multiple gene ID types accepted.
- Custom mappings added for species without standard annotations
- Example organisms with limited mapping: *Dictyostelium discoideum*, *Marchantia polymorpha*, *Physcomitrella patens*

![Mapping Flow](./new_tutorial_imgs/Mapping_Flow.png)

---

### Export Options

You can export:

- Network diagrams
- Bar plots
- Enrichment tables (.csv)
- Gene/group details reports

---

### Citation

Please cite the original DiVenn publication if you use this tool:

> **Sun et al.** *DiVenn: An Interactive and Integrated Web-Based Visualization Tool for Comparing Gene Lists*. Front. Genet. 2019.  
> [https://doi.org/10.3389/fgene.2019.00421](https://doi.org/10.3389/fgene.2019.00421)

---

## Contact & Contributions

DiVenn is developed and maintained by the **Research Computing Bioinformatics Team at Boston Children Hospital**.  
For issues or feature requests, [open an issue](https://github.com/BCH-RC/DiVenn2/issues) or reach out through the homepage.

