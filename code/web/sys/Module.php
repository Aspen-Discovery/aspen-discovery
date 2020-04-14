<?php


class Module extends DataObject
{
	public $__table = 'modules';
	public $id;
	public $name;
	public $enabled;
	public $indexName;
	public $backgroundProcess;
	public $logClassPath;
	public $logClassName;

	function getObjectStructure(){
		return [
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'The name of the module'),
			'enabled' => array('property' => 'enabled', 'type' => 'checkbox', 'label' => 'Enabled?', 'description'=>'Whether or not the module is enabled', 'default'=>'0'),
			'indexName' => array('property'=>'indexName', 'type'=>'text', 'label'=>'Index Name', 'description'=>'The name of the associated solr index if any'),
			'backgroundProcess' => array('property'=>'backgroundProcess', 'type'=>'text', 'label'=>'Background Process', 'description'=>'The name of the background process being run if any'),
			'logClassPath' => array('property'=>'logClassPath', 'type'=>'text', 'label'=>'Log Class Path', 'description'=>'The path to the class where logs are stored'),
			'logClassName' => array('property'=>'logClassName', 'type'=>'text', 'label'=>'Log Class Name', 'description'=>'The name of the class that does logging'),
		];
	}
}