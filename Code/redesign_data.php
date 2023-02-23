<?php

function getCutoffMode()
{
    return $_SESSION['cutofftype'];
}

function createMyDataCutoffed($cutoffMode)
{
    for ($number = 0; $number < $_SESSION['filenum']; $number++) {
        if ($cutoffMode == "Cutoff12") $_SESSION['mydata_cutoffed'][$number] = $_SESSION['mydata'][$number];
        else {
            $_SESSION['mydata_cutoffed'][$number] = '';
            for ($gen_id = 0; $gen_id < count($_SESSION['genes_IDs'][$number]); $gen_id++) {
                if ($_SESSION['genes_IDs'][$number][$gen_id] != null) {
                    $_SESSION['mydata_cutoffed'][$number] .= $_SESSION['genes_IDs'][$number][$gen_id] . "\t" .
                        $_SESSION['genes_values'][$number][$gen_id] . "\t" .
                        $_SESSION['raw_genes_values'][$number][$gen_id] . "\r\n";
                }
            }
            $_SESSION['mydata_cutoffed'][$number] = htmlspecialchars($_SESSION['mydata_cutoffed'][$number]);
            $_SESSION['mydata_cutoffed'][$number] = substr($_SESSION['mydata_cutoffed'][$number], 0, -2);
        }
    }
}
