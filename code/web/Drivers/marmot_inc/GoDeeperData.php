<?php
require_once(ROOT_DIR . '/Drivers/marmot_inc/ISBNConverter.php') ;
require_once ROOT_DIR . '/sys/Syndetics/SyndeticsData.php';

class GoDeeperData{
	static function getGoDeeperOptions($isbn, $upc){
		global $configArray;
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

		$goDeeperOptions = $memCache->get("go_deeper_options_{$isbn}_$upc");
		if (!$goDeeperOptions || isset($_REQUEST['reload'])){

			// Use Syndetics Go-Deeper Data.
			require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
			$syndeticsSettings = new SyndeticsSetting();
			if ($syndeticsSettings->find(true)){
				if (!$syndeticsSettings->syndeticsUnbound){
					try {
						if ($syndeticsSettings->hasSummary || $syndeticsSettings->hasAvSummary || $syndeticsSettings->hasToc || $syndeticsSettings->hasExcerpt || $syndeticsSettings->hasFictionProfile || $syndeticsSettings->hasAuthorNotes || $syndeticsSettings->hasVideoClip) {
							$clientKey = $syndeticsSettings->syndeticsKey;
							$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/INDEX.XML&client=$clientKey&type=xw10&upc=$upc";

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
							if (!preg_match('/<!DOCTYPE\\sHTML.*/', $response)) {
								//Got a valid response
								$data = new SimpleXMLElement($response);

								$validEnrichmentTypes = array();
								if (isset($data)) {
									if ($syndeticsSettings->hasSummary && isset($data->SUMMARY)) {
										$validEnrichmentTypes['summary'] = 'Summary';
										if (!isset($defaultOption)) $defaultOption = 'summary';
									}
									if ($syndeticsSettings->hasAvSummary && isset($data->AVSUMMARY)) {
										//AV Summary is weird since it combines both summary and table of contents for movies and music
										$avSummary = GoDeeperData::getAVSummary($syndeticsSettings, $isbn, $upc);
										if (isset($avSummary['summary'])) {
											$validEnrichmentTypes['summary'] = 'Summary';
											if (!isset($defaultOption)) $defaultOption = 'summary';
										}
										if (isset($avSummary['trackListing'])) {
											$validEnrichmentTypes['tableOfContents'] = 'Table of Contents';
											if (!isset($defaultOption)) $defaultOption = 'tableOfContents';
										}
									}
									if ($syndeticsSettings->hasAvProfile && isset($data->AVPROFILE)) {
										//Profile has similar bands and tags for music.  Not sure how to best use this
										$validEnrichmentTypes['avProfile'] = 'Profile';
									}
									if ($syndeticsSettings->hasToc && isset($data->TOC)) {
										$validEnrichmentTypes['tableOfContents'] = 'Table of Contents';
										if (!isset($defaultOption)) $defaultOption = 'tableOfContents';
									}
									if ($syndeticsSettings->hasExcerpt && isset($data->DBCHAPTER)) {
										$validEnrichmentTypes['excerpt'] = 'Excerpt';
										if (!isset($defaultOption)) $defaultOption = 'excerpt';
									}
									if ($syndeticsSettings->hasFictionProfile && isset($data->FICTION)) {
										$validEnrichmentTypes['fictionProfile'] = 'Character Information';
										if (!isset($defaultOption)) $defaultOption = 'fictionProfile';
									}
									if ($syndeticsSettings->hasAuthorNotes && isset($data->ANOTES)) {
										$validEnrichmentTypes['authorNotes'] = 'Author Notes';
										if (!isset($defaultOption)) $defaultOption = 'authorNotes';
									}
									if ($syndeticsSettings->hasVideoClip && isset($data->VIDEOCLIP)) {
										//Profile has similar bands and tags for music.  Not sure how to best use this
										$validEnrichmentTypes['videoClip'] = 'Video Clip';
										if (!isset($defaultOption)) $defaultOption = 'videoClip';
									}
								}
							}
						}
					} catch (Exception $e) {
						global $logger;
						$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
						if (isset($response)) {
							$logger->log($response, Logger::LOG_NOTICE);
						}
					}
				}
				$timer->logTime("Finished processing Syndetics options");
			}

			// Use Content Cafe Data
			require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
			$contentCafeSettings = new ContentCafeSetting();
			if ($contentCafeSettings->find(true)){
				$response = self::getContentCafeData($contentCafeSettings, $isbn, $upc);
				if ($response != false){
					$availableContent = $response[0]->AvailableContent;
					if ($contentCafeSettings->hasExcerpt && $availableContent->Excerpt) {
						$validEnrichmentTypes['excerpt'] = 'Excerpt';
						if (!isset($defaultOption)) $defaultOption = 'excerpt';
					}
					if ($contentCafeSettings->hasToc && $availableContent->TOC) {
						$validEnrichmentTypes['tableOfContents'] = 'Table of Contents';
						if (!isset($defaultOption)) $defaultOption = 'tableOfContents';
					}
					if ($contentCafeSettings->hasAuthorNotes && $availableContent->Biography) {
						$validEnrichmentTypes['authorNotes'] = 'Author Notes';
						if (!isset($defaultOption)) $defaultOption = 'authorNotes';
					}
					if ($contentCafeSettings->hasSummary && $availableContent->Annotation) {
						$validEnrichmentTypes['summary'] = 'Summary';
						if (!isset($defaultOption)) $defaultOption = 'summary';
					}
					$timer->logTime("Finished processing Content Cafe options");
				}
			}

			$goDeeperOptions = array('options' => $validEnrichmentTypes);
			if (count($validEnrichmentTypes) > 0 && isset($defaultOption)){
				$goDeeperOptions['defaultOption'] = $defaultOption;
			}
			$memCache->set("go_deeper_options_{$isbn}_$upc", $goDeeperOptions, $configArray['Caching']['go_deeper_options']);
		}

		return $goDeeperOptions;
	}

