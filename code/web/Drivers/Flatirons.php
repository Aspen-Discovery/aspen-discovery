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
require_once ROOT_DIR . '/Drivers/Millennium.php';
/**
 * Pika Connector for Flatirons Library Consortium's Innovative catalog (Millennium)
 *
 * This class uses screen scraping techniques to gather record holdings written
 * by Adam Bryn of the Tri-College consortium.
 *
 * @author Adam Brin <abrin@brynmawr.com>
 *
 * Extended by Mark Noble and CJ O'Hara based on specific requirements for
 * Marmot Library Network.
 *
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @author CJ O'Hara <cj@marmot.org>
 * 
 */
class Flatirons extends Millennium{
	public function getSelfRegistrationFields(){
		global $library;
		$fields = array();
		$fields[] = array('property'=>'firstName', 'type'=>'text', 'label'=>'First Name', 'description'=>'Your first name', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property'=>'middleName', 'type'=>'text', 'label'=>'Middle Name', 'description'=>'Your middle name', 'maxLength' => 40, 'required' => false);
		// gets added to the first name separated by a space
		$fields[] = array('property'=>'lastName', 'type'=>'text', 'label'=>'Last Name', 'description'=>'Your last name', 'maxLength' => 40, 'required' => true);
		if ($library && $library->promptForBirthDateInSelfReg){
			$fields[] = array('property'=>'birthDate', 'type'=>'date', 'label'=>'Date of Birth (MM-DD-YYYY)', 'description'=>'Date of birth', 'maxLength' => 10, 'required' => true);
		}
		$fields[] = array('property'=>'address', 'type'=>'text', 'label'=>'Mailing Address', 'description'=>'Mailing Address', 'maxLength' => 128, 'required' => true);
		$fields[] = array('property'=>'city', 'type'=>'text', 'label'=>'City', 'description'=>'City', 'maxLength' => 48, 'required' => true);
		$fields[] = array('property'=>'state', 'type'=>'text', 'label'=>'State', 'description'=>'State', 'maxLength' => 32, 'required' => true);
		$fields[] = array('property'=>'zip', 'type'=>'text', 'label'=>'Zip Code', 'description'=>'Zip Code', 'maxLength' => 32, 'required' => true);
		$fields[] = array('property'=>'phone', 'type'=>'text', 'label'=>'Phone Number', 'description'=>'Phone Number', 'maxLength' => 16, 'required' => true);
		$fields[] = array('property'=>'email', 'type'=>'email', 'label'=>'E-Mail', 'description'=>'E-Mail', 'maxLength' => 128, 'required' => false);
		//$fields[] = array('property'=>'universityID', 'type'=>'text', 'label'=>'Drivers License #', 'description'=>'Drivers License', 'maxLength' => 128, 'required' => false);

		return $fields;
	}

	function selfRegister()
	{
		// Capitalize Mailing address
		$_REQUEST['address'] = strtoupper($_REQUEST['address']);
		$_REQUEST['city']    = strtoupper($_REQUEST['city']);
		$_REQUEST['state']   = strtoupper($_REQUEST['state']);
		$_REQUEST['zip']     = strtoupper($_REQUEST['zip']);

		return parent::selfRegister();
	}


}
