#!/usr/bin/Rscript
resultdir <- "/var/www/html/v3/r/pngs"
setwd('/var/www/html/v3/r')
args <- commandArgs(TRUE)
##Generate error debug file
#file_con <- file("./Rdebug.txt", open = "a")
#sink(file_con, append=FALSE, type='message')
#go_all_file <- paste("go_all.png",sep="")
keyType <- "ENTREZID"

##lists of acceptable codes for each database and annohub database ids. If not in list, input will have been mapped in cluster.php
ensSpCodes <- c('cel', 'cfa', 'dre', 'dme', 'gga', 'hsa', 'mmu', 'rno', 'scs', 'spo')
uniSpCodes <- c('cel', 'cfa', 'dre', 'dme', 'gga', 'hsa', 'mmu', 'rno', 'scs', 'spo', 'ssc', 'xtr')
spCode <- c('ath', 'cel', 'cfa', 'dre', 'dme', 'gga', 'hsa', 'mmu', 'rno', 'scs', 'spo', 'ssc', 'xtr',
            'osi', 'mpo', 'pfa')
annoHubSpCodes <- c("bdi", "cre", "ddi", "gma", "mtr", "nat", "pvu", "ppa", "pti", "spo", "smo", "zma", "pfa")

## List of OrgDb base pkgs and self-made pkgs to match to species. List of Annohub ID's to match to species codes which require annohub OrgDb objects
orgdb <- c('org.At.tair.db', 'org.Ce.eg.db', 'org.Cf.eg.db', 'org.Dr.eg.db', 'org.Dm.eg.db', 'org.Gg.eg.db', 
            'org.Hs.eg.db', 'org.Mm.eg.db', 'org.Rn.eg.db', 'org.Sc.sgd.db', 'org.Sc.sgd.db', 'org.Ss.eg.db', 'org.Xl.eg.db',
            'org.Osativa.Indica.Group.eg.db', 'org.Mpolymorpha.eg.db', 'org.Pfalciparum.eg.db')
annoHubDbIDs <-  c("AH107895",'AH108689','AH109080','AH107443','AH107569','AH107564','AH108153','AH108453','AH107622',
                    "AH10598","AH107604","AH107470","AH107123")


annoHubdf <- data.frame(annoHubSpCodes, annoHubDbIDs)
dbdf <- data.frame(spCode, orgdb)

## Take in json encoded array and decode it to r data frame
## array must be a list of genes
## What we need: Gene list, OrgDb from args, species code, species lists for each database

## 3 letter species code
species <- args[3]

genearr <- jsonlite::fromJSON(args[1], flatten=TRUE)

refdb <- args[2] # Get reference DB

## format dataframe for mapped or unmapped input list
genearr <- as.data.frame(genearr)
colnames(genearr) <- c('V1')
genearr <- genearr[1]    


genearr <- as.vector(genearr)

