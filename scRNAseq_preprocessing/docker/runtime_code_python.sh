#!/bin/bash
CONTAINER_NAME="divenn2_degpreprocessing"

# Run the container in detached mode and capture the container ID
CONTAINER_ID=$(docker run -d -v /Users/chunhui/BCH_projects/Divenn/git_repo/DiVenn2/scRNAseq_preprocessing/Data:/data \
  $CONTAINER_NAME h5ad \
  -w "/data" \
  -i "/data/p238_seuratobj_downsize.h5ad" \
  -c "group" \
  -g "celltype" \
  -o "/data/p238_python.csv" \
  -f 0.2 \
  -r 0.01 \
  -v 0.05 \
  -x "CNV:ASD,CNV:CON")

# Show the container output
docker logs -f "$CONTAINER_ID"

# Wait for container to finish
docker wait "$CONTAINER_ID" > /dev/null

# Get exit code
# echo "CONTAINER_ID: $CONTAINER_ID"
EXIT_CODE=$(docker inspect --format='{{.State.ExitCode}}' "$CONTAINER_ID")

# Remove container manually
docker rm "$CONTAINER_ID" > /dev/null

# Detect OOM Kill
if [[ "$EXIT_CODE" -eq 137 ]]; then
    echo "Error: Container was terminated due to an Out-of-Memory (OOM) event."
fi

exit "$EXIT_CODE"
