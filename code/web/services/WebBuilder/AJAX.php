<?php
require_once ROOT_DIR . '/JSON_Action.php';

class WebBuilder_AJAX extends JSON_Action {
	/** @noinspection PhpUnused */
	function getPortalCellValuesForSource() {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];

		$sourceType = $_REQUEST['sourceType'];
		switch ($sourceType) {
			case 'basic_page':
			case 'basic_page_teaser':
				require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';
				$list = [];
				$list['-1'] = 'Select a page';

				$basicPage = new BasicPage();
				$basicPage->orderBy('title');
				$basicPage->find();

				while ($basicPage->fetch()) {
					$list[$basicPage->id] = $basicPage->title;
				}

				$result = [
					'success' => true,
					'values' => $list,
				];
				break;
			case 'collection_spotlight':
				require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
				$list = [];
				$list['-1'] = 'Select a spotlight';

				$collectionSpotlight = new CollectionSpotlight();
				if (!UserAccount::userHasPermission('Administer All Custom Pages')) {
					$homeLibrary = Library::getPatronHomeLibrary();
					$collectionSpotlight->whereAdd('libraryId = ' . $homeLibrary->libraryId . ' OR libraryId = -1');
				}
				$collectionSpotlight->orderBy('name');
				$collectionSpotlight->find();
				while ($collectionSpotlight->fetch()) {
					$list[$collectionSpotlight->id] = $collectionSpotlight->name;
				}

				$result = [
					'success' => true,
					'values' => $list,
				];
				break;
			case 'custom_form':
				require_once ROOT_DIR . '/sys/WebBuilder/CustomForm.php';
				$list = [];
				$list['-1'] = 'Select a form';

				$customForm = new CustomForm();
				$customForm->orderBy('title');
				$customForm->find();

				while ($customForm->fetch()) {
					$list[$customForm->id] = $customForm->title;
				}

				$result = [
					'success' => true,
					'values' => $list,
				];
				break;
			case 'image':
				require_once ROOT_DIR . '/sys/File/ImageUpload.php';
				$list = [];
				$list['-1'] = 'Select an image';
				$object = new ImageUpload();
				$object->type = 'web_builder_image';
				$object->orderBy('title');
				$object->find();
				while ($object->fetch()) {
					$list[$object->id] = $object->title;
				}
				$result = [
					'success' => true,
					'values' => $list,
				];
				break;
			case 'pdf':
				require_once ROOT_DIR . '/sys/File/FileUpload.php';
				$list = [];
				$list['-1'] = 'Select a PDF';
				$object = new FileUpload();
				$object->type = 'web_builder_pdf';
				$object->orderBy('title');
				$object->find();
				while ($object->fetch()) {
					$list[$object->id] = $object->title;
				}
				$result = [
					'success' => true,
					'values' => $list,
				];
				break;
			case 'video':
				require_once ROOT_DIR . '/sys/File/FileUpload.php';
				$list = [];
				$list['-1'] = 'Select a video';
				$object = new FileUpload();
				$object->type = 'web_builder_video';
				$object->orderBy('title');
				$object->find();
				while ($object->fetch()) {
					$list[$object->id] = $object->title;
				}
				$result = [
					'success' => true,
					'values' => $list,
				];
				break;
			case 'web_resource':
				require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
				$list = [];
				$list['-1'] = 'Select a web resource';
				$object = new WebResource();
				$object->orderBy('name');
				$object->find();
				while ($object->fetch()) {
					$list[$object->id] = $object->name;
				}
				$result = [
					'success' => true,
					'values' => $list,
				];
				break;
			default:
				$result['message'] = 'Unhandled Source Type ' . $sourceType;
		}

