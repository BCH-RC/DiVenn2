import argparse
import scanpy as sc
import pandas as pd
import warnings
import numpy as np
import os
from itertools import permutations
import logging
import re

warnings.filterwarnings("ignore")
pd.options.mode.chained_assignment = None
logging.getLogger("anndata").setLevel(logging.ERROR)

def load_h5ad(h5ad_file):
    """ Load .h5ad file and return AnnData object """
    print("\nLoading the .h5ad file... This might take some time.\n")
    try:
        adata = sc.read_h5ad(h5ad_file)
        print("\nMetadata columns in the .h5ad file:\n")
        for i, col in enumerate(adata.obs.columns, 1):
            print(f"{i}. {col}")
        return adata
    except Exception as e:
        print(f"Error loading the file: {e}")
        return None

def parse_comparison_pairs(comparison_str, conditions):
    """ Parse comparison string into a list of tuples """
    conditions = list(conditions)
    if comparison_str.lower() == "all":
        return list(permutations(conditions, 2))  # directed comparisons
    else:
        try:
            pairs = [tuple(pair.split(":")) for pair in comparison_str.split(",")]
            valid_pairs = [(c1, c2) for c1, c2 in pairs if (c1 in conditions and c2 in conditions)]
            invalid_pairs = [pair for pair in pairs if pair not in valid_pairs]
            if invalid_pairs:
                print(f"Warning: Some specified condition pairs do not exist in the dataset: {invalid_pairs}")
            return valid_pairs
        except Exception as e:
            print(f"Error parsing comparison pairs: {e}")
            return []

def _sanitize_key(x: str) -> str:
    """Make a string for anndata uns keys"""
    x = str(x)
    x = x.strip()
    x = re.sub(r"\s+", "_", x)
    x = re.sub(r"[^A-Za-z0-9_.-]", "_", x)
    return x

def read_gene_list(gene_list_file):
    """Read a text file with one gene per line."""
    if gene_list_file is None:
        return None
    if not os.path.exists(gene_list_file):
        raise FileNotFoundError(f"Gene list file does not exist: {gene_list_file}")

    with open(gene_list_file, "r") as f:
        genes = [line.strip() for line in f if line.strip() != ""]
    genes = list(dict.fromkeys(genes))
    return genes

def filter_deg_table_by_gene_list(deg_table, gene_list=None, gene_col="Gene", mode=None, ignore_case=False):
    """Filter DEG dataframe by gene list using keep/remove mode."""
    if gene_list is None or len(gene_list) == 0 or mode is None:
        return deg_table

    if gene_col not in deg_table.columns:
        raise ValueError(f"Column '{gene_col}' not found in DEG table.")

    mode = str(mode).strip().lower()
    if mode not in {"remove", "keep"}:
        raise ValueError("gene_filter_mode must be either 'remove' or 'keep'")

    genes_deg = deg_table[gene_col].astype(str)
    genes_ref = pd.Series(gene_list, dtype=str)

    if ignore_case:
        genes_deg_cmp = genes_deg.str.upper()
        genes_ref_cmp = set(genes_ref.str.upper())
    else:
        genes_deg_cmp = genes_deg
        genes_ref_cmp = set(genes_ref)

    if mode == "remove":
        keep_idx = ~genes_deg_cmp.isin(genes_ref_cmp)
    else:
        keep_idx = genes_deg_cmp.isin(genes_ref_cmp)

    return deg_table.loc[keep_idx].copy()

#def strip_counts_from_adata(adata, remove_raw=True, remove_counts_layer=True, count_layer_names=("counts", "raw_counts")):
#    """Remove raw/count storage from AnnData before writing output."""
#    if remove_raw:
#        try:
#            adata.raw = None
#            print("Removed adata.raw")
#        except Exception as e:
#            print(f"Could not remove adata.raw: {e}")

#    if remove_counts_layer:
#        try:
#            layer_keys = list(adata.layers.keys())
#            print("Existing adata layers:", ", ".join(layer_keys) if len(layer_keys) > 0 else "None")
#            for nm in count_layer_names:
#                if nm in adata.layers:
#                    del adata.layers[nm]
#                    print(f"Removed adata.layers['{nm}']")
#        except Exception as e:
#            print(f"Could not inspect/remove adata layers: {e}")

#    return adata

