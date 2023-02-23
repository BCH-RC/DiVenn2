import pandas as pd
import re
import urllib
from urllib import request, error

col_names_short = ["ensembl_id", "pathway_id", "path_url", "path_desc"]
col_names_long = ["ensembl_id", "pathway_id", "path_url", "path_desc", "data1", "species", "data2"]
url = 'https://reactome.org/download/current/Ensembl2Reactome_All_Levels.txt'


def extract_species_code(pid: str):
    pattern = '[A-Z]{3}'
    result = re.search(pattern, pid, re.IGNORECASE)
    if result is not None:
        return result.group()
    else:
        return None


try:
    with urllib.request.urlopen(url) as response:
        data = response.read().decode('UTF-8')
except urllib.error.URLError:
    print("-------------------problem with url:" + url)

data = str(data.replace('\t', ';'))

df_pathways = pd.DataFrame([x.split(';') for x in data.split('\r\n')])
df_pathways.columns = col_names_long
df_pathways = df_pathways[df_pathways.ensembl_id != '']
df_pathways = df_pathways.drop(columns=['data1', 'data2'], axis=1)

df_pathways = df_pathways.drop(columns=['species'], axis=1)
df_pathways_mapping = df_pathways.iloc[:, 0:2]
df_pathways_mapping.to_csv(path_or_buf='pathways_mapping.csv', index=False, sep=';')

df_pathways = df_pathways.drop(columns=['ensembl_id'], axis=1)
df_pathways.to_csv(path_or_buf='pathways.csv', index=False, sep=';')

path_url = df_pathways.groupby(['path_url']).groups.keys()
pathway_id = df_pathways.groupby(['pathway_id']).groups.keys()

species_shortnames = [extract_species_code(pid) for pid in list(pathway_id)]

in_pathway = df_pathways.groupby(['pathway_id']).value_counts().tolist()
df_pathways = pd.DataFrame.from_dict({'pathway_id': pathway_id, 'path_url': path_url,
                                      'species_shortname': species_shortnames, 'in_pathway': in_pathway})
df_pathways.to_csv(path_or_buf='pathways_with_in_pathway.csv', index=False, sep=';')
