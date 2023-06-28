<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Translation/TranslationTerm.php';
require_once ROOT_DIR . '/sys/Translation/Language.php';
require_once ROOT_DIR . '/sys/Translation/Translation.php';

$allTranslationTerms = new TranslationTerm();
$translationTerms = array_filter($allTranslationTerms->fetchAll('id'));
$allLanguages = new Language();
$languages = array_filter($allLanguages->fetchAll('id'));
$numUpdated = 0;

foreach($languages as $languageId) {
	$language = new Language();
	$language->id = $languageId;
	if($language->find(true)) {
		if($language->code != 'en' || $language->code != 'pig' || $language->code != 'ubb') {
			foreach($translationTerms as $translationTermId) {
				$term = new TranslationTerm();
				$term->id = $translationTermId;
				if($term->find(true)) {
					if($term->isMetadata === 0 && $term->isAdminEnteredData === 0) {
						$translation = new Translation();
						$translation->termId = $translationTermId;
						$translation->languageId = $language->id;
						if(!$translation->find(true)) {
							try {
								$now = time();
								$translationResponse = getCommunityTranslation($term->term, $language);
								if ($translationResponse['isTranslatedInCommunity']) {
									$translation->translated = 1;
									$translation->translation = trim($translationResponse['translation']);
								} else {
									$translation->lastCheckInCommunity = $now;
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

					$term->__destruct();
					$term = null;
				}
			}
		}
	}
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