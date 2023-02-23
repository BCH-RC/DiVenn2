import requests
import pandas as pd

ANIMAL_URL_TEMPLATE = "http://ftp.ensembl.org/pub/release-105/json/{species}/{species}.json"
NON_ANIMAL_BUT_BACTERIA_URL_TEMPLATE = "http://ftp.ebi.ac.uk/ensemblgenomes/pub/release-52/{kingdom}/json/{species}/{" \
                                       "species}.json"
BACTERIA_URL_TEMPLATE = "http://ftp.ebi.ac.uk/ensemblgenomes/pub/release-52/{kingdom}/json/{species}/{" \
                        "animals_ensembl_release}/{animals_ensembl_release}.json"

header = ['species', 'release', 'version', 'kingdom', 'animals_ensembl_release']


def send_request_and_write_file(url: str, path: str):
    response = requests.get(url, stream=True)
    print(path + ' is open...')
    with open(path, 'wb') as f:
        for chunk in response.iter_content(chunk_size=None):
            if chunk:  # filter out keep-alive new chunks
                f.write(chunk)
    print(path + ' is closed')
    response.close()


def load_ensembl(transcriptomes):
    for species in pd.read_csv(transcriptomes, sep="\t", index_col=False).itertuples():
        species_name = species.species
        if species.kingdom == 'animals':
            url = ANIMAL_URL_TEMPLATE.format(species=species_name)
        else:
            if species.kingdom != 'bacteria':
                url = NON_ANIMAL_BUT_BACTERIA_URL_TEMPLATE.format(kingdom=species.kingdom, species=species_name)
            else:
                url = BACTERIA_URL_TEMPLATE.format(kingdom=species.kingdom, species=species_name,
                                                   animals_ensembl_release=species.animals_ensembl_release)
        path = species_name + '.json'
        send_request_and_write_file(url, path)


if __name__ == "__main__":
    pathfile = "resources/transcriptomes_bacteria.tsv"
    load_ensembl(pathfile)
