import argparse
import scanpy as sc
import pandas as pd
import warnings
import numpy as np
import os
from itertools import permutations
import ast

warnings.filterwarnings("ignore")
pd.options.mode.chained_assignment = None

# Suppress AnnData implicit view warnings
import logging
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

def DiVenn2_preprocess_seuratobj(adata, cell_type_col, condition_col, logfc_threshold, min_pct, p_val_adj_thd, output_file, comparison_pairs):
    """ Perform differential expression analysis per cell type for DiVenn2 """
    if cell_type_col not in adata.obs.columns or condition_col not in adata.obs.columns:
        print("Error: Invalid column names provided.")
        return

    cell_types = adata.obs[cell_type_col].unique()
    conditions = adata.obs[condition_col].unique()
    all_degs = pd.DataFrame()
    adata = adata.raw.to_adata()

    # Determine condition pairs to use
    if comparison_pairs:
        condition_pairs = comparison_pairs
    else:
        condition_pairs = list(permutations(conditions, 2))

    for cell_type in cell_types:
        adata_subset = adata[adata.obs[cell_type_col] == cell_type].copy()
        for cond1, cond2 in condition_pairs:
            adata_cond1 = adata_subset[adata_subset.obs[condition_col] == cond1]
            adata_cond2 = adata_subset[adata_subset.obs[condition_col] == cond2]
            
            if len(adata_cond1) >= 3 and len(adata_cond2) >= 3:
                print(f"Comparing {cond1} vs {cond2} for cell type {cell_type}...")
                adata_subset_subset = adata_subset[adata_subset.obs[condition_col].isin([cond1, cond2])].copy()
                sc.tl.rank_genes_groups(adata_subset_subset, groupby=condition_col, groups=[cond1], reference=cond2, 
                                        method='wilcoxon', n_genes=None, pts=True, corr_method='bonferroni', tie_correct=True)
                
                if 'rank_genes_groups' in adata_subset_subset.uns:
                    result = adata_subset_subset.uns['rank_genes_groups']
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
    
    all_degs.to_csv(output_file, index=False)
    print(f"Saved consolidated DEGs file to {output_file}")

def main():
    parser = argparse.ArgumentParser(description="Perform preprocessed DEG csv file for DiVenn2 for given .h5ad file.")
    parser.add_argument("-w", "--workdir", type=str, default=None, help="Working directory")
    parser.add_argument("-i", "--input", type=str, required=True, help="H5ad file input file")
    parser.add_argument("-c", "--condition", type=str, required=True, help="Column name for sample condition (disease/normal condition)")
    parser.add_argument("-g", "--group", type=str, required=True, help="Column name for cell group (cell type)")
    parser.add_argument("-o", "--output", type=str, default="DiVenn_input.csv", help="Preprocessed DEG output File")
    parser.add_argument("-fc", "--logfc_threshold", type=float, default=0.2, help="Log fold change threshold (default: 0.2)")
    parser.add_argument("-pct", "--min_pct", type=float, default=0.01, help="Minimum cell percent in each condition threshold (default: 0.01)")
    parser.add_argument("-p", "--p_val_adj_thd", type=float, default=0.05, help="Adjusted p-value threshold (default: 0.05)")
    parser.add_argument("-comp", "--comparison", type=str, default=None, help="Specific condition pairs for DEG analysis (e.g., ""[(\'A\', \'B\'), (\'A\', \'C\')]""")
    
    args = parser.parse_args()

    if args.workdir:
        os.makedirs(args.workdir, exist_ok=True)
        os.chdir(args.workdir)
    
    comparison_pairs = ast.literal_eval(args.comparison) if args.comparison else None
    
    adata = load_h5ad(args.input)
    if adata is None:
        exit(1)
    
    DiVenn2_preprocess_seuratobj(adata, args.group, args.condition, args.logfc_threshold, args.min_pct, args.p_val_adj_thd, args.output, comparison_pairs)
    
if __name__ == "__main__":
    main()
