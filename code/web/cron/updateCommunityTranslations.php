<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Translation/TranslationTerm.php';
require_once ROOT_DIR . '/sys/Translation/Language.php';
require_once ROOT_DIR . '/sys/Translation/Translation.php';

$allTranslationTerms = new TranslationTerm();
$allLanguages = new Language();
$languages = array_filter($allLanguages->fetchAll('id'));
$numUpdated = 0;

foreach($languages as $languageId) {
	$language = new Language();
	$language->id = $languageId;
	if($language->find(true)) {
		if ($language->code != 'en' && $language->code != 'pig' && $language->code != 'ubb') {
			$translation = new Translation();
			$translation->languageId = $language->id;
			$translation->find();
			$allTranslations = [];
			while($translation->fetch()) {
				$translationTerm = new TranslationTerm();
				$translationTerm->id = $translation->termId;
				$translationTerm->whereAdd('isMetadata = 0');
				$translationTerm->whereAdd('isAdminEnteredData = 0');
				$translationTerm->whereAdd('isPublicFacing = 1 OR isAdminFacing = 1');
				if($translationTerm->find(true)) {
					$allTranslations[$translationTerm->id] = $translationTerm->term;
				}
				$translationTerm->__destruct();
				$translationTerm = null;
			}
			$terms = array_chunk($allTranslations, 100);
			foreach ($terms as $batch) {
				$response = getCommunityTranslations($batch, $language);
				if(!empty($response['translations'])) {
					$translatedBatch = $response['translations'];
					foreach ($translatedBatch as $updatedTranslation) {
						$translation = new Translation();
						$translation->termId = '';
						$translation->languageId = $language->id;
						if (!$translation->find(true)) {
							try {
								if ($response['isTranslatedInCommunity']) {
									$translation->translated = 1;
									$translation->translation = trim('');
								} else {
									$translation->lastCheckInCommunity = time();
								}
								$translation->update();
								$numUpdated++;
							} catch (Exception $e) {
								// This will happen before last check in community is set.
							}
						} else {
							// Translation already exists
						}
						$translation->__destruct();
						$translation = null;
					}
				}
			}
		}
	}
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
		$response = $communityContentCurlWrapper->curlPostPage($systemVariables->communityContentUrl . '/API/CommunityAPI?method=getDefaultTranslations', $body);
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

/**
 * @param string $phrase
 * @param Language $activeLanguage
 * @return array
 */
function getCommunityTranslation(string $phrase, $activeLanguage): array {
	require_once ROOT_DIR . '/sys/SystemVariables.php';
	$systemVariables = SystemVariables::getSystemVariables();
	$translatedInCommunity = false;
	$defaultTranslation = null;
	if ($systemVariables && !empty($systemVariables->communityContentUrl)) {
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$communityContentCurlWrapper = new CurlWrapper();
		$body = [
			'term' => $phrase,
			'languageCode' => $activeLanguage->code,
		];
		$response = $communityContentCurlWrapper->curlPostPage($systemVariables->communityContentUrl . '/API/CommunityAPI?method=getDefaultTranslation', $body);
		if ($response !== false) {
			$jsonResponse = json_decode($response);
			if (!empty($jsonResponse) && $jsonResponse->success) {
				$defaultTranslation = $jsonResponse->translation;
				$translatedInCommunity = true;
			}
		}
	}
	return [
		'isTranslatedInCommunity' => $translatedInCommunity,
		'translation' => $defaultTranslation
	];
}