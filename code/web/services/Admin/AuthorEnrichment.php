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
require_once ROOT_DIR . '/sys/LocalEnrichment/AuthorEnrichment.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_AuthorEnrichment extends ObjectEditor
{
	function getObjectType(){
		return 'AuthorEnrichment';
	}
	function getToolName(){
		return 'AuthorEnrichment';
	}
	function getPageTitle(){
		return 'Author Enrichment';
	}
	function getAllObjects(){
		$object = new AuthorEnrichment();
		$object->orderBy('authorName');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getObjectStructure(){
		return AuthorEnrichment::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin', 'cataloging');
	}
	function getInstructions(){
		return "For more information on how to create update author enrichment information, see the <a href=\"https://docs.google.com/document/d/1aNmuuFcMHU9i9ZrnqIbuzVEFJE6xMDTS8uloPAqIli8\">online documentation</a>.";
	}
	function getListInstructions(){
		return $this->getInstructions();
	}

}