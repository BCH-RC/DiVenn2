#!/usr/bin/Rscript
setwd('/var/www/html/v3/r')
##library(jsonlite)
args <- commandArgs(TRUE)
##Generate error debug file
file_con <- file("./Rdebug.txt", open = "a")
sink(file_con, append=FALSE, type='message')

## if no or low ncbi mapping, we must map to uniprot
badNcbiMap <- c('ddi', 'mpo', 'ppa')

## Take in json encoded array and decode it to r data frame
## array must be a list of genes
## What we need: ncbi or uniprot Gene list, ref Db from args

refdb <- args[2] # Get reference DB

genearr <- jsonlite::fromJSON(args[1], flatten=TRUE)

if (refdb == "Ensembl"){
    genearr <- as.data.frame(genearr)
} else {
    genearr <- as.data.frame(genearr)
    colnames(genearr) <- c('V1', 'V2')
    genearr <- genearr[1]    
}

genearr <- as.vector(genearr)


## 3 letter code
species <- args[3]

    if (refdb == "Ensembl"){
        if (species %in% badNcbiMap) {
            ## use ensembl mapped to ncbi ID list to run enrichKEGG
            enrichKEGG <- clusterProfiler::enrichKEGG(gene = genearr$ID, organism = species, keyType = 'uniprot')
            enrichKEGG <- as.data.frame(enrichKEGG)
            if (length(enrichKEGG) > 0) {
            colnames(enrichKEGG)[6] <- "p_adjust"
            ##write.table(enrichKEGG, file=paste("enrichKEGGEnsembl.txt", sep="")) 
            } else {
                cat("No Results")
            }
            #save(enrichGO_all, file = "enrichEnsembl.Rdata")
        } else {
            ## use ensembl mapped to ncbi ID list to run enrichKEGG
            enrichKEGG <- clusterProfiler::enrichKEGG(gene = genearr$ID, organism = species, keyType = 'ncbi-geneid')
            enrichKEGG <- as.data.frame(enrichKEGG)
            if (length(enrichKEGG) > 0) {
            colnames(enrichKEGG)[6] <- "p_adjust"
            ##write.table(enrichKEGG, file=paste("enrichKEGGEnsembl.txt", sep="")) 
            } else {
                cat("No Results")
            }
            #save(enrichGO_all, file = "enrichEnsembl.Rdata")
        }
        
    } else if (refdb == "Uniprot") {
        ## use uniprot ID list to run enrichKEGG
        enrichKEGG <- clusterProfiler::enrichKEGG(gene = genearr$V1, organism = species,  keyType = 'uniprot')
        enrichKEGG <- as.data.frame(enrichKEGG)
        if (length(enrichKEGG) > 0) {
            colnames(enrichKEGG)[6] <- "p_adjust"
            ##write.table(enrichKEGG, file=paste("enrichKEGGUni.txt", sep="")) 
            } else {
                cat("No Results")
            }
        #save(enrichGO_all, file = "enrichUni.Rdata")
    } else {
        ## use ncbi ID list to run enrichKEGG
        enrichKEGG <- clusterProfiler::enrichKEGG(gene = genearr$V1, organism = species,  keyType = 'ncbi-geneid')
        enrichKEGG <- as.data.frame(enrichKEGG)
        if (length(enrichKEGG) > 0) {
            colnames(enrichKEGG)[6] <- "p_adjust"
            ##write.table(enrichKEGG, file=paste("enrichKEGGNCBI.txt", sep="")) 
            } else {
                cat("No Results")
            }
        #save(enrichGO_all, file = "enrichNCBI.Rdata")
    }
    ##May need to trim results of enrichGO to avoid confusing json encoding
    enrichJSON <- jsonlite::toJSON(enrichKEGG, dataframe = "rows")
    cat(enrichJSON)


