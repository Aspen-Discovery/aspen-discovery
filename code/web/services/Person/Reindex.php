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

require_once ROOT_DIR . '/sys/Genealogy/Person.php';
require_once ROOT_DIR . '/Action.php';

class Reindex extends Action{
	function launch(){
		global $timer;

		$timer->logTime("Starting to reindex person");
		$recordId = $_REQUEST['id'];
		$quick = isset($_REQUEST['quick']) ? true : false;
		$person = new Person();
		$person->personId = $recordId;
		if ($person->find(true)){
			$ret = $person->saveToSolr($quick);
			if ($ret){
				echo(json_encode(array("success" => true)));
			}else{
				echo(json_encode(array("success" => false, "error" => "Could not update solr")));
			}
		}else{
			echo(json_encode(array("success" => false, "error" => "Could not find a record with that id")));
		}

	}

}