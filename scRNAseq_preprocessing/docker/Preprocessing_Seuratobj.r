# ---- R 4.4.2 ----#
library(optparse)
library(Matrix)
library(Seurat)
library(SingleCellExperiment)
library(zellkonverter)
library(reticulate)

DIVENN_COLUMNS <- c("Condition_1", "Condition_2", "CellType", "Gene", "Reg_direct")

# ------------------------------------------------------------------ options
option_list <- list(
  make_option(c("-w", "--workdir"), type = "character", default = NULL,
              help = "Working directory"),
  make_option(c("-i", "--input"), type = "character", default = NULL,
              help = "Seurat object input file (.rds)"),
  make_option(c("-c", "--condition"), type = "character", default = NULL,
              help = "Condition column in the Seurat meta data (e.g. disease/normal)"),
  make_option(c("-g", "--group"), type = "character", default = NULL,
              help = "Cell group column in the Seurat meta data (e.g. cell type)"),
  make_option(c("-o", "--output"), type = "character", default = NULL,
              help = "Output .h5ad file (DiVenn2-ready)"),
  make_option(c("-f", "--logfc_thd"), type = "numeric", default = 0.1,
              help = "Absolute log2 fold change threshold [default %default]"),
  make_option(c("-r", "--minpct_thd"), type = "numeric", default = 0.01,
              help = paste("Minimum fraction of cells expressing the gene in either condition",
                           "(Seurat min.pct; single_cell mode only, ignored in pseudobulk) [default %default]")),
  make_option(c("-v", "--padj_thd"), type = "numeric", default = 0.05,
              help = "Adjusted p-value threshold (Seurat Bonferroni p_val_adj) [default %default]"),
  make_option(c("-x", "--comparisons"), type = "character", default = "All",
              help = "Condition comparisons: 'All' or 'A:B,A:C' (A = treatment, B = control)"),
  make_option(c("-m", "--method"), type = "character", default = "wilcox",
              help = paste("single_cell only: FindMarkers test.use ('wilcox', 'wilcox_limma',",
                           "'bimod', 'roc', 't', 'negbinom', 'poisson', 'LR', 'MAST') [default %default]")),
  make_option(c("-s", "--write_csv"), action = "store_true", default = FALSE,
              help = "Write the DiVenn2 five-column DEG CSV"),
  make_option(c("--write_stats_csv"), action = "store_true", default = FALSE,
              help = "Write the retained DEGs with their statistics"),
  make_option(c("-l", "--gene_list_file"), type = "character", default = NULL,
              help = "Optional text file with one gene per line for DEG filtering"),
  make_option(c("-d", "--gene_filter_mode"), type = "character", default = NULL,
              help = "Optional DEG filtering mode: 'remove' or 'keep'"),
  make_option(c("-a", "--gene_filter_ignore_case"), action = "store_true", default = FALSE,
              help = "Ignore case when filtering DEGs by gene list"),
  make_option(c("--deg_mode"), type = "character", default = "single_cell",
              help = "'single_cell' or 'pseudobulk' [default %default]"),
  make_option(c("--sample_col"), type = "character", default = NULL,
              help = "Biological sample ID column (required for pseudobulk)"),
  make_option(c("--assay"), type = "character", default = "RNA",
              help = "Seurat assay to use [default %default]"),
  make_option(c("--counts_layer"), type = "character", default = "counts",
              help = "Layer with raw integer UMI counts (pseudobulk) [default %default]"),
  make_option(c("--min_cells_per_sample"), type = "integer", default = 10,
              help = "Minimum cells per sample x cell type for a pseudobulk sample [default %default]"),
  make_option(c("--min_replicates"), type = "integer", default = 2,
              help = "Minimum biological replicates per condition (pseudobulk) [default %default]")
)
opt <- parse_args(OptionParser(option_list = option_list,
  description = "DiVenn2-ready h5ad with single-cell or pseudobulk DEG results from a Seurat object"))

