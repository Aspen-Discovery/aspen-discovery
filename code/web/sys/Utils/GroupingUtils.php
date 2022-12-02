<?php

function mergeItemSummary($localCopies, $itemSummary) {
	foreach ($itemSummary as $key => $item) {
		if (isset($localCopies[$key])) {
			$localCopies[$key]['totalCopies'] += $item['totalCopies'];
			$localCopies[$key]['availableCopies'] += $item['availableCopies'];
			if ($item['displayByDefault']) {
				$localCopies[$key]['displayByDefault'] = true;
			}
		} else {
			$localCopies[$key] = $item;
		}
	}
	return $localCopies;
}