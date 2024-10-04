<?php
/**
 * Handles integration with SHAREit
 */

require_once ROOT_DIR . '/sys/CurlWrapper.php';

class ShareIt {
	/**
	 * Load search results from SHAREit using the encore interface.
	 **/
	function getTopSearchResults($searchTerms, $maxResults) {
		global $library;

		$authenticationCurlWrapper = new CurlWrapper();
		// Preparing request
		$apiUrl = $library->interLibraryLoanUrl . "/agapi/api/v1/auth/token";
		$params = [
			'Grant_Type' => 'client_credentials',
			'Cid' => $library->shareItCid,
			'Lid' => $library->shareItLid,
		];
		$authentication = $library->shareItUsername . ':' . $library->shareItPassword;

		$authenticationCurlWrapper->addCustomHeaders([
			'Accept: application/json',
			'Content-Type: application/json',
			'Authorization: Basic ' . base64_encode($authentication),
		], true);
		//Getting response body
		$authenticationResponse = $authenticationCurlWrapper->curlPostBodyData($apiUrl, $params, true);

		if (!empty($authenticationResponse)) {
			$authenticationResponse = json_decode($authenticationResponse);
			$authenticationToken = $authenticationResponse->access_Token;

			//Process the search in SHAREit
			$searchRequestWrapper = new CurlWrapper();
			$searchRequestWrapper->addCustomHeaders([
				'Accept: application/json',
				'Content-Type: application/json',
				'Authorization: Bearer ' . $authenticationToken,
			], false);

			$searchUrl = $library->interLibraryLoanUrl . "/searchapi/searchapi/v1/search/public";
			$shareItSearchTerms = [];
			foreach ($searchTerms as $term) {
				$shareItSearchTerm = new stdClass();
				$shareItSearchTerm->Index = $this->getShareItIndex($term['index']);
				$shareItSearchTerm->Query = $term['lookfor'];
				$shareItSearchTerms[] = $shareItSearchTerm;
			}
			$search = new stdClass();
			$search->Matches = $shareItSearchTerms;
			$search->Grouping = "None";

			$searchResponse = $searchRequestWrapper->curlPostBodyData($searchUrl, $search, true);

			if (!empty($searchResponse)) {
				$searchResponse = json_decode($searchResponse);
				$numResults = count($searchResponse->results);
				$shareItTitles = [];
				for ($i = 0; $i < min($numResults, $maxResults); $i++) {
					$curResult = $searchResponse->results[$i];
					//Get the control id of the first result
					$agControlId = '';
					$shard = '';
					$format = implode(', ', $curResult->availableFormats);
					foreach ($curResult->formats as $formatDetails) {
						foreach ($formatDetails->bibliographicRecords as $bibliographicRecord) {
							if (!empty($bibliographicRecord->agControlId)) {
								$agControlId = $bibliographicRecord->agControlId;
								$shard = $bibliographicRecord->shard;
								$format = $bibliographicRecord->format_Long;
								break;
							}
						}
						if (!empty($bibliographicRecord->agControlId)) {
							break;
						}
					}

					$curTitleInfo = [
						'id' => $agControlId,
						'link' => empty($agControlId) ? '' : "$library->interLibraryLoanUrl/details?agctrlid=$agControlId&searchId=$searchResponse->searchId&shard=$shard&cid=$library->shareItCid&lid=$library->shareItLid",
						'title' => $curResult->title,
						'author' => $curResult->author,
						'format' => $format,
						'pubDate' => implode(', ', $curResult->pubYear)
					];
					$shareItTitles[] = $curTitleInfo;

				}
				return [
					'searchId' => $searchResponse->searchId,
					'searchLink' => "$library->interLibraryLoanUrl/search?searchId=$searchResponse->searchId&cid=$library->shareItCid&lid=$library->shareItLid",
					'records' => $shareItTitles,
				];
			}
		}
		return [
			'searchId' => null,
			'searchLink' => '',
			'records' => [],
		];
	}

	function getBaseUrl() {
		global $library;
		$baseUrl = $library->interLibraryLoanUrl;
		if (str_ends_with($baseUrl, '/')) {
			$baseUrl = substr($baseUrl, 0, strlen($baseUrl) -1);
		}
		return "$baseUrl/home?cid=$library->shareItCid&lid=$library->shareItLid";
	}

	function getSearchLink($searchTerms) {
		$results = $this->getTopSearchResults($searchTerms, 1);
		return $results['searchLink'];
	}

	private function getShareItIndex(string $index): string {
		switch ($index) {
			case 'Author':
				return 'author';
			case 'Title':
				return 'title';
			case 'Subject':
				return 'subject';
			case 'StartOfTitle':
				return 'title_begin_with';
			case 'Series':
				return 'title_series';
			default:
				return 'all_headings';
		}
	}

	private function getLinkForControlId(CurlWrapper $searchRequestWrapper, string $searchUrl, string $agControlId) : string {
		global $library;
		$shareItSearchTerms = [];
		$shareItSearchTerm = new stdClass();
		$shareItSearchTerm->Index = 'all_headings';
		$shareItSearchTerm->Query = $agControlId;
		$shareItSearchTerms[] = $shareItSearchTerm;
		$search = new stdClass();
		$search->Matches = $shareItSearchTerms;
		$search->Grouping = "None";

		$searchResponse = $searchRequestWrapper->curlPostBodyData($searchUrl, $search, true);

		if (!empty($searchResponse)) {
			$searchResponse = json_decode($searchResponse);
			return "$library->interLibraryLoanUrl/search?searchId=$searchResponse->searchId&cid=$library->shareItCid&lid=$library->shareItLid";
		}
		return '';
	}
}