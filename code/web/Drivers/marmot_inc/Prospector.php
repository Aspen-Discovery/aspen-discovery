<?php
/**
 * Handles integration with Prospector
 */

require_once ROOT_DIR . '/sys/CurlWrapper.php';

class Prospector {
	/**
	 * Load search results from Prospector using the encore interface.
	 * If $prospectorRecordDetails are provided, will sort the existing result to the
	 * top and tag it as being the record.
	 **/
	function getTopSearchResults($searchTerms, $maxResults) {
		$prospectorUrl = $this->getSearchLink($searchTerms);
		//Load the HTML from Prospector
		$req = new CurlWrapper();
		$prospectorInfo = $req->curlGetPage($prospectorUrl);

		//Get the total number of results
		if (preg_match('/<span class="noResultsHideMessage">.*?(\d+) - (\d+) of (\d+).*?<\/span>/s', $prospectorInfo, $summaryInfo)) {
			$firstResult = $summaryInfo[1];
			$lastResult = $summaryInfo[2];
			$numberOfResults = $summaryInfo[3];

			//Parse the information to get the titles from the page
			preg_match_all('/gridBrowseCol2(.*?)bibLocations/si', $prospectorInfo, $titleInfo, PREG_SET_ORDER);
			$prospectorTitles = [];
			for ($matchi = 0; $matchi < count($titleInfo); $matchi++) {
				$curTitleInfo = [];
				//Extract the title and bid from the titleTitleInfo
				$titleTitleInfo = $titleInfo[$matchi][1];

				//Get the cover
				if (preg_match('/<div class="itemBookCover">.*?<img.*?src="(.*?)".*<\/div>/s', $titleTitleInfo, $imageMatches)) {
					$curTitleInfo['cover'] = $imageMatches[1];
					//echo "Found book cover " . $curTitleInfo['cover'];
				}

				if (preg_match('/<span class="title">.*?<a.*?href.*?__R(.*?)__.*?>\\s*(.*?)\\s*<\/a>.*?<\/span>/s', $titleTitleInfo, $titleMatches)) {
					$curTitleInfo['id'] = $titleMatches[1];
					//Create the link to the title in Encore
					$curTitleInfo['link'] = "http://encore.coalliance.org/iii/encore/record/C__R" . urlencode($curTitleInfo['id']) . "__Orightresult?lang=eng&amp;suite=def";
					$curTitleInfo['title'] = strip_tags($titleMatches[2]);
				} else {
					//Couldn't load information, skip to the next one.
					continue;
				}

				//Extract the format from the itemMediaDescription
				if (preg_match('/<span class="itemMediaDescription" id="mediaTypeInsertComponent">(.*?)<\/span>/s', $titleTitleInfo, $formatMatches)) {
					$formatInfo = trim(strip_tags($formatMatches[1]));
					if (strlen($formatInfo) > 0) {
						$curTitleInfo['format'] = $formatInfo;
					}
				}

				//Extract the author from the titleAuthorInfo
				$titleAuthorInfo = $titleInfo[$matchi][1];
				if (preg_match('/<div class="dpBibAuthor">(.*?)<\/div>/s', $titleAuthorInfo, $authorMatches)) {
					$authorInfo = trim(strip_tags($authorMatches[1]));
					if (strlen($authorInfo) > 0) {
						$curTitleInfo['author'] = $authorInfo;
					}
				}

				//Extract the publication date from the titlePubDateInfo
				$titlePubDateInfo = $titleInfo[$matchi][1];
				if (preg_match('/"itemMediaYear".*?>(.*?)<\/span>/s', $titlePubDateInfo, $pubMatches)) {
					//Make sure we are not getting scripts and copy counts
					if (!preg_match('/img/', $pubMatches[1]) && !preg_match('/script/', $pubMatches[1])) {
						$publicationInfo = trim(strip_tags($pubMatches[1]));
						if (strlen($publicationInfo) > 0) {
							$curTitleInfo['pubDate'] = $publicationInfo;
						}
					}
				}

				//Extract format titlePubDateInfo
				$titleFormatInfo = $titleInfo[$matchi][1];
				if (preg_match('/"itemMediaDescription".*?>(.*?)<\/span>/s', $titleFormatInfo, $formatMatches)) {
					//Make sure we are not getting scripts and copy counts
					$formatInfo = trim(strip_tags($formatMatches[1]));
					if (strlen($formatInfo) > 0) {
						$curTitleInfo['format'] = $formatInfo;
					}
				}

				$prospectorTitles[] = $curTitleInfo;
			}

			$prospectorTitles = array_slice($prospectorTitles, 0, $maxResults, true);
			return [
				'firstRecord' => $firstResult,
				'lastRecord' => $lastResult,
				'resultTotal' => $numberOfResults,
				'records' => $prospectorTitles,
			];
		} else {
			return [
				'firstRecord' => 0,
				'lastRecord' => 0,
				'resultTotal' => 0,
				'records' => [],
			];
		}

	}

	function getSearchLink($searchTerms) {
		$search = "";
		foreach ($searchTerms as $term) {
			if (strlen($search) > 0) {
				$search .= ' ';
			}
			if (is_array($term) && isset($term['group'])) {
				foreach ($term['group'] as $groupTerm) {
					if (strlen($search) > 0) {
						$search .= ' ';
					}
					if (isset($groupTerm['lookfor'])) {
						$termValue = $groupTerm['lookfor'];
						if (isset($groupTerm['index'])) {
							if ($term['index'] == 'Author') {
								$search .= "a:($termValue)";
							} elseif ($groupTerm['index'] == 'Title') {
								$search .= "t:($termValue)";
							} elseif ($groupTerm['index'] == 'Subject') {
								$search .= "d:($termValue)";
							} else {
								$search .= $termValue;
							}
						} else {
							$search .= $termValue;
						}
					}
				}
			} else {
				if (isset($term['lookfor'])) {
					$termValue = $term['lookfor'];
					if (isset($term['index'])) {
						if ($term['index'] == 'Author') {
							$search .= "a:($termValue)";
						} elseif ($term['index'] == 'Title') {
							$search .= "t:($termValue)";
						} elseif ($term['index'] == 'Subject') {
							$search .= "d:($termValue)";
						} else {
							$search .= $termValue;
						}
					} else {
						$search .= $termValue;
					}
				}
			}
		}
		//Setup the link to Prospector (search classic)
		//$prospectorUrl = "http://prospector.coalliance.org/search/?searchtype=X&searcharg=" . urlencode($search) . "&Da=&Db=&SORT=R";
		//Fix prospector url issue
		$search = str_replace('+', '%20', urlencode(str_replace('/', '', $search)));
		// Handle special exception: ? character in the search must be encoded specially
		$search = str_replace('%3F', 'Pw%3D%3D', $search);
		$prospectorUrl = "http://encore.coalliance.org/iii/encore/search/C__S" . $search . "__Orightresult__U1?lang=eng&amp;suite=def";
		return $prospectorUrl;
	}
}