<?php
require_once(ROOT_DIR . '/Drivers/marmot_inc/ISBNConverter.php') ;

class GoDeeperData{
	static function getGoDeeperOptions($isbn, $upc, $getDefaultData = false){
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		global $timer;
		if (is_array($upc)){
			$upc = count($upc) > 0 ? reset($upc) : '';
		}
		$validEnrichmentTypes = array();
		//Load the index page from syndetics
		if (!isset($isbn) && !isset($upc)){
			return $validEnrichmentTypes;
		}

		$goDeeperOptions = $memCache->get("go_deeper_options_{$isbn}_{$upc}");
		if (!$goDeeperOptions || isset($_REQUEST['reload'])){

			// Use Syndetics Go-Deeper Data.
			if (!empty($configArray['Syndetics']['key'])){
				$clientKey = $configArray['Syndetics']['key'];
				$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/INDEX.XML&client=$clientKey&type=xw10&upc=$upc";
				//echo($requestUrl . "\r\n");

				try{
					//Get the XML from the service
					$ctx = stream_context_create(array(
						'http' => array(
						'timeout' => 5
					)
					));
					$response = @file_get_contents($requestUrl, 0, $ctx);
					$timer->logTime("Got options from syndetics");
					//echo($response);

					//Parse the XML
					if (preg_match('/<!DOCTYPE\\sHTML.*/', $response)) {
						//The ISBN was not found in syndetics (we got an error message)
					} else {
						//Got a valid response
						$data = new SimpleXMLElement($response);

						$validEnrichmentTypes = array();
						if (isset($data)){
							if ($configArray['Syndetics']['showSummary'] && isset($data->SUMMARY)){
								$validEnrichmentTypes['summary'] = 'Summary';
								if (!isset($defaultOption)) $defaultOption = 'summary';
							}
							if ($configArray['Syndetics']['showAvSummary'] && isset($data->AVSUMMARY)){
								//AV Summary is weird since it combines both summary and table of contents for movies and music
								$avSummary = GoDeeperData::getAVSummary($isbn, $upc);
								if (isset($avSummary['summary'])){
									$validEnrichmentTypes['summary'] = 'Summary';
									if (!isset($defaultOption)) $defaultOption = 'summary';
								}
								if (isset($avSummary['trackListing'])){
									$validEnrichmentTypes['tableOfContents'] = 'Table of Contents';
									if (!isset($defaultOption)) $defaultOption = 'tableOfContents';
								}
								//$validEnrichmentTypes['avSummary'] = 'Summary';
								//if (!isset($defaultOption)) $defaultOption = 'avSummary';
							}
							if ($configArray['Syndetics']['showAvProfile'] && isset($data->AVPROFILE)){
								//Profile has similar bands and tags for music.  Not sure how to best use this
							}
							if ($configArray['Syndetics']['showToc'] && isset($data->TOC)){
								$validEnrichmentTypes['tableOfContents'] = 'Table of Contents';
								if (!isset($defaultOption)) $defaultOption = 'tableOfContents';
							}
							if ($configArray['Syndetics']['showExcerpt'] && isset($data->DBCHAPTER)){
								$validEnrichmentTypes['excerpt'] = 'Excerpt';
								if (!isset($defaultOption)) $defaultOption = 'excerpt';
							}
							if ($configArray['Syndetics']['showFictionProfile'] && isset($data->FICTION)){
								$validEnrichmentTypes['fictionProfile'] = 'Character Information';
								if (!isset($defaultOption)) $defaultOption = 'fictionProfile';
							}
							if ($configArray['Syndetics']['showAuthorNotes'] && isset($data->ANOTES)){
								$validEnrichmentTypes['authorNotes'] = 'Author Notes';
								if (!isset($defaultOption)) $defaultOption = 'authorNotes';
							}
							if ($configArray['Syndetics']['showVideoClip'] && isset($data->VIDEOCLIP)){
								//Profile has similar bands and tags for music.  Not sure how to best use this
								$validEnrichmentTypes['videoClip'] = 'Video Clip';
								if (!isset($defaultOption)) $defaultOption = 'videoClip';
							}
						}
					}
				}catch (Exception $e) {
					global $logger;
					$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERR);
					if (isset($response)){
						$logger->log($response, PEAR_LOG_INFO);
					}
				}
				$timer->logTime("Finished processing Syndetics options");
			}

			// Use Content Cafe Data
			elseif (!empty($configArray['Contentcafe']['pw']) && $configArray['Contentcafe']['pw'] != 'xxxxxx') {
				$response = self::getContentCafeData($isbn, $upc);
				if ($response != false){
					$availableContent = $response[0]->AvailableContent;
					if ($configArray['Contentcafe']['showExcerpt'] && $availableContent->Excerpt) {
						$validEnrichmentTypes['excerpt'] = 'Excerpt';
						if (!isset($defaultOption)) $defaultOption = 'excerpt';
					}
					if ($configArray['Contentcafe']['showToc'] && $availableContent->TOC) {
						$validEnrichmentTypes['tableOfContents'] = 'Table of Contents';
						if (!isset($defaultOption)) $defaultOption = 'tableOfContents';
					}
					if ($configArray['Contentcafe']['showAuthorNotes'] && $availableContent->Biography) {
						$validEnrichmentTypes['authorNotes'] = 'Author Notes';
						if (!isset($defaultOption)) $defaultOption = 'authorNotes';
					}
					if ($configArray['Contentcafe']['showSummary'] && $availableContent->Annotation) {
						$validEnrichmentTypes['summary'] = 'Summary';
						if (!isset($defaultOption)) $defaultOption = 'summary';
					}
					$timer->logTime("Finished processing Content Cafe options");
				}
			}

			$goDeeperOptions = array('options' => $validEnrichmentTypes);
			if (count($validEnrichmentTypes) > 0){
				$goDeeperOptions['defaultOption'] = $defaultOption;
			}
			$memCache->set("go_deeper_options_{$isbn}_{$upc}", $goDeeperOptions, 0, $configArray['Caching']['go_deeper_options']);
		}

