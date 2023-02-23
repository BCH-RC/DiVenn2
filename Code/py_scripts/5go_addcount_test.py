#!/usr/bin/python
#count all genes for the same go terms

#######################################To do before running this script################################
#ALTER TABLE pathway ADD COLUMN in_path INT AFTER path_url;
#ALTER TABLE pathway ADD COLUMN not_in_path INT AFTER in_path;
#######################################TEST database ################################
#create table go_test select * from go where species = 'ath';
#ALTER TABLE go_test ADD COLUMN in_go INT AFTER go_category;
#ALTER TABLE go_test ADD COLUMN not_in_go INT AFTER in_go;
import urllib2
import mysql.connector
import datetime

tstart = datetime.datetime.now()
print "-------------Starting----------------------"


speciesList = []
speciesName = "speciesList.txt"


mydb1 = mysql.connector.connect(
  host="localhost",
  user="root",
  passwd="divenn",
  database="divenn_db"
)
mycursor1 = mydb1.cursor()

#mycursor1.execute(sql_pathway)
with open(speciesName,"r") as speciesFile:
    for line in speciesFile:
        data = line.strip().split("\t")
        if not data[1] in speciesName:
            speciesList.append(data[1])
            
for species in speciesList:
    if species != "ath":
        continue
    sql0 = "select distinct(source) from go_test1 where species ='"+species+"'"
    mycursor1.execute(sql0)
    myresult0 = mycursor1.fetchall()
    source = []
    for x in myresult0:
        if not x[0] in source:
            source.append(x[0])
    for s in source:
        gocount = {}
        #geneCount = {}
        geneCount = int
        print "processing: "+species+"-"+s
        sql1 = "select count(distinct(id)) from go_test1 where species ='"+species+"' and source ='" +s+"'"
        mycursor1.execute(sql1)
        myresult1 = mycursor1.fetchone()
        #geneCount[(species,s)]= myresult1[0]
        geneCount= myresult1[0]
    
        sql2 = "select go_id, count(*) from go_test1 where species ='"+species+"' and source ='"+s+"' group by go_id"
        mycursor1.execute(sql2)
        #myresult1 = mycursor1.execute("select * from pathway_test limit 5")
        myresult2 = mycursor1.fetchall()
        for x in myresult2:
            if not x[0] == "-":
                #gocount[(species,s,x[0])] = x[1]
                print x[0]
                print x[1]
            #for species,s, go_id in gocount:
                not_in_go = (geneCount - x[1])
                #sql1 = "UPDATE go_test1 set in_go = "+ str(x[1]) +" where species = '"+species+"' and source ='"+s+"' and go_id = '"+x[0]+"'"
                sql3 = "UPDATE go_test1 set in_go = "+ str(x[1]) +", not_in_go ="+ str(not_in_go) +" where species = '"+species+"' and source ='"+s+"' and go_id = '"+x[0]+"'"
                #sql2 = "UPDATE go_test1 set not_in_go = "+ str(not_in_go) +" where species = '"+species+"' and source ='"+s+"' and go_id = '"+x[0]+"'"
                #mycursor1.execute(sql1)
                #mycursor1.execute(sql2)
                mycursor1.execute(sql3)
mydb1.commit()
tend = datetime.datetime.now()
time_total = int((tend-tstart).total_seconds()*1000)
print "-------------total time:"+str(time_total)
print "-------------Finished----------------------"