for (req in c("input", "condition", "group", "output")) {
  if (is.null(opt[[req]])) stop("--", req, " is required")
}
if (!opt$deg_mode %in% c("single_cell", "pseudobulk")) {
  stop("--deg_mode must be 'single_cell' or 'pseudobulk'")
}
if (!is.null(opt$gene_filter_mode)) {
  opt$gene_filter_mode <- tolower(trimws(opt$gene_filter_mode))
  if (!opt$gene_filter_mode %in% c("remove", "keep")) {
    stop("--gene_filter_mode must be 'remove' or 'keep'")
  }
}
if (opt$logfc_thd < 0 || !(opt$padj_thd > 0 && opt$padj_thd <= 1)) {
  stop("Invalid logFC or adjusted p-value threshold")
}
if (opt$minpct_thd < 0 || opt$minpct_thd > 1) stop("--minpct_thd must lie between 0 and 1")

# ------------------------------------------------------------------ helpers
sanitize_key <- function(x) {
  x <- trimws(as.character(x))
  x <- gsub("\\s+", "_", x)
  gsub("[^A-Za-z0-9_.-]", "_", x)
}

result_key <- function(cell_type, cond1, cond2) {
  paste0("rank_genes_groups__ct=", sanitize_key(cell_type), "__",
         sanitize_key(cond1), "_vs_", sanitize_key(cond2))
}

read_gene_list <- function(path) {
  if (is.null(path)) return(NULL)
  if (!file.exists(path)) stop("Gene list file does not exist: ", path)
  genes <- trimws(readLines(path, warn = FALSE))
  unique(genes[genes != ""])
}

filter_deg_table_by_gene_list <- function(deg_table, gene_list = NULL, mode = NULL,
                                          ignore_case = FALSE) {
  if (is.null(gene_list) || is.null(mode)) return(deg_table)
  genes <- as.character(deg_table$Gene)
  reference <- as.character(gene_list)
  if (ignore_case) {
    genes <- toupper(genes)
    reference <- toupper(reference)
  }
  selected <- genes %in% reference
  deg_table[if (mode == "keep") selected else !selected, , drop = FALSE]
}

# Directed (treatment, control) pairs; invalid tokens are skipped.
parse_comparison_pairs <- function(comparison_str, conditions) {
  conditions <- as.character(conditions)
  if (tolower(trimws(comparison_str)) == "all") {
    pairs <- expand.grid(c1 = conditions, c2 = conditions, stringsAsFactors = FALSE)
    return(pairs[pairs$c1 != pairs$c2, , drop = FALSE])
  }
  pairs <- data.frame(c1 = character(), c2 = character(), stringsAsFactors = FALSE)
  for (token in strsplit(comparison_str, ",")[[1]]) {
    pieces <- trimws(strsplit(token, ":")[[1]])
    if (length(pieces) != 2 || pieces[1] == pieces[2] || !all(pieces %in% conditions)) {
      cat("[DiVenn2] Skipping invalid comparison:", sQuote(token, FALSE), "\n")
      next
    }
    if (!any(pairs$c1 == pieces[1] & pairs$c2 == pieces[2])) {
      pairs[nrow(pairs) + 1, ] <- pieces
    }
  }
  pairs
}

validate_deg_inputs <- function(meta, genes, cell_type_col, condition_col, deg_mode,
                                sample_col, min_cells_per_sample, min_replicates) {
  required <- c(cell_type_col, condition_col)
  if (deg_mode == "pseudobulk") {
    if (is.null(sample_col)) stop("Pseudobulk requires --sample_col (biological replicate ID)")
    required <- c(required, sample_col)
    if (min_cells_per_sample < 1 || min_replicates < 2) {
      stop("--min_cells_per_sample must be >= 1 and --min_replicates >= 2")
    }
  }
  missing <- setdiff(required, colnames(meta))
  if (length(missing)) stop("Missing meta data column(s): ", paste(missing, collapse = ", "))
  for (col in required) {
    values <- meta[[col]]
    if (anyNA(values)) stop("Missing values found in meta data column '", col, "'")
    if (any(trimws(as.character(values)) == "")) stop("Empty labels found in meta data column '", col, "'")
  }
  if (deg_mode == "pseudobulk") {
    per_sample <- tapply(as.character(meta[[condition_col]]), as.character(meta[[sample_col]]),
                         function(x) length(unique(x)))
    if (any(per_sample > 1)) {
      bad <- head(names(per_sample)[per_sample > 1], 5)
      stop("Biological sample ID maps to multiple conditions: ", paste(bad, collapse = ", "),
           ". Use a globally unique biological sample ID.")
    }
    if (anyDuplicated(genes)) stop("Pseudobulk requires unique gene names")
  }
}

