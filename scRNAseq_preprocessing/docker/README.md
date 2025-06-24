# **DiVenn2 DEG Preprocessing Pipeline**

This directory contains scripts and a Docker environment for preprocessing **h5ad** and **rds (Seurat obj)** files to generate differentially expressed gene (DEG) files as input for **DiVenn2**. The provided Docker image ensures a standardized runtime environment for reproducibility.

## **Docker Image**
The preprocessing pipeline is encapsulated in a Docker image available on Docker Hub:

🛠 **Docker Image:** `rcbioinfo/divenn2_degpreprocessing:latest`

## **Installing Docker**
To use the DEG preprocessing pipeline, Docker must be installed and running on your system. Docker allows you to run applications in isolated environments called containers, ensuring consistency and reproducibility.

### 🔧 Installation Instructions

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

## **Folder Contents**

| File | Description |
|------|------------|
| **Dockerfile** | The script used to build the Docker image. |
| **Preprocessing_h5ad.py** | Python script for processing **h5ad** files to generate DEG files as input for DiVenn2. |
| **Preprocessing_Seuratobj.r** | R script for processing **rds (Seurat obj)** files to generate DEG files as input for DiVenn2. |
| **run_preprocessing.sh** | Wrapper script that allows users to run either `Preprocessing_h5ad.py` or `Preprocessing_Seuratobj.r` based on file type. |
| **runtime_code_python.sh** | Shell script for running the preprocessing pipeline inside the Docker container using the **h5ad** format. |
| **runtime_code_r.sh** | Shell script for running the preprocessing pipeline inside the Docker container using the **rds (Seurat obj)** format. |
| **README.md** | This documentation file. |

---

## **Running the Docker Image**
The following examples show how to run the Docker container for processing **h5ad** and **Seurat** files.

### **Example: Running the Pipeline for an h5ad File (Python)**
```bash
CONTAINER_ID=$(docker run -d \
  -v .../DiVenn2/scRNAseq_preprocessing/TestData:/data \
  rcbioinfo/divenn2_degpreprocessing:latest h5ad \
  -w /data \
  -i /data/TestInput.h5ad \
  -c group \
  -g celltype \
  -o /data/TestOutput_h5ad.csv \
  -f 0.2 \
  -r 0.01 \
  -v 0.05 \
  -x all
)
```

### **Example: Running the Pipeline for a rds File (R)**
```bash
CONTAINER_ID=$(docker run -d \
  -v .../DiVenn2/scRNAseq_preprocessing/TestData:/data \
  rcbioinfo/divenn2_degpreprocessing:latest seurat \
  -w /data \
  -i /data/TestInput.rds \
  -c group \
  -g celltype \
  -o /data/TestOutput_seurat.csv \
  -f 0.2 \
  -r 0.1 \
  -v 0.05 \
  -x all
)
```

#### **Parameter Descriptions**
| **Parameter** | **Description** |
|--------------|----------------|
| `-w, --workdir` | The working directory where files will be processed and stored. |
| `-i, --input` | Input file path (**h5ad** or **Seurat** format). |
| `-c, --condition` | Column name representing the sample condition (e.g., disease vs. normal). |
| `-g, --group` | Column name representing the cell type or other grouping variable. |
| `-o, --output` | Output file path for the processed DEG results (CSV format). |
| `-f` | Log fold-change filtering threshold for Seurat data (default: `0.2`). |
| `-r` | Minimum proportion of cells expressing a gene in one condition (default: `0.1`). |
| `-v` | Adjusted p-value threshold for Seurat data (default: `0.05`). |
| `-x, --comparisons` | Condition pairs for differential expression analysis (e.g., `"X:Y,X:Z"`). Use `"all"` for all possible comparisons. |

---

## **Output file**
The preprocessing step print out results in simple textual table.
| **Condition_1** | **Condition_2** | **CellType** | **Gene** | **Reg_direct** |
|--------------|----------------|--------------|----------------|----------------|
| X | Z | D | RNF220 | 1 |
| X	| Z	| D	| FRMD5	| 1 |
| X	| Z	| D	| AC092691.1 | 1 |
| X | Z | D | TNRC6B | 1 |

---

## **Notes**
- Ensure **Docker** is installed and running before executing the commands.
- The **volume mount (`-v /path/to/data:/data`)** should be updated to reflect your actual file locations.
- The container runs in **detached mode (`-d`)**, so you may use the following command to monitor progress:
  ```bash
  docker logs -f $CONTAINER_ID
