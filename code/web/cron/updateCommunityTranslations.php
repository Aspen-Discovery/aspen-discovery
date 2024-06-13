<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Translation/TranslationTerm.php';
require_once ROOT_DIR . '/sys/Translation/Language.php';
require_once ROOT_DIR . '/sys/Translation/Translation.php';

set_time_limit(0);

$allLanguages = new Language();
$languages = array_filter($allLanguages->fetchAll('id'));
$numUpdated = 0;
$allLanguages = null;

global $logger;

foreach($languages as $languageId) {
	$language = new Language();
	$language->id = $languageId;
	if($language->find(true)) {
		if ($language->code != 'en' && $language->code != 'pig' && $language->code != 'ubb') {
			echo ("Updating $language->displayNameEnglish\n");
			ob_flush();
			//Loop through all terms to be translated
			$translationTerm = new TranslationTerm();
			$translationTerm->whereAdd('isMetadata = 0');
			$translationTerm->whereAdd('isAdminEnteredData = 0');
			$translationTerm->whereAdd('isPublicFacing = 1 OR isAdminFacing = 1');
			$translationTerm->orderBy('id ASC');
			$translationTerm->find();
			$allTermsToTranslate = [];

			$batchNumber = 0;
			while ($translationTerm->fetch()) {
				$translation = new Translation();
				$translation->termId = $translationTerm->getId();
				$translation->languageId = $language->id;
				//Needs to be fetched from community if we haven't gotten a translation yet
				if ($translation->find(true)){
					if (!$translation->translated) {
						//The translation has not been done yet.
						$allTermsToTranslate[$translationTerm->getId()] = $translationTerm->getTerm();
						$translation->lastCheckInCommunity = time();
						$translation->update();
					}
				} else {
					//We don't have a translation for this term
					$allTermsToTranslate[$translationTerm->getId()] = $translationTerm->getTerm();
					$translation->lastCheckInCommunity = time();
					$translation->insert();
				}
				$translation->__destruct();
				$translation = null;
				if (count($allTermsToTranslate) > 0 && count($allTermsToTranslate) % 100 == 0) {
					$batchNumber++;
					$termIds = array_keys($allTermsToTranslate);
					$firstId = $termIds[0];
					$lastId = $termIds[count($termIds) -1];
					echo ("Loading batch $batchNumber from $firstId to $lastId.\n");
					ob_flush();
					translateTerms($allTermsToTranslate, $language, $numUpdated);
					$allTermsToTranslate = [];
				}
			}
			$translationTerm->__destruct();
			$translationTerm = null;

			if (count($allTermsToTranslate) > 0) {
				echo ("Loading final batch.\n");
				ob_flush();

				translateTerms($allTermsToTranslate, $language, $numUpdated);
				$allTermsToTranslate = [];
			}

			echo ("Found " . count($allTermsToTranslate). " terms to translate\n");
			ob_flush();
		}
	}
	$language = null;
}

echo ("Imported a total of " . $numUpdated. " translations\n");
ob_flush();

global $aspen_db;
$aspen_db = null;

echo ("Finished\n");
ob_flush();

die();


/**
 * @param array $allTermsToTranslate
 * @param Language $language
 * @param int $numUpdated
 */
function translateTerms(array $allTermsToTranslate, Language $language, int $numUpdated) {
	$response = getCommunityTranslations($allTermsToTranslate, $language);
	$numUpdatedThisBatch = 0;
	//Everything that has been translated is returned.  If there is not a translation in the
	//community, that term is nto returned.
	if (!empty($response['translations'])) {
		$translatedBatch = $response['translations'];
		foreach ($translatedBatch as $termId => $updatedTranslation) {
			$translation = new Translation();
			$translation->termId = $termId;
			$translation->languageId = $language->id;
			if (!$translation->find(true)) {
				try {
					$translation->translated = 1;
					$translation->translation = $updatedTranslation;
					$translation->update();
					$numUpdatedThisBatch++;
				} catch (Exception $e) {
					// This will happen before last check in community is set.
				}
			} else {
				// Translation already exists
				//Check to see if the term is translated
				if (!$translation->translated) {
					$translation->translated = 1;
					$translation->translation = $updatedTranslation;
					$translation->update();
					$numUpdatedThisBatch++;
				}
			}
			$translation->__destruct();
			$translation = null;
		}
	}
	$numUpdated+= $numUpdatedThisBatch;
	echo ("\tUpdated $numUpdatedThisBatch translations\n");
	ob_flush();
}

/**
 * @param array $terms
 * @param Language $activeLanguage
 * @return array
 */
function getCommunityTranslations(array $terms, Language $activeLanguage): array {
	require_once ROOT_DIR . '/sys/SystemVariables.php';
	$systemVariables = SystemVariables::getSystemVariables();
	$translatedInCommunity = false;
	$defaultTranslation = null;
	$translatedTerms = [];
	if ($systemVariables && !empty($systemVariables->communityContentUrl)) {
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$communityContentCurlWrapper = new CurlWrapper();
		$body = [
			'terms' => $terms,
			'languageCode' => $activeLanguage->code,
		];
		$url = $systemVariables->communityContentUrl . '/API/CommunityAPI?method=getDefaultTranslations';
		$response = $communityContentCurlWrapper->curlPostPage($url, $body);
		if ($response) {
			$jsonResponse = json_decode($response);
			if (!empty($jsonResponse->translations) && $jsonResponse->success) {
				$translatedInCommunity = true;
				$translatedTerms = $jsonResponse->translations;
			}
		}
	}
	return [
		'isTranslatedInCommunity' => $translatedInCommunity,
		'translations' => $translatedTerms,
	];
}