# Seurat v5 objects can carry split layers (counts.1, counts.2, ...) after integration.
join_layers_if_split <- function(seu, assay) {
  if (inherits(seu[[assay]], "Assay5")) {
    lyr <- Layers(seu[[assay]])
    if (any(grepl("^(counts|data)\\.", lyr))) {
      cat("[DiVenn2] Joining split layers in assay", assay, "\n")
      seu[[assay]] <- JoinLayers(seu[[assay]])
    }
  }
  seu
}

has_layer <- function(seu, assay, layer) {
  if (inherits(seu[[assay]], "Assay5")) return(layer %in% Layers(seu[[assay]]))
  m <- GetAssayData(seu, assay = assay, layer = layer)
  !is.null(m) && length(m) > 0 && all(dim(m) > 0)
}

# Single-cell tests and the h5ad X need log-normalized data; normalize if it is missing
# or still identical to the raw counts (e.g. CreateSeuratObject without NormalizeData).
ensure_normalized <- function(seu, assay) {
  needs <- !has_layer(seu, assay, "data")
  if (!needs && has_layer(seu, assay, "counts")) {
    d <- LayerData(seu, assay = assay, layer = "data")
    cts <- LayerData(seu, assay = assay, layer = "counts")
    if (identical(dim(d), dim(cts)) && inherits(d, "dgCMatrix") && inherits(cts, "dgCMatrix")) {
      n <- min(length(d@x), 1e5)
      if (n > 0 && length(cts@x) >= n && isTRUE(all.equal(d@x[seq_len(n)], cts@x[seq_len(n)]))) {
        needs <- TRUE
      }
    }
  }
  if (needs) {
    cat("[DiVenn2] 'data' layer missing or not log-normalized; running NormalizeData\n")
    seu <- NormalizeData(seu, assay = assay, verbose = FALSE)
  }
  seu
}

# Select raw counts explicitly; never infer counts from log-normalized data.
get_pseudobulk_counts <- function(seu, assay, counts_layer) {
  if (!has_layer(seu, assay, counts_layer)) {
    stop("Counts layer '", counts_layer, "' not found in assay '", assay, "'")
  }
  m <- LayerData(seu, assay = assay, layer = counts_layer)
  m <- as(as(m, "CsparseMatrix"), "dgCMatrix")
  x <- m@x
  if (any(!is.finite(x)) || any(x < 0) || any(abs(x - round(x)) > 1e-8)) {
    stop("Pseudobulk requires finite, nonnegative, integer raw counts in layer '", counts_layer,
         "'. Do not use log-normalized or scaled expression.")
  }
  m
}

# Build one pseudobulk Seurat object per cell type with Seurat::AggregateExpression
# (raw counts summed per biological sample). Samples with fewer than min_cells_per_sample
# cells are dropped first.
build_pseudobulk_objects <- function(counts, meta, cell_type_col, condition_col, sample_col,
                                     min_cells_per_sample) {
  ct_all <- as.character(meta[[cell_type_col]])
  sm_all <- as.character(meta[[sample_col]])
  cd_all <- as.character(meta[[condition_col]])
  result <- list()
  for (ct in unique(ct_all)) {
    idx <- which(ct_all == ct)
    n_per_sample <- table(sm_all[idx])
    small <- n_per_sample[n_per_sample < min_cells_per_sample]
    for (s in names(small)) {
      cat(sprintf("[Pseudobulk] Skipping %s/%s: %d cells < %d\n", ct, s, small[[s]],
                  min_cells_per_sample))
    }
    keep_samples <- names(n_per_sample)[n_per_sample >= min_cells_per_sample]
    if (!length(keep_samples)) next
    idx <- idx[sm_all[idx] %in% keep_samples]
    sub <- counts[, idx, drop = FALSE]

    # AggregateExpression rewrites group labels ('_' -> '-', 'g' prefix for leading digits),
    # so aggregate on safe IDs (s1, s2, ...) and map them back to the real sample names.
    safe_id <- setNames(paste0("s", seq_along(keep_samples)), keep_samples)
    cell_meta <- data.frame(pb_sample = unname(safe_id[sm_all[idx]]), row.names = colnames(sub))
    cell_obj <- CreateSeuratObject(counts = sub, meta.data = cell_meta, assay = "RNA",
                                   min.cells = 0, min.features = 0)
    pb <- suppressMessages(AggregateExpression(cell_obj, assays = "RNA", group.by = "pb_sample",
                                               return.seurat = TRUE, verbose = FALSE))
    real_sample <- names(safe_id)[match(colnames(pb), safe_id)]
    if (anyNA(real_sample)) stop("Could not map AggregateExpression columns back to samples")
    sample_cond <- cd_all[idx][match(real_sample, sm_all[idx])]
    pb$sample <- real_sample
    pb$condition <- sample_cond
    Idents(pb) <- factor(sample_cond)
    sample_meta <- data.frame(condition = sample_cond,
                              n_cells = as.integer(n_per_sample[real_sample]),
                              row.names = real_sample, stringsAsFactors = FALSE)
    result[[ct]] <- list(pb = pb, meta = sample_meta)
    rm(cell_obj, sub); invisible(gc())
  }
  result
}

