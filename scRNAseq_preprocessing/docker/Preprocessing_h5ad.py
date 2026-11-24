"""Prepare a DiVenn2-compatible AnnData file with single-cell or pseudobulk DEGs."""

import argparse
import logging
import os
import re
from itertools import permutations

import numpy as np
import pandas as pd
import scanpy as sc
import scipy.sparse as sp

logging.getLogger("anndata").setLevel(logging.ERROR)

DIVENN_COLUMNS = ["Condition_1", "Condition_2", "CellType", "Gene", "Reg_direct"]


def load_h5ad(h5ad_file):
    """Load a .h5ad file and display available observation annotations."""
    print("\nLoading the .h5ad file...\n")
    try:
        adata = sc.read_h5ad(h5ad_file)
    except Exception as exc:
        raise RuntimeError(f"Could not load .h5ad file '{h5ad_file}': {exc}") from exc
    print("Metadata columns in the .h5ad file:")
    for i, col in enumerate(adata.obs.columns, 1):
        print(f"{i}. {col}")
    return adata


def parse_comparison_pairs(comparison_str, conditions):
    """Return directed comparison pairs."""
    conditions = [str(x) for x in conditions]
    if comparison_str.strip().lower() == "all":
        return list(permutations(conditions, 2))
    pairs = []
    for token in comparison_str.split(","):
        pieces = [x.strip() for x in token.split(":")]
        if len(pieces) != 2 or pieces[0] == pieces[1] or any(x not in conditions for x in pieces):
            print(f"[DiVenn2] Skipping invalid comparison: {token!r}")
            continue
        pair = tuple(pieces)
        if pair not in pairs:
            pairs.append(pair)
    return pairs


def _sanitize_key(value):
    return re.sub(r"[^A-Za-z0-9_.-]", "_", re.sub(r"\s+", "_", str(value).strip()))


def _result_key(cell_type, cond1, cond2):
    return (
        f"rank_genes_groups__ct={_sanitize_key(cell_type)}__"
        f"{_sanitize_key(cond1)}_vs_{_sanitize_key(cond2)}"
    )


def read_gene_list(gene_list_file):
    if gene_list_file is None:
        return None
    with open(gene_list_file, encoding="utf-8") as handle:
        return list(dict.fromkeys(line.strip() for line in handle if line.strip()))


def filter_deg_table_by_gene_list(deg_table, gene_list=None, gene_col="Gene", mode=None, ignore_case=False):
    if gene_list is None or mode is None:
        return deg_table
    if mode not in {"remove", "keep"}:
        raise ValueError("gene_filter_mode must be 'remove' or 'keep'")
    if gene_col not in deg_table.columns:
        raise ValueError(f"Column '{gene_col}' not found in DEG table")
    genes = deg_table[gene_col].astype(str)
    reference = set(map(str, gene_list))
    if ignore_case:
        genes = genes.str.upper()
        reference = {x.upper() for x in reference}
    selected = genes.isin(reference)
    return deg_table.loc[selected if mode == "keep" else ~selected].copy()


def validate_deg_inputs(adata, cell_type_col, condition_col, deg_mode, sample_col=None, min_cells_per_sample=3, min_replicates=2):
    required = [cell_type_col, condition_col]
    if deg_mode == "pseudobulk":
        if not sample_col:
            raise ValueError("Pseudobulk requires --sample_col (biological replicate ID).")
        required.append(sample_col)
        if min_cells_per_sample < 1 or min_replicates < 2:
            raise ValueError("min_cells_per_sample must be >= 1 and min_replicates >= 2")
    missing = [col for col in required if col not in adata.obs]
    if missing:
        raise ValueError(f"Missing adata.obs column(s): {missing}")
    if adata.obs[required].isna().any().any():
        raise ValueError(f"Missing values found in required adata.obs columns: {required}")
    for col in required:
        if (adata.obs[col].astype(str).str.strip() == "").any():
            raise ValueError(f"Empty labels found in adata.obs['{col}']")
    if deg_mode == "pseudobulk":
        per_sample = (
            adata.obs[[sample_col, condition_col]]
            .astype(str)
            .drop_duplicates()
            .groupby(sample_col)[condition_col]
            .nunique())
        if (per_sample > 1).any():
            bad = per_sample.index[per_sample > 1].tolist()[:5]
            raise ValueError(
                f"Biological sample ID maps to multiple conditions: {bad}. "
                "Use a globally unique biological sample ID."
            )
        if not adata.var_names.is_unique:
            raise ValueError("Pseudobulk requires unique gene names in adata.var_names")


