<?php

/**
 * A persistent variable defined within the system
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 4/27/14
 * Time: 2:23 PM
 */
class Variable extends DB_DataObject {
	public $__table = 'variables'; // table name
	public $id;
	public $name;
	public $value;

	static function getObjectStructure() {
		$structure = array(
				'id' => array(
						'property' => 'id',
						'type' => 'hidden',
						'label' => 'Id',
						'description' => 'The unique id of the variable.',
						'primaryKey' => true,
						'storeDb' => true,
				),
				'name' => array(
						'property' => 'name',
						'type' => 'text',
						'label' => 'Name',
						'description' => 'The name of the variable.',
						'maxLength' => 255,
						'size' => 100,
						'storeDb' => true,
				),
				'value' => array(
						'property' => 'value',
						'type' => 'text',
						'label' => 'Value',
						'description' => 'The value of the variable',
						'storeDb' => true,
						'maxLength' => 255,
						'size' => 100,
				),
		);
		return $structure;
	}
} 