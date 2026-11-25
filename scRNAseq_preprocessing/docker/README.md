# **DiVenn 2 DEG Preprocessing Pipeline**

If your data is already in **.h5ad** format, we recommend using the DiVenn 2 website first, as it provides the most straightforward workflow with default DEG parameters. Annotated scRNA-seq data in either **.h5ad** or **.rds** format can be used as input for DEG preprocessing. **.h5ad** files can be processed through the Docker-based pipeline when customized DEG parameters are needed, whereas .rds files must be processed through the Docker-based pipeline. In the Docker workflow, users install Docker, download the pipeline from Docker Hub, and run DEG preprocessing locally. The final output is an .h5ad file containing DEG information that is ready for DiVenn 2 input.

![Divenn Flow Chart](../../images/Flowchart-DEGprep.png)

The following sections contain scripts and a Docker/Singularity-based environment for preprocessing single-cell datasets in **h5ad** and **rds (Seurat obj)** formats to generate differentially expressed gene (DEG) files as input for **DiVenn 2**. The containerized setup ensures reproducibility and consistency across computing environments.

## **Docker Image**
The preprocessing pipeline is encapsulated in a pre-built Docker image:

🛠 **Docker Hub:** [rcbioinfo/divenn2_degpreprocessing:latest](https://hub.docker.com/r/rcbioinfo/divenn2_degpreprocessing:latest)

To build the Docker image locally:

```bash
docker build -t divenn2_degpreprocessing:latest .
```

## ⚙️ **Installation Instructions**
To use the DEG preprocessing pipeline, Docker (or Singularity, for HPC systems) must be installed and running/loaded on your system. Docker allows you to run applications in isolated environments called containers, ensuring consistency and reproducibility.

### 🔧 **Docker Setup by Platform:**

#### macOS
Download and Install Docker Desktop on Mac:  
👉 [https://docs.docker.com/desktop/setup/install/mac-install/](https://docs.docker.com/desktop/setup/install/mac-install/)

#### Windows
Download and Install Docker Desktop on Windows:  
👉 [https://docs.docker.com/desktop/setup/install/windows-install/](https://docs.docker.com/desktop/setup/install/windows-install/)

#### Linux (Ubuntu)
Download and Install Docker Desktop on Linux:  
👉 [https://docs.docker.com/desktop/setup/install/linux/](https://docs.docker.com/desktop/setup/install/linux/)

### Get Started
Explore Docker Desktop:
👉 [https://docs.docker.com/desktop/use-desktop/](https://docs.docker.com/desktop/use-desktop/)

## 🧪 **Using Singularity (for HPC):**
To use this pipeline in HPC environments, convert the Docker image into a Singularity image:
```bash
singularity pull divenn2_degpreprocessing.sif docker://rcbioinfo/divenn2_degpreprocessing:latest
```

## **Folder Contents**

| File | Description |
|------|------------|
| `Dockerfile` | The script used to build the Docker image. |
| `Preprocessing_h5ad.py` | Python script for processing **h5ad** files to generate DEG files as input for DiVenn 2. |
| `Preprocessing_Seuratobj.r` | R script for processing **rds (Seurat obj)** files to generate DEG files as input for DiVenn 2, in `single_cell` or `pseudobulk` mode. |
| `run_preprocessing.sh` | Wrapper script that allows users to run either `Preprocessing_h5ad.py` or `Preprocessing_Seuratobj.r` based on file type. |
| `README.md` | This documentation file. |
---

## **Running the Pipeline**

Both workflows require an annotated object with:

- A metadata column identifying the condition to compare, passed with `-c, --condition`.
- A metadata column identifying the cell group, cell type, or cluster to run DE within, passed with `-g, --group`.

The `.h5ad` workflow supports two DEG analysis modes controlled by `--deg_mode`:

- **`single_cell` (default):** Uses `scanpy.tl.rank_genes_groups`. It requires at least 3 cells per condition within a cell group (comparisons with fewer cells are skipped) and uses `adata.raw.X` when usable; otherwise it uses `adata.X` (typically normalized/log-transformed expression).
- **`pseudobulk`:** Sums raw integer UMI counts by biological sample (`--sample_col`) within each cell group (`-g`) and runs `PyDESeq2` (`DeseqDataSet` and `DeseqStats` with default parameters and `~condition` design). It requires an unnormalized integer count matrix (`--counts_layer`, default `counts`, or `X`/`raw`), at least `--min_cells_per_sample` cells per sample/cell-type (default `3`), and at least `--min_replicates` biological samples per condition (default `2`). Single-cell expression fraction filters (`-r`, `-p`) are not applied in `pseudobulk` mode.

The Seurat (`.rds`) workflow supports the same two modes through `--deg_mode`:

- **`single_cell` (default):** Runs Seurat `FindMarkers` within each cell group on the `--assay` assay (default `RNA`). It uses the `data` layer for most tests and the `counts` layer for `negbinom` and `poisson`. `-r` is passed to `FindMarkers` as `min.pct` and `-f` as `logfc.threshold`. Comparisons with fewer than 3 cells per condition within a cell group are skipped. If the `data` layer is missing or not normalized, `NormalizeData` is run first.
- **`pseudobulk`:** Sums raw integer UMI counts from `--counts_layer` (default `counts`) by biological sample (`--sample_col`) within each cell group (`-g`) using `Seurat::AggregateExpression`, then runs `FindMarkers(test.use = "DESeq2")` with samples as the replicates. P-values come from the DESeq2 Wald test, `padj` is Seurat's Bonferroni-adjusted `p_val_adj` (adjusted over all genes in the object), and `log2FoldChange` is Seurat's `avg_log2FC`. It requires an unnormalized integer count layer, at least `--min_cells_per_sample` cells per sample/cell-type (default `10`; smaller samples are excluded before aggregation), and at least `--min_replicates` biological samples per condition (default `2`). The cell expression fraction filter (`-r`) and `-m` are not applied in `pseudobulk` mode; genes are filtered on `-v` and `-f` only.

Seurat v5 objects with split layers (e.g. after `IntegrateLayers`) are handled automatically: layers are joined before DEG analysis.

### **Using Docker**
The following examples show how to run the Docker container for processing **h5ad** and **Seurat** files.

#### **Example: Running the Pipeline for an h5ad File in Single-cell Mode (Python)**
```bash
CONTAINER_ID=$(docker run -d \
  -v .../DiVenn2/scRNAseq_preprocessing/TestData:/data \
  rcbioinfo/divenn2_degpreprocessing:latest h5ad \
  -w /data \
  -i /data/TestInput.h5ad \
  -c group \
  -g celltype \
  -o /data/TestOutput_h5ad.h5ad \
  --deg_mode single_cell \
  -f 0.2 \
  -r 0.01 \
  -p 0.5 \
  -v 0.05 \
  -x all \
  -m wilcoxon \
  -t benjamini-hochberg
)
```

#### **Example: Running the Pipeline for a rds File in Single-cell Mode (R)**
```bash
CONTAINER_ID=$(docker run -d \
  -v .../DiVenn2/scRNAseq_preprocessing/TestData:/data \
  rcbioinfo/divenn2_degpreprocessing:latest seurat \
  -w /data \
  -i /data/TestInput.rds \
  -c group \
  -g celltype \
  -o /data/TestOutput_seurat.h5ad \
  --deg_mode single_cell \
  -f 0.2 \
  -r 0.1 \
  -v 0.05 \
  -x all \
  -m wilcox \
  -s
)
```

#### **Example: Running the Pipeline for a rds File in Pseudobulk Mode (R)**
```bash
CONTAINER_ID=$(docker run -d \
  -v .../DiVenn2/scRNAseq_preprocessing/TestData:/data \
  rcbioinfo/divenn2_degpreprocessing:latest seurat \
  -w /data \
  -i /data/TestInput.rds \
  -c group \
  -g celltype \
  -o /data/TestOutput_seurat_pseudobulk.h5ad \
  --deg_mode pseudobulk \
  --sample_col sample \
  --counts_layer counts \
  --min_cells_per_sample 10 \
  --min_replicates 2 \
  -f 0 \
  -v 0.05 \
  -x all \
  -s \
  --write_stats_csv
)
```
### **Using Singularity on HPC Server**
Make sure to load Singularity on your HPC system (e.g., via module load singularity), then run:

#### **Example: Running the Pipeline for an h5ad File in Single-cell Mode (Python)**
```bash
singularity run -B ../DiVenn2/scRNAseq_preprocessing/TestData:/data \
  divenn2_degpreprocessing.sif h5ad \
  -w /data \
  -i /data/TestInput.h5ad \
  -c group \
  -g celltype \
  -o /data/TestOutput_h5ad.h5ad \
  --deg_mode single_cell \
  -f 0.2 \
  -r 0.01 \
  -p 0.5 \
  -v 0.05 \
  -x all \
  -m wilcoxon \
  -t benjamini-hochberg
```

#### **Example: Running the Pipeline for a rds File in Single-cell Mode (R)**
```bash
singularity run -B ../DiVenn2/scRNAseq_preprocessing/TestData:/data \
  divenn2_degpreprocessing.sif seurat \
  -w /data \
  -i /data/TestInput.rds \
  -c group \
  -g celltype \
  -o /data/TestOutput_seurat.h5ad \
  --deg_mode single_cell \
  -f 0.2 \
  -r 0.1 \
  -v 0.05 \
  -x all \
  -m wilcox \
  -s
```

#### **Example: Running the Pipeline for a rds File in Pseudobulk Mode (R)**
```bash
singularity run -B ../DiVenn2/scRNAseq_preprocessing/TestData:/data \
  divenn2_degpreprocessing.sif seurat \
  -w /data \
  -i /data/TestInput.rds \
  -c group \
  -g celltype \
  -o /data/TestOutput_seurat_pseudobulk.h5ad \
  --deg_mode pseudobulk \
  --sample_col sample \
  --counts_layer counts \
  --min_cells_per_sample 10 \
  --min_replicates 2 \
  -f 0 \
  -v 0.05 \
  -x all \
  -s \
  --write_stats_csv
```

### **Parameter Descriptions**
| Parameter | Applies to | Description |
| --- | --- | --- |
| `h5ad` or `seurat` | Both | First argument after the image name. Selects the Python `.h5ad` workflow or the R Seurat workflow. |
| `-w, --workdir` | Both | Working directory inside the container. Usually the mounted `/data` directory. |
| `-i, --input` | Both | Input file path inside the container. Use `.h5ad` for `h5ad` mode or `.rds` for `seurat` mode. |
| `-c, --condition` | Both | Metadata column containing the sample condition, such as disease/control. |
| `-g, --group` | Both | Metadata column containing the cell type, cluster, or other grouping variable. |
| `-o, --output` | Both | Output `.h5ad` file path. A companion DEG CSV is also written by default (Seurat workflow: only when `-s` is passed). |
| `-f, --logfc_thd` | Both | Absolute log fold-change threshold. Defaults: `.h5ad` workflow `1`; Seurat workflow `0.1`. |
| `-r, --minpct_thd` | Both | Minimum fraction of cells expressing a gene (`single_cell` mode only in both workflows; ignored in `pseudobulk`). In the Seurat workflow, a gene passes if it is detected in at least this fraction of cells in either condition (`FindMarkers` `min.pct`). Defaults: `.h5ad` workflow `0.25`; Seurat workflow `0.01`. |
| `-v, --padj_thd` | Both | Adjusted p-value threshold. Default: `0.05`. The Seurat workflow applies it to the Bonferroni-adjusted `p_val_adj` from `FindMarkers` in both modes. |
| `-x, --comparisons` | Both | Condition comparisons as `A:B,A:C`, where `A` is Condition_1 and `B` is Condition_2. Use `all` for all directed pairwise comparisons. |
| `-m, --method` | Both | Differential expression test (`single_cell` mode only; `.h5ad` `pseudobulk` uses `PyDESeq2`, Seurat `pseudobulk` uses DESeq2 through `FindMarkers`). See method options below. |
| `-l, --gene_list_file` | Both | Optional text file with one gene per line for post-DEG filtering. |
| `-d, --gene_filter_mode` | Both | Optional gene-list filtering mode: `remove` excludes listed genes; `keep` keeps only listed genes. |
| `-a, --gene_filter_ignore_case` | Both | Ignore case when applying the gene-list filter. |
| `-p, --maxpct_thd` | `.h5ad` only | Maximum out-group expression fraction used by Scanpy filtering. Default: `0.5`. |
| `-t, --correction_method` | `.h5ad` only | Scanpy p-value correction method: `benjamini-hochberg` or `bonferroni`. Default: `benjamini-hochberg`. |
| `-s, --write_csv` | Both | CSV behavior differs by script. In the `.h5ad` workflow, passing `-s` disables CSV writing. In the Seurat workflow, pass `-s` to write the CSV; it is not written by default. |
| `--deg_mode` | Both | DEG analysis mode: `single_cell` (Scanpy / Seurat `FindMarkers`) or `pseudobulk` (`PyDESeq2` / Seurat `AggregateExpression` + `FindMarkers(test.use = "DESeq2")`). Default: `single_cell`. |
| `--sample_col` | Both | Metadata column containing unique biological sample/replicate IDs. **Required** when `--deg_mode pseudobulk`. |
| `--counts_layer` | Both | Source of raw integer UMI counts for `pseudobulk` mode. `.h5ad`: `counts` layer name, `X`, or `raw`. Seurat: a layer of the `--assay` assay. Default: `counts`. |
| `--min_cells_per_sample` | Both | Minimum number of cells required per sample within a cell group in `pseudobulk` mode; smaller groups are excluded before aggregation. Defaults: `.h5ad` workflow `3`; Seurat workflow `10`. |
| `--min_replicates` | Both | Minimum number of biological replicate samples required per condition in `pseudobulk` mode. Default: `2`. |
| `--assay` | Seurat only | Seurat assay to use. Default: `RNA`. |
| `--n_cpus` | `.h5ad` only | Number of CPU workers used by `PyDESeq2` in `pseudobulk` mode. Default: `1`. |
| `--write_stats_csv` | Both | Optional flag to write an additional `<output_basename>_deg_statistics.csv` table with full statistical metrics. `.h5ad` workflow: `log2FoldChange`, `pvalue`, `padj`, `baseMean`, etc. Seurat workflow: `log2FoldChange`, `pvalue`, `padj`, plus `pct.1` and `pct.2` in `single_cell` mode. |

Method options:

- `.h5ad` / Scanpy: `wilcoxon`, `t-test`, `t-test_overestim_var`, `logreg`.
- Seurat (`single_cell` mode): `wilcox`, `wilcox_limma`, `bimod`, `roc`, `t`, `negbinom`, `poisson`, `LR`, `MAST`.

---

## 📤  **Output Format**

The primary output is a DiVenn2-ready `.h5ad` file. DEG results are embedded in `adata.uns` under keys like:

```text
rank_genes_groups__ct=<cell_type>__<condition_1>_vs_<condition_2>
```

The catalog of available DEG result keys is stored in:

```text
adata.uns["divenn_rank_genes_groups_catalog"]
```

The DEG analysis mode is stored in `adata.uns["divenn_deg_mode"]` (`single_cell` or `pseudobulk`).

By default, the workflow also writes a companion CSV named from the output file (Seurat workflow: only when `-s` is passed):

```text
<output_basename>_divenn2_deg.csv
```

In both workflows, passing `--write_stats_csv` also writes a full statistics table named:

```text
<output_basename>_deg_statistics.csv
```

The CSV has the standardized DiVenn 2 DEG table format:

| **Condition_1** | **Condition_2** | **CellType** | **Gene** | **Reg_direct** |
|--------------|----------------|--------------|----------------|----------------|
| X | Z | D | RNF220 | 1 |
| X	| Z	| D	| FRMD5	| 1 |
| X	| Z	| D	| AC092691.1 | 1 |
| X | Z | D | TNRC6B | 2 |

where 
- `Condition_1` and `Condition_2`: Conditions being compared. (e.g., disease vs control)
- CellType: Cell type (or group) where DEG analysis was performed
- Gene: Gene symbol
- Reg_direct: Direction of regulation - 1 = upregulated in Condition_1, 2 = downregulated in Condition_1

---

## 📝 **Notes**
- Ensure **Docker** or **Singularity** are installed and running before executing the commands.
- The **volume mount (`-v /path/to/data:/data`)** should be updated to reflect your actual file locations.
- The container runs in **detached mode (`-d`)**, so you may use the following command to monitor progress:
  ```bash
  docker logs -f $CONTAINER_ID
  ```
