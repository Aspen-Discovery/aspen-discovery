<?php

function smarty_function_jsTranslationsJson(array $params, Smarty_Internal_Template &$smarty): string {
	require_once ROOT_DIR . '/sys/Translation/JsTranslations.php';
	/** @var Translator $translator */ global $translator;

	$translations = [];
	foreach (JsTranslations::getTerms() as $phrase => $flags) {
		$translated = $translator->translate(
			$phrase,
			'',
			[],
			true,
			$flags['isPublicFacing'] ?? false,
			$flags['isAdminFacing'] ?? false
		);
		$translations[$phrase] = html_entity_decode(strip_tags(trim($translated)));
	}

	return json_encode($translations, JSON_HEX_APOS | JSON_HEX_QUOT);
}