def DiVenn2_preprocess_seuratobj(adata,cell_type_col,condition_col,logfc_threshold,min_pct,p_val_adj_thd,
                                 comparison_str,method,correction_method,output_h5ad,write_csv=False,
                                 gene_list=None,gene_filter_mode=None,gene_filter_ignore_case=False):
    """
    Perform DE analysis per each group/celltype for DiVenn2.
    - Stores each filtered DEG result in adata.uns under a unique key
    - Stores a catalog of available results in adata.uns['divenn_rank_genes_groups_catalog']
    - Writes a single output .h5ad
    """

    if cell_type_col not in adata.obs.columns or condition_col not in adata.obs.columns:
        raise ValueError("Error: Invalid column names provided.")

    adata_de = adata.copy()

    # check if raw exists and is usable
    has_raw = (adata_de.raw is not None)
    if has_raw:
        try:
            _ = adata_de.raw.X
        except Exception:
            has_raw = False
    print(f"[DiVenn2] rank_genes_groups will use: {'adata.raw.X' if has_raw else 'adata.X'}")

    cell_types = adata_de.obs[cell_type_col].unique().tolist()
    conditions = adata_de.obs[condition_col].unique().tolist()
    comparison_pairs = parse_comparison_pairs(comparison_str, conditions)

    catalog = []
    all_degs = pd.DataFrame()

    for cell_type in cell_types:
        print("--------------------------------------------")
        print(f"Cell group: {cell_type}")
        adata_ct = adata_de[adata_de.obs[cell_type_col] == cell_type].copy()

        for cond1, cond2 in comparison_pairs:
            adata_cond1 = adata_ct[adata_ct.obs[condition_col] == cond1]
            adata_cond2 = adata_ct[adata_ct.obs[condition_col] == cond2]

            if len(adata_cond1) >= 3 and len(adata_cond2) >= 3:
                print(f"{cond1} vs {cond2}")
                adata_pair = adata_ct[adata_ct.obs[condition_col].isin([cond1, cond2])].copy()

                ct_key = _sanitize_key(cell_type)
                c1_key = _sanitize_key(cond1)
                c2_key = _sanitize_key(cond2)
                key = f"rank_genes_groups__ct={ct_key}__{c1_key}_vs_{c2_key}"

                tie_correct = True if method.lower() == "wilcoxon" else False

                sc.tl.rank_genes_groups(adata_pair,
                    groupby=condition_col,
                    groups=[cond1],
                    reference=cond2,
                    method=method,
                    n_genes=None,
                    pts=True,
                    corr_method=correction_method,
                    tie_correct=tie_correct,
                    key_added=key,
                    use_raw=None
                )

                if key in adata_pair.uns:
                    result = sc.get.rank_genes_groups_df(adata_pair, key=key, group=cond1)
                    result.rename(columns={"names": "Gene"}, inplace=True)

                    filtered_degs_df = result[
                        (result["logfoldchanges"].abs() > logfc_threshold) &
                        (result["pct_nz_group"] >= min_pct) &
                        (result["pvals_adj"] < p_val_adj_thd)
                    ].copy()

                    if filtered_degs_df.empty:
                        print("No marker genes found!")
                        continue
                    else:
                        print(f"Number of marker genes before optional gene-list filtering: {len(filtered_degs_df)}")

                    filtered_degs_df["Reg_direct"] = filtered_degs_df["logfoldchanges"].apply(lambda x: "1" if x > 0 else "2")
                    filtered_degs_df["Condition_1"] = cond1
                    filtered_degs_df["Condition_2"] = cond2
                    filtered_degs_df["CellType"] = cell_type
                    filtered_degs_df = filtered_degs_df[["Condition_1", "Condition_2", "CellType", "Gene", "Reg_direct"]]

                    # optional DEG filtering by user-defined gene list
                    n_before_filter = filtered_degs_df.shape[0]
                    filtered_degs_df = filter_deg_table_by_gene_list(
                        deg_table=filtered_degs_df,
                        gene_list=gene_list,
                        gene_col="Gene",
                        mode=gene_filter_mode,
                        ignore_case=gene_filter_ignore_case)
                    n_after_filter = filtered_degs_df.shape[0]

                    if gene_filter_mode is not None and gene_list is not None:
                        print(f"Number of marker genes after gene-list filtering: {n_after_filter} (filtered {n_before_filter - n_after_filter} genes)")

                    if filtered_degs_df.empty:
                        print("No marker genes left after optional gene-list filtering!")
                        continue

                    catalog.append({
                        "key": key,
                        "cell_type": str(cell_type),
                        "cond1": str(cond1),
                        "cond2": str(cond2),
                        "method": method,
                        "groupby": condition_col
                    })

                    adata.uns[key] = {
                        "Gene": filtered_degs_df["Gene"].to_numpy().astype("U"),
                        "Reg_direct": filtered_degs_df["Reg_direct"].to_numpy().astype("U"),
                    }

                    all_degs = pd.concat([all_degs, filtered_degs_df], ignore_index=True)
            else:
                print(f"Not enough cells for comparison between {cond1} and {cond2}.")

    catalog_df = pd.DataFrame(catalog)
    adata.uns["divenn_rank_genes_groups_catalog"] = catalog_df.to_dict(orient="list")

    if write_csv:
        output_csv = os.path.splitext(output_h5ad)[0] + "_divenn2_deg.csv"
        all_degs.to_csv(output_csv, index=False)
        print(f"Saved consolidated DEGs CSV to {output_csv}")

    # remove raw counts / count layers before final output
    #adata = strip_counts_from_adata(
    #    adata=adata,
    #    remove_raw=remove_raw,
    #    remove_counts_layer=remove_counts_layer,
    #    count_layer_names=("counts", "raw_counts")
    #)
    
    print("layers before:", list(adata.layers.keys()))
    print("has raw before:", adata.raw is not None)
    #if "logcounts" in adata.layers:
    #    adata.X = adata.layers["logcounts"].copy()
    #    del adata.layers["logcounts"]

    #if "counts" in adata.layers:
    #    del adata.layers["counts"]
    #adata.raw = None

    #print("layers after:", list(adata.layers.keys()))
    #print("has raw after:", adata.raw is not None)

    adata.write_h5ad(output_h5ad, compression="gzip")
    print(f"Saved h5ad with embedded DE results to {output_h5ad}")

