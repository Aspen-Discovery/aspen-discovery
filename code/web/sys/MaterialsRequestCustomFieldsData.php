<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class MaterialsRequestCustomFieldsData extends DataObject {
	public $__table = 'materials_request_custom_fields_data';
	public $id;
	public $formFieldsId;
	public $requestId;
	public $data;
	public $text_data;

}