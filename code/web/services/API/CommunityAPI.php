<?php

require_once ROOT_DIR . '/Action.php';

class CommunityAPI extends Action {
	function launch() {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';

		global $activeLanguage;
		if (isset($_GET['language'])) {
			$language = new Language();
			$language->code = $_GET['language'];
			if ($language->find(true)) {
				$activeLanguage = $language;
			}
		}

		//Make sure the user can access the API based on the IP address
		if (!IPAddress::allowAPIAccessForClientIP()) {
			$this->forbidAPIAccess();
		}

		header('Content-type: application/json');
		//header('Content-type: text/html');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		if (method_exists($this, $method)) {
			$result = $this->$method();
			$output = json_encode($result);
			require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
			APIUsage::incrementStat('CommunityAPI', $method);
			ExternalRequestLogEntry::logRequest('CommunityAPI.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], $output, []);
		} else {
			$output = json_encode(['error' => 'invalid_method']);
		}
		echo $output;
	}

	/** @noinspection PhpUnused */
	public function addTranslationTerm(): array {
		$translationTerm = new TranslationTerm();
		$translationTerm->term = $_REQUEST['term'];
		if (!$translationTerm->find(true)) {
			$translationTerm->isPublicFacing = $_REQUEST['isPublicFacing'];
			$translationTerm->isAdminFacing = $_REQUEST['isAdminFacing'];
			$translationTerm->isMetadata = $_REQUEST['isMetadata'];
			$translationTerm->isAdminEnteredData = $_REQUEST['isAdminEnteredData'];
			$translationTerm->lastUpdate = time();
			try {
				$translationTerm->insert();
				$result = [
					'success' => true,
					'message' => translate([
						'text' => 'The term was added.',
						'isAdminFacing' => true,
					]),
				];
			} catch (Exception $e) {
				$result = [
					'success' => false,
					'message' => translate([
						'text' => 'Could not update term. %1%',
						'isAdminFacing' => true,
						1 => (string)$e,
					]),
				];
			}
		} else {
			$termChanged = false;
			if ($_REQUEST['isPublicFacing'] && !$translationTerm->isPublicFacing) {
				$translationTerm->isPublicFacing = $_REQUEST['isPublicFacing'];
				$termChanged = true;
			}
			if ($_REQUEST['isAdminFacing'] && !$translationTerm->isAdminFacing) {
				$translationTerm->isAdminFacing = $_REQUEST['isAdminFacing'];
				$termChanged = true;
			}
			if ($_REQUEST['isAdminFacing'] && !$translationTerm->isMetadata) {
				$translationTerm->isMetadata = $_REQUEST['isAdminFacing'];
				$termChanged = true;
			}
			if ($_REQUEST['isAdminEnteredData'] && !$translationTerm->isAdminEnteredData) {
				$translationTerm->isAdminEnteredData = $_REQUEST['isAdminEnteredData'];
				$termChanged = true;
			}
			if ($termChanged) {
				$translationTerm->lastUpdate = time();
				$translationTerm->update();
				$result = [
					'success' => true,
					'message' => translate([
						'text' => 'The term was updated.',
						'isAdminFacing' => true,
					]),
				];
			} else {
				$result = [
					'success' => true,
					'message' => translate([
						'text' => 'The term already existed.',
						'isAdminFacing' => true,
					]),
				];
			}
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	public function getDefaultTranslation() {
		$result = [
			'success' => false,
		];
		if (!empty($_REQUEST['term']) && !empty($_REQUEST['languageCode'])) {
			$translationTerm = new TranslationTerm();
			$translationTerm->term = $_REQUEST['term'];
			if ($translationTerm->find(true)) {
				$language = new Language();
				$language->code = $_REQUEST['languageCode'];
				if ($language->find(true)) {
					$translation = new Translation();
					$translation->termId = $translationTerm->id;
					$translation->languageId = $language->id;
					if ($translation->find(true)) {
						if ($translation->translated) {
							if (!empty($translationTerm->defaultText) && ($translationTerm->defaultText == $translation->translation)) {
								$result['message'] = 'Translation matches the original default text';
							} else if ($translationTerm->term == $translation->translation) {
								$result['message'] = 'Translation matches the original text';
							} else {
								$result['success'] = true;
								$result['translation'] = $translation->translation;
							}
						} else{
							$result['message'] = 'Term has not been translated yet';
						}
					} else {
						$result['message'] = 'No translation found';
					}
				} else {
					$result['message'] = 'Could not find language';
				}
			} else {
				$result['message'] = 'Could not find term';
			}
		} else {
			$result['message'] = 'Term and/or languageCode not provided';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	public function setTranslation() {
		$result = [
			'success' => false,
		];
		if (!empty($_REQUEST['term']) && !empty($_REQUEST['languageCode']) && !empty($_REQUEST['translation'])) {
			$translationTerm = new TranslationTerm();
			$translationTerm->term = $_REQUEST['term'];
			if ($translationTerm->find(true)) {
				$language = new Language();
				$language->code = $_REQUEST['languageCode'];
				if ($language->find(true)) {
					$translation = new Translation();
					$translation->termId = $translationTerm->id;
					$translation->languageId = $language->id;
					if ($translation->find(true)) {
						if (!$translation->translated) {
							$translation->translation = $_REQUEST['translation'];
							$translation->translated = 1;
							$translation->update();
							$result['success'] = true;
						} else {
							$result['message'] = 'Term already translated';
						}
					} else {
						$result['message'] = 'No translation found';
					}
				} else {
					$result['message'] = 'Could not find language';
				}
			} else {
				$result['message'] = 'Could not find term';
			}
		} else {
			$result['message'] = 'Term, languageCode, and/or translation  not provided';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function addSharedContent(): array {
		$result = [
			'success' => false,
		];

		require_once ROOT_DIR . '/sys/Community/SharedContent.php';
		$sharedContent = new SharedContent();
		$sharedContent->name = $_REQUEST['name'];
		$sharedContent->type = $_REQUEST['type'];
		$sharedContent->description = $_REQUEST['description'];
		$sharedContent->shareDate = time();
		$sharedContent->sharedFrom = $_REQUEST['sharedFrom'];
		$sharedContent->sharedByUserName = $_REQUEST['sharedByUserName'];
		$sharedContent->data = $_REQUEST['data'];
		if ($sharedContent->insert()) {
			$result['success'] = true;
		} else {
			$result['message'] = $sharedContent->getLastError();
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function searchSharedContent(): array {
		$result = [
			'numResults' => 0,
			'results' => [],
			'success' => true,
		];

		$objectType = $_REQUEST['objectType'];

		require_once ROOT_DIR . '/sys/Community/SharedContent.php';
		$sharedContent = new SharedContent();
		$sharedContent->type = $objectType;
		$sharedContent->approved = 1;
		if (!empty($_REQUEST['communitySearchTerm'])){
			$searchTerm = $_REQUEST['communitySearchTerm'];
			$escapedSearchTerm = $sharedContent->escape( '%'. $searchTerm . '%');
			$sharedContent->whereAdd("name like $escapedSearchTerm or description like $escapedSearchTerm or sharedFrom like $escapedSearchTerm");
		}
		$sharedContent->find();
		while ($sharedContent->fetch()) {
			$result['results'][] = [
				'id' => $sharedContent->id,
				'name' => $sharedContent->name,
				'description' => $sharedContent->description,
				'sharedFrom' => $sharedContent->sharedFrom,
				'shareDate' => $sharedContent->shareDate,
				'type' => $sharedContent->type,
			];
			$result['numResults']++;
		}

		if (!empty($_REQUEST['includeHtml'])) {
			global $interface;
			$interface->assign('toolModule', $_REQUEST['toolModule']);
			$interface->assign('toolName', $_REQUEST['toolName']);
			$interface->assign('results', $result['results']);
			$result['communityResults'] = $interface->fetch('Admin/communitySearchResults.tpl');
		}

		return $result;
	}

	function getSharedContent() : array {
		$result = [];
		$objectType = $_REQUEST['objectType'];
		$objectId = $_REQUEST['objectId'];

		require_once ROOT_DIR . '/sys/Community/SharedContent.php';
		$sharedContent = new SharedContent();
		$sharedContent->type = $objectType;
		$sharedContent->id = $objectId;
		if ($sharedContent->find(true)) {
			$result = [
				'success'=> true,
				'rawData' => $sharedContent->data
			];
		}else{
			$result = [
				'success' => false,
				'message' => 'Could not find content with that id'
			];
		}

		return $result;
	}

	function getBreadcrumbs(): array {
		return [];
	}
}