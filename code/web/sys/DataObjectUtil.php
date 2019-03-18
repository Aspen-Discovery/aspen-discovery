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
require_once ROOT_DIR . '/services/Admin/Admin.php';

class DataObjectUtil
{
	/**
	 * Get the edit form for a data object based on the structure of the object
	 *
	 * @param $objectStructure array representing the structure of the object.
	 *
	 * @return string and HTML Snippet representing the form for display.
	 */
	static function getEditForm($objectStructure){
		global $interface;

		//Define the structure of the object.
		$interface->assign('structure', $objectStructure);
		//Check to see if the request should be multipart/form-data
		$contentType = null;
		foreach ($objectStructure as $property){
			if ($property['type'] == 'image' || $property['type'] == 'file'){
				$contentType = 'multipart/form-data';
			}
		}
		$interface->assign('contentType', $contentType);
		return  $interface->fetch('DataObjectUtil/objectEditForm.tpl');
	}

	/**
	 * Save the object to the database (and optionally solr) based on the structure of the object
	 * Takes care of determining whether or not the object is new or not.
	 *
	 * @param $dataObject
	 * @param $form
	 */
	static function saveObject($structure, $dataType){
		global $logger;
		//Check to see if we have a new object or an exiting object to update
		$object = new $dataType();
		DataObjectUtil::updateFromUI($object, $structure);
		$primaryKeySet = false;
		foreach ($structure as $property){
			if (isset($property['primaryKey']) && $property['primaryKey'] == true){
				if (isset($object->$property['property']) && !is_null($object->$property['property']) && strlen($object->$property['property']) > 0){
					$object = new $dataType();
					$object->$property['property'] = $object->$property['property'];
					if ($object->find(true)){
						$logger->log("Loaded existing object from database", PEAR_LOG_DEBUG);
					}else{
						$logger->log("Could not find existing object in database", PEAR_LOG_ERR);
					}

					//Reload from UI
					DataObjectUtil::updateFromUI($object, $structure);
					$primaryKeySet = true;
					break;
				}
			}
		}
		$validationResults = DataObjectUtil::validateObject($structure, $object);
		$validationResults['object'] = $object;

		if ($validationResults['validatedOk']){
			//Check to see if we need to insert or update the object.
			//We can tell which to do based on whether or not the primary key is set

			if ($primaryKeySet){
				$result = $object->update();
				$validationResults['saveOk'] = ($result == 1);
			}else{
				$result = $object->insert();
				$validationResults['saveOk'] = $result;
			}
			if (!$validationResults['saveOk']){
				//TODO: Display the PEAR error (in certain circumstances only?)
				$error = &PEAR_Singleton::getStaticProperty('DB_DataObject','lastError');
				if (isset($error)){
					$validationResults['errors'][] = 'Save failed ' . $error->getMessage();
				}else{
					$validationResults['errors'][] = 'Save failed';
				}
			}
		}
		return $validationResults;
	}

	/**
	 * Delete an object from the database (and optionally solr).
	 *
	 * @param $dataObject
	 * @param $form
	 */
	static function deleteObject($structure, $dataType){

	}

	/**
	 * Validate that the inputs for the data object are correct prior to saving the object.
	 *
	 * @param $dataObject
	 * @param $object - The object to validate
     *
     * @return array Results of validation
	 */
	static function validateObject($structure, $object){
		//Setup validation return array
		$validationResults = array(
		  'validatedOk' => true,
		  'errors' => array(),
		);

		//Do the validation
		foreach ($structure as $property){
			$value = isset($_REQUEST[$property['property']]) ? $_REQUEST[$property['property']] : null;
			if (isset($property['required']) && $property['required'] == true){
				if ($value == null && strlen($value) > 0){
					$validationResults['errors'][] = $property['property'] . ' is required.';
				}
			}
			//Check to see if there is a custom validation routine
			if (isset($property['serverValidation'])){
			    $validationRoutine = $property['serverValidation'];
				$propValidation = $object->$validationRoutine();
				if ($propValidation['validatedOk'] == false){
					$validationResults['errors'] =  array_merge($validationResults['errors'], $propValidation['errors']);
				}
			}
		}

		//Make sure there aren't errors
		if (count($validationResults['errors']) > 0){
			$validationResults['validatedOk'] = false;
		}
		return $validationResults;
	}

	static function updateFromUI($object, $structure){
		foreach ($structure as $property){
			DataObjectUtil::processProperty($object, $property);
		}
	}

