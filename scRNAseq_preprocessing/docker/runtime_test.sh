#!/bin/bash
source /programs/biogrids.shrc

export PYTHON_X=3.11
export R_X=4.4.2

# ---- R version ----
file_path="/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2/DiVenn2-main/scRNAseq_preprocessing/case_study/p111_casestudy_ctx.rds"
output_dir="/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2/DiVenn2-main/scRNAseq_preprocessing/case_study"

Rscript Preprocessing_Seuratobj_dev.R \
  -w "$output_dir" \
  -i "$file_path" \
  -c "sample" \
  -g "celltypes" \
  -x "FXPM:CON,FXS:CON" \
  -m "MAST" \
  -f 0.83 \
  -r 0.1 \
  -v 0.05 \
  -l "/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2/DiVenn2-main/scRNAseq_preprocessing/case_study/fmr1_gene_targets.txt" \
  -d keep \
  -o "p111_casestudy_withDEGs_r_fltByFMR1Genes.h5ad"

# ---- Python version ----
file_path="/lab-share/RC-DST-Bioinfo-e2/Public/Chunhui/I26_DiVenn2/Data/p238_seuratobj_downsize.h5ad"
output_dir="/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2/DiVenn2-main/scRNAseq_preprocessing/docker/DiVenn_inputs"
python3 generate_divenn2_de_h5ad.py -i ${file_path} 
  -c "group" \
  -g "celltype" \
  -f 1 \
  -r 0.25 \
  -v 0.05 \
  -w ${output_dir} \
  -o "p238_downsize_deg_py.h5ad" 
