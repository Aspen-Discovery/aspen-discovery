<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class DataObjectUtil {
	/**
	 * Get the edit form for a data object based on the structure of the object
	 *
	 * @param $objectStructure array representing the structure of the object.
	 *
	 * @return string and HTML Snippet representing the form for display.
	 */
	static function getEditForm(array $objectStructure) : string {
		global $interface;

		//Define the structure of the object.
		$interface->assign('structure', $objectStructure);
		//Check to see if the request should be multipart/form-data
		$contentType = DataObjectUtil::getFormContentType($objectStructure);
		$interface->assign('contentType', $contentType);
		$interface->assign('formLabel', 'Edit ' . $contentType);
		return $interface->fetch('DataObjectUtil/objectEditForm.tpl');
	}

	static function getFormContentType(array $structure, ?string $contentType = null) : ?string {
		if ($contentType != null) {
			return $contentType;
		}
		//Check to see if the request should be multipart/form-data
		foreach ($structure as $property) {
			if ($property['type'] == 'section') {
				$contentType = DataObjectUtil::getFormContentType($property['properties'], $contentType);
			} elseif ($property['type'] == 'image' || $property['type'] == 'file') {
				$contentType = 'multipart/form-data';
			}
		}
		return $contentType;
	}

	/**
	 * Validate that the inputs for the data object are correct prior to saving the object.
	 *
	 * @param array $structure
	 * @param $object - The object to validate
	 *
	 * @return array Results of validation
	 */
	static function validateObject(array $structure, $object) : array {
		//Setup validation return array
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];

		//Do the validation
		foreach ($structure as $property) {
			if ($property['type'] == 'section') {
				$sectionResults = DataObjectUtil::validateObject($property['properties'], $object);
				if ($sectionResults['validatedOk'] == false) {
					$validationResults['errors'] = array_merge($validationResults['errors'], $sectionResults['errors']);
				}
				continue;
			}
			$value = isset($_REQUEST[$property['property']]) ? $_REQUEST[$property['property']] : null;
			if (isset($property['required']) && $property['required'] == true) {
				if ($value == null && strlen($value) > 0) {
					$validationResults['errors'][] = $property['property'] . ' is required.';
				}
			}
			if ($property['type'] == 'password' || $property['type'] == 'storedPassword') {
				$valueRepeat = isset($_REQUEST[$property['property'] . 'Repeat']) ? $_REQUEST[$property['property'] . 'Repeat'] : null;
				if ($value != $valueRepeat) {
					$validationResults['errors'][] = $property['property'] . ' does not match ' . $property['property'] . 'Repeat';
				}
			}

			//Check to see if there is a custom validation routine
			if (isset($property['serverValidation'])) {
				$validationRoutine = $property['serverValidation'];
				$propValidation = $object->$validationRoutine();
				if ($propValidation['validatedOk'] == false) {
					$validationResults['errors'] = array_merge($validationResults['errors'], $propValidation['errors']);
				}
			}
		}

		//Make sure there aren't errors
		if (count($validationResults['errors']) > 0) {
			$validationResults['validatedOk'] = false;
		}
		return $validationResults;
	}

	static function updateFromUI($object, $structure, $fieldLocks) : void {
		foreach ($structure as $property) {
			if (($fieldLocks != null && !in_array($property['property'], $fieldLocks)) || $fieldLocks == null) {
				DataObjectUtil::processProperty($object, $property, $fieldLocks);
			}
		}
	}

	static function structureContainsImagesOrFiles($structure) : bool {
		foreach ($structure as $property) {
			if ($property['type'] == 'image' || $property['type'] == 'file') {
				return true;
			}elseif ($property['type'] == 'section') {
				if (DataObjectUtil::structureContainsImagesOrFiles($property['properties'])) {
					return true;
				}
			}
		}
		return false;
	}

	static function updateImagesAndFilesAfterInsert($object, $structure) : void {
		foreach ($structure as $property) {
			if ($property['type'] == 'image' || $property['type'] == 'file') {
				DataObjectUtil::processProperty($object, $property, null);
			}elseif ($property['type'] == 'section') {
				DataObjectUtil::updateImagesAndFilesAfterInsert($object, $property['properties']);
			}
		}
	}

	static function processProperty(DataObject $object, $property, $fieldLocks) : void {
		global $logger;
		$propertyName = $property['property'];
		if ($property['type'] == 'section') {
			foreach ($property['properties'] as $subProperty) {
				DataObjectUtil::processProperty($object, $subProperty, $fieldLocks);
			}
			return;
		}
		if (($fieldLocks != null && in_array($property['property'], $fieldLocks))) {
			return;
		}
		if (in_array($property['type'], [
			'regularExpression',
			'multilineRegularExpression',
		])) {
			if (isset($_REQUEST[$propertyName])) {
				$object->setProperty($propertyName, trim($_REQUEST[$propertyName]), $property);
			} else {
				$object->setProperty($propertyName, "", $property);
			}

		} elseif (in_array($property['type'], [
			'text',
			'textFromNestedSection',
			'enumFromNestedSection',
			'enum',
			'hidden',
			'url',
			'email',
			'email2',
			'email_prefill',
			'multiemail',
			'barcode_prefill',
			'phone_prefill',
			'name_prefill',
			'address_prefill',
			'address2_prefill',
			'city_prefill',
			'state_prefill',
			'zip_prefill',
			'pin',
			'pinConfirmation'
		])) {
			if (empty($property['readOnly']) || empty($object->getPrimaryKeyValue())) {
				if (isset($_REQUEST[$propertyName])) {
					if ($object instanceof UnsavedDataObject && $property['type'] == 'enum') {
						$object->setProperty($propertyName, $property['values'][$_REQUEST[$propertyName]], $property);
					} else {
						$newValue = strip_tags(trim($_REQUEST[$propertyName]));
						if ($newValue != null) {
							$newValue = preg_replace('/\x{2029}/usm', '', $newValue);
						}
						$object->setProperty($propertyName, $newValue, $property);
					}
				} else {
					$object->setProperty($propertyName, "", $property);
				}
			}

		} elseif (in_array($property['type'], [
			'textarea',
			'html',
			'markdown',
			'javascript',
			'folder',
			'crSeparated',
		])) {
			if (empty($_REQUEST[$propertyName]) || strlen(trim($_REQUEST[$propertyName])) == 0) {
				$object->setProperty($propertyName, "", $property);
			} else {
				$object->setProperty($propertyName, trim($_REQUEST[$propertyName]), $property);
			}
			//Strip tags from the input to avoid problems
			if ($property['type'] == 'textarea' || $property['type'] == 'crSeparated') {
				$object->setProperty($propertyName, strip_tags($object->$propertyName), $property);
			} elseif ($property['type'] != 'javascript') {
				$systemVariables = SystemVariables::getSystemVariables();
				if ($systemVariables) {
					if ($systemVariables->allowHtmlInMarkdownFields || $systemVariables->useHtmlEditorRatherThanMarkdown) {
						if (!empty($systemVariables->allowableHtmlTags)) {
							$allowableTags = '<' . implode('><', explode('|', $systemVariables->allowableHtmlTags)) . '>';
						} else {
							$allowableTags = null;
						}
					} else {
						if (!empty($property['allowableTags'])) {
							$allowableTags = $property['allowableTags'];
						} else {
							/** @noinspection HtmlRequiredAltAttribute */
							$allowableTags = '<p><em><i><strong><b><a><ul><ol><li><h1><h2><h3><h4><h5><h6><h7><pre><code><hr><table><tbody><tr><th><td><caption><img><br><div><span>';
						}
					}

				} else {
					// set defaults if system variables do not exist
					/** @noinspection HtmlRequiredAltAttribute */
					$allowableTags = '<p><em><i><strong><b><a><ul><ol><li><h1><h2><h3><h4><h5><h6><h7><pre><code><hr><table><tbody><tr><th><td><caption><img><br><div><span>';
				}

				if (!empty($allowableTags)) {
					$object->setProperty($propertyName, strip_tags($object->$propertyName, $allowableTags), $property);
				} else {
					$object->setProperty($propertyName, $object->$propertyName, $property);
				}
			}
		} elseif ($property['type'] == 'timestamp') {
			if (empty($_REQUEST[$propertyName])) {
				$object->setProperty($propertyName, 0, $property);
			} else {
				try {
					$timeValue = new DateTime($_REQUEST[$propertyName]);
					$object->setProperty($propertyName, $timeValue->getTimestamp(), $property);
				} catch (Exception $e) {
					//Could not load the timestamp
					$object->setProperty($propertyName, 0, $property);
				}
			}
		} elseif ($property['type'] == 'integer') {
			if (preg_match('/\\d+/', $_REQUEST[$propertyName])) {
				$object->setProperty($propertyName, $_REQUEST[$propertyName], $property);
			} else {
				$object->setProperty($propertyName, 0, $property);
			}
		} elseif ($property['type'] == 'color' || $property['type'] == 'font') {
			$defaultProperty = $propertyName . 'Default';
			if (isset($_REQUEST[$propertyName . '-default']) && ($_REQUEST[$propertyName . '-default'] == 'on')) {
				$object->setProperty($defaultProperty, 1, null);
			} else {
				$object->setProperty($defaultProperty, 0, null);
			}
			$object->setProperty($propertyName, $_REQUEST[$propertyName], $property);
		} elseif ($property['type'] == 'currency') {
			if (preg_match('/\\$?\\d*\\.?\\d*/', $_REQUEST[$propertyName])) {
				if (str_starts_with($_REQUEST[$propertyName], '$')) {
					$object->setProperty($propertyName, substr($_REQUEST[$propertyName], 1), $property);
				} else {
					$object->setProperty($propertyName, $_REQUEST[$propertyName], $property);
				}
			} else {
				$object->setProperty($propertyName, 0, $property);
			}

		} elseif ($property['type'] == 'checkbox' || $property['type'] == 'checkboxFromNestedSection') {
			if (empty($property['readOnly'])) {
				$object->setProperty($propertyName, isset($_REQUEST[$propertyName]) && $_REQUEST[$propertyName] == 'on' ? 1 : 0, $property);
			}
		} elseif ($property['type'] == 'webBuilderColor') {
			$object->setProperty($propertyName, $_REQUEST[$propertyName], $property);
		} elseif ($property['type'] == 'multiSelect') {
			if (isset($_REQUEST[$propertyName]) && is_array($_REQUEST[$propertyName])) {
				$object->setProperty($propertyName, $_REQUEST[$propertyName], $property);
			} else {
				$object->setProperty($propertyName, [], $property);
			}

		} elseif ($property['type'] == 'date') {
			if (empty(strlen($_REQUEST[$propertyName])) || $_REQUEST[$propertyName] == '0000-00-00') {
				$object->setProperty($propertyName, null, $property);
			} else {
				$dateParts = date_parse($_REQUEST[$propertyName]);
				$time = $dateParts['year'] . '-' . str_pad($dateParts['month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($dateParts['day'], 2, '0', STR_PAD_LEFT);
				$object->setProperty($propertyName, $time, $property);
			}

		} elseif ($property['type'] == 'time') {
			if (empty(strlen($_REQUEST[$propertyName])) || $_REQUEST[$propertyName] == '00:00:00') {
				$object->setProperty($propertyName, null, $property);
			} else {
				$dateParts = date_parse($_REQUEST[$propertyName]);
				$time = $dateParts['hour'] . ':' . $dateParts['minute'] . ':' . $dateParts['second'];
				$object->setProperty($propertyName, $time, $property);
			}

		} elseif ($property['type'] == 'duration') {
			if (preg_match('/\\d+/', $_REQUEST[$propertyName])) {
				$object->setProperty($propertyName, $_REQUEST[$propertyName], $property);
			} else {
				$object->setProperty($propertyName, 0, $property);
			}
		} elseif ($property['type'] == 'dayMonth') {
			if (isset($_REQUEST[$propertyName . "_month"]) && isset($_REQUEST[$propertyName . "_day"])){
				if (is_numeric($_REQUEST[$propertyName . "_month"]) && is_numeric($_REQUEST[$propertyName . "_day"])) {
					$dayMonth = str_pad($_REQUEST[$propertyName . "_month"], "2", "0", STR_PAD_LEFT);
					$dayMonth .= "-" . str_pad($_REQUEST[$propertyName . "_day"], "2", "0", STR_PAD_LEFT);
					$object->setProperty($propertyName, $dayMonth, $property);
				}
			}

		} elseif ($property['type'] == 'partialDate') {
			$dayField = $property['propNameDay'];
			$object->setProperty($dayField, $_REQUEST[$dayField], null);
			$monthField = $property['propNameMonth'];
			$object->setProperty($monthField, $_REQUEST[$monthField], null);
			$yearField = $property['propNameYear'];
			$object->setProperty($yearField, $_REQUEST[$yearField], null);

		} elseif ($property['type'] == 'image') {
			//Make sure that the type is correct (jpg, png, or gif)
			if (isset($_REQUEST["remove{$propertyName}"])) {
				$object->setProperty($propertyName, '', $property);

			} elseif (isset($_FILES[$propertyName])) {
				if (isset($_FILES[$propertyName]["error"]) && $_FILES[$propertyName]["error"] == 4) {
					$logger->log("No file was uploaded for $propertyName", Logger::LOG_DEBUG);
					//No image supplied, use the existing value
				} elseif (isset($_FILES[$propertyName]["error"]) && $_FILES[$propertyName]["error"] > 0) {
					//return an error to the browser
					$logger->log("Error in file upload for $propertyName", Logger::LOG_ERROR);
				} elseif ((!empty($property['validTypes']) && !in_array($_FILES[$propertyName]["type"], $property['validTypes'])) ||
					(empty($property['validTypes']) && !in_array($_FILES[$propertyName]["type"], ['image/gif', 'image/jpeg', 'image/png', 'image/svg+xml']))) {
					$allowedTypes = !empty($property['validTypes']) ? implode(', ', $property['validTypes']) : 'image/gif, image/jpeg, image/png, image/svg+xml';
					AspenError::raiseError(translate([
						'text' => 'Invalid file type: %1%. Allowed types: %2%.',
						1 => $_FILES[$propertyName]["type"],
						2 => $allowedTypes,
						'isAdminFacing' => true
					]));
				} else {
					$logger->log("Processing uploaded file for $propertyName", Logger::LOG_DEBUG);
					//Copy the full image to the files directory
					//Filename is the name of the object + the original filename
					global $configArray;
					$fileType = $_FILES[$propertyName]["type"];
					$fileType = match ($fileType) {
						'image/gif' => ".gif",
						'image/png' => ".png",
						'image/svg+xml' => ".svg",
						default => ".jpg",
					};
					if (!empty($object->type)){
						$objectType = $object->type;
					} else {
						$objectType = $property['property'];
						$objectType = match ($objectType) {
							'logoName' => 'discovery_logo',
							'defaultCover' => 'default_cover',
							'headerBackgroundImage' => 'header_background_image',
							'footerLogo' => 'footer_logo',
							'logoApp' => 'logo_app',
							'headerLogoApp' => 'header_logo_app',
							'booksImage' => 'books_image',
							'booksImageSelected' => 'books_image_selected',
							'eBooksImage' => 'eBooks_image',
							'eBooksImageSelected' => 'eBooks_image_selected',
							'audioBooksImage' => 'audioBooks_image',
							'audioBooksImageSelected' => 'audioBooks_image_selected',
							'musicImage' => 'music_image',
							'musicImageSelected' => 'music_image_selected',
							'moviesImage' => 'movies_image',
							'moviesImageSelected' => 'movies_image_selected',
							'catalogImage' => 'catalog_image',
							'genealogyImage' => 'genealogy_image',
							'articlesDBImage' => 'articles_db_image',
							'eventsImage' => 'events_image',
							'listsImage' => 'lists_image',
							'seriesImage' => 'series_image',
							'libraryWebsiteImage' => 'library_website_image',
							'historyArchivesImage' => 'history_archives_image',
							default => get_class($object) . '_' . $property['property'],
						};
						if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'Placards') {
							$objectType = 'placard_image';
						}
						if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'WebResources') {
							$objectType = 'web_resource_image';
						}
					}
					if (isset($property['storagePath'])) {
						$destFileName = ($object->id != null) ? $objectType."_".$object->id.$fileType : "Temp_".$_FILES[$propertyName]["name"];
						$destFolder = $property['storagePath'];
						$destFullPath = $destFolder . '/' . $destFileName;
						$copyResult = copy($_FILES[$propertyName]["tmp_name"], $destFullPath);
						$logger->log("Copied file to $destFullPath result: $copyResult", Logger::LOG_DEBUG);
					} else {
						$logger->log("Creating thumbnails for $propertyName", Logger::LOG_DEBUG);
						if (isset($property['path'])) {
							$destFolder = $property['path'];
							$destFileName = ($object->id != null) ? $objectType."_".$object->id.$fileType : "Temp_".$_FILES[$propertyName]["name"];
							if (!file_exists($destFolder)) {
								mkdir($destFolder, 0755, true);
							}
							$pathToThumbs = $destFolder . '/thumbnail';
							$pathToMedium = $destFolder . '/medium';
						} else {
							$destFileName = ($object->id != null) ? $objectType."_".$object->id.$fileType : "Temp_".$_FILES[$propertyName]["name"];
							$destFolder = $configArray['Site']['local'] . '/files/original';
							$pathToThumbs = $configArray['Site']['local'] . '/files/thumbnail';
							$pathToMedium = $configArray['Site']['local'] . '/files/medium';
						}

						$destFullPath = $destFolder . '/' . $destFileName;
						//check for previous upload that needs to be overwritten to new naming convention
						$prevUpload = $destFolder . '/' . "Temp_" . $_FILES[$propertyName]["name"];
						if (file_exists($prevUpload)) {
							rename($prevUpload, $destFullPath);
						}
						$copyResult = copy($_FILES[$propertyName]["tmp_name"], $destFullPath);

						if ($copyResult) {
							require_once ROOT_DIR . '/sys/Covers/CoverImageUtils.php';

							if (isset($property['thumbWidth'])) {
								resizeImage($destFullPath, "{$pathToThumbs}/{$destFileName}", $property['thumbWidth'], $property['thumbWidth']);
							}
							if (isset($property['mediumWidth'])) {
								//Create a thumbnail if needed
								resizeImage($destFullPath, "{$pathToMedium}/{$destFileName}", $property['mediumWidth'], $property['mediumWidth']);
							}
							if (isset($property['maxWidth'])) {
								//Create a thumbnail if needed
								$width = $property['maxWidth'];
								$height = $property['maxWidth'];
								if (isset($property['maxHeight'])) {
									$height = $property['maxHeight'];
								}
								resizeImage($destFullPath, "{$destFolder}/{$destFileName}", $width, $height);
							}
						}
					}
					//store the actual filename
					$object->setProperty($propertyName, $destFileName, $property);
					$logger->log("Set $propertyName to $destFileName", Logger::LOG_DEBUG);
				}
			}

		} elseif ($property['type'] == 'file') {
			//Make sure that the type is correct (jpg, png, or gif)
			if (isset($_REQUEST["remove{$propertyName}"])) {
				$object->setProperty($propertyName, '', $property);
			} elseif (isset($_REQUEST["{$propertyName}_existing"]) && $_FILES[$propertyName]['error'] == 4) {
				$object->setProperty($propertyName, $_REQUEST["{$propertyName}_existing"], $property);
			} elseif (isset($_FILES[$propertyName])) {
				if ($_FILES[$propertyName]["error"] > 0) {
					//return an error to the browser
					$logger->log("Error uploading file " . $_FILES[$propertyName]["error"], Logger::LOG_ERROR);
				} elseif (true) { //TODO: validate the file type
					if (array_key_exists('validTypes', $property)) {
						$fileType = $_FILES[$propertyName]["type"];
						if (!in_array($fileType, $property['validTypes'])) {
							AspenError::raiseError('Incorrect file type uploaded ' . $fileType);
						}
					}
					if (!empty($object->type)){
						$objectType = $object->type;
					} else {
						$objectType = $property['type'];
					};
					$fileType = ".pdf";
					//Copy the full image to the correct location
					//Filename is the name of the object + the original filename
					$destFileName = ($object->id != null) ? $objectType."_".$object->id.$fileType : "Temp_".$_FILES[$propertyName]["name"];
					$destFolder = $property['path'];
					if (!file_exists($destFolder)) {
						mkdir($destFolder, 0775, true);
						chgrp($destFolder, 'aspen_apache');
						chmod($destFolder, 0775);
					}
					if (str_ends_with($destFolder, '/')) {
						$destFolder = substr($destFolder, 0, -1);
					}

					$destFullPath = $destFolder . '/' . $destFileName;
					//check for previous upload that needs to be overwritten to new naming convention
					$prevUpload = $destFolder . '/' . "Temp_" . $_FILES[$propertyName]["name"];
					if (file_exists($prevUpload)) {
						rename($prevUpload, $destFullPath);
						// Remove any old thumbnail for this PDF.
						$thumbPath = $prevUpload . '.jpg';
						if (file_exists($thumbPath)) {
							@unlink($thumbPath);
						}
					}
					$copyResult = copy($_FILES[$propertyName]["tmp_name"], $destFullPath);
					if ($copyResult) {
						$logger->log("Copied file from {$_FILES[$propertyName]["tmp_name"]} to $destFullPath", Logger::LOG_NOTICE);
					} else {
						$logger->log("Could not copy file from {$_FILES[$propertyName]["tmp_name"]} to $destFullPath", Logger::LOG_ERROR);
						if (!file_exists($_FILES[$propertyName]["tmp_name"])) {
							$logger->log("  Uploaded file did not exist", Logger::LOG_ERROR);
						}
						if (!is_writable($destFullPath)) {
							$logger->log("  Destination is not writable", Logger::LOG_ERROR);
						}
					}
					//store the actual filename
					$object->setProperty($propertyName, $destFullPath, $property);
				}
			}
		} elseif ($property['type'] == 'uploaded_font') {
			//Make sure that the type is correct (jpg, png, or gif)
			if (isset($_REQUEST["remove{$propertyName}"])) {
				$object->setProperty($propertyName, '', $property);
			} elseif (isset($_REQUEST["{$propertyName}_existing"]) && $_FILES[$propertyName]['error'] == 4) {
				$object->setProperty($propertyName, $_REQUEST["{$propertyName}_existing"], $property);
			} elseif (isset($_FILES[$propertyName])) {
				if ($_FILES[$propertyName]["error"] > 0) {
					//return an error to the browser
					$logger->log("Error uploading file " . $_FILES[$propertyName]["error"], Logger::LOG_ERROR);
				} elseif (true) { //TODO: validate the file type
					//Copy the full image to the correct location
					//Filename is the name of the object + the original filename
					global $configArray;
					$destFileName = $_FILES[$propertyName]["name"];
					$destFolder = $configArray['Site']['local'] . '/fonts';
					$destFullPath = $destFolder . '/' . $destFileName;
					$copyResult = copy($_FILES[$propertyName]["tmp_name"], $destFullPath);
					if ($copyResult) {
						$logger->log("Copied file from {$_FILES[$propertyName]["tmp_name"]} to $destFullPath", Logger::LOG_NOTICE);
					} else {
						$logger->log("Could not copy file from {$_FILES[$propertyName]["tmp_name"]} to $destFullPath", Logger::LOG_ERROR);
						if (!file_exists($_FILES[$propertyName]["tmp_name"])) {
							$logger->log("  Uploaded file did not exist", Logger::LOG_ERROR);
						}
						if (!is_writable($destFullPath)) {
							$logger->log("  Destination is not writable", Logger::LOG_ERROR);
						}
					}
					//store the actual filename
					$object->setProperty($propertyName, $destFileName, $property);
				}
			}
		} elseif ($property['type'] == 'password') {
			if (strlen($_REQUEST[$propertyName]) > 0 && ($_REQUEST[$propertyName] == $_REQUEST[$propertyName . 'Repeat'])) {
				$newValue = strip_tags(trim($_REQUEST[$propertyName]));
				if ($newValue != null) {
					$newValue = preg_replace('/\x{2029}/usm', '', $newValue);
				}
				$object->setProperty($propertyName, md5($newValue), $property);
			}
		} elseif ($property['type'] == 'storedPassword') {
			if (strlen($_REQUEST[$propertyName]) > 0 && ($_REQUEST[$propertyName] == $_REQUEST[$propertyName . 'Repeat'])) {
				$newValue = strip_tags(trim($_REQUEST[$propertyName]));
				if ($newValue != null) {
					$newValue = preg_replace('/\x{2029}/usm', '', $newValue);
				}
				$object->setProperty($propertyName, $newValue, $property);
			}
		} elseif ($property['type'] == 'translatableTextBlock' || $property['type'] == 'translatablePlainTextBlock') {
			//Set all the translations for
			$allTranslations = [];
			foreach ($_REQUEST as $requestName => $propertyValue) {
				if (str_starts_with($requestName, $propertyName)) {
					$language = str_replace($property['property'] . '_', '', $requestName);
					if ($language != 'default') {
						$allTranslations[$language] = $propertyValue;
					}
				}
			}
			$privatePropertyName = '_' . $propertyName;
			$object->$privatePropertyName = $allTranslations;
		} elseif ($property['type'] == 'oneToMany') {
			//Check for deleted associations
			$deletions = $_REQUEST[$propertyName . 'Deleted'] ?? [];
			//Check for changes to the sort order
			if ($property['sortable'] && isset($_REQUEST[$propertyName . 'Weight'])) {
				$weights = $_REQUEST[$propertyName . 'Weight'];
			}
			$values = [];
			if (isset($_REQUEST[$propertyName . 'Id'])) {
				$idsToSave = $_REQUEST[$propertyName . 'Id'];
				$existingValues = $object->$propertyName;
				$subObjectType = $property['subObjectType'];  // the PHP Class name
				$subStructure = $property['structure'];
				foreach ($idsToSave as $key => $id) {
					//Create the subObject
					if ($id < 0 || $id == "") {
						/** @var DataObject $subObject */
						$subObject = new $subObjectType();
						$id = $key;
					} else {
						if (!isset($existingValues[$id])) {
							if (!isset($deletions[$id]) || ($deletions[$id] == 'false')) {
								$logger->log("$subObjectType $id has been deleted from the database, but is still present in the interface", Logger::LOG_ERROR);
							}
							continue;
						} else {
							$subObject = $existingValues[$id];
						}
					}

					$deleted = $deletions[$id] ?? false;
					if ($deleted == 'true') {
						if ($subObject->getPrimaryKeyValue() > 0) {
							$object->handlePropertyChangeEffects($propertyName, $subObject, null, $property, 'deleted', 'oneToMany entry');
							require_once ROOT_DIR . '/sys/DB/DataObjectHistory.php';
							$history = new DataObjectHistory();
							$history->objectType = get_class($object);
							$primaryKey = $object->__primaryKey;
							$history->objectId = $object->$primaryKey;
							$history->propertyName = DataObjectUtil::getHistoryPropertyName($object, $propertyName);
							$history->actionType = 3;
							$history->oldValue = (string)$subObject;
							$history->newValue = 'Deleted sub-object';
							$history->changedBy = UserAccount::getActiveUserId();
							$history->changeDate = time();
							$history->insert();
						}
						$subObject->_deleteOnSave = true;
					} else {
						// Update properties of each associated sub-object.
						foreach ($subStructure as $subProperty) {
							$requestKey = $propertyName . '_' . $subProperty['property'];
							$subPropertyName = $subProperty['property'];
							$hideInLists = array_key_exists('hideInLists', $subProperty) ? $subProperty['hideInLists'] : false;
							if (!$hideInLists) {
								if (in_array($subProperty['type'], [
									'text',
									'enum',
									'integer',
									'numeric',
									'textarea',
									'html',
									'markdown',
									'javascript',
									'multiSelect',
									'regularExpression',
									'multilineRegularExpression',
								])) {
									$oldValue = $subObject->$subPropertyName;
									$changed = $subObject->setProperty($subPropertyName, $_REQUEST[$requestKey][$id], $subProperty);
									if ($changed && !empty($object->{$object->__primaryKey}) && $object->objectHistoryEnabled()) {
										require_once ROOT_DIR . '/sys/DB/DataObjectHistory.php';
										$history = new DataObjectHistory();
										$history->objectType = get_class($object);
										$primaryKey = $object->__primaryKey;
										$history->objectId = $object->$primaryKey;
										$history->propertyName = DataObjectUtil::getHistoryPropertyName($object, $propertyName . '.' . $subPropertyName);
										$history->oldValue = (string)$oldValue;
										$history->newValue = (string)$subObject->$subPropertyName;
										$history->changedBy = UserAccount::getActiveUserId();
										$history->changeDate = time();
										$history->insert();
									}
								} elseif ($subProperty['type'] == 'checkbox') {
									$oldValue = $subObject->$subPropertyName;
									$newVal = isset($_REQUEST[$requestKey][$id]) ? 1 : 0;
									$changed = $subObject->setProperty($subPropertyName, $newVal, $subProperty);
									if ($changed && !empty($object->{$object->__primaryKey}) && $object->objectHistoryEnabled()) {
										require_once ROOT_DIR . '/sys/DB/DataObjectHistory.php';
										$history = new DataObjectHistory();
										$history->objectType = get_class($object);
										$primaryKey = $object->__primaryKey;
										$history->objectId = $object->$primaryKey;
										$history->propertyName = DataObjectUtil::getHistoryPropertyName($object, $propertyName . '.' . $subPropertyName);
										$history->oldValue = (string)$oldValue;
										$history->newValue = (string)$newVal;
										$history->changedBy = UserAccount::getActiveUserId();
										$history->changeDate = time();
										$history->insert();
									}
								} elseif ($subProperty['type'] == 'date') {
									$oldValue = $subObject->$subPropertyName;
									if (strlen($_REQUEST[$requestKey][$id]) == 0 || $_REQUEST[$requestKey][$id] == '0000-00-00') {
										$changed = $subObject->setProperty($subPropertyName, null, $subProperty);
									} else {
										$dateParts = date_parse($_REQUEST[$requestKey][$id]);
										$time = $dateParts['year'] . '-' . str_pad($dateParts['month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($dateParts['day'], 2, '0', STR_PAD_LEFT);
										$changed = $subObject->setProperty($subPropertyName, $time, $subProperty);
									}

									if (!empty($changed) && !empty($object->{$object->__primaryKey}) && $object->objectHistoryEnabled()) {
										require_once ROOT_DIR . '/sys/DB/DataObjectHistory.php';
										$history = new DataObjectHistory();
										$history->objectType = get_class($object);
										$primaryKey = $object->__primaryKey;
										$history->objectId = $object->$primaryKey;
										$history->propertyName = DataObjectUtil::getHistoryPropertyName($object, $propertyName . '.' . $subPropertyName);
										$history->oldValue = (string)$oldValue;
										$history->newValue = (string)($subObject->$subPropertyName ?? '');
										$history->changedBy = UserAccount::getActiveUserId();
										$history->changeDate = time();
										$history->insert();
									}
								} elseif ($subProperty['type'] == 'time') {
									$oldValue = $subObject->$subPropertyName;
									if (empty(strlen($_REQUEST[$requestKey][$id])) || $_REQUEST[$requestKey][$id] == '00:00:00') {
										$changed = $subObject->setProperty($subPropertyName, null, $subProperty);
									} else {
										$dateParts = date_parse($_REQUEST[$requestKey][$id]);
										$time = str_pad($dateParts['hour'], 2, '0', STR_PAD_LEFT) . ':' . str_pad($dateParts['minute'], 2, '0', STR_PAD_LEFT) . ':' . str_pad($dateParts['second'], 2, '0', STR_PAD_LEFT);
										$changed = $subObject->setProperty($subPropertyName, $time, $subProperty);
									}

									if (!empty($changed) && !empty($object->{$object->__primaryKey}) && $object->objectHistoryEnabled()) {
										require_once ROOT_DIR . '/sys/DB/DataObjectHistory.php';
										$history = new DataObjectHistory();
										$history->objectType = get_class($object);
										$primaryKey = $object->__primaryKey;
										$history->objectId = $object->$primaryKey;
										$history->propertyName = DataObjectUtil::getHistoryPropertyName($object, $propertyName . '.' . $subPropertyName);
										$history->oldValue = (string)$oldValue;
										$history->newValue = (string)($subObject->$subPropertyName ?? '');
										$history->changedBy = UserAccount::getActiveUserId();
										$history->changeDate = time();
										$history->insert();
									}
								} elseif (!in_array($subProperty['type'], [
									'label',
									'foreignKey',
									'oneToMany',
									'dynamic_label',
								])) {
									//echo("Invalid Property Type " . $subProperty['type']);
									$logger->log("Invalid Property Type " . $subProperty['type'], Logger::LOG_DEBUG);
								}
							}
						}
					}
					if ($property['sortable'] && isset($weights)) {
						$subObject->setProperty('weight', $weights[$id], null);
					}

					//Update the values array
					$values[$id] = $subObject;
				}
			}

			$object->$propertyName = $values;
			if (isset($existingValues)) {
				$oldKeys = array_keys((array)$existingValues);
				$newKeys = array_keys($values);
				// Only log if the related IDs changed rather than logging all the time, which clutters the history.
				if ($oldKeys !== $newKeys && !empty($object->{$object->__primaryKey}) && $object->objectHistoryEnabled()) {
					require_once ROOT_DIR . '/sys/DB/DataObjectHistory.php';
					$history = new DataObjectHistory();
					$history->objectType = get_class($object);
					$primaryKey = $object->__primaryKey;
					$history->objectId = $object->$primaryKey;
					$history->propertyName = DataObjectUtil::getHistoryPropertyName($object, $propertyName);
					$history->actionType = 1;
					// Use human-readable labels for old and new values.
					$oldLabels = [];
					foreach ($existingValues as $subObject) {
						$oldLabels[] = (string)$subObject;
					}
					$newLabels = [];
					foreach ($values as $subObject) {
						$newLabels[] = (string)$subObject;
					}
					$history->oldValue = implode(',', $oldLabels);
					$history->newValue = implode(',', $newLabels);
					$history->changedBy = UserAccount::getActiveUserId();
					$history->changeDate = time();
					$history->insert();
				}
			}
		}
	}

	/**
	 * Get a human-readable property name for history logging.
	 *
	 * @param DataObject $object
	 * @param string $propertyName
	 * @return string formatted as "propertyName (Human Label)" or just "propertyName" if no label found.
	 */
	static function getHistoryPropertyName(DataObject $object, string $propertyName): string {
		try {
			if (!method_exists($object, 'getObjectStructure')) {
				return $propertyName;
			}

			// Cache the structure and a flat label map per class for this request.
			static $structureCache = []; // className => objectStructure
			static $labelMapCache  = []; // className => [ 'prop' => 'Label', 'parent.child' => 'Child Label', ... ]

			$class = get_class($object);

			if (!isset($structureCache[$class])) {
				$structureCache[$class] = $object->getObjectStructure();
			}
			$objectStructure = $structureCache[$class];

			if (!isset($labelMapCache[$class])) {
				$labelMap = [];
				$stack = [$objectStructure];

				while (!empty($stack)) {
					$section = array_pop($stack);
					if (!is_array($section)) continue;

					foreach ($section as $field) {
						if (!is_array($field)) continue;

						$type = $field['type'] ?? null;

						// Record label for simple properties.
						if (isset($field['property'])) {
							$prop = $field['property'];
							if (!empty($field['label'])) {
								$labelMap[$prop] = $field['label'];
							}

							// For oneToMany, also record labels for child properties as "parent.child".
							if ($type == 'oneToMany' && isset($field['structure']) && is_array($field['structure'])) {
								foreach ($field['structure'] as $sub) {
									if (is_array($sub) && isset($sub['property']) && !empty($sub['label'])) {
										$labelMap[$prop . '.' . $sub['property']] = $sub['label'];
									}
								}
							}
						}

						if ($type == 'section' && isset($field['properties']) && is_array($field['properties'])) {
							$stack[] = $field['properties'];
						}
					}
				}

				$labelMapCache[$class] = $labelMap;
			}

			$labelMap = $labelMapCache[$class];

			// Handle one-to-many relationships.
			$parts = explode('.', $propertyName);
			if (count($parts) == 2) {
				$parentProperty = $parts[0];
				$childProperty = $parts[1];

				$parentLabel = $labelMap[$parentProperty] ?? '';
				// Prefer exact "parent.child"; fall back to standalone child label if present.
				$childLabel = $labelMap[$propertyName] ?? ($labelMap[$childProperty] ?? '');

				if ($parentLabel && $childLabel) {
					return "$propertyName ($parentLabel - $childLabel)";
				} elseif ($childLabel) {
					return "$propertyName ($childLabel)";
				} elseif ($parentLabel) {
					return "$propertyName ($parentLabel)";
				}
			} else {
				// Simple property: fast lookup from the label map.
				if (isset($labelMap[$propertyName])) {
					$label = $labelMap[$propertyName];
					return "$propertyName ($label)";
				}
			}
		} catch (Exception) {
			// If anything fails, just return the original property name.
		}

		return $propertyName;
	}
}