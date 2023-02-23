import mysql.connector

class Term:
    def __init__(self):
        self.go_id = ""
        self.go_term = ""
        self.go_category = ""


go_terms = "GO/term.txt"
go2info = {}
species = ""
speciesList = []
speciesName = "speciesList.txt"

with open('config.inc.py', 'r') as file:
    host = file.readline()
    user = file.readline()
    passwd = file.readline()

mydb = mysql.connector.connect(host, passwd, user, database="DiVenn")

mycursor = mydb.cursor()
mycursor.execute(
    "CREATE TABLE IF NOT EXISTS go (id VARCHAR(255),species VARCHAR(255),source VARCHAR(255),go_id VARCHAR(255), "
    "go_term VARCHAR(255), go_category VARCHAR(255))")
sql_go = "INSERT IGNORE INTO go (id,species,source,go_id,go_term,go_category) VALUES (%s,%s,%s,%s,%s,%s)"

with open(speciesName, "r") as speciesFile:
    for line in speciesFile:
        data = line.strip().split("\t")
        if not data[1] in speciesName:
            speciesList.append(data[1])

with open(go_terms, "r") as lines:
    for line in lines:
        data = line.strip().split("\t")
        if not data:
            continue
        if not data[0] in go2info:
            go2info[data[0]] = Term()
            go2info[data[0]].go_id = data[0]
            go2info[data[0]].go_term = data[2]
            go2info[data[0]].go_category = data[1]
        else:
            print("duplicate GO ID:" + data[0])

for species in speciesList:

    fileName = "GO/" + species + "2go.txt"
    print("working with species:" + species)
    # human data is different from others

    with open(fileName, "r") as fileIN, open("GO_noterm.txt", "w") as fileOUT:
        next(fileIN)
        fileOUT.write("--------------------" + species + "--------------------\n")
        for line in fileIN:
            data = line.strip().split("\t")
            if data[2] in go2info:
                val_go = (data[1], species, data[0], data[2], go2info[data[2]].go_term, go2info[data[2]].go_category)
                mycursor.execute(sql_go, val_go)
            else:
                val_go = (data[1], species, data[0], data[2], "-", "-")
                mycursor.execute(sql_go, val_go)

                fileOUT.write("GO ID has no term:" + data[2] + "\n")

mydb.commit()
mydb.close()

print("------------------Writing to GO database is finished-----------------------")
