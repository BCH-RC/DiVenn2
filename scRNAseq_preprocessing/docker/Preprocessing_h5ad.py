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
        return list(permutations(conditions, 2)) # permutations gives A:B and B:A (directed comparisons)
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

def DiVenn2_preprocess_seuratobj(adata,cell_type_col,condition_col,logfc_threshold,min_pct,p_val_adj_thd,comparison_str,method,correction_method,output_h5ad,write_csv=False):
    """
    Perform DE analysis per each group/celltype for DiVenn2.
    - Stores each rank_genes_groups result in adata.uns under a unique key
    - Stores a catalog of available results in adata.uns['divenn_rank_genes_groups_catalog']
    - Stores the consolidated filtered DEG edge table in adata.uns['divenn_degs']
    - Writes a single output .h5ad
    """

    if cell_type_col not in adata.obs.columns or condition_col not in adata.obs.columns:
        raise ValueError("Error: Invalid column names provided.")

    #if counts_layer not in adata.layers.keys():
    #    raise ValueError(f"Error: counts layer '{counts_layer}' not found. Available layers: {list(adata.layers.keys())}")

    #adata.X = adata.layers[counts_layer].copy()
    adata_de = adata.copy()
    #adata_de.X = adata_de.layers[counts_layer]
    # store raw counts
    #adata.raw = adata.copy()
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

    catalog = []          # list of dicts describing each stored DE result
    all_degs = pd.DataFrame()    # filtered DEG dataframe

    for cell_type in cell_types:
        print("--------------------------------------------")
        print(f"Cell group: {cell_type}")
        adata_ct = adata_de[adata_de.obs[cell_type_col] == cell_type].copy()

        for cond1, cond2 in comparison_pairs:
            adata_cond1 = adata_ct[adata_ct.obs[condition_col] == cond1]
            adata_cond2 = adata_ct[adata_ct.obs[condition_col] == cond2]

            if len(adata_cond1) >= 3 and len(adata_cond2) >= 3:
                #print(f"Comparing {cond1} vs {cond2} for cell type {cell_type}...")
                print(f"{cond1} vs {cond2}")
                adata_pair = adata_ct[adata_ct.obs[condition_col].isin([cond1, cond2])].copy()
                # Make a stable unique key for this result
                ct_key = _sanitize_key(cell_type)
                c1_key = _sanitize_key(cond1)
                c2_key = _sanitize_key(cond2)
                key = f"rank_genes_groups__ct={ct_key}__{c1_key}_vs_{c2_key}"
                #print(key)

                # Run DE and store under key 
                tie_correct = True if method.lower() == "wilcoxon" else False # set tie_correct to True only if using wilcox method
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
                    use_raw=None)
                df = sc.get.rank_genes_groups_df(adata_pair, key=key, group=cond1)
                #df_down = df[(df["logfoldchanges"] < 0)]
                #print(df["logfoldchanges"].min(), df["logfoldchanges"].max())
                #min_fc=df["logfoldchanges"].min()
                #sc.tl.filter_rank_genes_groups(adata_pair, 
                #    key=key, 
                #    groupby=condition_col, 
                #    use_raw=None, 
                #    key_added='rank_genes_groups_filtered', 
                #    min_in_group_fraction=0.1, 
                #    min_fold_change=min_fc, 
                #    max_out_group_fraction=1, 
                #    compare_abs=False)
                #filtered_degs = sc.get.rank_genes_groups_df(adata_pair,key='rank_genes_groups_filtered',group=None)
                # Copy result into main adata.uns 
                #adata.uns[key] = adata_pair.uns[key]

                if key in adata_pair.uns:
                    #result = adata_pair.uns[key]
                    #groups = result['names'].dtype.names
                    #degs_df = pd.DataFrame({
                    #    "Gene": result['names'][groups[0]],
                    #    "logfoldchanges": result['logfoldchanges'][groups[0]],
                    #    "pvals_adj": result['pvals_adj'][groups[0]],
                    #    "pts1": result['pts'][cond1],  
                    #    "pts2": result['pts'][cond2]        
                    #})
                    
                    #filtered_degs_df = degs_df[(degs_df["logfoldchanges"].abs() > logfc_threshold) & 
                    #                           ((degs_df["pts1"] > min_pct) | (degs_df["pts2"] > min_pct)) &  
                    #                           (degs_df["pvals_adj"] < p_val_adj_thd)]
                    result = sc.get.rank_genes_groups_df(adata_pair, key=key, group=cond1)
                    result.rename(columns={"names": "Gene"}, inplace=True)
                    #result = sc.get.rank_genes_groups_df(adata_pair, group = cond1, key=key, pval_cutoff=0.05, log2fc_min=None, log2fc_max=None)
                    filtered_degs_df = result[(result["logfoldchanges"].abs() > logfc_threshold) & 
                                               (result["pct_nz_group"] >= min_pct) &  
                                               (result["pvals_adj"] < p_val_adj_thd)]
                    
                    # check if there is no gene pass the filtring
                    #if filtered_degs_df.shape[0] < 3:
                    if filtered_degs_df.empty:
                        print("No marker genes found!")
                        continue
                    else:
                        print(f"Number of marker genes:: {len(filtered_degs_df)}")

                    filtered_degs_df["Reg_direct"] = filtered_degs_df["logfoldchanges"].apply(lambda x: '1' if x > 0 else '2')
                    filtered_degs_df["Condition_1"] = cond1
                    filtered_degs_df["Condition_2"] = cond2
                    filtered_degs_df["CellType"] = cell_type  
                    filtered_degs_df = filtered_degs_df[["Condition_1", "Condition_2", "CellType", "Gene", "Reg_direct"]]
                    #adata.uns[key] = filtered_degs_df.to_dict(orient="list")
                    # add the key to catalog list
                    catalog.append({
                        "key": key,
                        "cell_type": str(cell_type),
                        "cond1": str(cond1),
                        "cond2": str(cond2),
                        "method": method,
                        "groupby": condition_col})

                    adata.uns[key] = {
                        "Gene": filtered_degs_df["Gene"].to_numpy().astype("U"),
                        "Reg_direct": filtered_degs_df["Reg_direct"].to_numpy().astype("U"),
                        #"Condition_1": str(cond1),
                        #"Condition_2": str(cond2),
                        #"CellType": str(cell_type)
                        }
                    all_degs = pd.concat([all_degs, filtered_degs_df], ignore_index=True)
            else:
                print(f"Not enough cells for comparison between {cond1} and {cond2}.")
    

    # Store into adata.uns 
    catalog_df = pd.DataFrame(catalog)
    adata.uns["divenn_rank_genes_groups_catalog"] = catalog_df.to_dict(orient="list")
    #adata.uns["divenn_rank_genes_groups_catalog"] = catalog
    #adata.uns["divenn_degs"] = all_degs.to_dict(orient="list")

    # Write CSV
    if write_csv:
        output_csv = os.path.splitext(output_h5ad)[0] + "_divenn2_deg.csv"
        all_degs.to_csv(output_csv, index=False)
        print(f"Saved consolidated DEGs CSV to {output_csv}")

    # Write output h5ad
    adata.write_h5ad(output_h5ad,compression="gzip")
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
    parser.add_argument("-x", "--comparisons", type=str, default="All",help="Condition comparisons list: 'All' or 'A:B,A:C' etc.")
    parser.add_argument("-m", "--method", type=str, default="t-test",help="DE method: 't-test', 't-test_overestim_var', 'wilcoxon', 'logreg' (default: t-test)")
    parser.add_argument("-t", "--correction_method", type=str, default="benjamini-hochberg",help="p-value correction method: 'benjamini-hochberg', 'bonferroni' (default: 'benjamini-hochberg')")
    parser.add_argument("-o", "--output", type=str, required=True, help="Output .h5ad file (DiVenn2-ready)")
    parser.add_argument("-s","--write_csv", action="store_false", help="Write all DEG as CSV file")

    args = parser.parse_args()

    if args.workdir:
        os.makedirs(args.workdir, exist_ok=True)
        os.chdir(args.workdir)

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
        write_csv=args.write_csv
    )

if __name__ == "__main__":
    main()