		$portalCellId = $_REQUEST['portalCellId'];
		$result['selected'] = '-1';
		if (!empty($portalCellId)) {
			require_once ROOT_DIR . '/sys/WebBuilder/PortalCell.php';
			$portalCell = new PortalCell();
			$portalCell->id = $portalCellId;
			if ($portalCell->find(true)) {
				if ($portalCell->sourceType == $sourceType) {
					$result['selected'] = $portalCell->sourceId;
				}
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function uploadImage() {
		$result = [
			'success' => false,
			'message' => 'Unknown error uploading image',
		];
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::userHasPermission('Administer All Web Content')) {
				if (!empty($_FILES)) {
					require_once ROOT_DIR . '/sys/File/ImageUpload.php';
					$structure = ImageUpload::getObjectStructure('');
					foreach ($_FILES as $file) {
						$image = new ImageUpload();
						$image->type = 'web_builder_image';
						$image->fullSizePath = $file['name'];
						$image->generateXLargeSize = true;
						$image->generateLargeSize = true;
						$image->generateMediumSize = true;
						$image->generateSmallSize = true;
						$destFileName = $file['name'];
						$destFolder = $structure['fullSizePath']['path'];
						if (!is_dir($destFolder)) {
							if (!mkdir($destFolder, 0755, true)) {
								$result['message'] = 'Could not create directory to upload files';
								if (IPAddress::showDebuggingInformation()) {
									$result['message'] .= " " . $destFolder;
								}
							}
						}
						$destFullPath = $destFolder . '/' . $destFileName;
						if (file_exists($destFullPath)) {
							$image->find(true);
						}

						$image->title = $file['name'];
						$copyResult = copy($file["tmp_name"], $destFullPath);
						if ($copyResult) {
							$image->update();
							$result = [
								'success' => true,
								'title' => $image->title,
								'imageUrl' => $image->getDisplayUrl('full'),
							];
							break;
						} else {
							$result['message'] = 'Could not save the image to disk';
						}
					}
				} else {
					$result['message'] = 'No file was selected';
				}
			} else {
				$result['message'] = 'You don\'t have the correct permissions to upload an image';
			}
		} else {
			$result['message'] = 'You must be logged in to upload an image';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function uploadImageTinyMCE() {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::userHasPermission('Administer All Web Content')) {
				if (!empty($_FILES)) {
					require_once ROOT_DIR . '/sys/File/ImageUpload.php';
					$structure = ImageUpload::getObjectStructure('');
					foreach ($_FILES as $file) {
						$image = new ImageUpload();
						$image->type = 'web_builder_image';
						$image->fullSizePath = $file['name'];
						$image->generateXLargeSize = true;
						$image->generateLargeSize = true;
						$image->generateMediumSize = true;
						$image->generateSmallSize = true;
						$destFileName = $file['name'];
						$destFolder = $structure['fullSizePath']['path'];
						if (!is_dir($destFolder)) {
							if (!mkdir($destFolder, 0755, true)) {
								$result['message'] = 'Could not create directory to upload files';
								if (IPAddress::showDebuggingInformation()) {
									$result['message'] .= " " . $destFolder;
								}
							}
						}
						$destFullPath = $destFolder . '/' . $destFileName;
						if (file_exists($destFullPath)) {
							$image->find(true);
						}

						$image->title = $file['name'];
						$copyResult = copy($file["tmp_name"], $destFullPath);
						if ($copyResult) {
							$image->update();
							$result = [
								'location' => $image->getDisplayUrl('full'),
							];
							break;
						} else {
							$result['message'] = 'Could not save the image to disk';
						}
					}
				} else {
					$result['message'] = 'No file was selected';
				}
			} else {
				$result['message'] = 'You don\'t have the correct permissions to upload an image';
			}
		} else {
			$result['message'] = 'You must be logged in to upload an image';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getUploadImageForm() {
		global $interface;
		$result = [
			'success' => false,
			'message' => 'Unknown error getting upload form',
		];
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::userHasPermission('Administer All Web Content')) {
				$editorName = strip_tags($_REQUEST['editorName']);
				$interface->assign('editorName', $editorName);
				$result = [
					'success' => true,
					'title' => 'Upload an Image',
					'modalBody' => $interface->fetch('WebBuilder/uploadImage.tpl'),
					'modalButtons' => "<button class='tool btn btn-primary' onclick='return AspenDiscovery.WebBuilder.doImageUpload()'>Upload Image</button>",
				];
			} else {
				$result['message'] = 'You don\'t have the correct permissions to upload an image';
			}
		} else {
			$result['message'] = 'You must be logged in to upload an image';
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteCell() {
		$result = [
			'success' => false,
			'message' => 'Unknown error deleting cell',
		];
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::userHasPermission([
				'Administer All Custom Pages',
				'Administer Library Custom Pages',
			])) {
				if (isset($_REQUEST['id'])) {
					require_once ROOT_DIR . '/sys/WebBuilder/PortalCell.php';
					require_once ROOT_DIR . '/sys/WebBuilder/PortalRow.php';
					$portalCell = new PortalCell();
					$portalCell->id = $_REQUEST['id'];
					if ($portalCell->find(true)) {
						//Update the widths of the cells based on the number of cells in the row
						$portalRow = new PortalRow();
						$portalRow->id = $portalCell->portalRowId;
						$portalCell->delete();
						if ($portalRow->find(true)) {
							$portalRow->resizeColumnWidths();
						}
						$result['success'] = true;
						$result['message'] = 'The cell was deleted successfully';
						global $interface;
						$interface->assign('portalRow', $portalRow);
						$result['rowId'] = $portalCell->portalRowId;
						$result['newRow'] = $interface->fetch('DataObjectUtil/portalRow.tpl');
					} else {
						$result['message'] = 'Unable to find that cell, it may have been deleted already';
					}
				} else {
					$result['message'] = 'No cell id was provided';
				}
			} else {
				$result['message'] = 'You don\'t have the correct permissions to delete a cell';
			}
		} else {
			$result['message'] = 'You must be logged in to delete a cell';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteRow() {
		$result = [
			'success' => false,
			'message' => 'Unknown error deleting row',
		];
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::userHasPermission([
				'Administer All Custom Pages',
				'Administer Library Custom Pages',
			])) {
				if (isset($_REQUEST['id'])) {
					require_once ROOT_DIR . '/sys/WebBuilder/PortalRow.php';
					$portalRow = new PortalRow();
					$portalRow->id = $_REQUEST['id'];
					if ($portalRow->find(true)) {
						$portalRow->delete();
						$result['success'] = true;
						$result['message'] = 'The row was deleted successfully';
					} else {
						$result['message'] = 'Unable to find that row, it may have been deleted already';
					}
				} else {
					$result['message'] = 'No row id was provided';
				}
			} else {
				$result['message'] = 'You don\'t have the correct permissions to delete a row';
			}
		} else {
			$result['message'] = 'You must be logged in to delete a row';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function moveRow() {
		$result = [
			'success' => false,
			'message' => 'Unknown error moving row',
		];
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::userHasPermission([
				'Administer All Custom Pages',
				'Administer Library Custom Pages',
			])) {
				if (isset($_REQUEST['rowId'])) {
					require_once ROOT_DIR . '/sys/WebBuilder/PortalPage.php';
					require_once ROOT_DIR . '/sys/WebBuilder/PortalRow.php';
					$portalRow = new PortalRow();
					$portalRow->id = $_REQUEST['rowId'];
					if ($portalRow->find(true)) {
						//Figure out new weights for rows
						$direction = $_REQUEST['direction'];
						$oldWeight = $portalRow->weight;
						if ($direction == 'up') {
							$newWeight = $oldWeight - 1;
						} else {
							$newWeight = $oldWeight + 1;
						}
						$rowToSwap = new PortalRow();
						$rowToSwap->portalPageId = $portalRow->portalPageId;
						$rowToSwap->weight = $newWeight;
						if ($rowToSwap->find(true)) {
							$portalRow->weight = $newWeight;
							$portalRow->update();
							$rowToSwap->weight = $oldWeight;
							$rowToSwap->update();

							$result['success'] = true;
							$result['message'] = 'The row was moved successfully';
							$result['swappedWithId'] = $rowToSwap->id;
						} else {
							if ($direction == 'up') {
								$result['message'] = 'Row is already at the top';
							} else {
								$result['message'] = 'Row is already at the bottom';
							}
						}
					} else {
						$result['message'] = 'Unable to find that row';
					}
				} else {
					$result['message'] = 'No row id was provided';
				}
			} else {
				$result['message'] = 'You don\'t have the correct permissions to move a row';
			}
		} else {
			$result['message'] = 'You must be logged in to move a row';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function moveCell() {
		$result = [
			'success' => false,
			'message' => 'Unknown error moving cell',
		];
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::userHasPermission([
				'Administer All Custom Pages',
				'Administer Library Custom Pages',
			])) {
				if (isset($_REQUEST['cellId'])) {
					require_once ROOT_DIR . '/sys/WebBuilder/PortalRow.php';
					require_once ROOT_DIR . '/sys/WebBuilder/PortalCell.php';
					$portalCell = new PortalCell();
					$portalCell->id = $_REQUEST['cellId'];
					if ($portalCell->find(true)) {
						//Figure out new weights for rows
						$direction = $_REQUEST['direction'];
						$oldWeight = $portalCell->weight;
						if ($direction == 'left') {
							$newWeight = $oldWeight - 1;
						} else {
							$newWeight = $oldWeight + 1;
						}
						$cellToSwap = new PortalCell();
						$cellToSwap->portalRowId = $portalCell->portalRowId;
						$cellToSwap->weight = $newWeight;
						if ($cellToSwap->find(true)) {
							$portalCell->weight = $newWeight;
							$portalCell->update();
							$cellToSwap->weight = $oldWeight;
							$cellToSwap->update();

							$result['success'] = true;
							$result['message'] = 'The cell was moved successfully';
							$result['swappedWithId'] = $cellToSwap->id;
						} else {
							if ($direction == 'left') {
								$result['message'] = 'The cell is already the first cell in the row';
							} else {
								$result['message'] = 'The cell is already the last cell in the row';
							}
						}
					} else {
						$result['message'] = 'Unable to find that cell';
					}
				} else {
					$result['message'] = 'No cell id was provided';
				}
			} else {
				$result['message'] = 'You don\'t have the correct permissions to move a cell';
			}
		} else {
			$result['message'] = 'You must be logged in to move a cell';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function addRow() {
		$result = [
			'success' => false,
			'message' => 'Unknown error adding row',
		];
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::userHasPermission([
				'Administer All Custom Pages',
				'Administer Library Custom Pages',
			])) {
				if (isset($_REQUEST['pageId'])) {
					require_once ROOT_DIR . '/sys/WebBuilder/PortalPage.php';
					require_once ROOT_DIR . '/sys/WebBuilder/PortalRow.php';
					$portalPage = new PortalPage();
					$portalPage->id = $_REQUEST['pageId'];
					if ($portalPage->find(true)) {
						$portalRow = new PortalRow();
						$portalRow->portalPageId = $portalPage->id;
						$portalRow->weight = count($portalPage->getRows());
						$portalRow->insert();
						global $interface;
						$interface->assign('portalRow', $portalRow);

						$result['success'] = true;
						$result['message'] = 'Added a new row';
						$result['newRow'] = $interface->fetch('DataObjectUtil/portalRow.tpl');
					} else {
						$result['message'] = 'Unable to find that page';
					}
				} else {
					$result['message'] = 'No page id was provided';
				}
			} else {
				$result['message'] = 'You don\'t have the correct permissions to add a row';
			}
		} else {
			$result['message'] = 'You must be logged in to add a row';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function addCell() {
		$result = [
			'success' => false,
			'message' => 'Unknown error adding cell',
		];
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::userHasPermission([
				'Administer All Custom Pages',
				'Administer Library Custom Pages',
			])) {
				if (isset($_REQUEST['rowId'])) {
					require_once ROOT_DIR . '/sys/WebBuilder/PortalRow.php';
					require_once ROOT_DIR . '/sys/WebBuilder/PortalCell.php';
					$portalRow = new PortalRow();
					$portalRow->id = $_REQUEST['rowId'];
					if ($portalRow->find(true)) {
						$portalCell = new PortalCell();
						$portalCell->portalRowId = $portalRow->id;
						$portalCell->weight = count($portalRow->getCells());
						$portalCell->widthTiny = 12;
						$portalCell->widthXs = 12;
						$portalCell->widthSm = 12;
						$portalCell->widthMd = 12;
						$portalCell->widthLg = 12;
						$portalCell->insert();

						$portalRow->resizeColumnWidths();

						global $interface;
						$interface->assign('portalCell', $portalCell);
						$interface->assign('portalRow', $portalRow);

						$result['success'] = true;
						$result['message'] = 'Added a new cell';
						$result['newCell'] = $interface->fetch('DataObjectUtil/portalCell.tpl');
						$result['newRow'] = $interface->fetch('DataObjectUtil/portalRow.tpl');
					} else {
						$result['message'] = 'Unable to find that row';
					}
				} else {
					$result['message'] = 'No row id was provided';
				}
			} else {
				$result['message'] = 'You don\'t have the correct permissions to add a cell';
			}
		} else {
			$result['message'] = 'You must be logged in to add a cell';
		}
		return $result;
	}

