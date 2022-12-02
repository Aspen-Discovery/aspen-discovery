<?php

require_once ROOT_DIR . '/sys/CurlWrapper.php';

/**
 * ExternalReviews Class
 *
 * This class fetches reviews from various services for presentation to
 * the user.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class ExternalReviews {
	private $isbn;
	private $results;

	/**
	 * Constructor
	 *
	 * Do the actual work of loading the reviews.
	 *
	 * @access  public
	 * @param string $isbn ISBN of book to find reviews for
	 */
	public function __construct($isbn) {
		$this->isbn = $isbn;
		$this->results = [];

		// We can't proceed without an ISBN:
		if (empty($this->isbn)) {
			return;
		}

		// Fetch from provider
		require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
		$syndeticsSettings = new SyndeticsSetting();
		if ($syndeticsSettings->find(true)) {
			$result = $this->syndetics($syndeticsSettings);
			if ($result != null) {
				$this->results['syndetics'] = $result;
			}
		}
		require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
		$contentCafeSettings = new ContentCafeSetting();
		if ($contentCafeSettings->find(true)) {
			if ($contentCafeSettings->enabled) {
				$result = $this->contentCafe($contentCafeSettings);
				if ($result != null) {
					$this->results['contentCafe'] = $result;
				}
			}
		}

		foreach ($this->results as $source => $reviews) {
			foreach ($this->results[$source] as $key => $reviewData) {
				$this->results[$source][$key] = $this->cleanupReview($reviewData);
			}
		}
	}

	/**
	 * Get the excerpt information.
	 *
	 * @access  public
	 * @return  array                       Associative array of excerpts.
	 */
	public function fetch() {
		return $this->results;
	}

	/**
	 * syndetics
	 *
	 * This method is responsible for connecting to Syndetics and abstracting
	 * reviews from multiple providers.
	 *
	 * It first queries the url for the primary ISBN entry seeking a review URL.
	 * If a review URL is found, the script will then use HTTP request to
	 * retrieve the script. The script will then parse the review according to
	 * US MARC (I believe). It will provide a link to the primary URL HTML page
	 * for more information.
	 * Configuration:  Sources are processed in order - refer to $sourceList.
	 * If your library prefers one reviewer over another change the order.
	 * If your library does not like a reviewer, remove it.  If there are more
	 * syndetics reviewers add another entry.
	 *
	 * @author  Joel Timothy Norman <joel.t.norman@wmich.edu>
	 * @author  Andrew Nagy <andrew.nagy@villanova.edu>
	 * @param   $settings $id Client access key
	 * @return  array|null  Returns array with review data, otherwise null.
	 * @access  private
	 */
	private function syndetics(SyndeticsSetting $settings) {
		global $library;
		global $locationSingleton;
		global $timer;
		global $logger;

		$review = [];
		$location = $locationSingleton->getActiveLocation();
		if ($location != null) {
			if ($location->getGroupedWorkDisplaySettings()->showStandardReviews == 0) {
				return $review;
			}
		} elseif ($library->getGroupedWorkDisplaySettings()->showStandardReviews == 0) {
			//return an empty review
			return $review;
		}

		//list of syndetics reviews
		//TODO: Review this list to see if we can show all
		$sourceList = [
			'CHREVIEW' => [
				'title' => 'Choice Review',
				'file' => 'CHREVIEW.XML',
			],
			'BLREVIEW' => [
				'title' => 'Booklist Review',
				'file' => 'BLREVIEW.XML',
			],
			'PWREVIEW' => [
				'title' => "Publisher's Weekly Review",
				'file' => 'PWREVIEW.XML',
			],
			'SLJREVIEW' => [
				'title' => 'School Library Journal Review',
				'file' => 'SLJREVIEW.XML',
			],
			'LJREVIEW' => [
				'title' => 'Library Journal Review',
				'file' => 'LJREVIEW.XML',
			],
			'HBREVIEW' => [
				'title' => 'Horn Book Review',
				'file' => 'HBREVIEW.XML',
			],
			'KIREVIEW' => [
				'title' => 'Kirkus Book Review',
				'file' => 'KIREVIEW.XML',
			],
			'CRITICASEREVIEW' => [
				'title' => 'Criti Case Review',
				'file' => 'CRITICASEREVIEW.XML',
			],
		];

		$timer->logTime("Got list of syndetics reviews to show");

		//first request url
		$baseUrl = 'https://syndetics.com';
		$url = $baseUrl . '/index.aspx?isbn=' . $this->isbn . '/' . 'index.xml&client=' . $settings->syndeticsKey . '&type=rw12,hw7';

		//find out if there are any reviews
		$client = new CurlWrapper();
		$http = $client->curlGetPage($url);
		$xmlDoc = new DomDocument();
		$xmlDoc->preserveWhiteSpace = FALSE;
		// Test XML Response
		if (!$xmlDoc->loadXml($http)) {
			// @codeCoverageIgnoreStart
			$logger->log("Did not receive XML from $url", Logger::LOG_ERROR);
			return null;
			// @codeCoverageIgnoreEnd
		}

		$review = [];
		$i = 0;
		foreach ($sourceList as $source => $sourceInfo) {
			$nodes = $xmlDoc->getElementsByTagName($source);
			if ($nodes->length) {
				// Load reviews
				$url = $baseUrl . '/index.aspx?isbn=' . $this->isbn . '/' . $sourceInfo['file'] . '&client=' . $settings->syndeticsKey . '&type=rw12,hw7';
				$http = $client->curlGetPage($url);

				$xmlDoc2 = new DomDocument();
				$xmlDoc2->preserveWhiteSpace = FALSE;
				if (!$xmlDoc2->loadXML($http)) {
					// @codeCoverageIgnoreStart
					return null;
					// @codeCoverageIgnoreEnd
				}

				// Get the marc field for reviews (520)
				$nodes = $xmlDoc2->GetElementsbyTagName("Fld520");
				if (!$nodes->length) {
					// @codeCoverageIgnoreStart
					// Skip reviews with missing text
					continue;
					// @codeCoverageIgnoreEnd
				}
				$review[$i]['Content'] = html_entity_decode($xmlDoc2->saveXML($nodes->item(0)));
				$review[$i]['Content'] = str_replace("<a>", "<p>", $review[$i]['Content']);
				$review[$i]['Content'] = str_replace("</a>", "</p>", $review[$i]['Content']);

				// Get the marc field for copyright (997)
				$nodes = $xmlDoc2->GetElementsbyTagName("Fld997");
				if ($nodes->length) {
					$review[$i]['Copyright'] = html_entity_decode($xmlDoc2->saveXML($nodes->item(0)));
				} else {
					// @codeCoverageIgnoreStart
					$review[$i]['Copyright'] = null;
					// @codeCoverageIgnoreEnd
				}

				//Check to see if the copyright is contained in the main body of the review and if so, remove it.
				//Does not happen often.
				if ($review[$i]['Copyright']) {  //stop duplicate copyrights
					$location = strripos($review[0]['Content'], $review[0]['Copyright']);
					// @codeCoverageIgnoreStart
					if ($location > 0) {
						$review[$i]['Content'] = substr($review[0]['Content'], 0, $location);
					}
					// @codeCoverageIgnoreEnd
				}

				$review[$i]['Source'] = $sourceInfo['title'];  //changes the xml to actual title
				$review[$i]['ISBN'] = $this->isbn; //show more link

				$i++;
			}
		}

		return $review;
	}

	/**
	 * Load review information from Content Cafe based on the ISBN
	 *
	 * @param ContentCafeSetting $settings Content Cafe Key
	 * @return array|null
	 */
	private function contentCafe($settings) {
		global $library;
		global $locationSingleton;

		$location = $locationSingleton->getActiveLocation();
		if ($location != null) {
			if ($location->getGroupedWorkDisplaySettings()->showStandardReviews == 0) {
				return null;
			}
		} elseif ($library->getGroupedWorkDisplaySettings()->showStandardReviews == 0) {
			return null;
		}

		$pw = $settings->pwd;
		$key = $settings->contentCafeId;

		$url = 'https://contentcafe2.btol.com/ContentCafe/ContentCafe.asmx?WSDL';

		$SOAP_options = [
//				'trace' => 1, // turns on debugging features
			'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
			// sets how the soap responses will be handled
			'soap_version' => SOAP_1_2,
		];
		try {
			$soapClient = new SoapClient($url, $SOAP_options);

			$params = [
				'userID' => $key,
				'password' => $pw,
				'key' => $this->isbn,
				'content' => 'ReviewDetail',
			];

			try {
				/** @noinspection PhpUndefinedMethodInspection */
				$response = $soapClient->Single($params);

				$review = [];
				if ($response) {
					if (!isset($response->ContentCafe->Error)) {
						$i = 0;
						if (isset($response->ContentCafe->RequestItems->RequestItem)) {
							foreach ($response->ContentCafe->RequestItems->RequestItem as $requestItem) {
								if (isset($requestItem->ReviewItems->ReviewItem)) { // if there are reviews available.
									foreach ($requestItem->ReviewItems->ReviewItem as $reviewItem) {
										$review[$i]['Content'] = $reviewItem->Review;
										$review[$i]['Source'] = $reviewItem->Publication->_;

										$copyright = stristr($reviewItem->Review, 'copyright');
										$review[$i]['Copyright'] = $copyright ? strip_tags($copyright) : '';

										$review[$i]['ISBN'] = $this->isbn; // show more link
										$i++;
									}
								}
							}
						} else {
							global $logger;
							$logger->log('Unexpected Content Cafe Response retrieving Reviews', Logger::LOG_ERROR);

						}
					} else {
						global $logger;
						$logger->log('Content Cafe Error Response' . $response->ContentCafe->Error, Logger::LOG_ERROR);
					}
				}

			} catch (Exception $e) {
				global $logger;
				$logger->log('Failed ContentCafe SOAP Request', Logger::LOG_ERROR);
				return null;
			}
		} catch (SoapFault $e) {
			global $logger;
			$logger->log('SoapFault making ContentCafe SOAP Request ' . $e, Logger::LOG_ERROR);
			return null;
		}
		return $review;
	}

	function cleanupReview($reviewData) {
		//Cleanup the review data
		$fullReview = strip_tags($reviewData['Content'], '<p><a><b><em><ul><ol><em><li><strong><i><br><iframe><div>');
		$reviewData['Content'] = $fullReview;
		$reviewData['Copyright'] = strip_tags($reviewData['Copyright'], '<a><p><b><em>');
		//Trim the review to the first paragraph or 240 characters whichever comes first.
		//Make sure we get at least 140 characters
		//Get rid of all tags for the teaser so we don't risk broken HTML
		$fullReview = strip_tags($fullReview, '<p>');
		if (strlen($fullReview) > 280) {
			$matches = [];
			$numMatches = preg_match_all('/<\/p>|\\r|\\n|[.,:;]/', substr($fullReview, 180, 60), $matches, PREG_OFFSET_CAPTURE);
			if ($numMatches > 0) {
				$teaserBreakPoint = $matches[0][$numMatches - 1][1] + 181;
			} else {
				//Did not find a match at a paragraph or sentence boundary, just trim to the closest word.
				$teaserBreakPoint = strrpos(substr($fullReview, 0, 240), ' ');
			}
			$teaser = substr($fullReview, 0, $teaserBreakPoint);
			$reviewData['Teaser'] = strip_tags($teaser);
		}
		return $reviewData;
	}
}