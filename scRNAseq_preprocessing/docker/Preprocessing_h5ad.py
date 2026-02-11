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

# counts_layer="counts_RNA"
def DiVenn2_preprocess_seuratobj(adata,cell_type_col,condition_col,logfc_threshold,min_pct,p_val_adj_thd,comparison_str,method,output_h5ad,write_csv=False,output_csv=None):
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

    # Restore raw counts into X (so rank_genes_groups uses counts, not scaled/log values)
    #counts_layer = "counts_RNA"
    #adata.X = adata.layers[counts_layer].copy()
    # store raw counts
    #adata.raw = adata.copy()
    adata = adata.raw.to_adata()

    cell_types = adata.obs[cell_type_col].unique().tolist()
    conditions = adata.obs[condition_col].unique().tolist()
    comparison_pairs = parse_comparison_pairs(comparison_str, conditions)

    catalog = []          # list of dicts describing each stored DE result
    all_degs = pd.DataFrame()    # filtered DEG dataframe

    for cell_type in cell_types:
        adata_ct = adata[adata.obs[cell_type_col] == cell_type].copy()

        for cond1, cond2 in comparison_pairs:
            adata_cond1 = adata_ct[adata_ct.obs[condition_col] == cond1]
            adata_cond2 = adata_ct[adata_ct.obs[condition_col] == cond2]

            if len(adata_cond1) >= 3 and len(adata_cond2) >= 3:
                print(f"Comparing {cond1} vs {cond2} for cell type {cell_type}...")
                adata_pair = adata_ct[adata_ct.obs[condition_col].isin([cond1, cond2])].copy()
                # Make a stable unique key for this result
                ct_key = _sanitize_key(cell_type)
                c1_key = _sanitize_key(cond1)
                c2_key = _sanitize_key(cond2)
                key = f"rank_genes_groups__ct={ct_key}__{c1_key}_vs_{c2_key}"
                print(key)

                # Run DE and store under key 
                sc.tl.rank_genes_groups(adata_pair, groupby=condition_col, groups=[cond1], reference=cond2,method=method, n_genes=None, pts=True, corr_method='bonferroni', tie_correct=True,key_added=key)

                # Copy result into main adata.uns 
                adata.uns[key] = adata_pair.uns[key]

                # add the key to catalog list
                catalog.append({
                    "key": key,
                    "cell_type": str(cell_type),
                    "cond1": str(cond1),
                    "cond2": str(cond2),
                    "n_cond1": len(adata_cond1),
                    "n_cond2": len(adata_cond2),
                    "method": method,
                    "groupby": condition_col})

                if key in adata_pair.uns:
                    result = adata_pair.uns[key]
                    groups = result['names'].dtype.names
                    degs_df = pd.DataFrame({
                        "Gene": result['names'][groups[0]],
                        "logfoldchanges": result['logfoldchanges'][groups[0]],
                        "pvals_adj": result['pvals_adj'][groups[0]],
                        "pts1": result['pts'][cond1],  
                        "pts2": result['pts'][cond2]        
                    })
                    
                    filtered_degs_df = degs_df[(degs_df["logfoldchanges"].abs() > logfc_threshold) & 
                                               ((degs_df["pts1"] >= min_pct) | (degs_df["pts2"] >= min_pct)) &  
                                               (degs_df["pvals_adj"] < p_val_adj_thd)]
                    
                    filtered_degs_df["Reg_direct"] = filtered_degs_df["logfoldchanges"].apply(lambda x: '1' if x > 0 else '2')
                    filtered_degs_df["Condition_1"] = cond1
                    filtered_degs_df["Condition_2"] = cond2
                    filtered_degs_df["CellType"] = cell_type  
                    filtered_degs_df = filtered_degs_df[["Condition_1", "Condition_2", "CellType", "Gene", "Reg_direct"]]
                    all_degs = pd.concat([all_degs, filtered_degs_df], ignore_index=True)
                else:
                    print(f"No DEGs found for {cell_type} ({cond1} vs {cond2}).")
            else:
                print(f"Not enough cells for comparison between {cond1} and {cond2} in {cell_type}.")
    

    # Store into adata.uns 
    #adata.uns["divenn_rank_genes_groups_catalog"] = catalog
    adata.uns["divenn_degs"] = all_degs.to_dict(orient="list")

    # Write CSV
    if write_csv:
        if output_csv is None:
            output_csv = os.path.splitext(output_h5ad)[0] + "_divenn2_deg.csv"
        all_degs.to_csv(output_csv, index=False)
        print(f"Saved consolidated DEGs CSV to {output_csv}")

    # Write output h5ad
    adata.write_h5ad(output_h5ad)
    print(f"Saved h5ad with embedded DE results to {output_h5ad}")

def main():
    parser = argparse.ArgumentParser(description="Create DiVenn2-ready h5ad with embedded DEG results.")
    parser.add_argument("-w", "--workdir", type=str, default=None, help="Working directory")
    parser.add_argument("-i", "--input", type=str, required=True, help="Input .h5ad file")
    parser.add_argument("-c", "--condition", type=str, required=True, help="Condition column in adata.obs")
    parser.add_argument("-g", "--group", type=str, required=True, help="Group column in adata.obs (e.g., cell type)")
    parser.add_argument("-f", "--logfc_thd", type=float, default=0.2, help="Abs logFC threshold (default: 0.2)")
    parser.add_argument("-r", "--minpct_thd", type=float, default=0.01, help="Min pct threshold (default: 0.01)")
    parser.add_argument("-v", "--padj_thd", type=float, default=0.05, help="Adj p-value threshold (default: 0.05)")
    parser.add_argument("-x", "--comparisons", type=str, default="All",help="Condition comparisons list: 'All' or 'A:B,A:C' etc.")
    parser.add_argument("-m", "--method", type=str, default="wilcoxon",help="DE method: 't-test', 't-test_overestim_var', 'wilcoxon', 'logreg' (default: wilcoxon)")
    parser.add_argument("-o", "--output", type=str, required=True, help="Output .h5ad file (DiVenn2-ready)")
    parser.add_argument("--write_csv", action="store_true", help="Write all DEG as CSV file")
    parser.add_argument("--output_csv", type=str, default=None, help="Path for optional DEG as CSV file")

    args = parser.parse_args()

    if args.workdir:
        os.makedirs(args.workdir, exist_ok=True)
        os.chdir(args.workdir)

    adata = load_h5ad(args.input)
    if adata is None:
        raise SystemExit(1)

    DiVenn2_preprocess_seuratobj(adata,args.group,args.condition,args.logfc_thd,args.minpct_thd,args.padj_thd,args.comparisons,args.method,args.output,args.write_csv,args.output_csv)

if __name__ == "__main__":
    main()
