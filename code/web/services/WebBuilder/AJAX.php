<?php
require_once ROOT_DIR . '/JSON_Action.php';

class WebBuilder_AJAX extends JSON_Action
{
	function getPortalCellValuesForSource() {
		$result = [
			'success' => false,
			'message' => 'Unknown error'
		];

		$sourceType = $_REQUEST['sourceType'];
		switch ($sourceType){
		case 'basic_page':
		case 'basic_page_teaser':
			require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';
			$list = [];
			$list[-1] = 'Select a page';

			$basicPage = new BasicPage();
			$basicPage->orderBy('title');
			$basicPage->find();

			while ($basicPage->fetch()){
				$list[$basicPage->id] = $basicPage->title;
			}

			$result = [
				'success' => true,
				'values' => $list
			];
			break;
		case 'collection_spotlight':
			require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
			$list = [];
			$list[-1] = 'Select a spotlight';

			$collectionSpotlight = new CollectionSpotlight();
			if (UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('contentEditor') || UserAccount::userHasRole('libraryManager') || UserAccount::userHasRole('locationManager')){
				$homeLibrary = Library::getPatronHomeLibrary();
				$collectionSpotlight->whereAdd('libraryId = ' . $homeLibrary->libraryId . ' OR libraryId = -1');
			}
			$collectionSpotlight->orderBy('name');
			$collectionSpotlight->find();
			while ($collectionSpotlight->fetch()){
				$list[$collectionSpotlight->id] = $collectionSpotlight->name;
			}

			$result = [
				'success' => true,
				'values' => $list
			];
			break;
		case 'event_calendar':
		case 'event_spotlight':
		default:
			$result['message'] = 'Unhandled Source Type ' . $sourceType;
		}

		return $result;
	}

	function uploadImage(){
		$result = [
			'success' => false,
			'message' => 'Unknown error uploading image'
		];
		if (UserAccount::isLoggedIn()){
			if (UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('web_builder_admin') || UserAccount::userHasRole('web_builder_creator')){
				$uploadedFiles = array();

				if (! empty($_FILES)) {
					require_once ROOT_DIR . '/sys/File/ImageUpload.php';
					$structure = ImageUpload::getObjectStructure();
					foreach ($_FILES as $file) {
						$image = new ImageUpload();
						$image->type = 'web_builder_image';
						$image->fullSizePath = $file['name'];
						$image->generateMediumSize = true;
						$image->generateSmallSize = true;
						$destFileName = $file['name'];
						$destFolder = $structure['fullSizePath']['path'];
						$destFullPath = $destFolder . '/' . $destFileName;
						if (file_exists($destFullPath)){
							$image->find(true);
						}

						$image->title = $file['name'];
						$copyResult = copy($file["tmp_name"], $destFullPath);
						if ($copyResult) {
							$image->update();
							$result = [
								'success' => true,
								'title' => $image->title,
								'imageUrl' => $image->getDisplayUrl('full')
							];
							break;
						}else{
							$result['message'] = 'Could not save the image to disk';
						}
					}
				}else{
					$result['message'] = 'No file was selected';
				}
			}else{
				$result['message'] = 'You don\'t have the correct permissions to upload an image';
			}
		}else{
			$result['message'] = 'You must be logged in to upload an image';
		}
		return $result;
	}

	function getUploadImageForm(){
		global $interface;
		$results = [
			'success' => false,
			'message' => 'Unknown error getting upload form'
		];
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('web_builder_admin') || UserAccount::userHasRole('web_builder_creator')) {
				$editorName = strip_tags($_REQUEST['editorName']);
				$interface->assign('editorName', $editorName);
				$results = array(
					'success' => true,
					'title' => 'Upload an Image',
					'modalBody' => $interface->fetch('WebBuilder/uploadImage.tpl'),
					'modalButtons' => "<button class='tool btn btn-primary' onclick='return AspenDiscovery.WebBuilder.doImageUpload()'>Upload Image</button>"
				);
			}else {
				$result['message'] = 'You don\'t have the correct permissions to upload an image';
			}
		}else{
			$result['message'] = 'You must be logged in to upload an image';
		}

		return $results;
	}
}