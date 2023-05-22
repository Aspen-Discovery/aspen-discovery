<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/Translation/Translation.php';
require_once ROOT_DIR . '/sys/Translation/Language.php';

$allTranslations = new Translation();
$translations = array_filter($allTranslations->fetchAll('id'));

$numUpdated = 0;

foreach($translations as $translationToUpdate) {
	$translation = new Translation();
	$translation->id = $translationToUpdate;
	if($translation->find(true)) {
		$language = new Language();
		$language->id = $translation->languageId;
		if($language->find(true)) {
			try {
				$now = time();
				if (!$translation->translated) {
					$translationResponse = getCommunityTranslation($translation->translation, $language->code);
					if ($translationResponse['isTranslatedInCommunity']) {
						$translation->translated = 1;
						$translation->translation = trim($translationResponse['translation']);
					} else {
						$translation->lastCheckInCommunity = $now;
					}
					$translation->update();
					$numUpdated++;
				}
			} catch (Exception $e) {
				// This will happen before last check in community is set.
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