run_single_cell_deg <- function(seu_ct, condition_col, cond1, cond2, method, min_pct,
                                logfc_thd, assay) {
  layer <- if (method %in% c("negbinom", "poisson")) "counts" else "data"
  res <- FindMarkers(seu_ct, ident.1 = cond1, ident.2 = cond2, test.use = method,
                     assay = assay, slot = layer, min.pct = min_pct,
                     logfc.threshold = logfc_thd, verbose = FALSE)
  if (!nrow(res)) return(NULL)
  data.frame(Gene = rownames(res), log2FoldChange = res$avg_log2FC, pvalue = res$p_val,
             padj = res$p_val_adj, pct.1 = res$pct.1, pct.2 = res$pct.2,
             row.names = NULL, stringsAsFactors = FALSE)
}

# Pseudobulk test: Seurat::FindMarkers(test.use = "DESeq2") on the aggregated samples, with
# samples as the independent replicates. Seurat runs DESeq2 on the summed counts (fitType
# "local") and reports avg_log2FC from the normalized pseudobulk data and a Bonferroni p_val_adj.
# FindMarkers' min.pct/logfc.threshold are disabled (every gene is tested), and no detection
# (-r) filter is applied in pseudobulk mode; -f and -v are applied in format_deg_results().
run_pseudobulk_deg <- function(pb, sample_meta, cond1, cond2, min_replicates) {
  n1 <- sum(sample_meta$condition == cond1)
  n2 <- sum(sample_meta$condition == cond2)
  if (min(n1, n2) < min_replicates) {
    cat(sprintf("[Pseudobulk] Skipping %s vs %s: %d vs %d biological replicates (minimum %d each)\n",
                cond1, cond2, n1, n2, min_replicates))
    return(list(result = NULL, n1 = n1, n2 = n2))
  }
  res <- tryCatch(
    suppressMessages(FindMarkers(pb, ident.1 = cond1, ident.2 = cond2, test.use = "DESeq2",
                                 min.pct = 0, logfc.threshold = 0,
                                 min.cells.group = min_replicates, verbose = FALSE)),
    error = function(e) {
      cat(sprintf("[Pseudobulk] Skipping %s vs %s: FindMarkers/DESeq2 failed (%s)\n",
                  cond1, cond2, conditionMessage(e)))
      NULL
    })
  if (is.null(res) || !nrow(res)) return(list(result = NULL, n1 = n1, n2 = n2))
  list(result = data.frame(Gene = rownames(res), log2FoldChange = res$avg_log2FC,
                           pvalue = res$p_val, padj = res$p_val_adj,
                           row.names = NULL, stringsAsFactors = FALSE),
       n1 = n1, n2 = n2)
}

