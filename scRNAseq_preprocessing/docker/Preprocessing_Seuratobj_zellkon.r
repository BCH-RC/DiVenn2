# ---- R 4.4.0 ----#
library(optparse)
library(Seurat)
library(SingleCellExperiment)
library(zellkonverter)
library(reticulate)

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
              help = "Output .h5ad file (DiVenn2-ready)", metavar = "character"),
  make_option(c("-f", "--logfc_thd"), type = "numeric", default = 0.2,
              help = "Log fold change threshold", metavar = "numeric"),
  make_option(c("-r", "--minpct_thd"), type = "numeric", default = 0.1,
              help = "Minmumum cell percent in either condition", metavar = "numeric"),
  make_option(c("-v", "--padj_thd"), type = "numeric", default = 0.05,
              help = "Adjusted p-value threshold", metavar = "numeric"),
  make_option(c("-x", "--comparisons"), type = "character", default = "All",
              help = "Condition comparisons list (format: A:B,A:C,B:C)", metavar = "character"),
  make_option(c("-m", "--method"), type = "character", default = "wilcox",
              help = "Denotes which test to use. Available options are: 'wilcox', 'wilcox_limma', 'bimod', 'roc', 't', 'negbinom', 'poisson', 'LR', 'MAST'", metavar = "character"),
  make_option(c("-s", "--write_csv"),action = "store_true", default = TRUE,
              help = "Write all DEG as CSV file")
)

start_time <- proc.time()
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

# Whether to save csv file 
write_csv <- opt$write_csv

# DEG method
method <- opt$method

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
cat("Preprocessed h5ad file with DEG preprocessed file:", opt$output, "\n")
cat("Log fold change threshold:", opt$logfc_thd, "\n")
cat("Minmumum cell percent in either condition:", opt$minpct_thd, "\n")
cat("Adjusted p-value threshold:", opt$padj_thd, "\n")
cat("Condition comparisons (User input):\n")
cat("DEG method:", method, "\n")
cat("Store CSV file:", write_csv, "\n")
print(condition_comparisons_table)

# helper function: check the name 
sanitize_key <- function(x) {
  x <- trimws(as.character(x))
  x <- gsub("\\s+", "_", x)
  x <- gsub("[^A-Za-z0-9_.-]", "_", x)
  x
}
# helper function: create a list of keys for each comparision
make_key <- function(cell_type, cond1, cond2) {
  paste0(
    "rank_genes_groups__ct=", sanitize_key(cell_type),
    "__", sanitize_key(cond1), "_vs_", sanitize_key(cond2)
  )
}

