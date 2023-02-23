import mysql.connector

pathcount = {}
geneCount = {}
speciesList = []
speciesName = "speciesList.txt"

with open('config.inc.py', 'r') as file:
    host = file.readline()
    user = file.readline()
    passwd = file.readline()

mydb1 = mysql.connector.connect(host, passwd, user, database="divenn_db")

mycursor1 = mydb1.cursor()

with open(speciesName, "r") as speciesFile:
    for line in speciesFile:
        data = line.strip().split("\t")
        if not data[1] in speciesName:
            speciesList.append(data[1])

for species in speciesList:
    sql1 = "select count(distinct(kegg_id)) from pathway where species_short ='" + species + "'"
    mycursor1.execute(sql1)
    myresult1 = mycursor1.fetchone()
    geneCount[species] = myresult1[0]

    sql2 = "select pathway, count(*) from pathway where species_short ='" + species + "' group by pathway"
    mycursor1.execute(sql2)
    myresult2 = mycursor1.fetchall()
    for x in myresult2:
        if not x[0] == "-":
            pathcount[(species, x[0])] = x[1]

for species, path in pathcount:
    not_in_path = (geneCount[species] - pathcount[(species, path)])
    sql1 = "UPDATE pathway set in_path = " + str(pathcount[(species, path)]) + " where species_short = '" + species + \
           "' and pathway = '" + path + "'"
    sql2 = "UPDATE pathway set not_in_path = " + str(not_in_path) + " where species_short = '" + species + \
           "' and pathway = '" + path + "'"
    mycursor1.execute(sql1)
    mycursor1.execute(sql2)

mydb1.commit()
