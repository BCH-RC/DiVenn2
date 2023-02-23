from pathlib import Path
import logging
import sys

import pandas as pd

ANIMAL_URL_TEMPLATE = "http://ftp.ensembl.org/pub/release-{release}/mysql/{species}_core_{release}_{version}/{" \
                      "table}.txt.gz"
NON_ANIMAL_URL_TEMPLATE = "http://ftp.ebi.ac.uk/ensemblgenomes/pub/current/{kingdom}/mysql/{species}_core_{" \
                          "release}_{animals_ensembl_release}_{version}/{table}.txt.gz"

PATHFILE = "resources/transcriptomes_without_bacteria_1.tsv"


def ensembl_table(species, table, names, dtype=None):
    kingdoms_list = [Kingdom.ANIMALS, Kingdom.PLANTS, Kingdom.BACTERIA,
                     Kingdom.METAZOA, Kingdom.PROTISTS, Kingdom.FUNGI]

    if not species.kingdom:
        logging.exception(
            f"Kingdom name missing. Acceptable kingdom names: {*kingdoms_list,}")
        sys.exit()

    if species.kingdom not in kingdoms_list:
        logging.exception(
            f"Incorrect kingdom name {species.kingdom}. Acceptable kingdom names: {*kingdoms_list,}")
        sys.exit()

    if species.kingdom == Kingdom.ANIMALS:
        url = ANIMAL_URL_TEMPLATE.format(release=species.release, version=species.version, species=species.species,
                                         table=table)
    else:
        url = NON_ANIMAL_URL_TEMPLATE.format(kingdom=species.kingdom, release=species.release, version=species.version,
                                             species=species.species,
                                             animals_ensembl_release=species.animals_ensembl_release, table=table)

    logging.debug(f"Loading {url}")
    return pd.read_csv(url, sep="\t", index_col=False, na_values="\\N", names=names, dtype=dtype)


class Kingdom:
    ANIMALS = "animals"
    BACTERIA = "bacteria"
    METAZOA = "metazoa"
    PLANTS = "plants"
    PROTISTS = "protists"
    FUNGI = "fungi"


def ensembl_genes(species):
    names = [
        "ensembl_gene_id",
        "gene_biotype",
        "analysis_id",
        "seq_region_id",
        "seq_region_start",
        "seq_region_end",
        "seq_region_strand",
        "display_xref_id",
        "source",
        "description",
        "is_current",
        "canonical_transcript_id",
        "gene_id",
        "version",
        "created_date",
        "modified_date",
    ]
    return ensembl_table(species, "gene", names)


def load_ensembl(transcriptomes):
    gene_ids = {}
    for species in pd.read_csv(transcriptomes, sep="\t").itertuples():
        genes = ensembl_genes(species)
        gene_ids_list = genes["gene_id"].loc[genes["gene_biotype"] == 'protein_coding']
        gene_ids[species.species] = gene_ids_list

    output_file = f"{Path(transcriptomes).stem}_genes_list.txt"
    with open(output_file, 'w') as f:
        f.write(f"Species\tGenes_list\n")
        for species, ids_list in gene_ids.items():
            ids_with_species_name = {"id": ids_list, "species": [species for _ in range(0, len(ids_list))]}
            df = pd.DataFrame.from_dict(ids_with_species_name)
            df.to_csv(path_or_buf=species + '.csv', index=False)


if __name__ == "__main__":
    load_ensembl(PATHFILE)