def _get_pseudobulk_counts(adata, counts_layer="counts"):
    """Select raw integer counts."""
    if counts_layer == "X":
        matrix = adata.X
        genes = adata.var_names
    elif counts_layer == "raw":
        if adata.raw is None:
            raise ValueError("--counts_layer raw requested but adata.raw is missing")
        matrix = adata.raw.X
        genes = adata.raw.var_names
    elif counts_layer in adata.layers:
        matrix = adata.layers[counts_layer]
        genes = adata.var_names
    else:
        raise ValueError(
            f"Counts source '{counts_layer}' does not exist. Available layers: "
            f"{list(adata.layers.keys())}; use --counts_layer X or raw "
            "only if that source contains original integer UMI counts."
        )
    if not pd.Index(genes).is_unique:
        raise ValueError("Count source has duplicate gene names")
    values = matrix.data if sp.issparse(matrix) else np.asarray(matrix).ravel()
    if (
        not np.issubdtype(values.dtype, np.number)
        or not np.all(np.isfinite(values))
        or np.any(values < 0)
        or not np.all(np.abs(values - np.rint(values)) < 1e-8)
    ):
        raise ValueError(
            "Pseudobulk requires finite, nonnegative, integer raw counts. "
            "Do not use log-normalized or scaled expression."
        )
    return matrix, pd.Index(genes).astype(str)


def aggregate_pseudobulk_counts(adata, cell_type_col, condition_col, sample_col, counts_layer="counts", min_cells_per_sample=3):
    """Sum raw UMI counts by biological sample x cell type."""
    matrix, genes = _get_pseudobulk_counts(adata, counts_layer)
    obs = adata.obs[[cell_type_col, condition_col, sample_col]].astype(str)
    result = {}
    for cell_type in obs[cell_type_col].unique():
        per_ct = obs[cell_type_col] == cell_type
        rows, sample_ids, conditions, cell_counts = [], [], [], []
        for sample in obs.loc[per_ct, sample_col].unique():
            mask = (per_ct & (obs[sample_col] == sample)).to_numpy()
            n_cells = int(mask.sum())
            if n_cells < min_cells_per_sample:
                print(
                    f"[Pseudobulk] Skipping {cell_type}/{sample}: "
                    f"{n_cells} cells < {min_cells_per_sample}"
                )
                continue
            part = matrix[mask, :]
            counts = np.asarray(part.sum(axis=0)).ravel()
            if not np.all(counts <= np.iinfo(np.int64).max):
                raise OverflowError("Aggregated counts exceed int64 range")
            rows.append(np.rint(counts).astype(np.int64))
            sample_ids.append(sample)
            condition = obs.loc[mask, condition_col].iloc[0]
            conditions.append(condition)
            cell_counts.append(n_cells)
        if not rows:
            continue
        count_df = pd.DataFrame(rows, index=pd.Index(sample_ids, name="sample"), columns=genes)
        meta_df = pd.DataFrame({"condition": conditions, "n_cells": cell_counts}, index=count_df.index)
        result[str(cell_type)] = (count_df, meta_df)
    return result


