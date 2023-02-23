#!/usr/bin/python
#count all genes for the same go terms

#######################################To do before running this script################################
#ALTER TABLE go ADD COLUMN in_go INT AFTER go_category;
#ALTER TABLE go ADD COLUMN not_in_go INT AFTER in_go;
#######################################TEST database ################################
#create table go_test select * from go where species = 'ath';
#ALTER TABLE go_test ADD COLUMN in_go INT AFTER go_category;
#ALTER TABLE go_test ADD COLUMN not_in_go INT AFTER in_go;


import urllib2
import mysql.connector


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
speciesList.sort()           
for species in speciesList:
    #if species != "ath":            ##########################change
        #continue                    ##########################change
    #sql0 = "select distinct(source) from go_test where species ='"+species+"'"          ##########################change
    #sql0 = "select distinct(source) from go where species ='"+species+"'"          ##########################change
    sql0 = "select distinct(source) from go_"+species; 
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
        #sql1 = "select count(distinct(id)) from go_test where species ='"+species+"' and source ='" +s+"'"     ##########################change
        sql1 = "select count(distinct(id)) from go_"+species+" where source ='" +s+"'"     ##########################change
        mycursor1.execute(sql1)
        myresult1 = mycursor1.fetchone()
        #geneCount[(species,s)]= myresult1[0]
        geneCount= myresult1[0]
    
        #sql2 = "select go_id, count(*) from go_test where species ='"+species+"' and source ='"+s+"' group by go_id"  ##########################change
        sql2 = "select go_id, count(*) from go_"+species+" where source ='"+s+"' group by go_id"  ##########################change
        mycursor1.execute(sql2)
        #myresult1 = mycursor1.execute("select * from pathway_test limit 5")
        myresult2 = mycursor1.fetchall()
        for x in myresult2:
            if not x[0] == "-":
                #gocount[(species,s,x[0])] = x[1]

            #for species,s, go_id in gocount:
                not_in_go = (geneCount - x[1])
                #sql1 = "UPDATE go_"+species+" set in_go = "+ str(x[1]) +" where source ='"+s+"' and go_id = '"+x[0]+"'"   ##########################change
                #sql2 = "UPDATE go_"+species+" set not_in_go = "+ str(not_in_go) +" where source ='"+s+"' and go_id = '"+x[0]+"'"   ##########################change
                sql3 = "UPDATE go_"+species+" set in_go = "+ str(x[1]) +", not_in_go ="+ str(not_in_go) +"  where source ='"+s+"' and go_id = '"+x[0]+"'"   ##########################change
                #mycursor1.execute(sql1)
                #mycursor1.execute(sql2)
                mycursor1.execute(sql3)
mydb1.commit()
print "-------------Finished----------------------"


# import urllib2
# import mysql.connector
# 
# 
# #gocount = {}
# #geneCount = {}
# speciesList = []
# speciesName = "speciesList.txt"
# 
# 
# mydb1 = mysql.connector.connect(
#   host="localhost",
#   user="root",
#   passwd="divenn",
#   database="divenn_db"            ############################################################# BE CAREFUL HERE
# )
# mycursor1 = mydb1.cursor()
# 
# #mycursor1.execute(sql_pathway)
# with open(speciesName,"r") as speciesFile:
#     for line in speciesFile:
#         data = line.strip().split("\t")
#         if not data[1] in speciesName:
#             speciesList.append(data[1])
#             
# for species in speciesList:
#     #if species != "ath":            ##########################change
#         #continue                    ##########################change
#     #sql0 = "select distinct(source) from go_test where species ='"+species+"'"          ##########################change
#     sql0 = "select distinct(source) from go where species ='"+species+"'"          ##########################change
#     mycursor1.execute(sql0)
#     myresult0 = mycursor1.fetchall()
#     source = []
#     for x in myresult0:
#         if not x[0] in source:
#             source.append(x[0])
#     for s in source:
#         #sql1 = "select count(distinct(id)) from go_test where species ='"+species+"' and source ='" +s+"'"     ##########################change
#         sql1 = "select count(distinct(id)) from go where species ='"+species+"' and source ='" +s+"'"     ##########################change
#         mycursor1.execute(sql1)
#         myresult1 = mycursor1.fetchone()
#         geneCount[(species,s)]= myresult1[0]
#     
#         #sql2 = "select go_id, count(*) from go_test where species ='"+species+"' and source ='"+s+"' group by go_id"  ##########################change
#         sql2 = "select go_id, count(*) from go where species ='"+species+"' and source ='"+s+"' group by go_id"  ##########################change
#         mycursor1.execute(sql2)
#         #myresult1 = mycursor1.execute("select * from pathway_test limit 5")
#         myresult2 = mycursor1.fetchall()
#         for x in myresult2:
#             if not x[0] == "-":
#                 gocount[(species,s,x[0])] = x[1]
#             
#             
# 
# 
# for species,s, go_id in gocount:
#     not_in_go = (geneCount[species,s] - gocount[(species,s,go_id)])
#     #sql1 = "UPDATE go_test set in_go = "+ str(gocount[(species,s,go_id)]) +" where species = '"+species+"' and source ='"+s+"' and go_id = '"+go_id+"'"           ##########################change
#     sql1 = "UPDATE go set in_go = "+ str(gocount[(species,s,go_id)]) +" where species = '"+species+"' and source ='"+s+"' and go_id = '"+go_id+"'"           ##########################change
#     #sql2 = "UPDATE go_test set not_in_go = "+ str(not_in_go) +" where species = '"+species+"' and source ='"+s+"' and go_id = '"+go_id+"'"                        ##########################change
#     sql2 = "UPDATE go set not_in_go = "+ str(not_in_go) +" where species = '"+species+"' and source ='"+s+"' and go_id = '"+go_id+"'"                        ##########################change
#     mycursor1.execute(sql1)
#     mycursor1.execute(sql2)
#     
# mydb1.commit()

