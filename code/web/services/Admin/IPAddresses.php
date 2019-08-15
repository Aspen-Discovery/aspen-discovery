<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/IP/IPAddress.php';

class IPAddresses extends ObjectEditor
{
	function getObjectType(){
		return 'IPAddress';
	}
	function getToolName(){
		return 'IPAddresses';
	}
	function getPageTitle(){
		return 'Location IP Addresses';
	}
	function getAllObjects(){
		$object = new IPAddress();
		$object->orderBy('ip');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getObjectStructure(){
		//Look lookup information for display in the user interface
		$location = new Location();
		$location->orderBy('displayName');
		$location->find();
		$locationList = array();
		$locationLookupList = array();
		$locationLookupList[-1] = '<No Nearby Location>';
		while ($location->fetch()){
			$locationLookupList[$location->locationId] = $location->displayName;
			$locationList[$location->locationId] = clone $location;
		}
		$structure = array(
          'ip' => array('property'=>'ip', 'type'=>'text', 'label'=>'IP Address', 'description'=>'The IP Address to map to a location formatted as xxx.xxx.xxx.xxx/mask'),
          'location' => array('property'=>'location', 'type'=>'text', 'label'=>'Display Name', 'description'=>'Descriptive information for the IP Address for internal use'),
          'locationid' => array('property'=>'locationid', 'type'=>'enum', 'values'=>$locationLookupList, 'label'=>'Location', 'description'=>'The Location which this IP address maps to'),
          'isOpac' => array('property' => 'isOpac', 'type' => 'checkbox', 'label' => 'Treat as a Public OPAC', 'description' => 'This IP address will be treated as a public OPAC with autologout features turned on.', 'default' => true),
		);
		return $structure;
	}
	function getPrimaryKeyColumn(){
		return 'ip';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin');
	}

}