<?php
function smarty_function_translate($params, Smarty_Internal_Template &$smarty) {
	global $translator;

	// If no translator exists yet, create one -- this may be necessary if we
	// encounter a failure before we are able to load the global translator
	// object.
	if (!is_object($translator)) {
		global $activeLanguage;
		if (empty($activeLanguage)) {
			$code = 'en';
		} else {
			$code = $activeLanguage->code;
		}
		$translator = new Translator('lang', $code);
	}
	if (is_array($params)) {
		$defaultText = isset($params['defaultText']) ? $params['defaultText'] : null;
		$inAttribute = isset($params['inAttribute']) ? $params['inAttribute'] : false;
		$isPublicFacing = isset($params['isPublicFacing']) ? $params['isPublicFacing'] : false;
		$isAdminFacing = isset($params['isAdminFacing']) ? $params['isAdminFacing'] : false;
		$isMetadata = isset($params['isMetadata']) ? $params['isMetadata'] : false;
		$isAdminEnteredData = isset($params['isAdminEnteredData']) ? $params['isAdminEnteredData'] : false;
		$translateParameters = isset($params['translateParameters']) ? $params['translateParameters'] : false;
		$replacementValues = [];
		foreach ($params as $index => $param) {
			if (is_numeric($index)) {
				$replacementValues[$index] = $param;
			}
		}
		return $translator->translate($params['text'], $defaultText, $replacementValues, $inAttribute, $isPublicFacing, $isAdminFacing, $isMetadata, $isAdminEnteredData, $translateParameters);
	} else {
		return $translator->translate($params, null, [], false);
	}
}