def run_single_cell_deg(adata_ct, condition_col, cond1, cond2, method, correction_method, min_pct, max_pct):
    pair = adata_ct[adata_ct.obs[condition_col].astype(str).isin([cond1, cond2])].copy()
    pair.obs[condition_col] = pair.obs[condition_col].astype(str).astype("category")
    key = "_divenn_sc_unfiltered"
    filtered_key = "_divenn_sc_filtered"
    sc.tl.rank_genes_groups(pair,
        groupby=condition_col,
        groups=[cond1],
        reference=cond2,
        method=method,
        n_genes=None,
        pts=True,
        corr_method=correction_method,
        tie_correct=(method.lower() == "wilcoxon"),
        key_added=key,
        use_raw=None,
    )
    lfc = pair.uns[key]["logfoldchanges"][cond1]
    sc.tl.filter_rank_genes_groups(
        pair,
        key=key,
        groupby=condition_col,
        use_raw=None,
        key_added=filtered_key,
        min_in_group_fraction=min_pct,
        min_fold_change=float(np.nanmin(lfc)) - 1,
        max_out_group_fraction=max_pct,
        compare_abs=False,
    )
    df = sc.get.rank_genes_groups_df(pair, key=filtered_key, group=cond1)
    return df.rename(
        columns={
            "names": "Gene",
            "logfoldchanges": "log2FoldChange",
            "pvals_adj": "padj",
            "pvals": "pvalue",
            "pct_nz_group": "pct_1",
            "pct_nz_reference": "pct_2",
        }
    )


def run_pseudobulk_deg(counts, metadata, cond1, cond2, min_replicates=2, correction_method="benjamini-hochberg", n_cpus=1):
    """Fit PyDESeq2 negative-binomial model using PyDESeq2 parameters."""
    if correction_method != "benjamini-hochberg":
        raise ValueError("Pseudobulk currently supports only Benjamini-Hochberg correction")
    mask = metadata["condition"].isin([cond1, cond2])
    counts = counts.loc[mask].copy()
    metadata = metadata.loc[mask, ["condition"]].copy()
    n1 = int((metadata["condition"] == cond1).sum())
    n2 = int((metadata["condition"] == cond2).sum())
    if min(n1, n2) < min_replicates:
        print(
            f"[Pseudobulk] Skipping {cond1} vs {cond2}: {n1} vs {n2} "
            f"biological replicates (minimum {min_replicates} each)"
        )
        return None, n1, n2
    counts = counts.loc[:, counts.sum(axis=0) > 0]
    if counts.shape[1] < 2 or (counts.sum(axis=1) == 0).any():
        print(f"[Pseudobulk] Skipping {cond1} vs {cond2}: insufficient nonzero counts")
        return None, n1, n2
    try:
        from pydeseq2.dds import DeseqDataSet
        from pydeseq2.ds import DeseqStats
    except ImportError as exc:
        raise ImportError(
            "Pseudobulk requires PyDESeq2 >= 0.5. "
            "Install with: pip install 'pydeseq2>=0.5,<0.6'"
        ) from exc

    metadata["condition"] = pd.Categorical(metadata["condition"], categories=[cond2, cond1])

    # Run DeseqDataSet and DeseqStats.
    dds = DeseqDataSet(
        counts=counts,
        metadata=metadata,
        design="~condition",
        n_cpus=n_cpus,
        quiet=True,
    )
    dds.deseq2()

    stats = DeseqStats(
        dds,
        contrast=["condition", cond1, cond2],
        n_cpus=n_cpus,
        quiet=True,
    )
    stats.summary()

    result = stats.results_df.copy()
    result.insert(0, "Gene", result.index.astype(str))
    return result.reset_index(drop=True), n1, n2


