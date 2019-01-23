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
require_once 'XML/Unserializer.php';

class LocationFacetSettings extends ObjectEditor
{

	function getObjectType(){
		return 'LocationFacetSetting';
	}
	function getToolName(){
		return 'LocationFacetSettings';
	}
	function getPageTitle(){
		return 'Location Facets';
	}
	function getAllObjects(){
		$facetsList = array();
		$locationId = $_REQUEST['locationId'];

		$library = new LocationFacetSetting();
		$library->locationId = $locationId;
		$library->orderBy('weight');
		$library->find();
		while ($library->fetch()){
			$facetsList[$library->id] = clone $library;
		}

		return $facetsList;
	}
	function getObjectStructure(){
		return LocationFacetSetting::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin');
	}
	function canAddNew(){
		$user = UserAccount::getLoggedInUser();
		return UserAccount::userHasRole('opacAdmin');
	}
	function canDelete(){
		$user = UserAccount::getLoggedInUser();
		return UserAccount::userHasRole('opacAdmin');
	}
	function getAdditionalObjectActions($existingObject){
		$objectActions = array();
		if (isset($existingObject) && $existingObject != null){
			$objectActions[] = array(
				'text' => 'Return to Location',
				'url' => '/Admin/Locations?objectAction=edit&id=' . $existingObject->locationId,
			);
		}
		return $objectActions;
	}
}