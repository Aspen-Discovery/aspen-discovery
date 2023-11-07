<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class AspenLiDASelfCheckBarcode extends DataObject {
	public $__table = 'aspen_lida_self_check_barcode';
	public $id;
	public $barcodeStyle;
	public $selfCheckSettingsId;


	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'barcodeStyle' => [
				'property' => 'barcodeStyle',
				'type' => 'enum',
				'label' => 'Supported Styles',
				'description' => 'Barcode style to allow for self-check',
				'required' => true,
				'values' => [
					'aztec' => 'Aztec',
					'codabar' => 'Codabar',
					'code39' => 'Code 39',
					'code93' => 'Code 93',
					'code128' => 'Code 128',
					'datamatrix' => 'Data Matrix',
					'ean13' => 'EAN 13',
					'ean8' => 'EAN 8',
					'itf14' => 'ITF-14',
					'pdf417' => 'PDF417',
					'upc_e' => 'UPC E',
					'upc_a' => 'UPC A (Android Only)',
					'upc_ean' => 'UPC/EAN (Android Only)',
					'qr' => 'QR',
				],
			],
		];
	}
}