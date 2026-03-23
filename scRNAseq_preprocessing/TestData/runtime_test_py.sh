#!/bin/bash
#SBATCH --partition=rcdst-bioinfo-compute    # queue to be used
#SBATCH --account=rc-dst-bioinfo    # user
#SBATCH --time=2:00:00             # Running time (in hours-minutes-seconds)
#SBATCH --job-name=divenn2      # Job name
#SBATCH --mail-type=END,FAIL       # send and email when the job begins, ends or fails
##SBATCH --mail-user=maryam.labaf@childrens.harvard.edu # Email address to send the job status
##SBATCH --output=log_%j.txt          # Name of the output file
##SBATCH --error=err%j.txt
#SBATCH --nodes=1               # Number of compute nodes
##SBATCH --nodelist=compute-5-0-7 # specify compute-5-0-7 node to run a job
#SBATCH --ntasks=2              # Number of threads/tasks on one node
#SBATCH --mem=50GB


source /programs/biogrids.shrc

export PYTHON_X=3.11

echo Running on `hostname`
echo Started: `date`


# ---- Set Path ----
Gene_list="/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2/DiVenn2-main/scRNAseq_preprocessing/case_study/input_data/fmr1_gene_targets.txt"
output_dir="/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2/DiVenn2-main/scRNAseq_preprocessing/case_study/results"
#file_path="/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2/DiVenn2-main/scRNAseq_preprocessing/case_study/input_data/p111_casestudy_ctx.h5ad"

# Download link: https://cellxgene.cziscience.com/collections/e5f58829-1a66-40b5-a624-9046778e74f5
file_path="/lab-share/RC-DST-Bioinfo-e2/Public/Chunhui/I26_DiVenn2/Data/p238_downsize_seuratobj.h5ad"

python3 Preprocessing_h5ad_update.py  \
    -w "$output_dir" \
    -i ${file_path} \
    -c "group" \
    -g "celltype" \
    -x "all" \
    -m "t-test" \
    -f 1 \
    -r 0.25 \
    -v 0.05 \
    -o "p238_downsize_py.h5ad"


echo Finished: `date`
