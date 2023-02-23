# gene annotation
# http://rest.kegg.jp/list/ath

# http://rest.kegg.jp/list/organism
# http://rest.kegg.jp/link/pathway/hsa
# http://rest.kegg.jp/link/pathway/hsa:10327
# http://rest.kegg.jp/list/path:hsa00010
# /get/hsa05130/image	  	retrieves the png image file of a pathway map

# from kegg to GO terms
# http://www.geneontology.org/page/download-mappings
# http://geneontology.org/external2go/kegg2go

# TO DO
# ADD MAP INFO: https://www.kegg.jp/kegg/rest/keggapi.html#info
# uniprot and ensembl ID convert
# GO

import urllib
import mysql.connector

from urllib import request

info = None
gene = None
speciesList = []
speciesName = "speciesList.txt"

with open('config.inc.py', 'r') as file:
    host = file.readline()
    user = file.readline()
    passwd = file.readline()

mydb = mysql.connector.connect(host, passwd, user)

mycursor1 = mydb.cursor()
mycursor1.execute(
    "CREATE TABLE IF NOT EXISTS pathway (kegg_id VARCHAR(255),species_short VARCHAR(255), gene_desc VARCHAR(255),"
    "pathway VARCHAR(255), path_url VARCHAR(255))")

sql_pathway = "INSERT IGNORE INTO pathway (kegg_id,species_short,gene_desc,pathway,path_url) VALUES (%s,%s,%s,%s,%s)"

with open(speciesName, "r") as file:
    for line in file:
        data = line.strip().split("\t")
        if not data[1] in speciesName:
            speciesList.append(data[1])

with urllib.request.urlopen("http://rest.kegg.jp/list/organism", timeout=20) as received_organisms_kegg:
    received_data = received_organisms_kegg.read().decode('UTF-8')

for sp_info in received_data.split("\n"):
    if not sp_info:
        continue
    speciesInfo = sp_info.strip().split("\t")

    spciesID = speciesInfo[0]
    species = speciesInfo[1]

    if species in speciesList:
        fileName = "pathway/" + species + ".txt"
        speciesName = "speciesName.txt"
        gene2path = {}
        gene2info = {}
        with open(fileName, "w") as fileout:
            print("working with species:" + species)
            fileout.write("geneID\tDescription\tPathway\tPathway_map\n")

        with urllib.request.urlopen("http://rest.kegg.jp/list/" + species) as data1:  # -----gene description
            for line in data1.split("\n"):
                if not line:
                    continue
                gene, info = line.strip().split("\t")
                geneID = gene.strip().split(":")[1]
                gene2info[geneID] = info

        with urllib.request.urlopen("http://rest.kegg.jp/link/pathway/" + species) as data2:  # -----gene to path ID
            for line in data2.split("\n"):
                if not line:
                    continue
                gene, path = line.strip().split("\t")

        with urllib.request.urlopen("http://rest.kegg.jp/list/" + path) as kegg_path:
            path_name = kegg_path.strip().split("\t")[1]
            if gene and path and path_name:
                geneID = gene.strip().split(":")[1]
                pathID = path.strip().split(":")[1]
                pathMap = "http://rest.kegg.jp/get/" + pathID + "/image"
                if (geneID, 'path') in gene2path:
                    gene2path[(geneID, 'path')].append((path_name, pathMap))
                else:
                    gene2path[(geneID, 'path')] = [(path_name, pathMap)]

        for geneID in gene2info:
            if (geneID, 'path') in gene2path:
                for path_name, pathMap in gene2path[(geneID, 'path')]:
                    fileout.write(geneID + "\t" + gene2info[geneID] + "\t" + path_name + "\t" + pathMap + "\n")
                    val_pathway = (geneID, species, gene2info[geneID], path_name, pathMap)
                    mycursor1.execute(sql_pathway, val_pathway)

            else:
                fileout.write(geneID + "\t" + gene2info[geneID] + "\t-\t-\n")
                val_pathway = (geneID, species, gene2info[geneID], '-', '-')
                mycursor1.execute(sql_pathway, val_pathway)

mydb.commit()
mydb.close()
