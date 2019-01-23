<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/subnet.php';
require_once 'XML/Unserializer.php';

class IPAddresses extends ObjectEditor
{
	function getObjectType(){
		return 'subnet';
	}
	function getToolName(){
		return 'IPAddresses';
	}
	function getPageTitle(){
		return 'Location IP Addresses';
	}
	function getAllObjects(){
		$object = new subnet();
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