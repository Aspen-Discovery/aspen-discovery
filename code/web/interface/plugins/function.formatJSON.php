<?php

function smarty_function_formatJSON($params, &$smarty) {
	if (!isset($params['subject'])) {
		$smarty->trigger_error("implode: missing 'subject' parameter");
		return null;
	}

	return format_json_object($params['subject'], 0);
}

function format_json_object($jsonObject, $indent) {

	if ($indent == 0) {
		$formattedText = "<dl class='horizontal'>";
	} else {
		$formattedText = "<ul class='unstyled'>";
	}
	foreach ($jsonObject as $key => $value) {
		if ($indent == 0) {
			$formattedText .= "<dt>{$key}</dd>";
			$formattedText .= '<dd>' . format_json_value($key, $value, $indent + 1) . '</dd>';
		} else {
			$formattedText .= "<li><strong></string>$key: </strong>";
			$formattedText .= format_json_value($key, $value, $indent + 1);
			$formattedText .= "</li>";
		}

	}
	if ($indent == 0) {
		$formattedText .= "</dl>";
	} else {
		$formattedText .= "</ul>";
	}
	return $formattedText;
}

function format_json_value($key, $value, $indent) {
	$formattedText = '';
	if (is_array($value) && is_numeric($key)) {
		$formattedText .= implode(',', $value);
	} elseif (is_array($value)) {
		$formattedText .= '<ol>';
		foreach ($value as $key2 => $value2) {
			$formattedText .= "<li>";
			if (!is_numeric($key2)) {
				$formattedText .= "<strong>$key2:</strong>";
			}
			$formattedText .= format_json_value($key2, $value2, $indent + 1);
			$formattedText .= "</li>";
		}
		$formattedText .= "</ol>";
	} elseif (is_object($value)) {
		$formattedText .= '<ul>';
		$formattedText .= format_json_object($value, $indent + 1);
		$formattedText .= "</ul>";
	} elseif (is_bool($value)) {
		if ($value) {
			$formattedText .= 'True';
		} else {
			$formattedText .= 'False';
		}
	} else {
		$formattedText .= $value;
	}
	return $formattedText;
}