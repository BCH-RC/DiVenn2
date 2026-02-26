# ---- R 4.4.2 ----#
library(optparse)
library(Seurat)
library(SeuratDisk)
library(reticulate)
#reticulate::use_python("/programs//x86_64-linux//scvi-tools/0.8.1/bin.capsules/python.scvi-tools")
reticulate::use_python(Sys.getenv("RETICULATE_PYTHON"), required = TRUE)
library(anndata)

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
  

  # Write intermediate .h5Seurat in same folder as output
  out_dir  <- dirname(fname)
  out_base <- sub("\\.h5ad$", "", basename(fname))

  tmp_h5seurat <- file.path(out_dir, paste0(out_base, ".h5Seurat"))
  tmp_h5ad     <- file.path(out_dir, paste0(out_base, ".h5ad")) 

  cat("\nSaving h5Seurat:", tmp_h5seurat, "\n")
  SaveH5Seurat(seurat_obj, filename = tmp_h5seurat, overwrite = TRUE)

  cat("Converting to h5ad (SeuratDisk default naming):", tmp_h5ad, "\n")
  Convert(tmp_h5seurat, dest = "h5ad", overwrite = TRUE)

  if (!file.exists(tmp_h5ad)) {
    stop("Convert() did not create expected file: ", tmp_h5ad,"\nFiles in output dir:\n", paste(list.files(out_dir), collapse = "\n"))
  }
  # -------------------- Add DEG uns to the converted h5ad --------------------
  np <- reticulate::import("numpy", convert = FALSE)
  adata <- read_h5ad(tmp_h5ad)

  if (nrow(output) > 0) {
    # Split by (CellType, Condition_1, Condition_2)
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
      if (nrow(df) == 0) next

      ct <- df$CellType[1]
      c1 <- df$Condition_1[1]
      c2 <- df$Condition_2[1]
      key <- make_key(ct, c1, c2)

      # store as numpy arrays (scanpy-friendly)
      adata$uns[[key]] <- reticulate::dict(
        Gene = np$array(as.character(df$Gene), dtype = "object"),
        Reg_direct = np$array(as.character(df$Reg_direct), dtype = "object")
      )
      #adata$uns[[key]] <- list(Gene = as.character(df$Gene),Reg_direct = as.character(df$Reg_direct))

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

    adata$uns[["divenn_rank_genes_groups_catalog"]] <- reticulate::dict(
      key       = np$array(as.character(catalog$key), dtype = "object"),
      cell_type = np$array(as.character(catalog$cell_type), dtype = "object"),
      cond1     = np$array(as.character(catalog$cond1), dtype = "object"),
      cond2     = np$array(as.character(catalog$cond2), dtype = "object"),
      method    = np$array(as.character(catalog$method), dtype = "object"),
      groupby   = np$array(as.character(catalog$groupby), dtype = "object")
    )
  } else {
    cat("No DEGs passed thresholds; writing h5ad without DEG uns keys.\n")
  }

  # Final write
  cat("Writing final h5ad:", fname, "\n")
  adata$write_h5ad(fname, compression = "gzip")

  # Optional CSV
  if (write_csv) {
    csv_fname <- sub("\\.h5ad$", "_divenn2_deg.csv", fname)
    write.csv(output, file = csv_fname, quote = FALSE, row.names = FALSE)
    cat("Saved DEG CSV to:", csv_fname, "\n")
  }

  # Cleanup temps
  #suppressWarnings({
  #  if (file.exists(tmp_h5ad)) file.remove(tmp_h5ad)
  #  # keep tmp_h5seurat if you want debugging; otherwise remove:
  #  # if (file.exists(tmp_h5seurat)) file.remove(tmp_h5seurat)
  #})

  cat("Done.\n")

}

# Create the preprocessed DEG csv file for DiVenn2
DiVenn2_preprocess_seuratobj(seurat_obj = seurat_obj, cond_col = condition_col, gp_col = group_col, fname = out_fname, logfc_thd = logfc_thd, min.pct_thd = minpct_thd, pval_adj_thd = padj_thd, condition_comparisons = condition_comparisons_table,store_csv = write_csv)

end_time <- proc.time()
elapsed_time <- end_time - start_time
cat("Running time:", elapsed_time, "\n")