	function getEditCellForm() {
		$result = [
			'success' => false,
			'message' => 'Unknown error adding cell',
		];
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::userHasPermission([
				'Administer All Custom Pages',
				'Administer Library Custom Pages',
			])) {
				if (isset($_REQUEST['cellId'])) {
					require_once ROOT_DIR . '/sys/WebBuilder/PortalCell.php';
					$portalCell = new PortalCell();
					$portalCell->id = $_REQUEST['cellId'];
					if ($portalCell->find(true)) {
						global $interface;
						$interface->assign('object', $portalCell);
						$interface->assign('structure', PortalCell::getObjectStructure(''));
						$interface->assign('saveButtonText', 'Update');
						$result['success'] = true;
						$result['message'] = 'Display form';
						$result['title'] = 'Edit Cell';
						$result['modalBody'] = $interface->fetch('DataObjectUtil/objectEditForm.tpl');
						$result['modalButtons'] = "<button class='tool btn btn-primary' onclick='AspenDiscovery.WebBuilder.editCell()'>" . translate([
								'text' => 'Update Cell',
								'isAdminFacing' => true,
							]) . "</button>";
					} else {
						$result['message'] = 'Unable to find that cell';
					}
				} else {
					$result['message'] = 'No cell id was provided';
				}
			} else {
				$result['message'] = 'You don\'t have the correct permissions to edit a cell';
			}
		} else {
			$result['message'] = 'You must be logged in to edit a cell';
		}
		return $result;

	}

	/** @noinspection PhpUnused */
	function getHoursAndLocations() {
		//Get a list of locations for the current library
		global $library;
		$tmpLocation = new Location();
		$tmpLocation->libraryId = $library->libraryId;
		$tmpLocation->showInLocationsAndHoursList = 1;
		$tmpLocation->orderBy('isMainBranch DESC, displayName'); // List Main Branches first, then sort by name
		$libraryLocations = [];
		$tmpLocation->find();
		if ($tmpLocation->getNumResults() == 0) {
			//Get all locations
			$tmpLocation = new Location();
			$tmpLocation->showInLocationsAndHoursList = 1;
			$tmpLocation->orderBy('displayName');
			$tmpLocation->find();
		}

		$locationsToProcess = [];
		while ($tmpLocation->fetch()) {
			$locationsToProcess[] = clone $tmpLocation;
		}

		require_once ROOT_DIR . '/sys/Enrichment/GoogleApiSetting.php';
		$googleSettings = new GoogleApiSetting();
		if ($googleSettings->find(true)) {
			$mapsKey = $googleSettings->googleMapsKey;
		} else {
			$mapsKey = null;
		}
		require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
		$parsedown = AspenParsedown::instance();
		$parsedown->setBreaksEnabled(true);
		foreach ($locationsToProcess as $locationToProcess) {
			$mapAddress = urlencode(preg_replace('/\r\n|\r|\n/', '+', $locationToProcess->address));
			$hours = $locationToProcess->getHours();
			foreach ($hours as $key => $hourObj) {
				if (!$hourObj->closed) {
					$hourString = $hourObj->open;
					[
						$hour,
						$minutes,
					] = explode(':', $hourString);
					if ($hour < 12) {
						if ($hour == 0) {
							$hour += 12;
						}
						$hourObj->open = +$hour . ":$minutes AM"; // remove leading zeros in the hour
					} elseif ($hour == 12 && $minutes == '00') {
						$hourObj->open = 'Noon';
					} elseif ($hour == 24 && $minutes == '00') {
						$hourObj->open = 'Midnight';
					} else {
						if ($hour != 12) {
							$hour -= 12;
						}
						$hourObj->open = "$hour:$minutes PM";
					}
					$hourString = $hourObj->close;
					[
						$hour,
						$minutes,
					] = explode(':', $hourString);
					if ($hour < 12) {
						if ($hour == 0) {
							$hour += 12;
						}
						$hourObj->close = "$hour:$minutes AM";
					} elseif ($hour == 12 && $minutes == '00') {
						$hourObj->close = 'Noon';
					} elseif ($hour == 24 && $minutes == '00') {
						$hourObj->close = 'Midnight';
					} else {
						if ($hour != 12) {
							$hour -= 12;
						}
						$hourObj->close = "$hour:$minutes PM";
					}
				}
				$hours[$key] = $hourObj;
			}
			$libraryLocation = [
				'id' => $locationToProcess->locationId,
				'name' => $locationToProcess->displayName,
				'address' => preg_replace('/\r\n|\r|\n/', '<br>', $locationToProcess->address),
				'phone' => $locationToProcess->phone,
				'tty' => $locationToProcess->tty,
				//'map_image' => "http://maps.googleapis.com/maps/api/staticmap?center=$mapAddress&zoom=15&size=200x200&sensor=false&markers=color:red%7C$mapAddress",
				'hours' => $hours,
				'hasValidHours' => $locationToProcess->hasValidHours(),
				'description' => $parsedown->parse($locationToProcess->description),
			];

			if (!empty($mapsKey)) {
				$libraryLocation['map_link'] = "http://maps.google.com/maps?f=q&hl=en&geocode=&q=$mapAddress&ie=UTF8&z=15&iwloc=addr&om=1&t=m&key=$mapsKey";
			}
			$libraryLocations[$locationToProcess->locationId] = $libraryLocation;
		}

		global $interface;
		$interface->assign('libraryLocations', $libraryLocations);
		return $interface->fetch('WebBuilder/libraryHoursAndLocations.tpl');
	}

	/** @noinspection PhpUnused */
	function getWebResource() {
		$result = [
			'success' => false,
			'message' => 'Unknown error getting web resource',
		];
		$resourceId = $_REQUEST['resourceId'];
		require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
		$webResource = new WebResource();
		$webResource->id = $resourceId;
		if ($webResource->find(true)) {
			/** @var Location $locationSingleton */ global $locationSingleton;
			$activeLibrary = $locationSingleton->getActiveLocation();
			$result = [
				'success' => true,
				'url' => $webResource->url,
				'requireLogin' => $webResource->requireLoginUnlessInLibrary == "1" ? true : false,
				'inLibrary' => $activeLibrary != null ? true : false,
				'openInNewTab' => $webResource->openInNewTab == "1" ? true : false,
			];
		} else {
			$result = [
				'success' => false,
				'message' => 'Unable to find requested web resource',
			];
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function trackWebResourceUsage() {
		$id = $_REQUEST['id'];
		$authType = $_REQUEST['authType'];

		require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
		$webResource = new WebResource();
		$webResource->id = $id;
		if ($webResource->find(true)) {
			require_once ROOT_DIR . '/sys/WebBuilder/WebResourceUsage.php';
			$webResourceUsage = new WebResourceUsage();
			$webResourceUsage->year = date('Y');
			$webResourceUsage->month = date('n');
			global $aspenUsage;
			$webResourceUsage->instance = $aspenUsage->instance;
			$webResourceUsage->resourceName = $webResource->name;
			if ($webResourceUsage->find(true)) {
				$webResourceUsage->pageViews++;
				if ($authType == "user") {
					$webResourceUsage->pageViewsByAuthenticatedUsers++;
				} elseif ($authType == "library") {
					$webResourceUsage->pageViewsInLibrary++;
				}
				$webResourceUsage->update();
			} else {
				$webResourceUsage->pageViews++;
				if ($authType == "user") {
					$webResourceUsage->pageViewsByAuthenticatedUsers++;
				} elseif ($authType == "library") {
					$webResourceUsage->pageViewsInLibrary++;
				}
				$webResourceUsage->insert();
			}
		}
	}
}