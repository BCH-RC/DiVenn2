require("mygene")
require(stringr)

#METHODS
create_go_dataframe <- function(species) {
  go.results <- data.frame(matrix(ncol = 4, nrow = 0))
  columns <- c("gene_id", "go_id", "go_category", "term")
  colnames(go.results) <- columns
  
  for (row_index in 1:nrow(mygenes)) {
    
    if (row_index %% 100 == 0) {
      cat("analyzying row:", row_index, "out of", nrow(mygenes), "\n")
    }
    
    gene_id <- mygenes[row_index, "query"]
    
    if ("go.BP" %in% colnames(mygenes)) {
      go.BP <- mygenes[row_index, "go.BP"][[1]]
      if (length(go.BP) != 0) {
        go.results <- add_go_results_to_df_for_go_category(gene_id,  "P", go.BP)
        go.results <- rbind(go.results , go_results_for_gene_id)
      }
    }
    
    if ("go.MF" %in% colnames(mygenes)) {
      go.MF <- mygenes[row_index, "go.MF"][[1]]
      if (length(go.MF) != 0) {
        go_results_for_gene_id <- add_go_results_to_df_for_go_category(gene_id, "F", go.MF)
        go.results <- rbind(go.results, go_results_for_gene_id)
      }
    }
    
    if ("go.CC" %in% colnames(mygenes)) {
      go.CC <- mygenes[row_index, 'go.CC'][[1]]
      if (length(go.CC) != 0) {
        go_results_for_gene_id <- add_go_results_to_df_for_go_category(gene_id, "C", go.CC)
        go.results <- rbind(go.results, go_results_for_gene_id)
      }
    }
  }
  go.results <- unique(go.results)
  write_go_results(go.results, species, output_dir)
}

add_go_results_to_df_for_go_category <-
  function(gene_id, go_category, df_go_input) {
    df_go_results <- data.frame(matrix(ncol = 4, nrow = 0))
    columns <- c("gene_id", "go_id", "go_category", "term")
    colnames(df_go_results) <- columns
    if (length(df_go_input[[1]]) == 1) {
      go_id <- df_go_input$id
      go_qualifier <- replace_dash_with_space_in_go_qualifier(df_go_input$qualifier)
      go_term <- paste(go_qualifier, df_go_input$term)
      df_go_results[nrow(df_go_results) + 1,] <- c(gene_id, go_id, go_category, go_term)
    }
    else{
      for (index in 1:length(df_go_input[[1]]))
      {
        go_id <- df_go_input[index, "id"]
        go_qualifier <- replace_dash_with_space_in_go_qualifier(df_go_input[index, "qualifier"])
        go_term <- paste(go_qualifier, df_go_input[index, "term"])
        df_go_results[nrow(df_go_results) + 1,] <- c(gene_id, go_id, go_category, go_term)
      }
    }
    return(df_go_results)
  }

replace_dash_with_space_in_go_qualifier <- function(qualifier) {
  return(gsub("_", " ", qualifier))
}

write_go_results <- function(df_go_results, species, output_dir) {
  write.table(
    df_go_results,
    file.path(output_dir, paste(species, "_go_results.tsv", sep = ""), fsep = "/"),
    sep = '\t',
    row.names = FALSE,
    quote = FALSE
  )
}

#MAIN: GET ONTOLOGIES
args <- commandArgs(trailingOnly = TRUE)
if (length(args) != 3) {
  stop(
    "Provide all needed arguments: species_file_path input_directory_path output_directory_path",
    call. = FALSE
  )
}

species_file_path <- args[1]
input_dir <- args[2]
output_dir <- args[3]

species_file_con <- file(species_file_path, open = 'r')
species_list <- readLines(species_file_con)
for (species in species_list)
{
  cat("Analyze organizm:", species, "\n")
  file <-
    file.path(input_dir, paste0(species, "_ncbi2kegg"), fsep = "/")
  gene_list <- read.table(file, sep = "\t")
  genes <- gene_list[1]
  genes <- str_replace(genes$V1, "ncbi-geneid:", "")
  unique_genes <- unique(genes)
  mygenes <- getGenes(unique_genes, fields = "go.BP,go.MF,go.CC")
  create_go_dataframe(species)
}
close(species_file_con)