	private static function getContentCafeData(ContentCafeSetting $contentCafeSettings, $isbn, $upc, $field = 'AvailableContent') {
		$url = 'https://contentcafe2.btol.com/ContentCafe/ContentCafe.asmx?WSDL';

		$SOAP_options = array(
				'features' => SOAP_SINGLE_ELEMENT_ARRAYS, // sets how the soap responses will be handled
				'soap_version' => SOAP_1_2,
//				'trace' => 1, // turns on debugging features
		);
		try {
			$soapClient = new SoapClient($url, $SOAP_options);

			$params = array(
				'userID'   => $contentCafeSettings->contentCafeId,
				'password' => $contentCafeSettings->pwd,
				'key'      => $isbn ?: $upc,
				'content'  => $field,
			);

			/** @noinspection PhpUndefinedMethodInspection */
			$response = $soapClient->Single($params);
			if ($response) {
				if (!isset($response->ContentCafe->Error)) {
					return $response->ContentCafe->RequestItems->RequestItem;
				} else {
					global $logger;
					$logger->log("Content Cafe Error Response for Content Type $field : ". $response->ContentCafe->Error, Logger::LOG_ERROR);
				}
			}
		} catch (Exception $e) {
			global $logger;
			$logger->log('Failed ContentCafe SOAP Request', Logger::LOG_ERROR);
		}

		return false;
	}

	static function getSummary($workId, $isbn, $upc){

		$summaryData = array();
		require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
		$syndeticsSettings = new SyndeticsSetting();
		if ($syndeticsSettings->find(true) && ($syndeticsSettings->syndeticsUnbound == false)){
			$summaryData = self::getSyndeticsSummary($syndeticsSettings, $workId, $isbn, $upc);
		}
		require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
		$contentCafeSettings = new ContentCafeSetting();
		if ($contentCafeSettings->find(true)){
			$summaryData = self::getContentCafeSummary($contentCafeSettings, $isbn, $upc);
		}
		return $summaryData;
	}