# Function to create the preprocessed DEG csv file for DiVenn2
DiVenn2_preprocess_seuratobj <- function(seurat_obj, cond_col, gp_col, fname, logfc_thd, min.pct_thd, pval_adj_thd, condition_comparisons,store_csv) {
  
  # Subseting the seurat object to optimize the memory usage
  seurat_obj@meta.data <- seurat_obj@meta.data[, c(cond_col, gp_col)]
  seurat_obj@assays <- seurat_obj@assays["RNA"]
  cat("Set the default assay to RNA!\n")
  DefaultAssay(seurat_obj) <- "RNA"
  gc()

  # Unique conditions
  conditions <- unique(as.vector(seurat_obj@meta.data[, cond_col]))
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
  gps <- unique(as.vector(seurat_obj@meta.data[, gp_col]))
  cat("Cell groups:\n", gps, "\n")
  
  # Extract the DEG list per cell group
  #output <- c()
  output <- data.frame()
  for (gp in gps) {
    # Identify marker genes for cell type by comparing smoking vs nonsmoking
    cat("Cell group:", gp, "\n")
    seurat_obj_gp <- seurat_obj[, seurat_obj@meta.data[, gp_col] %in% gp]
    Idents(seurat_obj_gp) <- seurat_obj_gp@meta.data[, cond_col]
    #cat("Normalization...\n")
    #seurat_obj_gp <- NormalizeData(seurat_obj_gp)

    for (i in 1:nrow(combinations)) {
      cond_1 <- combinations[i, 1]
      cond_2 <- combinations[i, 2]
      cat("Treatment/Disease:", cond_1, "Control:", cond_2, "\n")

      # Get the number of cells in each condition
      if (cond_1 %in% levels(Idents(seurat_obj_gp))) {
        cells.1 <- WhichCells(seurat_obj_gp, ident = cond_1)
      } else {
        cells.1 <- NULL
      }
      if (cond_2 %in% levels(Idents(seurat_obj_gp))) {
        cells.2 <- WhichCells(seurat_obj_gp, ident = cond_2)      
      } else {
        cells.2 <- NULL
      }

      # Check if both conditions have at least 3 cells
      if (length(cells.1) < 3 || length(cells.2) < 3) {
          warning(paste("Skipping comparison:", gp, cond_1, "vs", cond_2, "- One or both groups have fewer than 3 cells."))
          next
      } else {
          if (method == "negbinom" || method == "poisson") {
            marker_gene_gp <- FindMarkers(seurat_obj_gp, test.use = method, ident.1 = cond_1, ident.2 = cond_2, slot = "counts", min.pct = min.pct_thd, logfc.threshold = logfc_thd)
          } else {
            marker_gene_gp <- FindMarkers(seurat_obj_gp, test.use = method, ident.1 = cond_1, ident.2 = cond_2, slot = "data", min.pct = min.pct_thd, logfc.threshold = logfc_thd)
          }
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

    rm(seurat_obj_gp)
    gc()
  }
  
  # -------------------- ADD: write h5ad with uns --------------------
    # Convert Seurat to AnnData (adata)
  sce <- as.SingleCellExperiment(seurat_obj)
  rd <- reducedDims(sce)
  
  cat("Rename obsm keys...\n")
  print(names(rd)) # "PCA", "TSNE", "UMAP", "HARMONY"
  # Convert to Scanpy style: X_<lowercase_name>
  new_names <- paste0("X_", tolower(names(rd)))
  names(rd) <- new_names

  # force obsm to be plain matrices without dimnames 
  for (k in names(rd)) {
    m <- as.matrix(rd[[k]])     
    rownames(m) <- NULL         
    colnames(m) <- NULL         
    rd[[k]] <- m
  }

  reducedDims(sce) <- rd
  adata <- zellkonverter::SCE2AnnData(sce)

  # If output is empty, still write h5ad (just without DEG keys)
  if (nrow(output) > 0) {

      output$Condition_1 <- as.character(output$Condition_1)
      output$Condition_2 <- as.character(output$Condition_2)
      output$CellType    <- as.character(output$CellType)
      output$Gene        <- as.character(output$Gene)
      output$Reg_direct  <- as.character(output$Reg_direct)

      # Create per-comparison keys and catalog
      # Split output by (CellType, Condition_1, Condition_2)
      split_list <- split(output, list(output$CellType, output$Condition_1, output$Condition_2), drop = TRUE)

      catalog <- data.frame(
          key = character(),
          cell_type = character(),
          cond1 = character(),
          cond2 = character(),
          method = character(),
          groupby = character(),
          stringsAsFactors = FALSE
      )

      for (nm in names(split_list)) {
          df <- split_list[[nm]]
          # avoid empty keys 
          if (nrow(df) == 0) next  

          ct <- df$CellType[1]
          c1 <- df$Condition_1[1]
          c2 <- df$Condition_2[1]

          key <- make_key(ct, c1, c2)

          # adata.uns[key] = {Gene, Reg_direct}
          adata$uns[[key]] <- list(Gene = as.character(df$Gene),Reg_direct = as.character(df$Reg_direct))

          # catalog row (only if key exists / non-empty)
          catalog <- rbind(catalog, data.frame(
          key = key,
          cell_type = ct,
          cond1 = c1,
          cond2 = c2,
          method = method,        
          groupby = cond_col,  
          stringsAsFactors = FALSE
          ))
      }

      # Store catalog in uns as dict-of-lists
      adata$uns[["divenn_rank_genes_groups_catalog"]] <- list(
          key      = as.character(catalog$key),
          cell_type= as.character(catalog$cell_type),
          cond1    = as.character(catalog$cond1),
          cond2    = as.character(catalog$cond2),
          method   = as.character(catalog$method),
          groupby  = as.character(catalog$groupby)
      )

  }

  # Write the h5ad
  adata$write_h5ad(fname, compression = "gzip")
  cat("Saved h5ad with embedded DE results to:", fname, "\n")
  # Save the results as .csv file
  if (store_csv) {
      csv_fname <- sub("\\.h5ad$", "_divenn2_deg.csv", fname)       
      write.csv(output, file = csv_fname, quote = FALSE, row.names = FALSE)
      cat("Saved DEG CSV to:", csv_fname, "\n")
  }

}

# Create the preprocessed DEG csv file for DiVenn2
DiVenn2_preprocess_seuratobj(seurat_obj = seurat_obj, cond_col = condition_col, gp_col = group_col, fname = out_fname, logfc_thd = logfc_thd, min.pct_thd = minpct_thd, pval_adj_thd = padj_thd, condition_comparisons = condition_comparisons_table,store_csv = write_csv)

end_time <- proc.time()
elapsed_time <- end_time - start_time
cat("Running time:", elapsed_time, "\n")