if (any(dbdf$spCode == species)) {
    ## reference array and match to org.*.db based on 3 letter code
    select <- subset(dbdf, subset = (spCode == species))
    OrgDb <- select$orgdb[1]

    if (species == 'ath') {
        enrichGO_all_obj <- clusterProfiler::enrichGO(gene = genearr$V1, keyType="TAIR", OrgDb = OrgDb, ont = "ALL")#, readable = TRUE)
        enrichGO_all <- as.data.frame(enrichGO_all_obj)
        if (length(enrichGO_all) > 0) {
            colnames(enrichGO_all)[7] <- "p_adjust"
            enrichGO_all$GeneRatio <- sapply(enrichGO_all$GeneRatio, function(x) eval(parse(text=as.character(x))))
            #write.table(enrichGO_all, file=paste("enrichEnsembl.txt", sep=""))
            enrichGO_m <- as.matrix(enrichGO_all)
        } else {
            cat("No Results")
        }
    }

    ## Can condense these into single statement since we convert all to ENTREZID
    else if (refdb == "Ensembl" && any(ensSpCodes == species)){
        ## use Ensembl ID list to run enrichGO with "All" ont to obtain result if species has keytype=ENSEMBL
        enrichGO_all_obj <- clusterProfiler::enrichGO(gene = genearr$V1, keyType="ENSEMBL", OrgDb = OrgDb, ont = "ALL")#, readable = TRUE)
        enrichGO_all <- as.data.frame(enrichGO_all_obj)
        if (length(enrichGO_all) > 0) {
            colnames(enrichGO_all)[7] <- "p_adjust"
            enrichGO_all$GeneRatio <- sapply(enrichGO_all$GeneRatio, function(x) eval(parse(text=as.character(x))))
            #write.table(enrichGO_all, file=paste("enrichEnsembl.txt", sep=""))
            enrichGO_m <- as.matrix(enrichGO_all)
        } else {
            cat("No Results")
        }
    } else if (refdb == "Uniprot" && any(uniSpCodes == species)) {
        ## use Uniprot list to run enrichGO with "All" ont to obtain result if species has keytype=UNIPROT
        enrichGO_all_obj <- clusterProfiler::enrichGO(gene = genearr$V1, keyType="UNIPROT", OrgDb = OrgDb, ont = "ALL")#, readable = TRUE)
        enrichGO_all <- as.data.frame(enrichGO_all_obj)
        if (length(enrichGO_all) > 0) {
            colnames(enrichGO_all)[7] <- "p_adjust"
            enrichGO_all$GeneRatio <- sapply(enrichGO_all$GeneRatio, function(x) eval(parse(text=as.character(x))))
            #write.table(enrichGO_all, file=paste("enrichUni.txt", sep=""))
            enrichGO_m <- as.matrix(enrichGO_all)
        }  else {
            cat("No Results")
        }
    } else {
        ## use entrez ID list to run enrichGO with "All" ont to obtain result. Could be mapped using our sql database or given as input
        enrichGO_all_obj <- clusterProfiler::enrichGO(gene = genearr$V1, keyType="ENTREZID", OrgDb = OrgDb, ont = "ALL")#, readable = TRUE)
        enrichGO_all <- as.data.frame(enrichGO_all_obj)
        if (length(enrichGO_all) > 0) {
            colnames(enrichGO_all)[7] <- "p_adjust"
            enrichGO_all$GeneRatio <- sapply(enrichGO_all$GeneRatio, function(x) eval(parse(text=as.character(x))))
            #write.table(enrichGO_all, file=paste("enrichNCBI.txt", sep=""))
            enrichGO_m <- as.matrix(enrichGO_all)
        } else {
            cat("No Results")
        }
    }
    ## Return json encoded string of enrichGO results
    enrichJSON <- jsonlite::toJSON(enrichGO_all, dataframe = "rows")
    cat(enrichJSON)

    ## All Annohub and Self-generated OrgDb objects will onyl support entrezID so no need to change keyType here
} else if (any(annoHubdf$annoHubSpCodes == species)) {
    ## reference array and match to annotationHub db based on 3 letter code
    select <- subset(annoHubdf, subset = (annoHubSpCodes == species))
    OrgDbCode <- select$annoHubDbIDs[1]
    AnnotationHub::setAnnotationHubOption("CACHE","/usr/share/httpd/.cache/R/AnnotationHub")
    hub <- AnnotationHub::AnnotationHub()
    OrgDb <- hub[[OrgDbCode]]

    ## Can condense these into single statement since we convert all to ENTREZID
    if (refdb == "Ensembl" && any(ensSpCodes == species)){
        ## use entrez ID list to run enrichGO with "All" ont to obtain result
        enrichGO_all_obj <- clusterProfiler::enrichGO(gene = genearr$V1, keyType="ENTREZID", OrgDb = OrgDb, ont = "ALL")#, readable = TRUE)
        enrichGO_all <- as.data.frame(enrichGO_all_obj)
        if (length(enrichGO_all) > 0) {
            colnames(enrichGO_all)[7] <- "p_adjust"
            enrichGO_all$GeneRatio <- sapply(enrichGO_all$GeneRatio, function(x) eval(parse(text=as.character(x))))
            #write.table(enrichGO_all, file=paste("enrichEnsembl.txt", sep=""))
            enrichGO_m <- as.matrix(enrichGO_all)
        } else {
            cat("No Results")
        }
        
    } else if (refdb == "Uniprot" && any(uniSpCodes == species)) {
        ## use entrez ID list to run enrichGO with "All" ont to obtain result
        enrichGO_all_obj <- clusterProfiler::enrichGO(gene = genearr$V1, keyType="ENTREZID", OrgDb = OrgDb, ont = "ALL")#, readable = TRUE)
        enrichGO_all <- as.data.frame(enrichGO_all_obj)
        if (length(enrichGO_all) > 0) {
            colnames(enrichGO_all)[7] <- "p_adjust"
            enrichGO_all$GeneRatio <- sapply(enrichGO_all$GeneRatio, function(x) eval(parse(text=as.character(x))))
            #write.table(enrichGO_all, file=paste("enrichUni.txt", sep=""))
            enrichGO_m <- as.matrix(enrichGO_all)
        } else {
            cat("No Results")
        }
        
    } else {
        ## use entrez ID list to run enrichGO with "All" ont to obtain result
        enrichGO_all_obj <- clusterProfiler::enrichGO(gene = genearr$V1, keyType="ENTREZID", OrgDb = OrgDb, ont = "ALL")#, readable = TRUE)
        enrichGO_all <- as.data.frame(enrichGO_all_obj)
        if (length(enrichGO_all) > 0) {
            colnames(enrichGO_all)[7] <- "p_adjust"
            enrichGO_all$GeneRatio <- sapply(enrichGO_all$GeneRatio, function(x) eval(parse(text=as.character(x))))
            #write.table(enrichGO_all, file=paste("enrichNCBI.txt", sep=""))
            enrichGO_m <- as.matrix(enrichGO_all)
        } else {
            cat("No Results")
        }
        
    }
    enrichJSON <- jsonlite::toJSON(enrichGO_all, dataframe = "rows")
    cat(enrichJSON)

} else {
    cat("Not Available")
}