# Common significance and gene-list filters.
format_deg_results <- function(result, cell_type, cond1, cond2, logfc_thd, padj_thd,
                               gene_list, gene_filter_mode, gene_filter_ignore_case) {
  lfc <- suppressWarnings(as.numeric(result$log2FoldChange))
  padj <- suppressWarnings(as.numeric(result$padj))
  selected <- is.finite(lfc) & is.finite(padj) & abs(lfc) > logfc_thd & padj < padj_thd
  result <- result[selected, , drop = FALSE]
  result$Reg_direct <- ifelse(result$log2FoldChange > 0, "1", "2")
  n <- nrow(result)
  result$Condition_1 <- rep(as.character(cond1), n)
  result$Condition_2 <- rep(as.character(cond2), n)
  result$CellType <- rep(as.character(cell_type), n)
  result <- filter_deg_table_by_gene_list(result, gene_list, gene_filter_mode,
                                          gene_filter_ignore_case)
  result[, c(DIVENN_COLUMNS, setdiff(colnames(result), DIVENN_COLUMNS)), drop = FALSE]
}

write_csv_min_quote <- function(df, path) {
  needs_quote <- vapply(df, function(col) is.character(col) && any(grepl("[\",\n]", col)), logical(1))
  write.csv(df, path, row.names = FALSE, quote = if (any(needs_quote)) which(needs_quote) else FALSE)
}

# Seurat -> SingleCellExperiment -> AnnData (X = logcounts), with DiVenn2 results in uns.
write_divenn_h5ad <- function(seu, assay, deg_tables, catalog, deg_mode, fname) {
  sce <- as.SingleCellExperiment(seu, assay = assay)
  if (!"logcounts" %in% assayNames(sce)) stop("'logcounts' assay not found in sce")
  SummarizedExperiment::assays(sce) <- S4Vectors::SimpleList(logcounts = assay(sce, "logcounts"))

  # Scanpy-style obsm keys (X_umap, X_pca, ...) as plain matrices without dimnames.
  rd <- reducedDims(sce)
  if (length(rd)) {
    names(rd) <- paste0("X_", tolower(names(rd)))
    for (k in names(rd)) {
      m <- as.matrix(rd[[k]])
      dimnames(m) <- NULL
      rd[[k]] <- m
    }
    reducedDims(sce) <- rd
  }
  if (ncol(SummarizedExperiment::rowData(sce)) == 0) {
    SummarizedExperiment::rowData(sce)$features <- rownames(sce)
  }

  sce$ident <- NULL  # added by as.SingleCellExperiment; duplicates the Idents
  adata <- zellkonverter::SCE2AnnData(sce, X_name = "logcounts", metadata = FALSE)

  # Write uns on the Python side: assigning through adata$uns from R round-trips the whole
  # dict through R lists and turns int64 arrays into float64.
  py <- reticulate::py_run_string(paste(
    "def divenn_store(adata, deg_tables, catalog, deg_mode):",
    "    import numpy as np",
    "    adata.X = adata.X.astype(np.float32)",
    "    for key, tab in deg_tables.items():",
    "        adata.uns[key] = {'Gene': np.asarray(tab['Gene'], dtype=str),",
    "                          'Reg_direct': np.asarray(tab['Reg_direct'], dtype=str)}",
    "    adata.uns['divenn_rank_genes_groups_catalog'] = {",
    "        col: np.asarray(vals, dtype=np.int64 if col.startswith('n_replicates_') else str)",
    "        for col, vals in catalog.items()}",
    "    adata.uns['divenn_deg_mode'] = deg_mode",
    sep = "\n"), local = TRUE, convert = FALSE)
  # I() keeps length-1 vectors as lists, so single-gene comparisons stay arrays.
  tabs <- lapply(deg_tables, function(df) list(Gene = I(as.character(df$Gene)),
                                               Reg_direct = I(as.character(df$Reg_direct))))
  cat_list <- lapply(catalog, function(col) I(if (is.numeric(col)) as.integer(col) else as.character(col)))
  py_tabs <- if (length(tabs)) reticulate::r_to_py(tabs) else reticulate::dict()
  py$divenn_store(adata, py_tabs, reticulate::r_to_py(cat_list), deg_mode)
  adata$write_h5ad(fname, compression = "gzip")
}

