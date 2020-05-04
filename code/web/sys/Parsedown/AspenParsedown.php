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

	protected function inlineLink($Excerpt)
	{
		$element = parent::inlineLink($Excerpt);
		$matches = [];
		if (preg_match_all('/spotlight:(.*)/i', $element['element']['attributes']['href'], $matches)){
			require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
			$collectionSpotlight = new CollectionSpotlight();
			$collectionSpotlight->id = $matches[1][0];
			if ($collectionSpotlight->find(true)){
				global $interface;
				$interface->assign('collectionSpotlight', $collectionSpotlight);
				return array(
					'element' => array('rawHtml' => $interface->fetch('CollectionSpotlight/collectionSpotlightTabs.tpl')),
					'extent' => strlen($Excerpt['text']),
				);
			}
		}
		return $element;
	}
}