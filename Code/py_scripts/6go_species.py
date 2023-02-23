#!/usr/bin/python

import urllib2
import mysql.connector


print "-------------Starting----------------------"


speciesList = []
speciesName = "speciesList.txt"


mydb = mysql.connector.connect(
  host="localhost",
  user="root",
  passwd="divenn",
  database="divenn_db"
)
mycursor = mydb.cursor()

#mycursor1.execute(sql_pathway)
with open(speciesName,"r") as speciesFile:
    for line in speciesFile:
        data = line.strip().split("\t")
        if not data[1] in speciesName:
            speciesList.append(data[1])
            
for species in speciesList:
    # if species != "ath":
    #     continue
    print "processing species:"+species
    sql0 = "drop table go_"+species
    sql1 = "create table go_"+species+" select * from go where species ='"+species+"'"
    sql2 = "create index go_index on go_"+species+" (id, source, go_id)"
    
    mycursor.execute(sql0)
    mycursor.execute(sql1)
    mycursor.execute(sql2)

mydb.commit()

print "-------------Finished----------------------"

