#!/bin/bash
#SBATCH --partition=rcdst-bioinfo-compute    # queue to be used
#SBATCH --account=rc-dst-bioinfo    # user
#SBATCH --time=120:00:00             # Running time (in hours-minutes-seconds)
#SBATCH --job-name=divenn2      # Job name
#SBATCH --mail-type=END,FAIL       # send and email when the job begins, ends or fails
##SBATCH --mail-user=maryam.labaf@childrens.harvard.edu # Email address to send the job status
##SBATCH --output=log_%j.txt          # Name of the output file
##SBATCH --error=err%j.txt
#SBATCH --nodes=1               # Number of compute nodes
##SBATCH --nodelist=compute-5-0-7 # specify compute-5-0-7 node to run a job
#SBATCH --ntasks=5              # Number of threads/tasks on one node
#SBATCH --mem=150GB


source /programs/biogrids.shrc

export R_X=4.4.2

echo Running on `hostname`
echo Started: `date`

export RETICULATE_PYTHON=/programs//x86_64-linux//scvi-tools/0.8.1/bin.capsules/python.scvi-tools

# ---- Set Path ----
Gene_list="/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2/DiVenn2-main/scRNAseq_preprocessing/case_study/input_data/fmr1_gene_targets.txt"
output_dir="/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2/DiVenn2-main/scRNAseq_preprocessing/case_study/results"

# ---- R version ----
#file_path="/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2/DiVenn2-main/scRNAseq_preprocessing/case_study/input_data/p111_casestudy_ctx.rds"
file_path="/lab-share/RC-DST-Bioinfo-e2/Public/Chunhui/I26_DiVenn2/Data/p238_seuratobj_downsize.rds"

# NOTE: Seurat v4:findMarkers ln-FC cutoff = 0.25 ~ Seurat v5:FindMarkers log2-FC cutoff = 0.36 (log2(x) =log(x)/log(2))
#Rscript Preprocessing_Seuratobj_zellkon.R \
#  -w "$output_dir" \
#  -i "$file_path" \
#  -c "sample" \
#  -g "celltypes" \
#  -x "FXPM:CON,FXS:CON" \
#  -m "MAST" \
#  -f 0.83 \
#  -r 0.01 \
#  -v 0.05 \
#  -l "$Gene_list" \
#  -d keep \
#  -o "p111_r_r0.01_f0.83.h5ad"

Rscript Preprocessing_Seuratobj_zellkon.R \
  -w "$output_dir" \
  -i "$file_path" \
  -c "group" \
  -g "celltype" \
  -x "all" \
  -m "wilcox" \
  -f 0.1 \
  -r 0.01 \
  -v 0.05 \
  #-l "$Gene_list" \
  #-d keep \
  -o "p238_seuratobj_downsize_r.h5ad"

echo Finished: `date`