	/**
	 * @param ContentCafeSetting $settings
	 * @param string $isbn
	 * @param string $upc
	 * @return array|bool|mixed
	 */
	private static function getContentCafeSummary(ContentCafeSetting $settings, $isbn, $upc) {
		global $configArray;
		global $memCache;
		$memCacheKey = "contentcafe_summary_{$isbn}_{$upc}";
		$summaryData = $memCache->get($memCacheKey);
		if (!$summaryData || isset($_REQUEST['reload'])){
			$summaryData = array();
			$response = self::getContentCafeData($settings, $isbn, $upc, 'AnnotationDetail');
			if ($response) {
				$temp = array();
				if (isset($response[0]->AnnotationItems->AnnotationItem)){
					foreach ($response[0]->AnnotationItems->AnnotationItem as $summary) {
						//Correct poorly encoded quotes
						$temp[strlen($summary->Annotation)] = str_replace('&amp;&#34;', '"', $summary->Annotation);
					}
					$summaryData['summary'] = end($temp); // Grab the Longest Summary
				}
				if (!empty($summaryData['summary'])) {
					$memCache->set($memCacheKey, $summaryData, $configArray['Caching']['enrichment_data']);
				}else{
					$memCache->set($memCacheKey, 'no_summary', $configArray['Caching']['enrichment_data']);
				}
			}
		}
		if ($summaryData == 'no_summary'){
			return array();
		}else{
			return $summaryData;
		}

	}

