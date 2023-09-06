<?php

class Exporter {
	/**
	 * @param $fileName - the name of the file to use
	 * @param $tpl - the smarty template for CSV to use
	 * @param $dataList - the array of data rows to export
	 * @return void
	 */
	public static function downloadCSV($fileName, $tpl, $structure, $dataList) {
		global $interface;
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $fileName . '.csv"');
		header('Cache-Control: max-age=0');
		$fp = fopen('php://output', 'w');
		//add BOM to fix UTF-8 in Excel
		fputs($fp, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
		$interface->assign('structure', $structure);
		$interface->assign('dataList', $dataList);
		$body = $interface->fetch($tpl);
		fputs($fp, $body);
		exit;
	}
}