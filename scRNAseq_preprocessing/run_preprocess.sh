#!/bin/bash
source /programs/biogrids.shrc
export PYTHON_X=3.6.5

file_path="/lab-share/RC-DST-Bioinfo-e2/Public/Chunhui/I26_DiVenn2/Data/p238.h5ad"
output_dir="/lab-share/RC-DST-Bioinfo-e2/Public/Maryam/I26_DiVenn2"
# Preprocess the h5ad file for all the conditions
python Preprocessing_h5ad_v2.py -i ${file_path} \
                                -c "group" \
                                -g "celltype" \
                                -o "p238_divenne_all.csv" \
                                -fc 0.2 \
                                -pct 0.01 \
                                -p 0.05 \
                                -w ${output_dir}

# Preprocess the h5ad file for CVN vs ASD and CNV vs CON condition
python Preprocessing_h5ad_v2.py -i ${file_path} \
                          -c "group" \
                          -g "celltype" \
                          -o "p238_divenne_withCondition.csv" \
                          -fc 0.2 \
                          -pct 0.01 \
                          -p 0.05 \
                          -x "CNV:ASD,CNV:CON" \
                          -w ${output_dir}
