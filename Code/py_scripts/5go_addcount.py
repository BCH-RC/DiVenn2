import mysql.connector

print("-------------Starting----------------------")

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
    sql0 = "select distinct(source) from go where species ='" + species + "'"
    mycursor1.execute(sql0)
    myresult0 = mycursor1.fetchall()
    source = []
    for x in myresult0:
        if not x[0] in source:
            source.append(x[0])
    for s in source:
        gocount = {}
        print("processing: " + species + "-" + s)
        sql1 = "select count(distinct(id)) from go where species ='" + species + "' and source ='" + s + "'"
        mycursor1.execute(sql1)
        myresult1 = mycursor1.fetchone()
        geneCount = myresult1[0]

        sql2 = "select go_id, count(*) from go where species ='" + species + "' and source ='" + s + "' group by go_id"
        mycursor1.execute(sql2)
        myresult2 = mycursor1.fetchall()
        for x in myresult2:
            if not x[0] == "-":
                not_in_go = (geneCount - x[1])
                sql1 = "UPDATE go set in_go = " + str(
                    x[1]) + " where species = '" + species + "' and source ='" + s + "' and go_id = '" + x[
                           0] + "'"
                sql2 = "UPDATE go set not_in_go = " + str(
                    not_in_go) + " where species = '" + species + "' and source ='" + s + "' and go_id = '" + x[
                           0] + "'"
                mycursor1.execute(sql1)
                mycursor1.execute(sql2)
mydb1.commit()
print("-------------Finished----------------------")