def format_deg_results(result, cell_type, cond1, cond2, logfc_threshold, p_val_adj_thd, gene_list, gene_filter_mode, gene_filter_ignore_case):
    """Apply significance thresholds and optional gene-list filters."""
    required = {"Gene", "log2FoldChange", "padj"}
    if not required.issubset(result.columns):
        raise ValueError(f"Missing columns from DEG result: {required - set(result.columns)}")
    result = result.copy()
    for column in ["log2FoldChange", "padj"]:
        result[column] = pd.to_numeric(result[column], errors="coerce")

    selected = (
        np.isfinite(result["log2FoldChange"])
        & np.isfinite(result["padj"])
        & (result["log2FoldChange"].abs() > logfc_threshold)
        & (result["padj"] < p_val_adj_thd)
    )

    result = result.loc[selected].copy()
    result["Reg_direct"] = np.where(result["log2FoldChange"] > 0, "1", "2")
    result["Condition_1"] = str(cond1)
    result["Condition_2"] = str(cond2)
    result["CellType"] = str(cell_type)
    result = filter_deg_table_by_gene_list(
        result,
        gene_list=gene_list,
        gene_col="Gene",
        mode=gene_filter_mode,
        ignore_case=gene_filter_ignore_case,
    )
    return result


def store_divenn_results(adata, catalog, key, result, cell_type, cond1, cond2, method, condition_col, deg_mode, n1, n2, counts_layer=None):
    if key in adata.uns:
        raise ValueError(f"Duplicate DiVenn2 output key: {key}; check label collisions")
    adata.uns[key] = {
        "Gene": result["Gene"].astype(str).to_numpy(dtype="U"),
        "Reg_direct": result["Reg_direct"].astype(str).to_numpy(dtype="U"),
    }
    catalog.append(
        {
            "key": key,
            "cell_type": str(cell_type),
            "cond1": str(cond1),
            "cond2": str(cond2),
            "method": str(method),
            "groupby": condition_col,
            "deg_mode": deg_mode,
            "n_replicates_cond1": int(n1),
            "n_replicates_cond2": int(n2),
            "counts_source": str(counts_layer or "scanpy_default"),
        }
    )


