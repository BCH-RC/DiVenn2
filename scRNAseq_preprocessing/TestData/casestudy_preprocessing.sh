#!/bin/bash
source /programs/biogrids.shrc

export PYTHON_X=3.11
export R_X=4.4.2

# ---- Set Path ----
Gene_list="/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2/DiVenn2-main/scRNAseq_preprocessing/case_study/fmr1_gene_targets.txt"
output_dir="/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2/DiVenn2-main/scRNAseq_preprocessing/case_study"

# ---- R version ----
file_path="/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2/DiVenn2-main/scRNAseq_preprocessing/case_study/p111_casestudy_ctx.rds"
# NOTE: Seurat v4:findMarkers ln-FC cutoff = 0.25 ~ Seurat v5:FindMarkers log2-FC cutoff = 0.36 (log2(x) =log(x)/log(2))
Rscript Preprocessing_Seuratobj_dev.R \
  -w "$output_dir" \
  -i "$file_path" \
  -c "sample" \
  -g "celltypes" \
  -x "FXPM:CON,FXS:CON" \
  -m "MAST" \
  -f 0.36 \
  -r 0.01 \
  -v 0.05 \
  -l "$Gene_list" \
  -d keep \
  -o "p111_casestudy_withDEGs_r_fltByFMR1Genes.h5ad"

# ---- Python version ----
file_path="/lab-share/RC-DST-Bioinfo-e2/Public/Chunhui/I26_DiVenn2/Data/p238_seuratobj_downsize.h5ad"
python3 generate_divenn2_de_h5ad.py 
  -w "$output_dir" \
  -i ${file_path} 
  -c "group" \
  -g "celltype" \
  -f 1 \
  -r 0.25 \
  -v 0.05 \
  -l "$Gene_list" \
  -d keep \
  -o "p238_downsize_deg_py.h5ad" 
