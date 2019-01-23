<?php

/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 12/19/2016
 *
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
class MaterialsRequestCustomFieldsData extends DB_DataObject
{
	public $__table = 'materials_request_custom_fields_data';
	public $id;
	public $formFieldsId;
	public $requestId;
	public $data;
	public $text_data;

}