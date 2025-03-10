#!/bin/bash
source /programs/biogrids.shrc
export PYTHON_X=3.6.5

file_path="/lab-share/RC-DST-Bioinfo-e2/Public/Chunhui/I26_DiVenn2/Data/p238.h5ad"
output_dir="/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2"
python3 preprocess_h5ad.py -i ${file_path} \
                          -c "group" \
                          -g "celltype" \
                          -o "p238_divenne.csv" \
                          -fc 0.2 \
                          -pct 0.01 \
                          -p 0.05 \
                          -w ${output_dir}

python Preprocessing_h5ad_v2.py -i ${file_path} \
                          -c "group" \
                          -g "celltype" \
                          -o "p238_divenne_withCondition.csv" \
                          -fc 0.2 \
                          -pct 0.01 \
                          -p 0.05 \
                          -comp "[('CNV', 'ASD'), ('CNV', 'CON')]" \
                          -w ${output_dir}