# ------------------------------------------------------------------ main
DiVenn2_preprocess_seuratobj <- function(seu, opt) {
  cell_type_col <- opt$group
  condition_col <- opt$condition
  deg_mode <- opt$deg_mode
  assay <- opt$assay
  gene_list <- read_gene_list(opt$gene_list_file)

  # SeuratObject:: needed: SummarizedExperiment also exports an Assays() function.
  if (!assay %in% SeuratObject::Assays(seu)) stop("Assay '", assay, "' not found in the Seurat object")
  DefaultAssay(seu) <- assay
  seu <- join_layers_if_split(seu, assay)
  meta <- seu@meta.data
  validate_deg_inputs(meta, rownames(seu[[assay]]), cell_type_col, condition_col, deg_mode,
                      opt$sample_col, opt$min_cells_per_sample, opt$min_replicates)

  conditions <- unique(as.character(meta[[condition_col]]))
  cat("Sample conditions:", conditions, "\n")
  comparisons <- parse_comparison_pairs(opt$comparisons, conditions)
  if (!nrow(comparisons)) stop("No valid condition comparisons were selected")
  cat("Condition comparisons (treatment vs control):\n")
  print(comparisons, row.names = FALSE)

  seu <- ensure_normalized(seu, assay)
  cell_types <- unique(as.character(meta[[cell_type_col]]))

  if (deg_mode == "pseudobulk") {
    cat("[DiVenn2] Pseudobulk: AggregateExpression + FindMarkers(test.use = 'DESeq2');",
        "samples are the independent replicates\n")
    counts <- get_pseudobulk_counts(seu, assay, opt$counts_layer)
    aggregated <- build_pseudobulk_objects(counts, meta, cell_type_col, condition_col,
                                           opt$sample_col, opt$min_cells_per_sample)
    rm(counts); invisible(gc())
  } else {
    cat("[DiVenn2] Single-cell: Seurat FindMarkers (", opt$method, ")\n", sep = "")
  }

  deg_tables <- list()
  csv_tables <- list()
  stats_tables <- list()
  catalog <- data.frame(key = character(), cell_type = character(), cond1 = character(),
                        cond2 = character(), method = character(), groupby = character(),
                        deg_mode = character(), n_replicates_cond1 = integer(),
                        n_replicates_cond2 = integer(), counts_source = character(),
                        stringsAsFactors = FALSE)

  for (ct in cell_types) {
    cat("[DiVenn2] Cell type:", ct, "\n")
    if (deg_mode == "pseudobulk") {
      if (is.null(aggregated[[ct]])) {
        cat("[DiVenn2] No pseudobulk samples pass cell-count filtering\n")
        next
      }
      agg <- aggregated[[ct]]
    } else {
      seu_ct <- seu[, as.character(meta[[cell_type_col]]) == ct]
      Idents(seu_ct) <- as.character(seu_ct@meta.data[[condition_col]])
    }

    for (i in seq_len(nrow(comparisons))) {
      cond1 <- comparisons$c1[i]
      cond2 <- comparisons$c2[i]
      key <- result_key(ct, cond1, cond2)
      if (deg_mode == "single_cell") {
        n1 <- sum(Idents(seu_ct) == cond1)
        n2 <- sum(Idents(seu_ct) == cond2)
        if (min(n1, n2) < 3) {
          cat(sprintf("[DiVenn2] Skipping %s vs %s: %d/%d cells\n", cond1, cond2, n1, n2))
          next
        }
        result <- run_single_cell_deg(seu_ct, condition_col, cond1, cond2, opt$method,
                                      opt$minpct_thd, opt$logfc_thd, assay)
        used_method <- opt$method
      } else {
        out <- run_pseudobulk_deg(agg$pb, agg$meta, cond1, cond2, opt$min_replicates)
        result <- out$result
        n1 <- out$n1
        n2 <- out$n2
        used_method <- "FindMarkers_DESeq2"
      }
      if (is.null(result)) {
        if (deg_mode == "single_cell") cat("[DiVenn2] No DEGs retained:", ct, ",", cond1, "vs", cond2, "\n")
        next
      }
      filtered <- format_deg_results(result, ct, cond1, cond2, opt$logfc_thd, opt$padj_thd,
                                     gene_list, opt$gene_filter_mode, opt$gene_filter_ignore_case)
      if (!nrow(filtered)) {
        cat("[DiVenn2] No DEGs retained:", ct, ",", cond1, "vs", cond2, "\n")
        next
      }
      if (!is.null(deg_tables[[key]])) stop("Duplicate DiVenn2 output key: ", key, "; check label collisions")
      deg_tables[[key]] <- filtered
      catalog[nrow(catalog) + 1, ] <- list(key, ct, cond1, cond2, used_method, condition_col,
                                           deg_mode, as.integer(n1), as.integer(n2),
                                           if (deg_mode == "pseudobulk") opt$counts_layer else "seurat_data")
      csv_tables[[key]] <- filtered[, DIVENN_COLUMNS]
      if (opt$write_stats_csv) stats_tables[[key]] <- filtered
      cat(sprintf("[DiVenn2] Stored %d DEGs (%d up, %d down): %s, %s vs %s\n", nrow(filtered),
                  sum(filtered$Reg_direct == "1"), sum(filtered$Reg_direct == "2"), ct, cond1, cond2))
    }
    if (deg_mode == "single_cell") { rm(seu_ct); invisible(gc()) }
  }

  fname <- normalizePath(opt$output, mustWork = FALSE)
  dir.create(dirname(fname), recursive = TRUE, showWarnings = FALSE)
  stem <- sub("\\.h5ad$", "", fname)
  empty <- as.data.frame(setNames(replicate(length(DIVENN_COLUMNS), character(), simplify = FALSE),
                                  DIVENN_COLUMNS))
  if (opt$write_csv) {
    csv <- paste0(stem, "_divenn2_deg.csv")
    write_csv_min_quote(if (length(csv_tables)) do.call(rbind, unname(csv_tables)) else empty, csv)
    cat("[DiVenn2] Saved", csv, "\n")
  }
  if (opt$write_stats_csv) {
    stats_csv <- paste0(stem, "_deg_statistics.csv")
    write_csv_min_quote(if (length(stats_tables)) do.call(rbind, unname(stats_tables)) else empty, stats_csv)
    cat("[DiVenn2] Saved", stats_csv, "\n")
  }
  write_divenn_h5ad(seu, assay, deg_tables, catalog, deg_mode, fname)
  cat("[DiVenn2] Saved .h5ad containing", nrow(catalog), "comparisons:", fname, "\n")
}

