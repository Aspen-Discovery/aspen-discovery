<?php

/**
 * Description goes here
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 3/29/2016
 * Time: 12:05 PM
 */
class NonGroupedRecord extends DB_DataObject{
	public $__table = 'nongrouped_records';
	public $id;
	public $source;
	public $recordId;
	public $notes;

	static function getObjectStructure() {
		global $indexingProfiles;
		$availableSources = array();
		foreach ($indexingProfiles as $profile){
			$availableSources[$profile->name] = $profile->name;
		}
		$availableSources['overdrive'] = 'overdrive';

		$structure = array(
			array(
				'property' => 'id',
				'type' => 'hidden',
				'label' => 'Id',
				'description' => 'The unique id of the merged grouped work in the database',
				'storeDb' => true,
				'primaryKey' => true,
			),
			array(
				'property' => 'source',
				'type' => 'enum',
				'values' => $availableSources,
				'label' => 'Source of the Record Id',
				'description' => 'The source of the record to avoid merging.',
				'default' => 'ils',
				'storeDb' => true,
				'required' => true,
			),
			array(
				'property' => 'recordId',
				'type' => 'text',
				'size' => 36,
				'maxLength' => 36,
				'label' => 'Record Id',
				'description' => 'The id of the record that should not be merged.',
				'storeDb' => true,
				'required' => true,
			),
			array(
				'property' => 'notes',
				'type' => 'text',
				'size' => 255,
				'maxLength' => 255,
				'label' => 'Notes',
				'description' => 'Notes related to the record.',
				'storeDb' => true,
				'required' => true,
			),
		);
		return $structure;
	}

}