		return $goDeeperOptions;
	}

	private function getContentCafeData($isbn, $upc, $field = 'AvailableContent') {
		global $configArray;

		if (isset($configArray['Contentcafe']['pw']) && strlen($configArray['Contentcafe']['pw']) > 0) {
			$pw = $configArray['Contentcafe']['pw'];
		}else{
			return false;
		}
		if (isset($configArray['Contentcafe']['id']) && strlen($configArray['Contentcafe']['id']) > 0){
			$key = $configArray['Contentcafe']['id'];
		}else{
			return false;
		}


		$url = isset($configArray['Contentcafe']['url']) ? $configArray['Contentcafe']['url'] : 'http://contentcafe2.btol.com';
		$url .= '/ContentCafe/ContentCafe.asmx?WSDL';

		$SOAP_options = array(
				'features' => SOAP_SINGLE_ELEMENT_ARRAYS, // sets how the soap responses will be handled
				'soap_version' => SOAP_1_2,
//				'trace' => 1, // turns on debugging features
		);
		$soapClient   = new SoapClient($url, $SOAP_options);

		$params = array(
				'userID'   => $key,
				'password' => $pw,
				'key'      => $isbn ? $isbn : $upc,
				'content'  => $field,
		);

		try {
			$response = $soapClient->Single($params);
			if ($response) {
				if (!isset($response->ContentCafe->Error)) {
					return $response->ContentCafe->RequestItems->RequestItem;
				} else {
					global $logger;
					$logger->log("Content Cafe Error Response for Content Type $field : ". $response->ContentCafe->Error, PEAR_LOG_ERR);
				}
			}
		} catch (Exception $e) {
			global $logger;
			$logger->log('Failed ContentCafe SOAP Request', PEAR_LOG_ERR);
		}

		return false;
	}

	static function getSummary($isbn, $upc){
		global $configArray;
		$summaryData = array();
		if (!empty($configArray['Syndetics']['key'])) {
			$summaryData = self::getSyndeticsSummary($isbn, $upc);
		} elseif (!empty($configArray['Contentcafe']['pw'])) {
			$summaryData = self::getContentCafeSummary($isbn, $upc);
		}
		return $summaryData;
	}

	private function getContentCafeSummary($isbn, $upc) {
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$memCacheKey = "contentcafe_summary_{$isbn}_{$upc}";
		$summaryData = $memCache->get($memCacheKey);
		if (!$summaryData || isset($_REQUEST['reload'])){
			$summaryData = array();
			$response = self::getContentCafeData($isbn, $upc, 'AnnotationDetail');
			if ($response) {
				$temp = array();
				if (isset($response[0]->AnnotationItems->AnnotationItem)){
					foreach ($response[0]->AnnotationItems->AnnotationItem as $summary) {
						$temp[strlen($summary->Annotation)] = $summary->Annotation;
					}
					$summaryData['summary'] = end($temp); // Grab the Longest Summary
				}
				if (!empty($summaryData['summary'])) {
					$memCache->set($memCacheKey, $summaryData, 0, $configArray['Caching']['contentcafe_sumary']);
				}else{
					$memCache->set($memCacheKey, 'no_summary', 0, $configArray['Caching']['contentcafe_sumary']);
				}
			}
		}
		if ($summaryData == 'no_summary'){
			return array();
		}else{
			return $summaryData;
		}

	}

	private function getSyndeticsSummary($isbn, $upc){
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$key = "syndetics_summary_{$isbn}_{$upc}";
		$summaryData = $memCache->get($key);

		if (!$summaryData || isset($_REQUEST['reload'])){
			try{
				$clientKey = $configArray['Syndetics']['key'];
				//Load the index page from syndetics
				$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/SUMMARY.XML&client=$clientKey&type=xw10&upc=$upc";

				//Get the XML from the service
				$ctx = stream_context_create(array(
						  'http' => array(
						  'timeout' => 2
				)
				));

				$response = @file_get_contents($requestUrl, 0, $ctx);
				if (!preg_match('/Error in Query Selection|The page you are looking for could not be found/', $response)){
					//Parse the XML
					$data = new SimpleXMLElement($response);

					$summaryData = array();
					if (isset($data)){
						if (isset($data->VarFlds->VarDFlds->Notes->Fld520->a)){
							$summaryData['summary'] = (string)$data->VarFlds->VarDFlds->Notes->Fld520->a;
						}
					}
				}

				//The summary can also be in the avsummary
				if (!isset($summaryData['summary'])){
					$avSummary = GoDeeperData::getAVSummary($isbn, $upc);
					if (isset($avSummary['summary'])){
						$summaryData['summary'] = $avSummary['summary'];
					}
				}
			}catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERR);
				$logger->log("Request URL was $requestUrl", PEAR_LOG_ERR);
				$summaryData = array();
			}
			if ($summaryData == false){
				$memCache->set($key, 'no_summary', 0, $configArray['Caching']['syndetics_summary']);
			}else{
				$memCache->set($key, $summaryData, 0, $configArray['Caching']['syndetics_summary']);
			}
		}
		if ($summaryData == 'no_summary'){
			return array();
		}else{
			return $summaryData;
		}
	}

	function getTableOfContents($isbn, $upc){
		global $configArray;
		$tocData = array();
		if (!empty($configArray['Syndetics']['key'])) {
			$tocData = self::getSyndeticsTableOfContents($isbn, $upc);
		} elseif (!empty($configArray['Contentcafe']['pw'])) {
			$tocData = self::getContentCafeTableOfContents($isbn, $upc);
		}
		return $tocData;
	}

	private function getContentCafeTableOfContents($isbn, $upc) {
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$memCacheKey = "contentcafe_toc_{$isbn}_{$upc}";
		$tocData = $memCache->get($memCacheKey);
		if (!$tocData || isset($_REQUEST['reload'])){
			$tocData = array();
			$response = self::getContentCafeData($isbn, $upc, 'TocDetail');
			if ($response) {
				$tocData['html'] = $response[0]->TocItems->TocItem[0]->Toc;
				if (!empty($tocData['html'])) {
					$memCache->set($memCacheKey, $tocData, 0, $configArray['Caching']['contentcafe_toc']);
				}
			}

		}
		return $tocData;
	}

	private function getSyndeticsTableOfContents($isbn, $upc){
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$tocData = $memCache->get("syndetics_toc_{$isbn}_{$upc}");

		if (!$tocData || isset($_REQUEST['reload'])){
			$clientKey = $configArray['Syndetics']['key'];
			//Load the index page from syndetics
			$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/TOC.XML&client=$clientKey&type=xw10&upc=$upc";

			try{
				//Get the XML from the service
				$ctx = stream_context_create(array(
					  'http' => array(
					  'timeout' => 2
				)
				));
				$response =file_get_contents($requestUrl, 0, $ctx);
				$tocData = array();

				if (!preg_match('/Error in Query Selection|The page you are looking for could not be found/', $response)){
					//Parse the XML
					$data = new SimpleXMLElement($response);


					if (isset($data)){
						if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld970)){
							foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld970 as $field){
								$tocData[] = array(
		                            'label' => (string)$field->l,
		                            'title' => (string)$field->t,
		                            'page' => (string)$field->p,
								);
							}
						}
					}
				}
				if (count($tocData) == 0){
					$avSummary = GoDeeperData::getAVSummary($isbn, $upc);
					if (isset($avSummary['trackListing'])){
						$tocData = $avSummary['trackListing'];
					}
				}

			}catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERR);
				$tocData = array();
			}
			$memCache->set("syndetics_toc_{$isbn}_{$upc}", $tocData, 0, $configArray['Caching']['syndetics_toc']);
		}
		return $tocData;
	}

	function getFictionProfile($isbn, $upc){
		//Load the index page from syndetics
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$fictionData = $memCache->get("syndetics_fiction_profile_{$isbn}_{$upc}");

		if (!$fictionData){
			$clientKey = $configArray['Syndetics']['key'];
			$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/FICTION.XML&client=$clientKey&type=xw10&upc=$upc";

			try{
				//Get the XML from the service
				$ctx = stream_context_create(array(
					  'http' => array(
					  'timeout' => 2
				)
				));
				$response =file_get_contents($requestUrl, 0, $ctx);

				//Parse the XML
				$data = new SimpleXMLElement($response);

				$fictionData = array();
				if (isset($data)){
					//Load characters
					if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld920)){
						foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld920 as $field){
							$fictionData['characters'][] = array(
	                            'name' => (string)$field->b,
	                            'gender' => (string)$field->c,
	                            'age' => (string)$field->d,
	                            'description' => (string)$field->f,
	                            'occupation' => (string)$field->g,
							);
						}

					}
					//Load subjects
					if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld950)){
						foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld950 as $field){
							$fictionData['topics'][] = (string)$field->a;
						}
					}
					//Load settings
					if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld951)){
						foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld951 as $field){
							if (isset($field->c)){
								$fictionData['settings'][] = (string)$field->a . ' -- ' . (string)$field->c;
							}else{
								$fictionData['settings'][] = (string)$field->a;
							}
						}
					}
					//Load additional settings
					if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld952)){
						foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld952 as $field){
							if (isset($field->c)){
								$fictionData['settings'][] = (string)$field->a . ' -- ' . (string)$field->c;
							}else{
								$fictionData['settings'][] = (string)$field->a;
							}
						}
					}
					//Load genres
					if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld955)){
						foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld955 as $field){
							$genre = (string)$field->a;
							$subGenres = array();
							if (isset($field->b)){
								foreach ($field->b as $subGenre){
									$subGenres[] = (string)$field->b;
								}
							}
							$fictionData['genres'][] = array(
	                            'name'=>$genre,
	                            'subGenres'=>$subGenres
							);
						}
					}
					//Load awards
					if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld985)){
						foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld985 as $field){
							$fictionData['awards'][] = array(
	                            'name' => (string)$field->a,
	                            'year' => (string)$field->y,
							);
						}

					}
				}
			}catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERR);
				$fictionData = array();
			}
			$memCache->set("syndetics_fiction_profile_{$isbn}_{$upc}", $fictionData, 0, $configArray['Caching']['syndetics_fiction_profile']);
		}
		return $fictionData;
	}

	function getAuthorNotes($isbn, $upc){
		global $configArray;
		$summaryData = array();
		if (!empty($configArray['Syndetics']['key'])) {
			$summaryData = $this->getSyndeticsAuthorNotes($isbn, $upc);
		} elseif (!empty($configArray['Contentcafe']['pw'])) {
			$summaryData = $this->getContentCafeAuthorNotes($isbn, $upc);
		}
		return $summaryData;
	}

	private function getContentCafeAuthorNotes($isbn, $upc) {
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$memCacheKey = "contentcafe_author_notes_{$isbn}_{$upc}";
		$authorData = $memCache->get($memCacheKey);
		if (!$authorData || isset($_REQUEST['reload'])){
			$authorData = array();
			$response = self::getContentCafeData($isbn, $upc, 'BiographyDetail');
			if ($response) {
				$authorData['summary'] = $response[0]->BiographyItems->BiographyItem[0]->Biography;
				if (!empty($authorData['summary'])) {
					$memCache->set($memCacheKey, $authorData, 0, $configArray['Caching']['contentcafe_author_notes']);
				}
			}

		}
		return $authorData;
	}

	private function getSyndeticsAuthorNotes($isbn, $upc){
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$summaryData = $memCache->get("syndetics_author_notes_{$isbn}_{$upc}");

		if (!$summaryData){
			$clientKey = $configArray['Syndetics']['key'];

			//Load the index page from syndetics
			$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/ANOTES.XML&client=$clientKey&type=xw10&upc=$upc";

			try{
				//Get the XML from the service
				$ctx = stream_context_create(array(
					  'http' => array(
					  'timeout' => 2
				)
				));
				$response =file_get_contents($requestUrl, 0, $ctx);

				//Parse the XML
				$data = new SimpleXMLElement($response);

				$summaryData = array();
				if (isset($data)){
					if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld980->a)){
						$summaryData['summary'] = (string)$data->VarFlds->VarDFlds->SSIFlds->Fld980->a;
					}
				}

				return $summaryData;
			}catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERR);
				$summaryData = array();
			}
			$memCache->set("syndetics_author_notes_{$isbn}_{$upc}", $summaryData, 0, $configArray['Caching']['syndetics_author_notes']);
		}
		return $summaryData;
	}

	private function getSyndeticsExcerpt($isbn, $upc) {
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$excerptData = $memCache->get("syndetics_excerpt_{$isbn}_{$upc}");

		if (!$excerptData || isset($_REQUEST['reload'])){
			$clientKey = $configArray['Syndetics']['key'];

			//Load the index page from syndetics
			$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/DBCHAPTER.XML&client=$clientKey&type=xw10&upc=$upc";

			try{
				//Get the XML from the service
				$ctx = stream_context_create(array(
						'http' => array(
								'timeout' => 2
						)
				));
				$response =file_get_contents($requestUrl, 0, $ctx);

				//Parse the XML
				$data = new SimpleXMLElement($response);

				$excerptData = array();
				if (isset($data)){
					if (isset($data->VarFlds->VarDFlds->Notes->Fld520)){
						$excerptData['excerpt'] = (string)$data->VarFlds->VarDFlds->Notes->Fld520;
						$excerptData['excerpt'] = '<p>' . str_replace(chr( 194 ) . chr( 160 ), '</p><p>', $excerptData['excerpt']) . '</p>';
					}
				}

				$memCache->set("syndetics_excerpt_{$isbn}_{$upc}", $excerptData, 0, $configArray['Caching']['syndetics_excerpt']);
			}catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERR);
				$excerptData = array();
			}
		}
		return $excerptData;
	}

	private function getContentCafeExcerpt($isbn, $upc) {
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$memCacheKey = "contentcafe_excerpt_{$isbn}_{$upc}";
		$excerptData = $memCache->get($memCacheKey);

		if (!$excerptData || isset($_REQUEST['reload'])){
			$excerptData = array();
			$response = self::getContentCafeData($isbn, $upc, 'ExcerptDetail');
			if ($response) {
				$excerptData['excerpt'] = $response[0]->ExcerptItems->ExcerptItem[0]->Excerpt;
				if (!empty($excerptData['excerpt'])) {
					$memCache->set($memCacheKey, $excerptData, 0, $configArray['Caching']['contentcafe_excerpt']);
				}
			}
		}
		return $excerptData;
	}

	function getExcerpt($isbn, $upc){
		global $configArray;
		$excerptData = array();
		if (!empty($configArray['Syndetics']['key'])) {
			$excerptData = $this->getSyndeticsExcerpt($isbn, $upc);
		} elseif (!empty($configArray['Contentcafe']['pw'])) {
			$excerptData = $this->getContentCafeExcerpt($isbn, $upc);
		}
		return $excerptData;
	}

	function getVideoClip($isbn, $upc){
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$summaryData = $memCache->get("syndetics_video_clip_{$isbn}_{$upc}");

		if (!$summaryData){
			$clientKey = $configArray['Syndetics']['key'];
			//Load the index page from syndetics
			$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/VIDEOCLIP.XML&client=$clientKey&type=xw10&upc=$upc";

			try{
				//Get the XML from the service
				$ctx = stream_context_create(array(
					  'http' => array(
					  'timeout' => 2
				)
				));
				$response =file_get_contents($requestUrl, 0, $ctx);

				//Parse the XML
				$data = new SimpleXMLElement($response);

				$summaryData = array();
				if (isset($data)){
					if (isset($data->VarFlds->VarDFlds->VideoLink)){
						$summaryData['videoClip'] = (string)$data->VarFlds->VarDFlds->VideoLink;
					}
					if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld997)){
						$summaryData['source'] = (string)$data->VarFlds->VarDFlds->SSIFlds->Fld997;
					}
				}

			}catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERR);
				$summaryData = array();
			}
			$memCache->set("syndetics_video_clip_{$isbn}_{$upc}", $summaryData, 0, $configArray['Caching']['syndetics_video_clip']);
		}

		return $summaryData;
	}

	function getAVSummary($isbn, $upc){
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$avSummaryData = $memCache->get("syndetics_av_summary_{$isbn}_{$upc}");

		if (!$avSummaryData || isset($_REQUEST['reload'])){
			$clientKey = $configArray['Syndetics']['key'];

			//Load the index page from syndetics
			$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/AVSUMMARY.XML&client=$clientKey&type=xw10&upc=$upc";

			try{
				//Get the XML from the service
				$ctx = stream_context_create(array(
					  'http' => array(
					  'timeout' => 2
				)
				));
				$response = file_get_contents($requestUrl, 0, $ctx);
				$avSummaryData = array();
				if (!preg_match('/Error in Query Selection|The page you are looking for could not be found/', $response)){
					//Parse the XML
					$data = new SimpleXMLElement($response);

					if (isset($data)){
						if (isset($data->VarFlds->VarDFlds->Notes->Fld520->a)){
							$avSummaryData['summary'] = (string)$data->VarFlds->VarDFlds->Notes->Fld520->a;
						}
						if (isset($data->VarFlds->VarDFlds->SSIFlds->Fld970)){
							foreach ($data->VarFlds->VarDFlds->SSIFlds->Fld970 as $field){
								$avSummaryData['trackListing'][] = array(
		                            'number' => (string)$field->l,
		                            'name' => (string)$field->t,
								);
							}
						}
					}
				}

				$memCache->set("syndetics_av_summary_{$isbn}_{$upc}", $avSummaryData, 0, $configArray['Caching']['syndetics_av_summary']);
			}catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", PEAR_LOG_ERR);
				$avSummaryData = array();
			}
		}
		return $avSummaryData;
	}

	static function getHtmlData($dataType, $recordType, $isbn, $upc, $id = null){
		global $interface;
		global $configArray;
		$interface->assign('recordType', $recordType);
		$id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : $_GET['id'];
		// TODO: request id is not always set here. a quick of static call
		$interface->assign('id', $id);
		$interface->assign('isbn', $isbn);
		$interface->assign('upc', $upc);

		// Use Syndetics Data
		if (!empty($configArray['Syndetics']['key'])) {
			switch (strtolower($dataType)) {
				case 'summary' :
					$data = GoDeeperData::getSummary($isbn, $upc);
					$interface->assign('summaryData', $data);
					return $interface->fetch('Record/view-syndetics-summary.tpl');
				case 'tableofcontents' :
					$data = GoDeeperData::getSyndeticsTableOfContents($isbn, $upc);
					$interface->assign('tocData', $data);
					return $interface->fetch('Record/view-syndetics-toc.tpl');
				case 'fictionprofile' :
					$data = GoDeeperData::getFictionProfile($isbn, $upc);
					$interface->assign('fictionData', $data);
					return $interface->fetch('Record/view-syndetics-fiction.tpl');
				case 'authornotes' :
					$data = GoDeeperData::getSyndeticsAuthorNotes($isbn, $upc);
					$interface->assign('authorData', $data);
					return $interface->fetch('Record/view-syndetics-author-notes.tpl');
				case 'excerpt' :
					$data = GoDeeperData::getSyndeticsExcerpt($isbn, $upc);
					$interface->assign('excerptData', $data);
					return $interface->fetch('Record/view-syndetics-excerpt.tpl');
				case 'avsummary' :
					$data = GoDeeperData::getAVSummary($isbn, $upc);
					$interface->assign('avSummaryData', $data);
					return $interface->fetch('Record/view-syndetics-av-summary.tpl');
				case 'videoclip' :
					$data = GoDeeperData::getVideoClip($isbn, $upc);
					$interface->assign('videoClipData', $data);
					return $interface->fetch('Record/view-syndetics-video-clip.tpl');
				default :
					return "Loading data for Syndetics $dataType still needs to be handled.";
			}
		}

		// Use Content Cafe Data
		elseif (!empty($configArray['Contentcafe']['pw'])) {
			switch (strtolower($dataType)) {
				case 'tableofcontents' :
					$data = GoDeeperData::getContentCafeTableOfContents($isbn, $upc);
					$interface->assign('tocData', $data);
					return $interface->fetch('Record/view-contentcafe-toc.tpl');
				case 'authornotes' :
					$data = GoDeeperData::getContentCafeAuthorNotes($isbn, $upc);
					$interface->assign('authorData', $data);
					return $interface->fetch('Record/view-syndetics-author-notes.tpl');
				case 'excerpt' :
					$data = GoDeeperData::getContentCafeExcerpt($isbn, $upc);
					$interface->assign('excerptData', $data);
					return $interface->fetch('Record/view-syndetics-excerpt.tpl');
				default :
					return "Loading data for Content Cafe $dataType still needs to be handled.";
			}

		}

		}
}