def main():
    parser = argparse.ArgumentParser(description="Create DiVenn2-ready h5ad with embedded DEG results.")
    parser.add_argument("-w", "--workdir", type=str, default=None, help="Working directory")
    parser.add_argument("-i", "--input", type=str, required=True, help="Input .h5ad file")
    parser.add_argument("-c", "--condition", type=str, required=True, help="Condition column in adata.obs")
    parser.add_argument("-g", "--group", type=str, required=True, help="Group column in adata.obs (e.g., cell type)")
    parser.add_argument("-f", "--logfc_thd", type=float, default=1, help="Abs logFC threshold (default: 1)")
    parser.add_argument("-r", "--minpct_thd", type=float, default=0.25, help="Min pct threshold (default: 0.25)")
    parser.add_argument("-v", "--padj_thd", type=float, default=0.05, help="Adj p-value threshold (default: 0.05)")
    parser.add_argument("-x", "--comparisons", type=str, default="All", help="Condition comparisons list: 'All' or 'A:B,A:C' etc.")
    parser.add_argument("-m", "--method", type=str, default="t-test", help="DE method: 't-test', 't-test_overestim_var', 'wilcoxon', 'logreg' (default: t-test)")
    parser.add_argument("-t", "--correction_method", type=str, default="benjamini-hochberg", help="p-value correction method: 'benjamini-hochberg', 'bonferroni' (default: 'benjamini-hochberg')")
    parser.add_argument("-o", "--output", type=str, required=True, help="Output .h5ad file (DiVenn2-ready)")
    parser.add_argument("-s", "--write_csv", action="store_false", help="Write all DEG as CSV file")

    # DEG gene list filtering
    parser.add_argument("-l", "--gene_list_file", type=str, default=None,
                        help="Optional text file with one gene per line for DEG filtering")
    parser.add_argument("-d", "--gene_filter_mode", type=str, default=None,
                        help="Optional DEG filtering mode: 'remove' or 'keep'")
    parser.add_argument("-a", "--gene_filter_ignore_case", action="store_true", default=False,
                        help="Ignore case when filtering DEGs by gene list")


    args = parser.parse_args()

    if args.gene_filter_mode is not None:
        args.gene_filter_mode = args.gene_filter_mode.strip().lower()
        if args.gene_filter_mode not in {"remove", "keep"}:
            raise ValueError("--gene_filter_mode must be either 'remove' or 'keep'")

    gene_list = read_gene_list(args.gene_list_file) if args.gene_list_file is not None else None

    if args.workdir:
        os.makedirs(args.workdir, exist_ok=True)
        os.chdir(args.workdir)

    print("Working directory:", os.getcwd())
    print("Input h5ad file:", args.input)
    print("Condition column:", args.condition)
    print("Group column:", args.group)
    print("Output file:", args.output)
    print("Log fold change threshold:", args.logfc_thd)
    print("Minimum cell percent in either condition:", args.minpct_thd)
    print("Adjusted p-value threshold:", args.padj_thd)
    print("Condition comparisons:", args.comparisons)
    print("DEG method:", args.method)
    print("Correction method:", args.correction_method)
    print("Store CSV file:", args.write_csv)
    print("Gene list file:", args.gene_list_file)
    print("Gene filter mode:", args.gene_filter_mode)
    print("Gene filter ignore case:", args.gene_filter_ignore_case)

    adata = load_h5ad(args.input)
    if adata is None:
        raise SystemExit(1)

    DiVenn2_preprocess_seuratobj(
        adata=adata,
        cell_type_col=args.group,
        condition_col=args.condition,
        logfc_threshold=args.logfc_thd,
        min_pct=args.minpct_thd,
        p_val_adj_thd=args.padj_thd,
        comparison_str=args.comparisons,
        method=args.method,
        correction_method=args.correction_method,
        output_h5ad=args.output,
        write_csv=args.write_csv,
        gene_list=gene_list,
        gene_filter_mode=args.gene_filter_mode,
        gene_filter_ignore_case=args.gene_filter_ignore_case
    )

if __name__ == "__main__":
    main()
    