if (!is.null(opt$workdir)) {
  dir.create(opt$workdir, recursive = TRUE, showWarnings = FALSE)
  setwd(opt$workdir)
}
if (nzchar(Sys.getenv("RETICULATE_PYTHON"))) {
  reticulate::use_python(Sys.getenv("RETICULATE_PYTHON"), required = TRUE)
}
if (opt$deg_mode == "pseudobulk" && !requireNamespace("DESeq2", quietly = TRUE)) {
  stop("Pseudobulk requires the Bioconductor package DESeq2")
}

cat("Working directory:", getwd(), "\n")
for (nm in c("input", "condition", "group", "output", "deg_mode", "sample_col", "assay",
             "counts_layer", "logfc_thd", "minpct_thd", "padj_thd", "comparisons", "method",
             "min_cells_per_sample", "min_replicates", "write_csv", "write_stats_csv",
             "gene_list_file", "gene_filter_mode", "gene_filter_ignore_case")) {
  cat(sprintf("%s: %s\n", nm, if (is.null(opt[[nm]])) "NULL" else as.character(opt[[nm]])))
}

start_time <- proc.time()
# Only Seurat objects saved as .rds are accepted (as in Preprocessing_Seuratobj.r).
# For .h5ad data, convert first with convert_h5ad_to_seuratobj.r,
# or use Preprocessing_h5ad_with_pseudobulk.py directly.
if (!file.exists(opt$input)) stop("Input file does not exist: ", opt$input)
if (!grepl("\\.rds$", opt$input, ignore.case = TRUE)) {
  stop("--input must be a Seurat object saved as .rds (got '", basename(opt$input), "'). ",
       "Convert .h5ad files first with convert_h5ad_to_seuratobj.r.")
}
seurat_obj <- readRDS(opt$input)
if (!inherits(seurat_obj, "Seurat")) {
  stop("--input does not contain a Seurat object (class: ",
       paste(class(seurat_obj), collapse = "/"), ")")
}
DiVenn2_preprocess_seuratobj(seurat_obj, opt)
cat("Running time (s):", round((proc.time() - start_time)[["elapsed"]], 1), "\n")