def DiVenn2_preprocess_seuratobj(adata, cell_type_col, condition_col, logfc_threshold,
                                 min_pct, max_pct, p_val_adj_thd, comparison_str, method,
                                 correction_method, output_h5ad, write_csv=False,
                                 gene_list=None, gene_filter_mode=None,
                                 gene_filter_ignore_case=False, deg_mode="single_cell",
                                 sample_col=None, counts_layer="counts",
                                 min_cells_per_sample=3, min_replicates=2,
                                 n_cpus=1, write_stats_csv=False):
    """Run single-cell or pseudobulk DEG analysis and save results in DiVenn2 format."""
    if deg_mode not in {"single_cell", "pseudobulk"}:
        raise ValueError("deg_mode must be 'single_cell' or 'pseudobulk'")
    if not (0 <= min_pct <= 1 and 0 <= max_pct <= 1):
        raise ValueError("min_pct and max_pct must lie between 0 and 1")
    if logfc_threshold < 0 or not (0 < p_val_adj_thd <= 1):
        raise ValueError("Invalid logFC or adjusted p-value threshold")
    if deg_mode == "pseudobulk" and correction_method != "benjamini-hochberg":
        raise ValueError("Pseudobulk supports --correction_method benjamini-hochberg only")

    validate_deg_inputs(
        adata, cell_type_col, condition_col, deg_mode,
        sample_col, min_cells_per_sample, min_replicates,
    )
    conditions = adata.obs[condition_col].astype(str).unique().tolist()
    comparisons = parse_comparison_pairs(comparison_str, conditions)
    if not comparisons:
        raise ValueError("No valid condition comparisons were selected")

    # Clone input AnnData so original expression matrices remain untouched.
    out = adata.copy()
    out.uns.pop("divenn_rank_genes_groups_catalog", None)
    catalog, tables, stats_tables = [], [], []

    if deg_mode == "pseudobulk":
        print("[DiVenn2] Pseudobulk: PyDESeq2 (default parameters)")
        aggregated = aggregate_pseudobulk_counts(
            adata, cell_type_col, condition_col, sample_col,
            counts_layer, min_cells_per_sample,
        )
    else:
        print("[DiVenn2] Single-cell: Scanpy rank_genes_groups")

    cell_types = adata.obs[cell_type_col].astype(str).unique().tolist()

    for cell_type in cell_types:
        print(f"[DiVenn2] Cell type: {cell_type}")
        if deg_mode == "pseudobulk" and cell_type not in aggregated:
            print("[DiVenn2] No pseudobulk samples pass cell-count filtering")
            continue
        if deg_mode == "single_cell":
            ct = adata[adata.obs[cell_type_col].astype(str) == cell_type].copy()
        else:
            counts, metadata = aggregated[cell_type]

        for cond1, cond2 in comparisons:
            key = _result_key(cell_type, cond1, cond2)
            if deg_mode == "single_cell":
                n1 = int((ct.obs[condition_col].astype(str) == cond1).sum())
                n2 = int((ct.obs[condition_col].astype(str) == cond2).sum())
                if min(n1, n2) < 3:
                    print(f"[DiVenn2] Skipping {cond1} vs {cond2}: {n1}/{n2} cells")
                    continue
                result = run_single_cell_deg(
                    ct, condition_col, cond1, cond2, method,
                    correction_method, min_pct, max_pct,
                )
                used_method = method
            else:
                result, n1, n2 = run_pseudobulk_deg(
                    counts, metadata, cond1, cond2,
                    min_replicates, correction_method, n_cpus,
                )
                if result is None:
                    continue
                used_method = "pydeseq2"

            filtered = format_deg_results(
                result, cell_type, cond1, cond2, logfc_threshold, p_val_adj_thd,
                gene_list, gene_filter_mode, gene_filter_ignore_case,
            )
            if filtered.empty:
                print(f"[DiVenn2] No DEGs retained: {cell_type}, {cond1} vs {cond2}")
                continue

            store_divenn_results(
                out, catalog, key, filtered, cell_type, cond1, cond2,
                used_method, condition_col, deg_mode, n1, n2,
                counts_layer if deg_mode == "pseudobulk" else None,
            )
            tables.append(filtered[DIVENN_COLUMNS])
            if write_stats_csv:
                stats_tables.append(filtered.copy())
            print(f"[DiVenn2] Stored {len(filtered)} DEGs: {cell_type}, {cond1} vs {cond2}")

    catalog_columns = [ "key", "cell_type", "cond1", "cond2", "method", "groupby", "deg_mode", "n_replicates_cond1", "n_replicates_cond2", "counts_source"]
    catalog_df = pd.DataFrame(catalog, columns=catalog_columns)
    if catalog_df.empty:
        out.uns["divenn_rank_genes_groups_catalog"] = {
            col: np.array([], dtype=np.int64 if col.startswith("n_replicates_") else "U1")
            for col in catalog_columns
        }
    else:
        out.uns["divenn_rank_genes_groups_catalog"] = catalog_df.to_dict(orient="list")
    out.uns["divenn_deg_mode"] = deg_mode

    output_h5ad = os.path.abspath(output_h5ad)
    os.makedirs(os.path.dirname(output_h5ad), exist_ok=True)
    if write_csv:
        output_csv = os.path.splitext(output_h5ad)[0] + "_divenn2_deg.csv"
        if tables:
            pd.concat(tables, ignore_index=True).to_csv(output_csv, index=False)
        else:
            pd.DataFrame(columns=DIVENN_COLUMNS).to_csv(output_csv, index=False)
        print(f"[DiVenn2] Saved {output_csv}")
    if write_stats_csv:
        stats_csv = os.path.splitext(output_h5ad)[0] + "_deg_statistics.csv"
        if stats_tables:
            pd.concat(stats_tables, ignore_index=True).to_csv(stats_csv, index=False)
        else:
            empty_cols = (
                ["Gene", "baseMean", "log2FoldChange", "lfcSE", "stat", "pvalue", "padj", "Reg_direct", "Condition_1", "Condition_2", "CellType"]
                if deg_mode == "pseudobulk"
                else ["Gene", "log2FoldChange", "pvalue", "padj", "pct_1", "pct_2", "Reg_direct", "Condition_1", "Condition_2", "CellType"]
            )
            pd.DataFrame(columns=empty_cols).to_csv(stats_csv, index=False)
        print(f"[DiVenn2] Saved {stats_csv}")

    out.write_h5ad(output_h5ad, compression="gzip")
    print(f"[DiVenn2] Saved .h5ad containing {len(catalog)} comparisons: {output_h5ad}")
    return out


