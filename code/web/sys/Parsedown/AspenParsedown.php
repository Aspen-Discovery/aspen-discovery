<?php

require_once ROOT_DIR . '/sys/Parsedown/ParsedownExtra.php';
class AspenParsedown extends ParsedownExtra{
	protected function inlineImage($Excerpt) {
		$result = parent::inlineImage($Excerpt);
		if (!empty($result)) {
			$result['element']['attributes']['class'] = 'img-responsive';
		}
		return $result;
	}
}