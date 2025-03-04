# Load the required libraries
if (!requireNamespace("optparse", quietly = TRUE)) {
  install.packages("optparse", repos = "http://cran.us.r-project.org")
}
library(optparse)
if (!requireNamespace("Seurat", quietly = TRUE)) {
  install.packages("Seurat", repos = "http://cran.us.r-project.org")
}
library(Seurat)

##### Change the following for your own data
# Define the list of options
option_list <- list(
  make_option(c("-wd", "--workdir"), type = "character", default = NULL,
              help = "Working Directory", metavar = "character"),
  make_option(c("-i", "--input"), type = "character", default = 10,
              help = "Seurat Object Input File", metavar = "character"),
  make_option(c("-c", "--condition"), type = "character", default = 10,
              help = "Sample Condition (disease/normal condition)", metavar = "character"),
  make_option(c("-g", "--group"), type = "character", default = 10,
              help = "Cell Group (cell type)", metavar = "character"),
  make_option(c("-o", "--output"), type = "character", default = 10,
              help = "Preprocessed DEG output File", metavar = "character"),
)

# Parse the command-line arguments
opt_parser <- OptionParser(option_list = option_list)
opt <- parse_args(opt_parser)

# Set your working directory
setwd(opt$workdir)

# Load your You data data
seurat_obj <- readRDS(opt$file_input)

# Condition column in the meta data such as the disease conditions
condition_col <- opt$condition

# Cell group column in the meta data such as cell types
group_col <- opt$group

# Output file name
out_fname <- opt$file_output

cat("Working directory:", opt$workdir, "\n")
cat("Input seurat object file:", opt$input, "\n")
cat("Seurat object meta data column for sample condition (disease/normal):", opt$condition, "\n")
cat("Seurat object meta data column for cell group (cell types):", opt$group, "\n")
cat("Preprocessed DEG preprocessed file:", opt$ouput, "\n")

# Function to create the preprocessed DEG csv file for DiVenn2
DiVenn2_preprocess_seuratobj <- function(seuratobj, cond_col, gp_col, fname, min.pct_thd = 0.1, logfc_thd = 0.2, pval_adj_thd = 0.05) {
  # Unique conditions
  conditions <- unique(seurat_obj@meta.data[, cond_col])
  
  # Unique groups
  gps <- unique(seurat_obj@meta.data[, gp_col])
  
  # Extract the DEG list per cell group
  output <- c()
  for (gp in gps) {
    # Identify marker genes for cell type by comparing smoking vs nonsmoking
    cat("Cell group:", gp, "\n")
    seurat_obj_gp <- seurat_obj[, seurat_obj@meta.data[, gp_col] %in% gp]
    Idents(seurat_obj_gp) <- seurat_obj_gp@meta.data[, condition_col]
    
    # Generate all possible combinations
    combinations <- expand.grid(seq(1, length(conditions)), seq(1, length(conditions)))
    combinations <- combinations[combinations[, 1] != combinations[, 2], ]
    for (i in 1:nrow(combinations)) {
      cond_1 <- conditions[combinations[i, 1]]
      cond_2 <- conditions[combinations[i, 2]]
      cat("Condition 1:", cond_1, "Condition 2:", cond_2, "\n")
      
      marker_gene_gp <- FindMarkers(seurat_obj_gp, ident.1 = cond_1, ident.2 = cond_2, min.pct = min.pct_thd, logfc.threshold = logfc_thd)
      marker_gene_gp <- marker_gene_gp[as.numeric(marker_gene_gp$p_val_adj)<pval_adj_thd, ]
      
      # Check if DEGs are found
      if (nrow(marker_gene_gp) == 0) {
        cat("No marker genes found!\n")
        next
      } else {
        cat("Number of marker genes:", nrow(marker_gene_gp), "\n")
      }
      
      # Create a nx5 matrix for each cell type
      # column 1: Condition 1, e.g. treatment
      # column 2: Condition 2, e.g. control
      # column 3: cell type name
      # column 4: DEG name
      # column 5: 1 - up-regulated, 2 - down-regulated 
      output_gp <- data.frame(Condition_1 = cond_1, Condition_2 = cond_2, gp = gp, Gene = rownames(marker_gene_gp), Reg_direct = rep(0, nrow(marker_gene_gp)))
      idx_up <- which(marker_gene_gp$avg_log2FC > 0)
      idx_dn <- which(marker_gene_gp$avg_log2FC < 0)
      output_gp$Reg_direct[idx_up] <- 1
      output_gp$Reg_direct[idx_dn] <- 2
      
      # Update marker_genes
      output <- rbind(output, output_gp)
    }
  }
  
  # Save the results as .csv file
  write.csv(output, file = paste0(fname, ".csv"), quote = F, row.names = F)
}

# Create the preprocessed DEG csv file for DiVenn2
DiVenn2_preprocess_seuratobj(seuratobj = seurat_obj, cond_col = condition_col, gp_col = group_col, fname = out_fname)

