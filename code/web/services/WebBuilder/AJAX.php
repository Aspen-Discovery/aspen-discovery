<?php

use JetBrains\PhpStorm\NoReturn;

require_once ROOT_DIR . '/JSON_Action.php';

class WebBuilder_AJAX extends JSON_Action {
	/** @noinspection PhpUnused */
	function getPortalCellValuesForSource(): array {
		$this->checkRequiredModule('Web Builder');
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Administer All Custom Pages', 'Administer Library Custom Pages',]);
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
				require_once ROOT_DIR . '/sys/WebBuilder/LibraryBasicPage.php';
				$list = [];
				$list['-1'] = 'Select a page';

				$basicPage = new BasicPage();
				$basicPage->orderBy('title ASC, id ASC');

				if (!UserAccount::userHasPermission('Administer All Basic Pages')) {
					$libraryList = Library::getLibraryList(true);
					if (count($libraryList) == 0) {
						$result = [
							'success' => true,
							'values' => $list,
						];
						break;
					}
					$validBasicPageIds = [];
					$libraryBasicPage = new LibraryBasicPage();
					$libraryBasicPage->whereAddIn('libraryId', array_keys($libraryList), false);
					$libraryBasicPage->find();
					while ($libraryBasicPage->fetch()) {
						$validBasicPageIds[$libraryBasicPage->basicPageId] = $libraryBasicPage->basicPageId;
					}
					if (count($validBasicPageIds) == 0) {
						$result = [
							'success' => true,
							'values' => $list,
						];
						break;
					}
					$basicPage->whereAddIn('id', array_keys($validBasicPageIds), true);
				}
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
				$collectionSpotlight->orderBy('name ASC, id ASC');
				if (!UserAccount::userHasPermission('Administer All Collection Spotlights')) {
					$accessibleLibraries = Library::getLibraryList(true);
					if (count($accessibleLibraries) > 0) {
						$collectionSpotlight->whereAddIn('libraryId', array_keys($accessibleLibraries), false);
						$collectionSpotlight->whereAdd('libraryId = -1', 'OR');
					} else {
						$collectionSpotlight->whereAdd('libraryId = -1');
					}
				}
				$collectionSpotlight->find();
				while ($collectionSpotlight->fetch()) {
					$list[$collectionSpotlight->id] = $collectionSpotlight->name;
				}

				$result = [
					'success' => true,
					'values' => $list,
				];
				break;
			case 'hero_slider':
				require_once ROOT_DIR . '/sys/HeroSlider/HeroSliderLocation.php';
				$list = [];
				$list['-1'] = 'Select a hero slider';

				$heroSliderLocation = new HeroSliderLocation();
				$heroSliderLocation->orderBy('name ASC, id ASC');
				if (!UserAccount::userHasPermission('Administer All Hero Sliders')) {
					$accessibleLibraries = Library::getLibraryList(true);
					if (count($accessibleLibraries) > 0) {
						$heroSliderLocation->whereAddIn('libraryId', array_keys($accessibleLibraries), false);
						$heroSliderLocation->whereAdd('libraryId = -1', 'OR');
					} else {
						$heroSliderLocation->whereAdd('libraryId = -1');
					}
				}
				$heroSliderLocation->find();
				while ($heroSliderLocation->fetch()) {
					$list[$heroSliderLocation->id] = $heroSliderLocation->name;
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
				$customForm->orderBy('title ASC, id ASC');
				if (!UserAccount::userHasPermission('Administer All Custom Forms')) {
					$libraryCustomForm = new LibraryCustomForm();
					$userLibrary = Library::getPatronHomeLibrary();
					$additionalAdminLocations = UserAccount::getActiveUserObj()->getAdditionalAdministrationLocations();

					$validLibraryIds = [];

					if ($userLibrary) {
						$validLibraryIds[] = $userLibrary->libraryId;
					}


					if (!empty($additionalAdminLocations)) {
						foreach ($additionalAdminLocations as $locationId => $locationName) {
							$library = Library::getLibraryForLocation($locationId);
							if ($library) {
								$validLibraryIds[] = $library->libraryId;
							}
						}
					}


					if (count($validLibraryIds) > 0) {

						$libraryCustomForm->whereAddIn('libraryId', $validLibraryIds, true);


						$validCustomForms = [];
						$libraryCustomForm->find();
						while ($libraryCustomForm->fetch()) {
							$validCustomForms[] = $libraryCustomForm->formId;
						}

						if (count($validCustomForms) > 0) {
							$customForm->whereAddIn('id', $validCustomForms, true);
						} else {
							$result = [
								'success' => true,
								'values' => $list,
							];
							break;
						}
					} else {
						$result = [
							'success' => false,
							'message' => 'Unable to determine the home library for this user.',
						];
						break;
					}
				}
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
				if (!UserAccount::userHasPermission('Administer All Web Content') && (UserAccount::userHasPermission('Administer Web Content for Home Library'))) {
					$libraryList = Library::getLibraryList(true);
					$object->whereAddIn("owningLibrary", array_keys($libraryList), false, "OR");
					$object->whereAdd("owningLibrary = -1", "OR");
					$object->whereAdd("sharing = 2 OR sharing = 3", "OR");
					if (Library::getLibraryList(true)){
						$object->whereAdd("sharing = 1 AND sharedWithLibrary IN (" . implode(",", array_keys($libraryList)) . ")", "OR");
					}
				}
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
				if (!UserAccount::userHasPermission('Administer All Web Content') && (UserAccount::userHasPermission('Administer Web Content for Home Library'))) {
					$libraryList = Library::getLibraryList(true);
					$object->whereAddIn("owningLibrary", array_keys($libraryList), false, "OR");
					$object->whereAdd("owningLibrary = -1", "OR");
					$object->whereAdd("sharing = 2 OR sharing = 3", "OR");
					if (Library::getLibraryList(true)){
						$object->whereAdd("sharing = 1 AND sharedWithLibrary IN (" . implode(",", array_keys($libraryList)) . ")", "OR");
					}
				}
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
				require_once ROOT_DIR . '/sys/WebBuilder/LibraryWebResource.php';
				$list = [];
				$list['-1'] = 'Select a web resource';
				$object = new WebResource();
				$object->orderBy('name ASC, id ASC');
				if (!UserAccount::userHasPermission('Administer All Web Resources')) {
					$libraryList = Library::getLibraryList(true);
					if (count($libraryList) == 0) {
						$result = [
							'success' => true,
							'values' => $list,
						];
						break;
					}
					$validResourceIds = [];
					$libraryWebResource = new LibraryWebResource();
					$libraryWebResource->whereAddIn('libraryId', array_keys($libraryList), false);
					$libraryWebResource->find();
					while ($libraryWebResource->fetch()) {
						$validResourceIds[$libraryWebResource->webResourceId] = $libraryWebResource->webResourceId;
					}
					if (count($validResourceIds) == 0) {
						$result = [
							'success' => true,
							'values' => $list,
						];
						break;
					}
					$object->whereAddIn('id', array_keys($validResourceIds), true);
				}
				$object->find();
				while ($object->fetch()) {
					$list[$object->id] = $object->name;
				}
				$result = [
					'success' => true,
					'values' => $list,
				];
				break;
			case 'quick_poll':
				require_once ROOT_DIR . '/sys/WebBuilder/QuickPoll.php';
				require_once ROOT_DIR . '/sys/WebBuilder/LibraryQuickPoll.php';
				$list = [];
				$list['-1'] = 'Select a quick poll';
				$object = new QuickPoll();
				$object->orderBy('title ASC, id ASC');
				if (!UserAccount::userHasPermission('Administer All Quick Polls')) {
					$libraryList = Library::getLibraryList(true);
					if (count($libraryList) == 0) {
						$result = [
							'success' => true,
							'values' => $list,
						];
						break;
					}
					$validPollIds = [];
					$libraryQuickPoll = new LibraryQuickPoll();
					$libraryQuickPoll->whereAddIn('libraryId', array_keys($libraryList), false);
					$libraryQuickPoll->find();
					while ($libraryQuickPoll->fetch()) {
						$validPollIds[$libraryQuickPoll->pollId] = $libraryQuickPoll->pollId;
					}
					if (count($validPollIds) == 0) {
						$result = [
							'success' => true,
							'values' => $list,
						];
						break;
					}
					$object->whereAddIn('id', array_keys($validPollIds), true);
				}
				$object->find();
				while ($object->fetch()) {
					$list[$object->id] = $object->title;
				}
				$result = [
					'success' => true,
					'values' => $list,
				];
				break;
			case 'custom_web_resource_page':
				require_once ROOT_DIR . '/sys/WebBuilder/CustomWebResourcePage.php';
				require_once ROOT_DIR . '/sys/WebBuilder/LibraryCustomWebResourcePage.php';
				$list = [];
				$list['-1'] = 'Select a custom web resource page';
				$object = new CustomWebResourcePage();
				$object->orderBy('title ASC, id ASC');
				if (!UserAccount::userHasPermission('Administer All Custom Web Resource Pages')) {
					$libraryList = Library::getLibraryList(true);
					if (count($libraryList) == 0) {
						$result = [
							'success' => true,
							'values' => $list,
						];
						break;
					}
					$validPageIds = [];
					$libraryCustomWebResourcePage = new LibraryCustomWebResourcePage();
					$libraryCustomWebResourcePage->whereAddIn('libraryId', array_keys($libraryList), false);
					$libraryCustomWebResourcePage->find();
					while ($libraryCustomWebResourcePage->fetch()) {
						$validPageIds[$libraryCustomWebResourcePage->customResourcePageId] = $libraryCustomWebResourcePage->customResourcePageId;
					}
					if (count($validPageIds) == 0) {
						$result = [
							'success' => true,
							'values' => $list,
						];
						break;
					}
					$object->whereAddIn('id', array_keys($validPageIds), true);
				}
				$object->find();
				while ($object->fetch()) {
					$list[$object->id] = $object->title;
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
	function getSourceValuesForPlacard(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Administer All Web Resources', 'Administer Library Web Resources','Administer All Placards', 'Administer Library Placards']);

		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];

		$sourceType = $_REQUEST['sourceType'];
		switch ($sourceType) {
			case 'none':
				$result = [
					'success' => true,
				];
				break;
			case 'web_resource':
				require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
				require_once ROOT_DIR . '/sys/WebBuilder/LibraryWebResource.php';
				$list = [];
				$list['-1'] = 'Select a web resource';
				$object = new WebResource();
				$object->orderBy('name ASC, id ASC');
				if (!UserAccount::userHasPermission('Administer All Web Resources')) {
					$libraryList = Library::getLibraryList(true);
					if (count($libraryList) == 0) {
						$result = [
							'success' => true,
							'values' => $list,
						];
						break;
					}
					$validResourceIds = [];
					$libraryWebResource = new LibraryWebResource();
					$libraryWebResource->whereAddIn('libraryId', array_keys($libraryList), false);
					$libraryWebResource->find();
					while ($libraryWebResource->fetch()) {
						$validResourceIds[$libraryWebResource->webResourceId] = $libraryWebResource->webResourceId;
					}
					if (count($validResourceIds) == 0) {
						$result = [
							'success' => true,
							'values' => $list,
						];
						break;
					}
					$object->whereAddIn('id', array_keys($validResourceIds), true);
				}
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

		$placardId = $_REQUEST['placardId'];
		$result['selected'] = '-1';
		if (!empty($placardId)) {
			require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
			$placard = new Placard();
			$placard->id = $placardId;
			if ($placard->find(true)) {
				if ($placard->sourceType == $sourceType) {
					$result['selected'] = $placard->sourceId;
				}
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function checkLinkedObject() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Administer All Web Resources', 'Administer Library Web Resources']);

		if (!empty($_REQUEST['objectId']) && is_numeric($_REQUEST['objectId'])) {
			require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
			$placard = new Placard();
			$placard->sourceId = $_REQUEST['objectId'];
			if ($placard->find(true)) {
				$result = [
					'success' => true,
					'title' => translate([
						'text' => 'Save Linked Object?',
						'isAdminFacing' => true,
					]),
					'modalBody' => translate([
						'text' => 'This Web Resource is linked to a Placard. Would you like to update the linked placard as well?',
						'isAdminFacing' => true,
					]),
					'modalButtons' => "<button class='tool btn btn-primary modal-btn' onclick='return AspenDiscovery.WebBuilder.saveLinkedObject(true)'>Yes</button><button class='tool btn btn-primary modal-btn' onclick='return AspenDiscovery.WebBuilder.saveLinkedObject(false)'>No</button>",
				];
			} else {
				$result = [
					'success' => true,
					'noLinkedObject' => true,
				];
			}
		} else {
			$result = [
				'success' => true,
				'noLinkedObject' => true,
			];
		}

		return $result;

	}

	/** @noinspection PhpUnused */
	function saveLinkedObject() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Administer All Placards', 'Administer Library Placards']);

		//Save Linked Placard
		require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
		$fileType = substr($_REQUEST['image'], 0, -3);
		$fileType = match ($fileType) {
			'gif' => ".gif",
			'png' => ".png",
			'svg' => ".svg",
			default => ".jpg",
		};
		$placard = new Placard();
		$placard->sourceId = $_REQUEST['objectId'];
		if ($placard->find(true)) {
			if ($_REQUEST['doFullSave'] == "true"){
				$placard->title = $_REQUEST['objectName'];
				$placard->image = "web_resource_image_".$_REQUEST['objectId'].$fileType;
				$placard->link = $_REQUEST['url'];
				$placard->body = $_REQUEST['body'];
				$placard->isCustomized = 0;
			} else {
				if ($placard->title != $_REQUEST['objectName'] || $placard->image != $_REQUEST['image'] || $placard->link != $_REQUEST['url'] || $placard->body != $_REQUEST['body']) {
					$placard->isCustomized = 1;
				} else{
					$placard->isCustomized = 0;
				}
			}
			$placard->update('',false);
			$result = [
				'success' => true,
			];
		}else{
			$result = [
				'success' => false,
				'message' => 'Could not find the placard to update.'
			];
		}
		return $result;
	}
	/** @noinspection PhpUnused */
	function uploadImage(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Administer All Web Content', 'Administer Web Content for Home Library']);

		$result = [
			'success' => false,
			'message' => 'Unknown error uploading image',
		];
		if (!empty($_FILES)) {
			require_once ROOT_DIR . '/sys/File/ImageUpload.php';
			$structure = ImageUpload::getObjectStructure();
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
		return $result;
	}

	/** @noinspection PhpUnused */
	function uploadImageTinyMCE() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Administer All Web Content', 'Administer Web Content for Home Library']);
		if (!empty($_FILES)) {
			require_once ROOT_DIR . '/sys/File/ImageUpload.php';
			$structure = ImageUpload::getObjectStructure();
			$result = [
				'success' => true,
				'message' => ''
			];
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
		return $result;
	}

	/** @noinspection PhpUnused */
	function getUploadImageForm() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Administer All Web Content', 'Administer Web Content for Home Library']);

		global $interface;
		$editorName = strip_tags($_REQUEST['editorName']);
		$interface->assign('editorName', $editorName);
		return [
			'success' => true,
			'title' => 'Upload an Image',
			'modalBody' => $interface->fetch('WebBuilder/uploadImage.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='return AspenDiscovery.WebBuilder.doImageUpload()'>Upload Image</button>",
		];
	}

	/** @noinspection PhpUnused */
	function getAddQuickPollOptionForm() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Administer All Quick Polls', 'Administer Library Quick Polls']);

		global $interface;
		$result = [
			'success' => false,
		];
		$pollId = $_REQUEST['pollId'];
		if (!is_numeric($pollId)) {
			$result['message'] = 'Invalid poll provided';
		} else {
			require_once ROOT_DIR . '/sys/WebBuilder/QuickPoll.php';
			$quickPoll = new QuickPoll();
			$quickPoll->id = $pollId;
			if ($quickPoll->find(true)) {
				$interface->assign('pollId', $pollId);
				if ($quickPoll->allowSuggestingNewOptions) {
					$interface->assign('poll', $quickPoll);
					$interface->assign('saveButtonText', 'Add Option');
					$result['success'] = true;
					$result['message'] = translate(['text'=>'Display form', 'isPublicFacing'=>true]);
					$result['title'] = translate(['text'=>'Add Poll Option', 'isPublicFacing'=>true]);
					$result['modalBody'] = $interface->fetch('WebBuilder/addQuickPollOptionForm.tpl');
					$result['modalButtons'] = "<button class='tool btn btn-primary' onclick=\"AspenDiscovery.WebBuilder.addQuickPollOption('$pollId')\">" . translate([
							'text' => 'Add Option',
							'isPublicFacing' => true,
						]) . "</button>";
				} else {
					$result['message'] = translate(['text'=>'This poll does not allow new options to be added', 'isPublicFacing'=>true]);
				}
			} else {
				$result['message'] = translate(['text'=>'Invalid poll provided', 'isPublicFacing'=>true]);
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function addQuickPollOption() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission(['Administer All Quick Polls', 'Administer Library Quick Polls']);

		global $interface;
		$result = [
			'success' => false,
		];
		$pollId = $_REQUEST['pollId'];
		if (!is_numeric($pollId)) {
			$result['message'] = 'Invalid poll provided';
		} else {
			require_once ROOT_DIR . '/sys/WebBuilder/QuickPoll.php';
			$quickPoll = new QuickPoll();
			$quickPoll->id = $pollId;
			if ($quickPoll->find(true)) {
				$interface->assign('pollId', $pollId);
				$interface->assign('poll', $quickPoll);
				if ($quickPoll->allowSuggestingNewOptions) {
					//Get the text of the option
					$newOption = $_REQUEST['newOption'];

					require_once ROOT_DIR . '/sys/LocalEnrichment/BadWord.php';
					$badWords = new BadWord();
					//Make sure the new option is valid
					if (isSpammySearchTerm($newOption)) {
						$result['message'] = translate(['text'=>'Invalid option. Options can be plain text only', 'isPublicFacing'=>true]);
					} else if (!preg_match_all('/^[a-zA-Z0-9\s?.-]*$/',$newOption)){
						$result['message'] = translate(['text'=>'Invalid option. Options can be plain text only', 'isPublicFacing'=>true]);
					} else if ($badWords->hasBadWords($newOption)) {
						$result['message'] = translate(['text'=>'Invalid option. Option must meet our guidelines.', 'isPublicFacing'=>true]);
					} else {
						$pollOption = new QuickPollOption();
						$pollOption->pollId = $pollId;
						$pollOption->label = $newOption;
						if ($pollOption->find(true)) {
							$result['message'] = translate(['text'=>'That option already exists.', 'isPublicFacing'=>true]);
						} else {
							$pollOption->insert();
							$interface->assign('pollOption', $pollOption);
							$result['success'] = true;
							$result['message'] = translate(['text'=>'The option was created successfully.', 'isPublicFacing'=>true]);
							$result['formattedOption'] = $interface->fetch('WebBuilder/quickPollOption.tpl');
						}
					}
				} else {
					$result['message'] = translate(['text'=>'This poll does not allow new options to be added', 'isPublicFacing'=>true]);
				}
			} else {
				$result['message'] = translate(['text'=>'Invalid poll provided', 'isPublicFacing'=>true]);
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteCell(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission([
			'Administer All Custom Pages',
			'Administer Library Custom Pages',
		]);
		$result = [
			'success' => false,
		];

		if (isset($_REQUEST['id'])) {
			require_once ROOT_DIR . '/sys/WebBuilder/PortalCell.php';
			require_once ROOT_DIR . '/sys/WebBuilder/PortalRow.php';
			$portalCell = new PortalCell();
			$portalCell->id = $_REQUEST['id'];
			if ($portalCell->find(true)) {
				// Update the widths of the cells based on the number of cells in the row.
				$portalRow = new PortalRow();
				$portalRow->id = $portalCell->portalRowId;
				$portalCell->delete();
				if ($portalRow->find(true)) {
					$portalRow->resizeColumnWidths();
				}
				$result['success'] = true;
				//$result['message'] = 'The cell was deleted successfully.';
				global $interface;
				$interface->assign('portalRow', $portalRow);
				$interface->assign('inPageEditor', false);
				$result['rowId'] = $portalCell->portalRowId;
				$result['newRow'] = $interface->fetch('DataObjectUtil/portalRow.tpl');
			} else {
				$result['message'] = 'Unable to find that cell; it may have been already deleted.';
			}
		} else {
			$result['message'] = 'No cell id was provided.';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteRow(): array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission([
			'Administer All Custom Pages',
			'Administer Library Custom Pages',
		]);
		$result = [
			'success' => false,
		];

		if (isset($_REQUEST['id'])) {
			require_once ROOT_DIR . '/sys/WebBuilder/PortalRow.php';
			$portalRow = new PortalRow();
			$portalRow->id = $_REQUEST['id'];
			if ($portalRow->find(true)) {
				$portalRow->delete();
				$result['success'] = true;
				//$result['message'] = 'The row was deleted successfully.';
			} else {
				$result['message'] = 'Unable to find that row; it may have been already deleted.';
			}
		} else {
			$result['message'] = 'No row id was provided.';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function moveRow() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission([
			'Administer All Custom Pages',
			'Administer Library Custom Pages',
		]);
		$result = [
			'success' => false,
		];

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
		return $result;
	}

	/** @noinspection PhpUnused */
	function moveCell() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission([
			'Administer All Custom Pages',
			'Administer Library Custom Pages',
		]);
		$result = [
			'success' => false,
		];

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
		return $result;
	}

	/** @noinspection PhpUnused */
	function addRow() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission([
			'Administer All Custom Pages',
			'Administer Library Custom Pages',
		]);
		$result = [
			'success' => false,
		];

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
				$interface->assign('inPageEditor', true);

				$result['success'] = true;
				$result['message'] = 'Added a new row';
				$result['newRow'] = $interface->fetch('DataObjectUtil/portalRow.tpl');
			} else {
				$result['message'] = 'Unable to find that page';
			}
		} else {
			$result['message'] = 'No page id was provided';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function addCell() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission([
			'Administer All Custom Pages',
			'Administer Library Custom Pages',
		]);
		$result = [
			'success' => false,
		];

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
				$interface->assign('inPageEditor', true);
				$result['newCell'] = $interface->fetch('DataObjectUtil/portalCell.tpl');
				$result['newRow'] = $interface->fetch('DataObjectUtil/portalRow.tpl');
			} else {
				$result['message'] = 'Unable to find that row';
			}
		} else {
			$result['message'] = 'No row id was provided';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function getEditCellForm() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission([
			'Administer All Custom Pages',
			'Administer Library Custom Pages',
		]);
		$result = [
			'success' => false,
		];

		if (isset($_REQUEST['cellId'])) {
			require_once ROOT_DIR . '/sys/WebBuilder/PortalCell.php';
			$portalCell = new PortalCell();
			$portalCell->id = $_REQUEST['cellId'];
			if ($portalCell->find(true)) {
				global $interface;
				$interface->assign('object', $portalCell);
				$interface->assign('structure', PortalCell::getObjectStructure());
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
		return $result;

	}

	/** @noinspection PhpUnused */
	function getHoursAndLocations() : string {
		//Get a list of locations for the current library
		global $library;
		global $configArray;
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
			$parentLibrary = $locationToProcess->getParentLibrary();
			$libraryLocation = [
				'id' => $locationToProcess->locationId,
				'name' => $locationToProcess->displayName,
				'address' => preg_replace('/\r\n|\r|\n/', '<br>', $locationToProcess->address),
				'phone' => $locationToProcess->phone,
				'tty' => $locationToProcess->tty,
				'email' => $locationToProcess->contactEmail,
				//'map_image' => "http://maps.googleapis.com/maps/api/staticmap?center=$mapAddress&zoom=15&size=200x200&sensor=false&markers=color:red%7C$mapAddress",
				'hours' => $hours,
				'hasValidHours' => $locationToProcess->hasValidHours(),
				'description' => $parsedown->parse($locationToProcess->description),
				'image' => $locationToProcess->locationImage ? $configArray['Site']['url'] . '/files/original/' . $locationToProcess->locationImage : null,
				'longitude' => floatval($locationToProcess->longitude),
				'latitude' => floatval($locationToProcess->latitude),
				'homeLink' => (!empty($locationToProcess->homeLink) && $locationToProcess->homeLink !== 'default') ? $locationToProcess->homeLink : ((!empty($parentLibrary->homeLink) && $parentLibrary->homeLink !== 'default') ? $parentLibrary->homeLink : null),
				'hoursMessage' => Location::getLibraryHoursMessage($locationToProcess->locationId, true),
			];

			if (!empty($mapsKey)) {
				$libraryLocation['map_link'] = "https://maps.google.com/maps?f=q&hl=en&geocode=&q=$mapAddress&ie=UTF8&z=15&iwloc=addr&om=1&t=m&key=$mapsKey";
			}
			$libraryLocations[$locationToProcess->locationId] = $libraryLocation;
		}

		global $interface;
		$interface->assign('libraryLocations', $libraryLocations);
		return $interface->fetch('WebBuilder/libraryHoursAndLocations.tpl');
	}

	/** @noinspection PhpUnused */
	function getWebResource(): array {
		$result = [
			'success' => false,
			'message' => 'Unknown error getting web resource',
		];
		$resourceId = $_REQUEST['resourceId'];
		require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
		$webResource = new WebResource();
		$webResource->id = $resourceId;
		if ($webResource->find(true)) {
			/** @var Location $locationSingleton */
			global $locationSingleton;
			$activeLocation = $locationSingleton->getActiveLocation();
			$canView = $webResource->canView();
			$libraryId = null;
			$activeLibrary = Library::getActiveLibrary();
			if ($activeLibrary != null) {
				$libraryId = $activeLibrary->libraryId;
			}

			$result = [
				'success' => true,
				'url' => $webResource->getUrlForLibrary($libraryId),
				'requireLogin' => $webResource->requireLoginUnlessInLibrary == "1",
				'inLibrary' => $activeLocation != null,
				'openInNewTab' => $webResource->openInNewTab == "1",
				'canView' => $canView,
				'userNoAccessTitle' => translate(['text' => 'Access Not Allowed', 'isPublicFacing' => true]),
				'userNoAccessMessage' => translate(['text' => 'Your library does not provide access to this resource.', 'isPublicFacing' => true]),
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
	function trackWebResourceUsage() : void {
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
			$webResourceUsage->instance = $aspenUsage->getInstance();
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

	/** @noinspection PhpUnused */
	function saveAsTemplate() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission([
			'Administer All Grapes Pages',
			'Administer Library Custom Pages'
		]);

		require_once ROOT_DIR . '/sys/WebBuilder/GrapesTemplate.php';

		try {
			$newGrapesPageContent = json_decode(file_get_contents("php://input"), true);
			$templateId = $newGrapesPageContent['templateId'];
			$html = $newGrapesPageContent['html'];
			$css = $newGrapesPageContent['css'];
			$projectData = json_encode($newGrapesPageContent['projectData']);

			$template = new GrapesTemplate();
			$template->id = $templateId;

			if (!$template->find(true)) {
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true
					]),
					'message' => translate([
						'text' => "Template with ID $templateId not found. Unable to update.",
						'isPublicFacing' => true
					])
				];
			}
			$template->templateContent = $projectData;
			$template->htmlData = $html;
			$template->cssData = $css;

			if(!$template->update()) {
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true
					]),
					'message' => translate([
						'text' => 'Failed to update the template.',
						'isPublicFacing' => true
					])
				];
			}
			return [ 
				'success' => true,
				'title' => translate([
					'text' => 'Success',
					'isPublicFacing' => true
				]),
					'message' => translate([
					'text' => 'Template saved successfully.',
					'isPublicFacing' => true
				])
			];
		} catch (Exception $e) {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'An unexpected error occurred: ' . $e->getMessage(),
					'isPublicFacing' => true
				])
			];
		}
	}

	/** @noinspection PhpUnused */
	function saveAsPage() : array {
		$this->requireLoggedInUser();
		$this->checkRequiredPermission([
			'Administer All Grapes Pages',
			'Administer Library Custom Pages'
		]);

		require_once ROOT_DIR .  '/sys/WebBuilder/GrapesPage.php';
		try {
			$newGrapesPageContent = json_decode(file_get_contents("php://input"), true);
			$grapesPageId = $newGrapesPageContent['grapesPageId'];
			$grapesGenId = $newGrapesPageContent['grapesGenId'];
			$html = $newGrapesPageContent['html'];
			$css = $newGrapesPageContent['css'];
			$grapesPage = new GrapesPage();
			$grapesPage->id = $grapesPageId;	
			$projectData = json_encode($newGrapesPageContent['projectData']);

			if (!$grapesPage->find(true)) {
				return [ 
					'success' =>false,
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true
					]),
					'message' => translate([
						'text' => "Page with ID $grapesPageId not found. Unable to update.",
						'isPublicFacing' => true
					])
				];
			}
			$grapesPage->grapesGenId = $grapesGenId;
			$grapesPage->templateContent = $projectData;
			$grapesPage->htmlData = $html;
			$grapesPage->cssData = $css;

			if (!$grapesPage->update()) {
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isPublicFacing' => true
					]),
					'message' => translate([
						'text' => 'Failed to update the page.',
						'isPublicFacing' => true
					])
				];
			}
			return [
				'success' => true,
				'title' => translate([
					'text' => 'Success',
					'isPublicFacing' => true
				]),
					'message' => translate([
					'text' => 'Page saved successfully.',
					'isPublicFacing' => true
				])
			];
		} catch (Exception $e) {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true
				]),
				'message' => translate([
					'text' => 'An unexpected error occurred: ' . $e->getMessage(),
					'isPublicFacing' => true
				])
			];
		}
	}

	/** @noinspection PhpUnused */
	#[NoReturn]
	function loadGrapesPage() : void {
		require_once ROOT_DIR . '/sys/WebBuilder/GrapesPage.php';
		require_once ROOT_DIR . '/sys/WebBuilder/GrapesTemplate.php';

		$grapesPageId = $_GET['id'];
		$response = [];

		$grapesPage = new GrapesPage();
		$grapesPage->id = $grapesPageId;

		if ($grapesPage->find(true)) {
			if(empty($grapesPage->templateContent) && $grapesPage->templatesSelect !== null) {
				$template = new GrapesTemplate();
				$template->id = $grapesPage->templatesSelect;

				if ($template->find(true)) {
					$response['success'] = true;
					$response['html'] = $template->htmlData;
					$response['css'] = $template->cssData;
					$response['projectData'] = json_decode($template->templateContent, true);

					$grapesPage->templateContent = json_decode($template->templateContent, true);
					$grapesPage->htmlData = $template->htmlData;
					$grapesPage->cssData = $template->cssData;
					$grapesPage->update();
				} else {
					$response['success'] = false;
					$response['message'] = 'Template not found';
				}
			} elseif (!empty($grapesPage->templateContent)) {
				$response['success'] = true;
				$response['html'] = $grapesPage->htmlData;
				$response['css'] = $grapesPage->cssData;
				$response['projectData'] = json_decode($grapesPage->templateContent, true);
			} else {
				$response['success'] = false;
				$response['message'] = 'Template Content and Template Select are both empty';
			}
		} else {
			$response['success'] = false;
			$response['message'] = 'Page not found';
		}
		echo json_encode($response);
		exit();
	  }

	/** @noinspection PhpUnused */
	#[NoReturn]
	function loadGrapesTemplate() : void {
		require_once ROOT_DIR . '/sys/WebBuilder/GrapesTemplate.php';

		$templateId = $_GET['id'];
		$response = [];

		$template = new GrapesTemplate();
		$template->id = $templateId;

		if ($template->find(true)) {
			if ($template->htmlData !== null && $template->cssData !== null) {
				$response['success'] = true;
				$response['html'] = $template->htmlData;
				$response['css'] = $template->cssData;
				$response['projectData'] = json_decode($template->templateContent, true);
			} else {
				$response['success'] = false;
				$response['message'] = 'Template has no data.';
			}
		} else {
			$response['success'] = false;
			$response['message'] = 'Template not found.';
		}
		echo json_encode($response);
		exit();
	}

}