def main():
    parser = argparse.ArgumentParser(description="DiVenn2-ready h5ad with single-cell or pseudobulk DEG results")
    parser.add_argument("-w", "--workdir", default=None)
    parser.add_argument("-i", "--input", required=True, help="Input .h5ad")
    parser.add_argument("-c", "--condition", required=True, help="Condition column in adata.obs")
    parser.add_argument("-g", "--group", required=True, help="Cell-type column in adata.obs")
    parser.add_argument("-f", "--logfc_thd", type=float, default=1)
    parser.add_argument("-r", "--minpct_thd", type=float, default=0.25, help="Min pct threshold (single_cell only)")
    parser.add_argument("-p", "--maxpct_thd", type=float, default=0.5, help="Max pct threshold (single_cell only)")
    parser.add_argument("-v", "--padj_thd", type=float, default=0.05)
    parser.add_argument("-x", "--comparisons", default="All", help="All or A:B,A:C")
    parser.add_argument("-m", "--method", default="t-test", help="Scanpy method (single_cell only)")
    parser.add_argument("-t", "--correction_method", default="benjamini-hochberg")
    parser.add_argument("-o", "--output", required=True)
    parser.add_argument("-s", "--write_csv", action="store_true", help="Write DiVenn2 five-column CSV")
    parser.add_argument("--write_stats_csv", action="store_true", help="Write filtered DEGs with statistics")
    parser.add_argument("-l", "--gene_list_file", default=None)
    parser.add_argument("-d", "--gene_filter_mode", choices=["remove", "keep"], default=None)
    parser.add_argument("-a", "--gene_filter_ignore_case", action="store_true")
    parser.add_argument("--deg_mode", choices=["single_cell", "pseudobulk"], default="single_cell")
    parser.add_argument("--sample_col", default=None, help="Biological sample ID column (required in pseudobulk)")
    parser.add_argument("--counts_layer", default="counts", help="Raw count layer or X/raw (pseudobulk)")
    parser.add_argument("--min_cells_per_sample", type=int, default=3, help="Minimum cells per sample/cell-type")
    parser.add_argument("--min_replicates", type=int, default=2, help="Minimum samples per condition")
    parser.add_argument("--n_cpus", type=int, default=1, help="PyDESeq2 CPU workers")
    args = parser.parse_args()

    if args.n_cpus < 1:
        parser.error("--n_cpus must be >= 1")
    if args.workdir:
        os.makedirs(args.workdir, exist_ok=True)
        os.chdir(args.workdir)

    adata = load_h5ad(args.input)
    DiVenn2_preprocess_seuratobj(
        adata,
        cell_type_col=args.group,
        condition_col=args.condition,
        logfc_threshold=args.logfc_thd,
        min_pct=args.minpct_thd,
        max_pct=args.maxpct_thd,
        p_val_adj_thd=args.padj_thd,
        comparison_str=args.comparisons,
        method=args.method,
        correction_method=args.correction_method,
        output_h5ad=args.output,
        write_csv=args.write_csv,
        gene_list=read_gene_list(args.gene_list_file),
        gene_filter_mode=args.gene_filter_mode,
        gene_filter_ignore_case=args.gene_filter_ignore_case,
        deg_mode=args.deg_mode,
        sample_col=args.sample_col,
        counts_layer=args.counts_layer,
        min_cells_per_sample=args.min_cells_per_sample,
        min_replicates=args.min_replicates,
        n_cpus=args.n_cpus,
        write_stats_csv=args.write_stats_csv,
    )


if __name__ == "__main__":
    main()
