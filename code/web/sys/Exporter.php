<?php

namespace sys;

class Exporter {
    /**
     * @param $tpl - the smarty template for CSV to use
     * @param $dataList - the array of data rows to export
     * @return void
     */
    public function downloadCSV($tpl, $dataList)
    {
        global $interface;
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="DonationsReport.csv"');
        header('Cache-Control: max-age=0');
        $fp = fopen('php://output', 'w');
        //add BOM to fix UTF-8 in Excel
        fputs($fp, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        $interface->assign('dataList', $dataList);
        $interface->setTemplate($tpl);
        exit;
    }
}