	static function processProperty($object, $property){
		global $logger;
		$propertyName = $property['property'];
		if ($property['type'] == 'section'){
			foreach ($property['properties'] as $subProperty){
				DataObjectUtil::processProperty($object, $subProperty);
			}
		}else if (in_array($property['type'], array('text', 'enum', 'hidden', 'url', 'email', 'multiemail'))){
			if (isset($_REQUEST[$propertyName])){
				$object->$propertyName = strip_tags(trim($_REQUEST[$propertyName]));
			} else {
                $object->$propertyName = "";
            }

		}else if (in_array( $property['type'], array('textarea', 'html', 'folder', 'crSeparated'))){
			if (strlen(trim($_REQUEST[$propertyName])) == 0){
				$object->$propertyName = null;
			}else{
				$object->$propertyName = trim($_REQUEST[$propertyName]);
			}
			//Strip tags from the input to avoid problems
			if ($property['type'] == 'textarea' || $property['type'] == 'crSeparated'){
				$object->$propertyName = strip_tags($object->$propertyName);
			}else{
				$allowableTags = isset($property['allowableTags']) ? $property['allowableTags'] : '<p><a><b><em><ul><ol><em><li><strong><i><br>';
				$object->$propertyName = strip_tags($object->$propertyName, $allowableTags);
			}
		}else if ($property['type'] == 'integer'){
			if (preg_match('/\\d+/', $_REQUEST[$propertyName])){
				$object->$propertyName =  $_REQUEST[$propertyName];
			}else{
				$object->$propertyName =  0;
			}
        }else if ($property['type'] == 'color'){
		    $defaultProperty = $propertyName . 'Default';
            if (isset($_REQUEST[$propertyName . '-default']) && ($_REQUEST[$propertyName . '-default'] == 'on')){
                $object->$defaultProperty = 1;
            }else{
                $object->$defaultProperty = 0;
            }
            $object->$propertyName = $_REQUEST[$propertyName];
		}else if ($property['type'] == 'currency'){
			if (preg_match('/\\$?\\d*\\.?\\d*/', $_REQUEST[$propertyName])){
				if (substr($_REQUEST[$propertyName], 0, 1) == '$'){
					$object->$propertyName =  substr($_REQUEST[$propertyName], 1);
				}else{
					$object->$propertyName =  $_REQUEST[$propertyName];
				}
			}else{
				$object->$propertyName =  0;
			}

		}else if ($property['type'] == 'checkbox'){
			$object->$propertyName = isset($_REQUEST[$propertyName]) && $_REQUEST[$propertyName] == 'on' ? 1 : 0;

		}else if ($property['type'] == 'multiSelect'){
			if (isset($_REQUEST[$propertyName]) && is_array($_REQUEST[$propertyName])){
				$object->$propertyName = $_REQUEST[$propertyName];
			}else{
				$object->$propertyName = array();
			}

		}else if ($property['type'] == 'date'){
			if (strlen($_REQUEST[$propertyName]) == 0 || $_REQUEST[$propertyName] == '0000-00-00'){
				$object->$propertyName = null;
			}else{
				$dateParts = date_parse($_REQUEST[$propertyName]);
				$time = $dateParts['year'] . '-' . $dateParts['month'] . '-' . $dateParts['day'];
				$object->$propertyName = $time;
			}

		}else if ($property['type'] == 'partialDate'){
			$dayField = $property['propNameDay'];
			$object->$dayField = $_REQUEST[$dayField];
			$monthField = $property['propNameMonth'];
			$object->$monthField = $_REQUEST[$monthField];
			$yearField = $property['propNameYear'];
			$object->$yearField = $_REQUEST[$yearField];

		}else if ($property['type'] == 'image'){
			//Make sure that the type is correct (jpg, png, or gif)
			if (isset($_REQUEST["remove{$propertyName}"])){
				$object->$propertyName = '';

			}else if (isset($_FILES[$propertyName])){
				if (isset($_FILES[$propertyName]["error"]) && $_FILES[$propertyName]["error"] == 4){
					$logger->log("No file was uploaded for $propertyName", PEAR_LOG_DEBUG);
					//No image supplied, use the existing value
				}else if (isset($_FILES[$propertyName]["error"]) && $_FILES[$propertyName]["error"] > 0){
					//return an error to the browser
					$logger->log("Error in file upload for $propertyName", PEAR_LOG_ERR);
				}else if (in_array($_FILES[$propertyName]["type"], array('image/gif', 'image/jpeg', 'image/png'))){
					$logger->log("Processing uploaded file for $propertyName", PEAR_LOG_DEBUG);
					//Copy the full image to the files directory
					//Filename is the name of the object + the original filename
					global $configArray;
					if (isset($property['storagePath'])){
						$destFileName = $_FILES[$propertyName]["name"];
						$destFolder = $property['storagePath'];
						$destFullPath = $destFolder . '/' . $destFileName;
						$copyResult = copy($_FILES[$propertyName]["tmp_name"], $destFullPath);
						$logger->log("Copied file to $destFullPath", PEAR_LOG_DEBUG);
					}else{
						$logger->log("Creating thumbnails for $propertyName", PEAR_LOG_DEBUG);
						$destFileName = $propertyName . $_FILES[$propertyName]["name"];
						$destFolder = $configArray['Site']['local'] . '/files/original';
						$pathToThumbs = $configArray['Site']['local'] . '/files/thumbnail';
						$pathToMedium = $configArray['Site']['local'] . '/files/medium';
						$destFullPath = $destFolder . '/' . $destFileName;
						$copyResult = copy($_FILES[$propertyName]["tmp_name"], $destFullPath);

						if ($copyResult){
							$img = imagecreatefromstring(file_get_contents($destFullPath));
							$width = imagesx( $img );
							$height = imagesy( $img );

							if (isset($property['thumbWidth'])){
                                //Create a thumbnail if needed
                                $thumbWidth = $property['thumbWidth'];
                                if ($width > $thumbWidth) {
                                    $new_width = $thumbWidth;
                                    $new_height = floor( $height * ( $thumbWidth / $width ) );

                                    // create a new temporary image
                                    $tmp_img = imagecreatetruecolor( $new_width, $new_height );
                                    imagealphablending($tmp_img, false);
                                    imagesavealpha($tmp_img,true);
                                    $transparent = imagecolorallocatealpha($tmp_img, 255, 255, 255, 127);
                                    imagefilledrectangle($tmp_img, 0, 0, $width, $height, $transparent);

                                    // copy and resize old image into new image
                                    imagecopyresized( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

                                    // save thumbnail into a file
                                    imagepng( $tmp_img, "{$pathToThumbs}/{$destFileName}" );
                                } else {
                                    copy($destFullPath, "{$pathToThumbs}/{$destFileName}");
                                }

							}
							if (isset($property['mediumWidth'])){
                                //Create a thumbnail if needed
                                $thumbWidth = $property['mediumWidth'];
                                if ($width > $thumbWidth){
                                    $new_width = $thumbWidth;
                                    $new_height = floor( $height * ( $thumbWidth / $width ) );

                                    // create a new temporary image
                                    $tmp_img = imagecreatetruecolor( $new_width, $new_height );
                                    imagealphablending($tmp_img, false);
                                    imagesavealpha($tmp_img,true);
                                    $transparent = imagecolorallocatealpha($tmp_img, 255, 255, 255, 127);
                                    imagefilledrectangle($tmp_img, 0, 0, $width, $height, $transparent);

                                    // copy and resize old image into new image
                                    imagecopyresized( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

                                    // save thumbnail into a file
                                    imagepng( $tmp_img, "{$pathToMedium}/{$destFileName}" );
                                } else {
                                    copy($destFullPath, "{$pathToMedium}/{$destFileName}");
                                }
							}
						}
					}
					//store the actual filename
					$object->$propertyName = $destFileName;
					$logger->log("Set $propertyName to $destFileName", PEAR_LOG_DEBUG);
				}
			}

		}else if ($property['type'] == 'file'){
			//Make sure that the type is correct (jpg, png, or gif)
			if (isset($_REQUEST["remove{$propertyName}"])){
				$object->$propertyName = '';
			}elseif (isset($_REQUEST["{$propertyName}_existing"]) && $_FILES[$propertyName]['error'] == 4){
				$object->$propertyName = $_REQUEST["{$propertyName}_existing"];
			}else if (isset($_FILES[$propertyName])){
				if ($_FILES[$propertyName]["error"] > 0){
					//return an error to the browser
				}else if (true){ //TODO: validate the file type
					//Copy the full image to the correct location
					//Filename is the name of the object + the original filename
					global $configArray;
					$destFileName = $_FILES[$propertyName]["name"];
					$destFolder = $property['path'];
					$destFullPath = $destFolder . '/' . $destFileName;
					$copyResult = copy($_FILES[$propertyName]["tmp_name"], $destFullPath);
					if ($copyResult){
						$logger->log("Copied file from {$_FILES[$propertyName]["tmp_name"]} to $destFullPath", PEAR_LOG_INFO);
					}else{
						$logger->log("Could not copy file from {$_FILES[$propertyName]["tmp_name"]} to $destFullPath", PEAR_LOG_ERR);
						if (!file_exists($_FILES[$propertyName]["tmp_name"])){
							$logger->log("  Uploaded file did not exist", PEAR_LOG_ERR);
						}
						if (!is_writable($destFullPath)){
							$logger->log("  Destination is not writable", PEAR_LOG_ERR);
						}
					}
					//store the actual filename
					$object->$propertyName = $destFileName;
				}
			}
		}else if ($property['type'] == 'password'){
			if (strlen($_REQUEST[$propertyName]) > 0 && ($_REQUEST[$propertyName] == $_REQUEST[$propertyName . 'Repeat'])){
				$object->$propertyName = md5($_REQUEST[$propertyName]);
			}
		}else if ($property['type'] == 'oneToMany'){
			//Check for deleted associations
			$deletions = isset($_REQUEST[$propertyName . 'Deleted']) ? $_REQUEST[$propertyName . 'Deleted'] : array();
			//Check for changes to the sort order
			if ($property['sortable'] == true && isset($_REQUEST[$propertyName . 'Weight'])){
				$weights = $_REQUEST[$propertyName . 'Weight'];
			}
			$values = array();
			if (isset($_REQUEST[$propertyName.'Id'])){
				$idsToSave = $_REQUEST[$propertyName.'Id'];
				$existingValues = $object->$propertyName;
				$subObjectType = $property['subObjectType'];  // the PHP Class name
				$subStructure = $property['structure'];
				foreach ($idsToSave as $key => $id){
					//Create the subObject
					if ($id < 0 || $id == ""){
						$subObject = new $subObjectType();
						$id = $key;
					}else{
                        if (!isset($existingValues[$id])){
                            if (!isset($deletions[$id]) || ($deletions[$id] == 'false')) {
                                $logger->log("$subObjectType $id has been deleted from the database, but is still present in the interface", PEAR_LOG_ERR);
                            }
                            continue;
                        } else {
                            $subObject = $existingValues[$id];
                        }

					}

					$deleted = isset($deletions[$id]) ? $deletions[$id] : false;
					if ($deleted == 'true'){
						$subObject->deleteOnSave = true;
					}else{
						//Update properties of each associated object
						foreach ($subStructure as $subProperty){
							$requestKey = $propertyName . '_' . $subProperty['property'];
							$subPropertyName = $subProperty['property'];
							if (in_array($subProperty['type'], array('text', 'enum', 'integer', 'numeric', 'textarea', 'html', 'multiSelect') )){
								$subObject->$subPropertyName = $_REQUEST[$requestKey][$id];
							}elseif (in_array($subProperty['type'], array('checkbox') )){
								$subObject->$subPropertyName = isset($_REQUEST[$requestKey][$id]) ? 1 : 0;
							}elseif ($subProperty['type'] == 'date'){
								if (strlen($_REQUEST[$requestKey][$id]) == 0 || $_REQUEST[$requestKey][$id] == '0000-00-00'){
									$subObject->$subPropertyName = null;
								}else{
									$dateParts = date_parse($_REQUEST[$requestKey][$id]);
									$time = $dateParts['year'] . '-' . $dateParts['month'] . '-' . $dateParts['day'];
									$subObject->$subPropertyName = $time;
								}
							}elseif (!in_array($subProperty['type'], array('label', 'foreignKey', 'oneToMany') )){
								//echo("Invalid Property Type " . $subProperty['type']);
							}
						}
					}
					if ($property['sortable'] == true && isset($weights)){
						$subObject->weight = $weights[$id];
					}

					//Update the values array
					$values[$id] = $subObject;
				}
			}

			$object->$propertyName = $values;
		}
	}

	static function getObjectListFilters($objectStructure){

	}

	static function getObjectList($objectStructure, $objectsToShow){

	}

	static function getObjectExportFile($objectStructure, $objectsToExport, $exportFilename){

	}
	static function compareObjects($objectStructure, $object1, $object2){

	}
	static function importObjectsFromFile($objectStructure, $objectType, $importFilename){

	}

	static function getFileUploadMessage($errorNo, $fieldname){
		$errorMessages = array(
		0=>"There is no error, the file for $fieldname uploaded with success",
		1=>"The uploaded file for $fieldname exceeds the maximum file size for the server",
		2=>"The uploaded file for $fieldname exceeds the maximum file size for this field",
		3=>"The uploaded file for $fieldname was only partially uploaded",
		4=>"No file was uploaded for $fieldname",
		6=>"Missing a temporary folder for $fieldname"
		);
		return $errorMessages[$errorNo];
	}
}