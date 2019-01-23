<?php
/**
 * Indexing information for what records to are owned by a particular scope
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/18/2015
 * Time: 10:31 AM
 */

require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';
class RecordOwned extends DB_DataObject {
	public $id;
	public $indexingProfileId;
	public $location;
	public $subLocation;

	static function getObjectStructure(){
		$indexingProfiles = array();
		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		$indexingProfile->orderBy('name');
		$indexingProfile->find();
		while ($indexingProfile->fetch()){
			$indexingProfiles[$indexingProfile->id] = $indexingProfile->name;
		}
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of this association'),
			'indexingProfileId' => array('property' => 'indexingProfileId', 'type' => 'enum', 'values' => $indexingProfiles, 'label' => 'Indexing Profile Id', 'description' => 'The Indexing Profile this map is associated with'),
			'location' => array('property'=>'location', 'type'=>'text', 'label'=>'Location', 'description'=>'A regular expression for location codes to include', 'maxLength' => '100', 'required' => true),
			'subLocation' => array('property'=>'subLocation', 'type'=>'text', 'label'=>'Sub Location', 'description'=>'A regular expression for sublocation codes to include', 'maxLength' => '100', 'required' => false),
		);
		return $structure;
	}
}