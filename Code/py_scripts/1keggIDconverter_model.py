import os.path
import urllib
import mysql.connector

from urllib import request, error

ncbi2kegg_data = None
uniprot2kegg_data = None
speciesFile = "speciesList.txt"
speciesName = []

with open('config.inc.py', 'r') as file:
    host = file.readline()
    user = file.readline()
    passwd = file.readline()

mydb = mysql.connector.connect(host, passwd, user)

mycursor1 = mydb.cursor()

mycursor1.execute(
    "CREATE TABLE IF NOT EXISTS species (species_id VARCHAR(255) PRIMARY KEY, short_name VARCHAR(255),
    full_name VARCHAR(255), taxonomy VARCHAR(255))")
mycursor1.execute(
    "CREATE TABLE IF NOT EXISTS ncbi2kegg (ncbi_id VARCHAR(255), species_short VARCHAR(255),kegg_id VARCHAR(255))")
mycursor1.execute(
    "CREATE TABLE IF NOT EXISTS uniprot2kegg (uniprot_id VARCHAR(255), species_short VARCHAR(255),kegg_id VARCHAR(255))")

sql_species_insert = "INSERT IGNORE INTO species (species_id,short_name,full_name,taxonomy) VALUES (%s,%s,%s,%s)"
sql_uniprot_insert = "INSERT IGNORE INTO uniprot2kegg (uniprot_id,species_short,kegg_id) VALUES (%s,%s,%s)"
sql_ncbi_insert = "INSERT IGNORE INTO ncbi2kegg (ncbi_id,species_short,kegg_id) VALUES (%s,%s,%s)"

with urllib.request.urlopen("http://rest.kegg.jp/list/organism", timeout=20) as received_organisms_kegg:
    received_data = received_organisms_kegg.read().decode('UTF-8')

for sp_info in received_data.split("\n"):
    if not sp_info:
        continue
    speciesInfo = sp_info.strip().split("\t")

    spciesID = speciesInfo[0]
    species = speciesInfo[1]

    ncbi2kegg = "ID_mapping/" + species + "_ncbi2kegg.txt"
    uniprot2kegg = "ID_mapping/" + species + "_uniprot2kegg.txt"

    if os.path.isfile(ncbi2kegg):
        print("working with species:" + species)
        try:
            with urllib.request.urlopen("http://rest.kegg.jp/conv/" + species + "/ncbi-geneid") as received_conv_ncbi:
                ncbi2kegg_data = received_conv_ncbi.read().decode('UTF-8')
        except urllib.error.URLError:
            print("-------------------problem with species:" + species)

        if ncbi2kegg_data:
            speciesName.append(sp_info.strip())
            for line in ncbi2kegg_data.split("\n"):
                if not line:
                    continue
                ncbi, kegg = line.strip().split("\t")
                ncbiID = ncbi.strip().split(":")[1]
                keggID = kegg.strip().split(":")[1]

                with open(ncbi2kegg) as file:
                    file.write(ncbiID + "\t" + keggID + "\n")
                    val_ncbi = (ncbiID, species, keggID)
                    mycursor1.execute(sql_ncbi_insert, val_ncbi)

    if os.path.isfile(uniprot2kegg):
        try:
            with urllib.request.urlopen("http://rest.kegg.jp/conv/" + species + "/uniprot") as received_conv_uniprot:
                uniprot2kegg_data = received_conv_uniprot.read().decode('UTF-8')
        except urllib.error.URLError:
            print("-------------------problem with species:" + species)

        if uniprot2kegg_data:
            for line in uniprot2kegg_data.split("\n"):
                if not line:
                    continue
                uniprot, kegg = line.strip().split("\t")
                uniprotID = uniprot.strip().split(":")[1]
                keggID = kegg.strip().split(":")[1]

                with open(uniprot2kegg) as file:
                    file.write(uniprotID + "\t" + keggID + "\n")
                    val_uniprot = (uniprotID, species, keggID)
                    mycursor1.execute(sql_uniprot_insert, val_uniprot)

with open(speciesFile, "w") as speciesFile:
    for s in speciesName:
        data = s.strip().split("\t")
        speciesFile.write(s + "\n")
        val_species = (data[0], data[1], data[2], data[3])
        mycursor1.execute(sql_species_insert, val_species)

mydb.close()
