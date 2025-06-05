library(optparse)
library(Seurat)

##### Change the following for your own data
# Define the list of options
option_list <- list(
  make_option(c("-w", "--workdir"), type = "character", default = NULL,
              help = "Working Directory", metavar = "character"),
  make_option(c("-i", "--input"), type = "character", default = NULL,
              help = "Seurat Object Input File", metavar = "character"),
  make_option(c("-c", "--condition"), type = "character", default = NULL,
              help = "Sample Condition (disease/normal condition)", metavar = "character"),
  make_option(c("-g", "--group"), type = "character", default = NULL,
              help = "Cell Group (cell type)", metavar = "character"),
  make_option(c("-o", "--output"), type = "character", default = NULL,
              help = "Preprocessed DEG output File", metavar = "character"),
  make_option(c("-f", "--logfc_thd"), type = "numeric", default = 0.2,
              help = "Log fold change threshold", metavar = "numeric"),
  make_option(c("-r", "--minpct_thd"), type = "numeric", default = 0.1,
              help = "Minmumum cell percent in either condition", metavar = "numeric"),
  make_option(c("-v", "--padj_thd"), type = "numeric", default = 0.05,
              help = "Adjusted p-value threshold", metavar = "numeric"),
  make_option(c("-x", "--comparisons"), type = "character", default = "All",
              help = "Condition comparisons list (format: A:B,A:C,B:C)", metavar = "character")
)

# Parse the command-line arguments
opt_parser <- OptionParser(option_list = option_list)
opt <- parse_args(opt_parser)

# Set your working directory
setwd(opt$workdir)

# Load your You data data
seurat_obj <- readRDS(opt$input)
# head(seurat_obj)

# Condition column in the meta data such as the disease conditions
condition_col <- opt$condition

# Cell group column in the meta data such as cell types
group_col <- opt$group

# Output file name
out_fname <- opt$output

# Log fold change threshold
logfc_thd <- opt$logfc_thd

# Minmumum cell percent in either condition
minpct_thd <- opt$minpct_thd

# Adjusted p-value threshold
padj_thd <- opt$padj_thd

# Condition comparisons
if (tolower(opt$comparisons) == "all") {
  condition_comparisons_table = "All"
} else {
  condition_comparisons <- strsplit(opt$comparisons, ",")[[1]]
  condition_comparisons_table <- matrix("", length(condition_comparisons), 2)
  colnames(condition_comparisons_table) <- c("Treatment/Disease", "Control")
  for (i in 1:length(condition_comparisons)) {
    comparison.i <- strsplit(condition_comparisons[[i]], ":")[[1]]
    condition_comparisons_table[i, 1] <- comparison.i[1]
    condition_comparisons_table[i, 2] <- comparison.i[2]
  }
}

cat("Working directory:", getwd(), "\n")
cat("Input seurat object file:", opt$input, "\n")
cat("Seurat object meta data column for sample condition (disease/normal):", opt$condition, "\n")
cat("Seurat object meta data column for cell group (cell types):", opt$group, "\n")
cat("Preprocessed DEG preprocessed file:", opt$output, "\n")
cat("Log fold change threshold:", opt$logfc_thd, "\n")
cat("Minmumum cell percent in either condition:", opt$minpct_thd, "\n")
cat("Adjusted p-value threshold:", opt$padj_thd, "\n")
cat("Condition comparisons (User input):\n")
print(condition_comparisons_table)

# Function to create the preprocessed DEG csv file for DiVenn2
DiVenn2_preprocess_seuratobj <- function(seuratobj, cond_col, gp_col, fname, logfc_thd, min.pct_thd, pval_adj_thd, condition_comparisons) {
  # Unique conditions
  conditions <- unique(seurat_obj@meta.data[, cond_col])
  cat("Sample conditions:\n", conditions, "\n")
  
  # Generate all condition comparison combinations
  if (tolower(opt$comparisons) == "all") {
    # Default
    cat("Perform all pairwise sample condition comparisons by default!\n")
    combinations <- as.matrix(expand.grid(conditions, conditions))
    combinations <- combinations[combinations[, 1] != combinations[, 2], ]
  } else {
    # User defined condition comparisons
    missing_conditions <- setdiff(unique(condition_comparisons), conditions)
    if (length(missing_conditions)>0) {
      stop("-x comparison conditions: ", paste(missing_conditions, collapse = ","), " cannot be found in the meta data of the seurat object!")
    }
    combinations <- condition_comparisons
  }

  cat("Sample condition comparisons:\n")
  colnames(combinations) <- c("Treatment/Disease", "Control")
  print(combinations)
  
  # Unique groups
  gps <- unique(seurat_obj@meta.data[, gp_col])
  cat("Cell groups:\n", gps, "\n")
  
  # Extract the DEG list per cell group
  output <- c()
  for (gp in gps) {
    # Identify marker genes for cell type by comparing smoking vs nonsmoking
    cat("Cell group:", gp, "\n")
    seurat_obj_gp <- seurat_obj[, seurat_obj@meta.data[, gp_col] %in% gp]
    Idents(seurat_obj_gp) <- seurat_obj_gp@meta.data[, condition_col]
    head(seurat_obj_gp)

    for (i in 1:nrow(combinations)) {
      cond_1 <- combinations[i, 1]
      cond_2 <- combinations[i, 2]
      cat("Treatment/Disease:", cond_1, "Control:", cond_2, "\n")
      # cat(combinations[i, ], "\n")

      # Get the number of cells in each condition
      cells.1 <- WhichCells(seurat_obj_gp, ident = cond_1)
      cells.2 <- WhichCells(seurat_obj_gp, ident = cond_2)

      # Check if both conditions have at least 3 cells
      if (length(cells.1) < 3 || length(cells.2) < 3) {
          warning(paste("Skipping comparison:", gp, cond_1, "vs", cond_2, "- One or both groups have fewer than 3 cells."))
          next
      } else {
          marker_gene_gp <- FindMarkers(seurat_obj_gp, ident.1 = cond_1, ident.2 = cond_2, min.pct = min.pct_thd, logfc.threshold = logfc_thd)
          marker_gene_gp <- marker_gene_gp[as.numeric(marker_gene_gp$p_val_adj)<pval_adj_thd, ]
      }

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
      output_gp <- data.frame(Condition_1 = cond_1, Condition_2 = cond_2, CellType = gp, Gene = rownames(marker_gene_gp), Reg_direct = rep(0, nrow(marker_gene_gp)), row.names = NULL)
      idx_up <- which(marker_gene_gp$avg_log2FC > 0)
      idx_dn <- which(marker_gene_gp$avg_log2FC < 0)
      output_gp$Reg_direct[idx_up] <- 1
      output_gp$Reg_direct[idx_dn] <- 2
      
      # Update marker_genes
      output <- rbind(output, output_gp)
    }
  }
  
  # Save the results as .csv file
  write.csv(output, file = fname, quote = F, row.names = F)
}

# Create the preprocessed DEG csv file for DiVenn2
DiVenn2_preprocess_seuratobj(seuratobj = seurat_obj, cond_col = condition_col, gp_col = group_col, fname = out_fname, logfc_thd = logfc_thd, min.pct_thd = minpct_thd, pval_adj_thd = padj_thd, condition_comparisons = condition_comparisons_table)

