#!/bin/bash

# Wrapper script for running either the Python or R preprocessing script

# # Function to show usage
# show_usage() {
#     echo "Usage: $0 <h5ad|seurat> [arguments]"
#     echo "  python  - Run Preprocessing_h5ad.py"
#     echo "  r       - Run Preprocessing_Seuratobj.r"
#     echo ""
#     echo "Example:"
#     echo "  $0 python --input /data/input.h5ad --condition group --group celltype --output /data/output.csv"
#     echo "  $0 r --input /data/P235_dim30_r0.5.rds --condition group --group celltype --output /data/p235"
# }

# Check if the user provided an argument
if [[ $# -lt 1 ]]; then
    show_usage
    exit 1
fi

# Extract the first argument (script selection)
DATA_TYPE=$1
shift # Remove the first argument so the rest can be passed to the script

# Run the appropriate script based on user choice
if [[ "$DATA_TYPE" == "h5ad" ]]; then
    echo "Running Python preprocessing script..."
    python3 /usr/src/app/Preprocessing_h5ad.py "$@"
elif [[ "$DATA_TYPE" == "seurat" ]]; then
    echo "Running R preprocessing script..."
    Rscript /usr/src/app/Preprocessing_Seuratobj.r "$@"
else
    echo "Error: Invalid argument '$SCRIPT_TYPE'"
    show_usage
    exit 1
fi

