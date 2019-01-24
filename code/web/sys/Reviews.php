<?php
/**
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once ROOT_DIR . '/sys/Amazon.php';
require_once ROOT_DIR . '/sys/HTTP/Proxy_Request.php';

/**
 * ExternalReviews Class
 *
 * This class fetches reviews from various services for presentation to
 * the user.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class ExternalReviews
{
	private $isbn;
	private $results;

	/**
	 * Constructor
	 *
	 * Do the actual work of loading the reviews.
	 *
	 * @access  public
	 * @param   string      $isbn           ISBN of book to find reviews for
	 */
	public function __construct($isbn)
	{
		global $configArray;

		$this->isbn = $isbn;
		$this->results = array();

		// We can't proceed without an ISBN:
		if (empty($this->isbn)) {
			return;
		}

		// Fetch from provider
		if (isset($configArray['Content']['reviews'])) {
			$providers = explode(',', $configArray['Content']['reviews']);
			foreach ($providers as $provider) {
				$provider = explode(':', trim($provider));
				$func = strtolower($provider[0]);
				$key = $provider[1];
				$this->results[$func] = method_exists($this, $func) ? $this->$func($key) : false;

				// If the current provider had no valid reviews, store nothing:
				if (empty($this->results[$func]) || PEAR_Singleton::isError($this->results[$func])) {
					unset($this->results[$func]);
				}else{
					if (is_array($this->results[$func])){
						foreach ($this->results[$func] as $key => $reviewData){
							$this->results[$func][$key] = self::cleanupReview($this->results[$func][$key]);
						}
					}else{
						$this->results[$func] = self::cleanupReview($this->results[$func]);
					}
				}
			}
		}
	}

	/**
	 * Get the excerpt information.
	 *
	 * @access  public
	 * @return  array                       Associative array of excerpts.
	 */
	public function fetch()
	{
		return $this->results;
	}

	/**
	 * syndetics
	 *
	 * This method is responsible for connecting to Syndetics and abstracting
	 * reviews from multiple providers.
	 *
	 * It first queries the master url for the ISBN entry seeking a review URL.
	 * If a review URL is found, the script will then use HTTP request to
	 * retrieve the script. The script will then parse the review according to
	 * US MARC (I believe). It will provide a link to the URL master HTML page
	 * for more information.
	 * Configuration:  Sources are processed in order - refer to $sourceList.
	 * If your library prefers one reviewer over another change the order.
	 * If your library does not like a reviewer, remove it.  If there are more
	 * syndetics reviewers add another entry.
	 *
	 * @param   string  $id Client access key
	 * @return  array       Returns array with review data, otherwise a
	 *                      PEAR_Error.
	 * @access  private
	 * @author  Joel Timothy Norman <joel.t.norman@wmich.edu>
	 * @author  Andrew Nagy <andrew.nagy@villanova.edu>
	 */
	private function syndetics($id)
	{
		global $library;
		global $locationSingleton;
		global $configArray;
		global $timer;
		global $logger;

		$review = array();
		$location = $locationSingleton->getActiveLocation();
		if (isset($library) && $location != null){
			if ($library->showStandardReviews == 0 || $location->showStandardReviews == 0){
				return $review;
			}
		}else if ($location != null && ($location->showStandardReviews == 0)){
			//return an empty review
			return $review;
		}else if (isset($library) && ($library->showStandardReviews == 0)){
			//return an empty review
			return $review;
		}

		//list of syndetic reviews
		if (isset($configArray['SyndeticsReviews']['SyndeticsReviewsSources'])){
			$sourceList = array();
			foreach ($configArray['SyndeticsReviews']['SyndeticsReviewsSources'] as $key => $label){
				$sourceList[$key] = array('title' => $label, 'file' => "$key.XML");
			}
		}else{
			$sourceList = array(/*'CHREVIEW' => array('title' => 'Choice Review',
			'file' => 'CHREVIEW.XML'),*/
	                            'BLREVIEW' => array('title' => 'Booklist Review',
	                                                'file' => 'BLREVIEW.XML'),
	                            'PWREVIEW' => array('title' => "Publisher's Weekly Review",
	                                                'file' => 'PWREVIEW.XML'),
			/*'SLJREVIEW' => array('title' => 'School Library Journal Review',
			 'file' => 'SLJREVIEW.XML'),*/
	                            'LJREVIEW' => array('title' => 'Library Journal Review',
	                                                'file' => 'LJREVIEW.XML'),
			/*'HBREVIEW' => array('title' => 'Horn Book Review',
			 'file' => 'HBREVIEW.XML'),
			 'KIREVIEW' => array('title' => 'Kirkus Book Review',
			 'file' => 'KIREVIEW.XML'),
			 'CRITICASEREVIEW' => array('title' => 'Criti Case Review',
			 'file' => 'CRITICASEREVIEW.XML')*/);
		}
		$timer->logTime("Got list of syndetic reviews to show");

		//first request url
		$baseUrl = isset($configArray['Syndetics']['url']) ?
		$configArray['Syndetics']['url'] : 'http://syndetics.com';
		$url = $baseUrl . '/index.aspx?isbn=' . $this->isbn . '/' .
               'index.xml&client=' . $id . '&type=rw12,hw7';

		//find out if there are any reviews
		$client = new Proxy_Request();
		$client->setMethod('GET');
		$client->setURL($url);
		if (PEAR_Singleton::isError($http = $client->sendRequest())) {
			// @codeCoverageIgnoreStart
			$logger->log("Error connecting to $url", PEAR_LOG_ERR);
			$logger->log("$http", PEAR_LOG_ERR);
			return $http;
			// @codeCoverageIgnoreEnd
		}

		// Test XML Response
		if (!($xmldoc = @DOMDocument::loadXML($client->getResponseBody()))) {
			// @codeCoverageIgnoreStart
			$logger->log("Did not receive XML from $url", PEAR_LOG_ERR);
			return new PEAR_Error('Invalid XML');
			// @codeCoverageIgnoreEnd
		}

		$review = array();
		$i = 0;
		foreach ($sourceList as $source => $sourceInfo) {
			$nodes = $xmldoc->getElementsByTagName($source);
			if ($nodes->length) {
				// Load reviews
				$url = $baseUrl . '/index.aspx?isbn=' . $this->isbn . '/' .
				$sourceInfo['file'] . '&client=' . $id . '&type=rw12,hw7';
				$client->setURL($url);
				if (PEAR_Singleton::isError($http = $client->sendRequest())) {
					// @codeCoverageIgnoreStart
					$logger->log("Error connecting to $url", PEAR_LOG_ERR);
					$logger->log("$http", PEAR_LOG_ERR);
					continue;
					// @codeCoverageIgnoreEnd
				}

				// Test XML Response
				$responseBody = $client->getResponseBody();
				if (!($xmldoc2 = @DOMDocument::loadXML($responseBody))) {
					// @codeCoverageIgnoreStart
					return new PEAR_Error('Invalid XML');
					// @codeCoverageIgnoreEnd
				}

				// Get the marc field for reviews (520)
				$nodes = $xmldoc2->GetElementsbyTagName("Fld520");
				if (!$nodes->length) {
					// @codeCoverageIgnoreStart
					// Skip reviews with missing text
					continue;
					// @codeCoverageIgnoreEnd
				}
				$review[$i]['Content'] = html_entity_decode($xmldoc2->saveXML($nodes->item(0)));
				$review[$i]['Content'] = str_replace("<a>","<p>",$review[$i]['Content']);
				$review[$i]['Content'] = str_replace("</a>","</p>",$review[$i]['Content']);

				// Get the marc field for copyright (997)
				$nodes = $xmldoc2->GetElementsbyTagName("Fld997");
				if ($nodes->length) {
					$review[$i]['Copyright'] = html_entity_decode($xmldoc2->saveXML($nodes->item(0)));
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
				$review[$i]['username'] = isset($configArray['BookReviews']) ? $configArray['BookReviews']['id'] : '';

				$i++;
			}
		}

		return $review;
	}

	/**
	 * Load review information from Content Cafe based on the ISBN
	 *
	 * @param $key     Content Cafe Key
	 * @return array
	 */
	private function contentCafe($key){
		global $library;
		global $locationSingleton;
		global $configArray;

		$location = $locationSingleton->getActiveLocation();
		if (isset($library) && $location != null){
			if ($library->showStandardReviews == 0 || $location->showStandardReviews == 0){
				return null;
			}
		}elseif ($location != null && ($location->showStandardReviews == 0)){
			return null;
		}elseif (isset($library) && ($library->showStandardReviews == 0)){
			return null;
		}

		$pw = $configArray['Contentcafe']['pw'];
		if (!$key) {
			$key = $configArray['Contentcafe']['id'];
		}

		$url = isset($configArray['Contentcafe']['url']) ? $configArray['Contentcafe']['url'] : 'http://contentcafe2.btol.com';
		$url .= '/ContentCafe/ContentCafe.asmx?WSDL';

		$SOAP_options = array(
//				'trace' => 1, // turns on debugging features
				'features' => SOAP_SINGLE_ELEMENT_ARRAYS, // sets how the soap responses will be handled
				'soap_version' => SOAP_1_2
		);
		$soapClient   = new SoapClient($url, $SOAP_options);

		$params = array(
				'userID'   => $key,
				'password' => $pw,
				'key'      => $this->isbn,
				'content'  => 'ReviewDetail',
		);

		try{
			$response = $soapClient->Single($params);
//			$request = $soapClient->__getLastRequest(); // for debugging

			$review = array();
			if ($response) {
				if (!isset($response->ContentCafe->Error)) {
					$i = 0;
					if (isset($response->ContentCafe->RequestItems->RequestItem)) {
						foreach ($response->ContentCafe->RequestItems->RequestItem as $requestItem) {
							if (isset($requestItem->ReviewItems->ReviewItem)) { // if there are reviews available.
								foreach ($requestItem->ReviewItems->ReviewItem as $reviewItem) {
									$review[$i]['Content'] = $reviewItem->Review;
									$review[$i]['Source']  = $reviewItem->Publication->_;

									$copyright               = stristr($reviewItem->Review, 'copyright');
									$review[$i]['Copyright'] = $copyright ? strip_tags($copyright) : '';

									$review[$i]['ISBN'] = $this->isbn; // show more link
									//						$review[$i]['username']  = isset($configArray['BookReviews']) ? $configArray['BookReviews']['id'] : '';
									// this data doesn't look to be used in published reviews
									$i++;
								}
							}
						}
					} else {
						global $logger;
						$logger->log('Unexpected Content Cafe Response retrieving Reviews', PEAR_LOG_ERR);

					}
				} else {
					global $logger;
					$logger->log('Content Cafe Error Response'. $response->ContentCafe->Error, PEAR_LOG_ERR);
				}
			}

		} catch (Exception $e) {
			global $logger;
			$logger->log('Failed ContentCafe SOAP Request', PEAR_LOG_ERR);
			return new PEAR_Error('Failed ContentCafe SOAP Request');
		}
		return $review;
	}

	function cleanupReview($reviewData){
		//Cleanup the review data
		$fullReview = strip_tags($reviewData['Content'], '<p><a><b><em><ul><ol><em><li><strong><i><br><iframe><div>');
		$reviewData['Content'] = $fullReview;
		$reviewData['Copyright'] = strip_tags($reviewData['Copyright'], '<a><p><b><em>');
		//Trim the review to the first paragraph or 240 characters whichever comes first.
		//Make sure we get at least 140 characters
		//Get rid of all tags for the teaser so we don't risk broken HTML
		$fullReview = strip_tags($fullReview, '<p>');
		if (strlen($fullReview) > 280){
			$matches = array();
			$numMatches = preg_match_all('/<\/p>|\\r|\\n|[.,:;]/', substr($fullReview, 180, 60), $matches, PREG_OFFSET_CAPTURE);
			if ($numMatches > 0){
				$teaserBreakPoint = $matches[0][$numMatches - 1][1] + 181;
			}else{
				//Did not find a match at a paragraph or sentence boundary, just trim to the closest word.
				$teaserBreakPoint = strrpos(substr($fullReview, 0, 240), ' ');
			}
			$teaser = substr($fullReview, 0, $teaserBreakPoint);
			$reviewData['Teaser'] = strip_tags($teaser);
		}
		return $reviewData;
	}
}