<?php
/**
 *
 * Copyright (C) Villanova University 2007.
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
require_once ROOT_DIR . '/sys/HTTP/HTTP_Request.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/ISBNConverter.php';
require_once(ROOT_DIR . '/sys/LocalEnrichment/EditorialReview.php');

require_once 'Record.php';

class Record_Reviews extends Record_Record
{
	function launch()
	{
		global $interface;
		global $configArray;

		$interface->setPageTitle('Reviews: ' . $this->record['title_short']);

		//Load the data for the reviews and populate in the user interface
		$this->loadReviews($this->id, $this->isbn, true);

		$interface->assign('subTemplate', 'view-reviews.tpl');
		$interface->setTemplate('view.tpl');

		// Display Page
		$interface->display('layout.tpl', $this->cacheId);
	}

	/**
	 * Load information from the review provider and update the interface with the data.
	 *
	 * @return array       Returns array with review data, otherwise a
	 *                      PEAR_Error.
	 */
	static function loadReviews($id, $isbn, $includeEditorial = false) {
		global $interface;
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;

		$reviews = $memCache->get("reviews_{$isbn}");
		if (!$reviews || isset($_REQUEST['reload'])){
			// Fetch from provider
			if (isset($configArray['Content']['reviews'])) {
				$providers = explode(',', $configArray['Content']['reviews']);
				foreach ($providers as $provider) {
					$provider = explode(':', trim($provider));
					$func = strtolower($provider[0]);
					$key = $provider[1];
					$reviews[$func] = Record_Reviews::$func($isbn, $key);

					// If the current provider had no valid reviews, store nothing:
					if (empty($reviews[$func]) || PEAR_Singleton::isError($reviews[$func])) {
						unset($reviews[$func]);
					}else{
						if (is_array($reviews[$func])){
							foreach ($reviews[$func] as $key => $reviewData){
								$reviews[$func][$key] = Record_Reviews::cleanupReview($reviews[$func][$key]);
							}
						}else{
							$reviews[$func] = Record_Reviews::cleanupReview($reviews[$func]);
						}
					}
				}
			}
			$memCache->set("reviews_{$isbn}", $reviews, 0, $configArray['Caching']['purchased_reviews']);
		}

		//Load Editorial Reviews
		if ($includeEditorial){
			if (isset($id)){
				$recordId = $id;

				$editorialReview = new EditorialReview();
				$editorialReviewResults = array();
				$editorialReview->whereAdd("recordId = '{$recordId}'");
				$editorialReview->find();
				if ($editorialReview->N > 0){
					while ($editorialReview->fetch()){
						$editorialReviewResults[] = clone $editorialReview;
					}
				}

				//$reviews["editorialReviews"] = array();
				if (count($editorialReviewResults) > 0) {
					foreach ($editorialReviewResults AS $key=>$result ){
						$reviews["editorialReviews"][$key]["Content"] = $result->review;
						$reviews["editorialReviews"][$key]["Copyright"] = $result->source;
						$reviews["editorialReviews"][$key]["Source"] = $result->source;
						$reviews["editorialReviews"][$key]["ISBN"] = null;
						$reviews["editorialReviews"][$key]["username"] = null;

						$reviews["editorialReviews"][$key] = Record_Reviews::cleanupReview($reviews["editorialReviews"][$key]);
						if ($result->teaser){
							$reviews["editorialReviews"][$key]["Teaser"] = $result->teaser;
						}
					}
				}
			}
		}

		//Load Reviews from Good Reads
		if ($isbn){
			require_once ROOT_DIR . '/sys/NovelistFactory.php';
			$novelist = NovelistFactory::getNovelist();
			$enrichment = $novelist->loadEnrichment($isbn);
			if (isset($enrichment['goodReads'])){
				$reviews['goodReads'] = $enrichment['goodReads'];
			}
		}

		if ($reviews) {
			if (!PEAR_Singleton::isError($reviews)) {
				$interface->assign('reviews', $reviews);
			}else{
				echo($reviews);
			}

		}

		return $reviews;
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

	/**
	 * Amazon Reviews
	 *
	 * This method is responsible for connecting to Amazon AWS and abstracting
	 * customer reviews for the specific ISBN
	 *
	 * @return  array       Returns array with review data, otherwise a
	 *                      PEAR_Error.
	 * @access  public
	 * @author  Andrew Nagy <andrew.nagy@villanova.edu>
	 */
	function amazon($isbn, $id)	{
		global $library;
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		$result = null;
		if (isset($library) && $location != null){
			if ($library->showAmazonReviews == 0 || $location->showAmazonReviews == 0){
				return $result;
			}
		}else if ($location != null && ($location->showAmazonReviews == 0)){
			//return an empty review
			return $result;
		}else if (isset($library) && ($library->showAmazonReviews == 0)){
			//return an empty review
			return $result;
		}
		$params = array('ResponseGroup' => 'Reviews', 'ItemId' => $isbn);
		$request = new AWS_Request($id, 'ItemLookup', $params);
		$response = $request->sendRequest();
		if (!PEAR_Singleton::isError($response)) {
			$unxml = new XML_Unserializer();
			$result = $unxml->unserialize($response);
			if (!PEAR_Singleton::isError($result)) {
				$data = $unxml->getUnserializedData();
				if ($data['Items']['Item']['CustomerReviews']['Review']['ASIN']) {
					$data['Items']['Item']['CustomerReviews']['Review'] = array($data['Items']['Item']['CustomerReviews']['Review']);
				}
				$i = 0;
				$result = array();
				if (!empty($data['Items']['Item']['CustomerReviews']['Review'])) {
					foreach ($data['Items']['Item']['CustomerReviews']['Review'] as $review) {
						$customer = $this->getAmazonCustomer($id, $review['CustomerId']);
						if (!PEAR_Singleton::isError($customer)) {
							$result[$i]['Source'] = $customer;
						}
						$result[$i]['Rating'] = $review['Rating'];
						$result[$i]['Summary'] = $review['Summary'];
						$result[$i]['Content'] = $review['Content'];
						$i++;
					}
				}
				return $result;
			} else {
				return $result;
			}
		} else {
			return $result;
		}
	}

	/**
	 * Amazon Editorial
	 *
	 * This method is responsible for connecting to Amazon AWS and abstracting
	 * editorial reviews for the specific ISBN
	 *
	 * @return  array       Returns array with review data, otherwise a
	 *                      PEAR_Error.
	 * @access  public
	 * @author  Andrew Nagy <andrew.nagy@villanova.edu>
	 */
	function amazoneditorial($isbn, $id){
		global $library;
		$result = array();
		if (isset($library) && ($library->showAmazonReviews == 0)){
			//return an empty review
			return $result;
		}
		if (!isset($isbn)){
			return $result;
		}
		if (strlen($isbn) == 13){
			//convert to a 10 digit ISBN since Amazon likes that best.
			$isbn = ISBNConverter::convertISBN13to10($isbn);
		}
		$params = array('ResponseGroup' => 'EditorialReview', 'ItemId' => $isbn);
		$request = new AWS_Request($id, 'ItemLookup', $params);
		$response = $request->sendRequest();
		if (!PEAR_Singleton::isError($response)) {
			$unxml = new XML_Unserializer();
			$result = $unxml->unserialize($response);
			if (!PEAR_Singleton::isError($result)) {
				$data = $unxml->getUnserializedData();
				if (isset($data['Items']['Item'])){
					if (isset($data['Items']['Item']['EditorialReviews']['EditorialReview']['Source'])) {
						$data['Items']['Item']['EditorialReviews']['EditorialReview'] = array($data['Items']['Item']['EditorialReviews']['EditorialReview']);
					}

					// Filter out product description
					for ($i=0; $i<=count($data['Items']['Item']['EditorialReviews']['EditorialReview']); $i++) {
						if ($data['Items']['Item']['EditorialReviews']['EditorialReview'][$i]['Source'] == 'Product Description') {
							unset($data['Items']['Item']['EditorialReviews']['EditorialReview'][$i]);
						}
					}

					return $data['Items']['Item']['EditorialReviews']['EditorialReview'];
				}else{
					//An error of some sort occurred.
					//return $result;
					return null;
				}

			} else {
				//return $result;
				return null;
			}
		} else {
			//return $result;
			return null;
		}
	}


	/**
	 * getSyndeticsReviews
	 *
	 * This method is responsible for connecting to Syndetics and abstracting
	 * reviews from only 1 provider.
	 *
	 * It first queries the master url for the ISBN entry seeking a review url.
	 * If a review url is found, the script will then use http request to
	 * retrieve the script. The script will then parse the review according to
	 * US MARC (i believe). It will provide a link to the url master html page
	 * for more information.
	 * Configuration:  Sources are processed in order - refer to $sourceList.
	 * If your library prefers one reviewer over another change the order.
	 * If your library does not like a reviewer, remove it.  If there are more
	 * syndetics reviewers add another entry.
	 *
	 * @return  array       Returns array with review data, otherwise a
	 *                      PEAR_Error.
	 * @access  public
	 * @author  Joel Timothy Norman <joel.t.norman@wmich.edu>
	 * @author  Andrew Nagy <andrew.nagy@villanova.edu>
	 */
	function syndetics($isbn, $id){
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
		$url = 'http://syndetics.com/index.aspx?isbn=' . $isbn . '/' .
               'index.xml&client=' . $id . '&type=rw12,hw7';

		//find out if there are any reviews
		$client = new HTTP_Request();
		$client->setMethod('GET');
		$client->setURL($url);
		if (PEAR_Singleton::isError($http = $client->sendRequest())) {
			$logger->log("Error connecting to $url", PEAR_LOG_ERR);
			$logger->log("$http", PEAR_LOG_ERR);
			return $http;
		}

		// Test XML Response
		if (!($xmldoc = @DOMDocument::loadXML($client->getResponseBody()))) {
			$logger->log("Did not receive XML from $url", PEAR_LOG_ERR);
			return new PEAR_Error('Invalid XML');
		}

		$i = 0;
		foreach ($sourceList as $source => $sourceInfo) {
			$nodes = $xmldoc->getElementsByTagName($source);
			if ($nodes->length) {
				// Load reviews
				$url = 'http://syndetics.com/index.aspx?isbn=' . $isbn . '/' .
				$sourceInfo['file'] . '&client=' . $id . '&type=rw12,hw7';

				$client->setURL($url);
				if (PEAR_Singleton::isError($http = $client->sendRequest())) {
					$logger->log("Error connecting to $url", PEAR_LOG_ERR);
					$logger->log("$http", PEAR_LOG_ERR);
					continue;
				}

				// Test XML Response
				$responseBody = $client->getResponseBody();
				if (!($xmldoc2 = @DOMDocument::loadXML($responseBody))) {
					return new PEAR_Error('Invalid XML');
				}

				// Get the marc field for reviews (520)
				$nodes = $xmldoc2->GetElementsbyTagName("Fld520");
				if (!$nodes->length) {
					// Skip reviews with missing text
					continue;
				}
				$review[$i]['Content'] = html_entity_decode($xmldoc2->saveXML($nodes->item(0)));
				$review[$i]['Content'] = str_replace("<a>","<p>",$review[$i]['Content']);
				$review[$i]['Content'] = str_replace("</a>","</p>",$review[$i]['Content']);
				//echo $review[$i]['Content'];
				// Get the marc field for copyright (997)
				$nodes = $xmldoc2->GetElementsbyTagName("Fld997");
				if ($nodes->length) {
					$review[$i]['Copyright'] = html_entity_decode($xmldoc2->saveXML($nodes->item(0)));
				} else {
					$review[$i]['Copyright'] = null;
				}

				if ($review[$i]['Copyright']) {  //stop duplicate copyrights
					$location = strripos($review[0]['Content'], $review[0]['Copyright']);
					if ($location > 0) {
						$review[$i]['Content'] = substr($review[0]['Content'], 0, $location);
					}
				}

				$review[$i]['Source'] = $sourceInfo['title'];  //changes the xml to actual title
				$review[$i]['ISBN'] = $isbn; //show more link
				if (isset($configArray['BookReviews']['id'])){
					$review[$i]['username'] = $configArray['BookReviews']['id'];
				}

				$i++;
			}
		}

		return $review;
	}

	/**
	 * Load review information from Content Cafe based on the ISBN
	 *
	 * @param $id
	 * @return array
	 */
	function contentcafe($isbn, $id){
		global $library;
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		$result = null;
		if (isset($library) && $location != null){
			if ($library->showAmazonReviews == 0 || $location->showStandardReviews == 0){
				return $result;
			}
		}else if ($location != null && ($location->showStandardReviews == 0)){
			//return an empty review
			return $result;
		}else if (isset($library) && ($library->showStandardReviews == 0)){
			//return an empty review
			return $result;
		}

		//Setup the soap client to load the
		$soapClient = new SoapClient('http://contentcafe.btol.com/ContentCafe/Review.asmx?WSDL', array('features' => SOAP_SINGLE_ELEMENT_ARRAYS));

		$params = array(
    	   'UserID'   => 'EBSMARMOT',
    	   'Password' => $id,
    	   'ItemKey'  => $isbn,
		);

		try{
			$response = $soapClient->fnDetailByItemKey($params);

			$reviews = $response->fnDetailByItemKeyResult->Review;
			if (!is_null($reviews)){
				$review = array();
				$i = 0;
				foreach ($reviews as $reviewData){
					$review[$i]['Content'] = $reviewData->ReviewText;
					$review[$i]['Source'] = $reviewData->ReviewLiteral;
					$review[$i]['Copyright'] = "Content Cafe Review";
					$i++;
				}
				return $review;
			}
		}catch (Exception $e) {
			//TODO: Log the error someplace.
		}
	}

	private function getAmazonCustomer($id, $customerId){
		$params = array('ResponseGroup' => 'CustomerInfo', 'CustomerId' => $customerId);
		$request = new AWS_Request($id, 'CustomerContentLookup', $params);
		$response = $request->sendRequest();
		$result = null;
		if (!PEAR_Singleton::isError($response)) {
			$unxml = new XML_Unserializer();
			$result = $unxml->unserialize($response);
			if (!PEAR_Singleton::isError($result)) {
				$data = $unxml->getUnserializedData();
				if (isset($data['Customers']['Customer']['Name'])) {
					return $data['Customers']['Customer']['Name'];
				} elseif (isset($data['Customers']['Customer']['Nickname'])) {
					return $data['Customers']['Customer']['Nickname'];
				} else {
					return 'Anonymous';
				}
			} else {
				return $result;
			}
		} else {
			return $result;
		}

	}

}