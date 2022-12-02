<?php
function smarty_modifier_formatCurrency($number) {
	global $activeLanguage;

	$currencyCode = 'USD';
	$variables = new SystemVariables();
	if ($variables->find(true)) {
		$currencyCode = $variables->currencyCode;
	}

	$currencyFormatter = new NumberFormatter($activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY);

	return $currencyFormatter->formatCurrency($number, $currencyCode);
}