	/**
	 * @param SyndeticsSetting $settings
	 * @param string $workId
	 * @param string $isbn
	 * @param string $upc
	 * @return array|bool|mixed
	 */
	private static function getSyndeticsSummary($settings, $workId, $isbn, $upc){
		global $configArray;

		if ($settings->hasSummary){
			global $memCache;
			$key = "syndetics_summary_{$isbn}_{$upc}";
			$summaryData = $memCache->get($key);

			if (!$summaryData || isset($_REQUEST['reload'])){
				$syndeticsData = new SyndeticsData();
				$syndeticsData->groupedRecordPermanentId = $workId;
				$syndeticsData->primaryIsbn = $isbn;
				$syndeticsData->primaryUpc = $upc;
				$doReload = false;
				if ($syndeticsData->find(true)){
					//Reload the summary every 4 weeks
					if ($syndeticsData->lastDescriptionUpdate < time() - 4 * 7 * 24 * 60 * 60){
						$doReload = true;
					}
				}else{
					$doReload = true;
				}
				if (isset($_REQUEST['reload'])){
					$doReload = true;
				}
				if ($doReload){
					try{

						//Load the index page from syndetics
						$requestUrl = "http://syndetics.com/index.aspx?isbn=$isbn/SUMMARY.XML&client={$settings->syndeticsKey}&type=xw10&upc=$upc";

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
							$avSummary = GoDeeperData::getAVSummary($settings, $isbn, $upc);
							if (isset($avSummary['summary'])){
								$summaryData['summary'] = $avSummary['summary'];
							}
						}
						if ($summaryData == false) {
							$syndeticsData->description = 'no_summary';
						}else{
							$syndeticsData->description = $summaryData['summary'];
						}
						$syndeticsData->lastDescriptionUpdate = time();
						$ret = $syndeticsData->update();
						if (!$ret){
							global $logger;
							$logger->log("An error occurred updating syndetics", Logger::LOG_WARNING);
						}
					}catch (Exception $e) {
						global $logger;
						$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
						$logger->log("Request URL was $requestUrl", Logger::LOG_ERROR);
						$summaryData = array();
					}
				}else{
					if ($syndeticsData->description == 'no_summary'){
						$summaryData = $syndeticsData->description;
					}else{
						$summaryData['summary'] = $syndeticsData->description;
					}
				}

				if ($summaryData == false){
					$memCache->set($key, 'no_summary', $configArray['Caching']['enrichment_data']);
				}else{
					$memCache->set($key, $summaryData, $configArray['Caching']['enrichment_data']);
				}
			}
			if ($summaryData == 'no_summary'){
				return array();
			}else{
				return $summaryData;
			}
		}else{
			return array();
		}
	}

	function getTableOfContents($isbn, $upc){
		$tocData = array();
		require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
		$syndeticsSettings = new SyndeticsSetting();
		if ($syndeticsSettings->find(true)){
			$tocData = self::getSyndeticsTableOfContents($syndeticsSettings, $isbn, $upc);
		}
		require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
		$contentCafeSettings = new ContentCafeSetting();
		if ($contentCafeSettings->find(true)){
			$tocData = self::getContentCafeTableOfContents($contentCafeSettings, $isbn, $upc);
		}
		return $tocData;
	}

	/**
	 * @param ContentCafeSetting $settings
	 * @param string $isbn
	 * @param string $upc
	 * @return array|bool|mixed
	 */
	private static function getContentCafeTableOfContents($settings, $isbn, $upc) {
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$memCacheKey = "contentcafe_toc_{$isbn}_{$upc}";
		$tocData = $memCache->get($memCacheKey);
		if (!$tocData || isset($_REQUEST['reload'])){
			$tocData = array();
			$response = self::getContentCafeData($settings, $isbn, $upc, 'TocDetail');
			if ($response) {
				$tocData['html'] = $response[0]->TocItems->TocItem[0]->Toc;
				if (!empty($tocData['html'])) {
					$memCache->set($memCacheKey, $tocData, $configArray['Caching']['enrichment_data']);
				}
			}

		}
		return $tocData;
	}

	/**
	 * @param SyndeticsSetting $settings
	 * @param string $isbn
	 * @param string $upc
	 * @return array|bool|mixed
	 */
	private static function getSyndeticsTableOfContents($settings, $isbn, $upc){
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$tocData = $memCache->get("syndetics_toc_{$isbn}_{$upc}");

		if (!$tocData || isset($_REQUEST['reload'])){
			$clientKey = $settings->syndeticsKey;
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
					$avSummary = GoDeeperData::getAVSummary($settings, $isbn, $upc);
					if (isset($avSummary['trackListing'])){
						$tocData = $avSummary['trackListing'];
					}
				}

			}catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
				$tocData = array();
			}
			$memCache->set("syndetics_toc_{$isbn}_{$upc}", $tocData, $configArray['Caching']['enrichment_data']);
		}
		return $tocData;
	}

	/**
	 * @param SyndeticsSetting $settings
	 * @param $isbn
	 * @param $upc
	 * @return array|bool|mixed
	 */
	static function getSyndeticsFictionProfile($settings, $isbn, $upc){
		//Load the index page from syndetics
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$fictionData = $memCache->get("syndetics_fiction_profile_{$isbn}_{$upc}");

		if (!$fictionData){
			$clientKey = $settings->syndeticsKey;
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
									$subGenres[] = $subGenre;
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
				$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
				$fictionData = array();
			}
			$memCache->set("syndetics_fiction_profile_{$isbn}_{$upc}", $fictionData, $configArray['Caching']['enrichment_data']);
		}
		return $fictionData;
	}

	/**
	 * @param ContentCafeSetting $settings
	 * @param string $isbn
	 * @param string $upc
	 * @return array|bool|mixed
	 */
	private static function getContentCafeAuthorNotes($settings, $isbn, $upc) {
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$memCacheKey = "contentcafe_author_notes_{$isbn}_{$upc}";
		$authorData = $memCache->get($memCacheKey);
		if (!$authorData || isset($_REQUEST['reload'])){
			$authorData = array();
			$response = self::getContentCafeData($settings, $isbn, $upc, 'BiographyDetail');
			if ($response) {
				$authorData['summary'] = $response[0]->BiographyItems->BiographyItem[0]->Biography;
				if (!empty($authorData['summary'])) {
					$memCache->set($memCacheKey, $authorData, $configArray['Caching']['enrichment_data']);
				}
			}

		}
		return $authorData;
	}

	/**
	 * @param SyndeticsSetting $settings
	 * @param $isbn
	 * @param $upc
	 * @return array|bool|mixed
	 */
	private static function getSyndeticsAuthorNotes($settings, $isbn, $upc){
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$summaryData = $memCache->get("syndetics_author_notes_{$isbn}_{$upc}");

		if (!$summaryData){
			$clientKey = $settings->syndeticsKey;

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
				$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
				$summaryData = array();
			}
			$memCache->set("syndetics_author_notes_{$isbn}_{$upc}", $summaryData, $configArray['Caching']['enrichment_data']);
		}
		return $summaryData;
	}

	/**
	 * @param SyndeticsSetting $settings
	 * @param $isbn
	 * @param $upc
	 * @return array|bool|mixed
	 */
	private static function getSyndeticsExcerpt($settings, $isbn, $upc) {
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$excerptData = $memCache->get("syndetics_excerpt_{$isbn}_{$upc}");

		if (!$excerptData || isset($_REQUEST['reload'])){
			$clientKey = $settings->syndeticsKey;

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

				$memCache->set("syndetics_excerpt_{$isbn}_{$upc}", $excerptData, $configArray['Caching']['enrichment_data']);
			}catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
				$excerptData = array();
			}
		}
		return $excerptData;
	}

	/**
	 * @param ContentCafeSetting $settings
	 * @param $isbn
	 * @param $upc
	 * @return array|bool|mixed
	 */
	private static function getContentCafeExcerpt($settings, $isbn, $upc) {
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$memCacheKey = "contentcafe_excerpt_{$isbn}_{$upc}";
		$excerptData = $memCache->get($memCacheKey);

		if (!$excerptData || isset($_REQUEST['reload'])){
			$excerptData = array();
			$response = self::getContentCafeData($settings, $isbn, $upc, 'ExcerptDetail');
			if ($response) {
				$excerptData['excerpt'] = $response[0]->ExcerptItems->ExcerptItem[0]->Excerpt;
				if (!empty($excerptData['excerpt'])) {
					$memCache->set($memCacheKey, $excerptData, $configArray['Caching']['enrichment_data']);
				}
			}
		}
		return $excerptData;
	}

	/**
	 * @param SyndeticsSetting $settings
	 * @param $isbn
	 * @param $upc
	 * @return array|bool|mixed
	 */
	private static function getVideoClip($settings, $isbn, $upc){
		global $configArray;
		/** @var Memcache $memCache */
		global $memCache;
		$summaryData = $memCache->get("syndetics_video_clip_{$isbn}_{$upc}");

		if (!$summaryData){
			$clientKey = $settings->syndeticsKey;
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
				$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
				$summaryData = array();
			}
			$memCache->set("syndetics_video_clip_{$isbn}_{$upc}", $summaryData, $configArray['Caching']['enrichment_data']);
		}

		return $summaryData;
	}

	/**
	 * @param SyndeticsSetting $settings
	 * @param $isbn
	 * @param $upc
	 * @return array|bool|mixed
	 */
	static function getAVSummary($settings, $isbn, $upc){
		global $configArray;
		/** @var Memcache $memCache */
		if (!$settings->hasAvSummary){
			return [];
		}
		global $memCache;
		$avSummaryData = $memCache->get("syndetics_av_summary_{$isbn}_{$upc}");

		if (!$avSummaryData || isset($_REQUEST['reload'])){
			$clientKey = $settings->syndeticsKey;

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

				$memCache->set("syndetics_av_summary_{$isbn}_{$upc}", $avSummaryData, $configArray['Caching']['enrichment_data']);
			}catch (Exception $e) {
				global $logger;
				$logger->log("Error fetching data from Syndetics $e", Logger::LOG_ERROR);
				$avSummaryData = array();
			}
		}
		return $avSummaryData;
	}

	static function getHtmlData($dataType, $recordType, $isbn, $upc){
		global $interface;

		$interface->assign('recordType', $recordType);
		$id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : $_GET['id'];
		// TODO: request id is not always set here. a quirk of static call
		$interface->assign('id', $id);
		$interface->assign('isbn', $isbn);
		$interface->assign('upc', $upc);

		// Use Syndetics Data
		require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
		$syndeticsSettings = new SyndeticsSetting();
		if ($syndeticsSettings->find(true)){
			switch (strtolower($dataType)) {
				case 'summary' :
					$data = GoDeeperData::getSyndeticsSummary($syndeticsSettings, $id, $isbn, $upc);
					$interface->assign('summaryData', $data);
					return $interface->fetch('Record/view-syndetics-summary.tpl');
				case 'tableofcontents' :
					$data = GoDeeperData::getSyndeticsTableOfContents($syndeticsSettings, $isbn, $upc);
					$interface->assign('tocData', $data);
					return $interface->fetch('Record/view-syndetics-toc.tpl');
				case 'fictionprofile' :
					$data = GoDeeperData::getSyndeticsFictionProfile($syndeticsSettings, $isbn, $upc);
					$interface->assign('fictionData', $data);
					return $interface->fetch('Record/view-syndetics-fiction.tpl');
				case 'authornotes' :
					$data = GoDeeperData::getSyndeticsAuthorNotes($syndeticsSettings, $isbn, $upc);
					$interface->assign('authorData', $data);
					return $interface->fetch('Record/view-syndetics-author-notes.tpl');
				case 'excerpt' :
					$data = GoDeeperData::getSyndeticsExcerpt($syndeticsSettings, $isbn, $upc);
					$interface->assign('excerptData', $data);
					return $interface->fetch('Record/view-syndetics-excerpt.tpl');
				case 'avsummary' :
					$data = GoDeeperData::getAVSummary($syndeticsSettings, $isbn, $upc);
					$interface->assign('avSummaryData', $data);
					return $interface->fetch('Record/view-syndetics-av-summary.tpl');
				case 'videoclip' :
					$data = GoDeeperData::getVideoClip($syndeticsSettings, $isbn, $upc);
					$interface->assign('videoClipData', $data);
					return $interface->fetch('Record/view-syndetics-video-clip.tpl');
				default :
					return "Loading data for Syndetics $dataType still needs to be handled.";
			}
		}

		// Use Content Cafe Data
		require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
		$contentCafeSettings = new ContentCafeSetting();
		if ($contentCafeSettings->find(true)){
			switch (strtolower($dataType)) {
				case 'tableofcontents' :
					$data = GoDeeperData::getContentCafeTableOfContents($contentCafeSettings, $isbn, $upc);
					$interface->assign('tocData', $data);
					return $interface->fetch('Record/view-contentcafe-toc.tpl');
				case 'authornotes' :
					$data = GoDeeperData::getContentCafeAuthorNotes($contentCafeSettings, $isbn, $upc);
					$interface->assign('authorData', $data);
					return $interface->fetch('Record/view-syndetics-author-notes.tpl');
				case 'excerpt' :
					$data = GoDeeperData::getContentCafeExcerpt($contentCafeSettings, $isbn, $upc);
					$interface->assign('excerptData', $data);
					return $interface->fetch('Record/view-syndetics-excerpt.tpl');
				default :
					return "Loading data for Content Cafe $dataType still needs to be handled.";
			}

		}

		return "Unhandled option or incorrectly configured option";
	}
}