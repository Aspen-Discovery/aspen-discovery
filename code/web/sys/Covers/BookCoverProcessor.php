<?php
require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';

class BookCoverProcessor {
	/**
	 * @var BookCoverInfo
	 */
	private $bookCoverInfo;
	private $bookCoverPath;
	private $localFile;
	private $size;
	private $id;
	private $isn;
	private $issn;
	private $upc;
	private $isEContent;
	private $type;
	private $cacheName;
	private $cacheFile;
	private $defaultCoverCacheFile; //Includes servername so each member of a consortium can have different covers
	public $error;
	/** @var null|GroupedWorkDriver */
	private $groupedWork = null;
	private $reload;
	/** @var  Logger $logger */
	private $logger;
	private $doCoverLogging = false;
	private $configArray;
	/** @var  Timer $timer */
	private $timer;
	private $doTimings;

	public function loadCover($configArray, $timer, $logger) {
		$this->configArray = $configArray;
		$this->timer = $timer;
		$this->doTimings = $this->configArray['System']['coverTimings'];
		$this->timer->enableTimings($this->doTimings);
		$this->logger = $logger;

		$this->log("Starting to load cover", Logger::LOG_NOTICE);
		$this->bookCoverPath = $configArray['Site']['coverPath'];
		if (!$this->loadParameters()) {
			return true;
		}

		if (!$this->reload) {
			$this->log("Looking for Cached cover", Logger::LOG_NOTICE);
			if ($this->getCachedCover()) {
				return true;
			}
		}

		if ($this->checkForEarlyRedirect()) {
			return true;
		}

		if($this->bookCoverInfo->imageSource == 'upload') {
			if($this->getUploadedRecordCover($this->id)) {
				return true;
			}
		}
		if ($this->type == 'open_archives') {
			if ($this->getOpenArchivesCover($this->id)) {
				return true;
			}
		} elseif ($this->type == 'list') {
			if ($this->getListCover($this->id)) {
				return true;
			}
		} elseif ($this->type == 'series') {
			if ($this->getSeriesCover($this->id)) {
				return true;
			}
		} elseif ($this->type == 'seriesMember') {
			if ($this->getSeriesMemberCover($this->id)) {
				return true;
			}
		} elseif ($this->type == 'course_reserves') {
			if ($this->getCourseReservesCover($this->id)) {
				return true;
			}
		} elseif ($this->type == 'library_calendar_event') {
			if ($this->getLibraryCalendarCover($this->id)) {
				return true;
			}
		} elseif ($this->type == 'springshare_libcal_event') {
			if ($this->getSpringshareLibCalCover($this->id)) {
				return true;
			}
		} elseif ($this->type == 'communico_event') {
			if ($this->getCommunicoCover($this->id)){
				return true;
			}
		} elseif ($this->type == 'assabet_event') {
			if ($this->getAssabetCover($this->id)){
				return true;
			}
		} elseif ($this->type == 'aspenEvent_event') {
			if ($this->getAspenEventsDateCover($this->id)){
				return true;
			}
		} elseif ($this->type == 'aspenEvent_eventRecord') {
			if ($this->getAspenEventsImageCover($this->id)){
				return true;
			}
		} elseif ($this->type == 'webpage' || $this->type == 'WebPage' || $this->type == 'BasicPage' || $this->type == 'WebResource' || $this->type == 'PortalPage' || $this->type == 'GrapesPage'
			|| $this->type == 'ResourceAudiencePage' || $this->type == 'ResourceCategoryPage' || $this->type == 'CustomResourcePage' || $this->type == 'WebResourcesAtoZ') {
			if ($this->getWebPageCover($this->id)) {
				return true;
			}
		} elseif ($this->type == 'ebsco_eds') {
			if ($this->getEbscoEdsCover($this->id)) {
				return true;
			}
		} elseif ($this->type == 'ebscohost') {
			if ($this->getEbscohostCover($this->id)) {
				return true;
			}
		} elseif ($this->type == 'summon') {
			if ($this->getSummonCover($this->id)) {
				return true;
			}
		} else {
			global $sideLoadSettings;
			if ($this->type == 'overdrive') {
				//Will exit if we find a cover
				if ($this->getOverDriveCover()) {
					return true;
				}
			} elseif ($this->type == 'hoopla') {
				//Will exit if we find a cover
				if ($this->getHooplaCover($this->id)) {
					return true;
				}
			} elseif ($this->type == 'cloud_library') {
				//Will exit if we find a cover
				if ($this->getCloudLibraryCover($this->id, true)) {
					return true;
				}
			} elseif ($this->type == 'palace_project') {
				//Will exit if we find a cover
				if ($this->getPalaceProjectCover($this->id, true)) {
					return true;
				}
			} elseif ($this->type == 'Colorado State Government Documents') {
				if ($this->getColoradoGovDocCover()) {
					return true;
				}
			} elseif ($this->type == 'Classroom Video on Demand') {
				if ($this->getClassroomVideoOnDemandCover($this->id)) {
					return true;
				}
			} elseif (stripos($this->type, 'films on demand') !== false) {
				if ($this->getFilmsOnDemandCover($this->id)) {
					return true;
				}
			} elseif (stripos($this->type, 'proquest') !== false || stripos($this->type, 'ebrary') !== false) {
				if ($this->getEbraryCover($this->id)) {
					return true;
				}
			} elseif (stripos($this->type, 'talpa') !== false) {
				$this->isn = $this->id;

				if($this->type=='talpa') {
					$record=array(
						'isbn' => $_GET['isbn']?:'',
						'title' => $_GET['title'],
						'author' => $_GET['author'],
						'upc' => $_GET['upc']?:'',
					);
					$recordToString = json_encode($record);
					$this->id = $recordToString;
				}

				if ($this->getCoverFromProvider()) {
					return true;
				}

			} elseif (stripos($this->type, 'zinio') !== false) {
				if ($this->getZinioCover($this->type . ':' . $this->id)) {
					return true;
				}
			} elseif (array_key_exists($this->type, $sideLoadSettings)) {
				if ($this->getSideLoadedCover($this->id)) {
					return true;
				}
			}

			if ($this->type == 'grouped_work' && $this->getUploadedGroupedWorkCover($this->id)) {
				return true;
			} elseif ($this->type != 'grouped_work' && $this->type != 'talpa') {
				//Check to see if we have have an uploaded cover for the work
				if ($this->loadGroupedWork()) {
					if ($this->getUploadedGroupedWorkCover($this->groupedWork->getPermanentId())) {
						return true;
					}
				}
			}

			if ($this->type == 'list' && $this->getUploadedListCover($this->id)) {
				return true;
			}

			if ($this->type != 'grouped_work' && $this->getCoverFromMarc()) {
				return true;
			}

			$this->log("Looking for cover from providers", Logger::LOG_NOTICE);
			if ($this->getCoverFromProvider()) {
				return true;
			}

			if ($this->type != 'talpa' && $this->getGroupedWorkCover()) {
				return true;
			}
		}

		$this->log("No image found, using default image", Logger::LOG_NOTICE);
		return $this->getDefaultCover();
	}

	private function getHooplaCover($id) {
		if (strpos($id, ':') !== false) {
			[
				,
				$id,
			] = explode(":", $id);
		}
		require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
		$driver = new HooplaRecordDriver($id);
		if ($driver->isValid()) {
			$coverUrl = $driver->getHooplaCoverUrl();
			return $this->processImageURL('hoopla', $coverUrl, true);
		}

		return false;
	}

	private function getSideLoadedCover($sourceAndId) {
		if (strpos($sourceAndId, ':') !== false) {
			// Side-loaded Record requires both source & id

			require_once ROOT_DIR . '/RecordDrivers/SideLoadedRecord.php';
			$driver = new SideLoadedRecord($sourceAndId);
			if ($driver && $driver->isValid()) {
				/** @var File_MARC_Data_Field[] $linkFields */
				$linkFields = $driver->getMarcRecord()->getFields('856');
				foreach ($linkFields as $linkField) {
					if ($linkField->getIndicator(1) == 4 && ($linkField->getIndicator(2) == 2 || $linkField->getIndicator(2) == 0)) {
						$coverUrl = null;
						if ($linkField->getSubfield('u') != null) {
							$coverUrl = $linkField->getSubfield('u')->getData();
						} elseif ($linkField->getSubfield('a') != null) {
							$coverUrl = $linkField->getSubfield('a')->getData();
						}
						if ($coverUrl != null) {
							$isImage = false;
							$extension = substr($coverUrl, -4);
							if ((strcasecmp($extension, '.jpg') === 0) || (strcasecmp($extension, '.gif') === 0) || (strcasecmp($extension, '.png') === 0)) {
								$isImage = true;
							} elseif ($linkField->getIndicator(1) == 4 && $linkField->getIndicator(2) == 2) {
								$isImage = true;
							}
							if ($isImage) {
								if ($this->processImageURL('sideload', $coverUrl, true)) {
									return true;
								}
							}
						}
					}
				}
			}
		}
		return false;
	}

	private function getColoradoGovDocCover() {
		$filename = "interface/themes/responsive/images/state_flag_of_colorado.png";
		if ($this->processImageURL('coloradoGovDoc', $filename, false)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param string $id When using a grouped work, the Ebrary Id should be passed to this function
	 * @return bool
	 */
	private function getEbraryCover($id) {
		if (strpos($id, ':') !== false) {
			[
				,
				$id,
			] = explode(":", $id);
		}
		$coverId = preg_replace('/^[a-zA-Z]+/', '', $id);
		$coverUrl = "http://ebookcentral.proquest.com/covers/$coverId-l.jpg";
		if ($this->processImageURL('ebrary', $coverUrl, true)) {
			return true;
		} else {
			return false;
		}
	}

	private function getClassroomVideoOnDemandCover($id) {
		if (strpos($id, ':') !== false) {
			[
				,
				$id,
			] = explode(":", $id);
		}
		$coverId = preg_replace('/^10+/', '', $id);
		$coverUrl = "http://cvod.infobase.com/image/$coverId";
		if ($this->processImageURL('classroomVideoOnDemand', $coverUrl, true)) {
			return true;
		} else {
			return false;
		}
	}

	private function getFilmsOnDemandCover($id) {
		if (strpos($id, ':') !== false) {
			[
				,
				$id,
			] = explode(":", $id);
		}
		$coverId = preg_replace('/^10+/', '', $id);
		$coverUrl = "http://fod.infobase.com/image/$coverId";
		if ($this->processImageURL('filmsOnDemand', $coverUrl, true)) {
			return true;
		} else {
			return false;
		}
	}

	private function getOverDriveCover($id = null) {
		require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
		require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductMetaData.php';
		$overDriveProduct = new OverDriveAPIProduct();
		if (strpos($id, ':') !== false) {
			[
				,
				$id,
			] = explode(":", $id);
		}
		$overDriveProduct->overdriveId = $id == null ? $this->id : $id;
		if ($overDriveProduct->find(true)) {
			$overDriveMetadata = new OverDriveAPIProductMetaData();
			$overDriveMetadata->productId = $overDriveProduct->id;
			$overDriveMetadata->find(true);
			$filename = $overDriveMetadata->cover;
			if ($filename != null) {
				return $this->processImageURL('overdrive', $filename, true);
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	private function getZinioCover($sourceAndId) {
		if (strpos($sourceAndId, ':') !== false) {
			// Side loaded Record requires both source & id

			require_once ROOT_DIR . '/RecordDrivers/SideLoadedRecord.php';
			$driver = new SideLoadedRecord($sourceAndId);
			if ($driver) {
				/** @var File_MARC_Data_Field[] $linkFields */
				$linkFields = $driver->getMarcRecord()->getFields('856');
				foreach ($linkFields as $linkField) {
					if ($linkField->getIndicator(1) == 4 && $linkField->getSubfield('3') != NULL && $linkField->getSubfield('3')->getData() == 'Image') {
						$coverUrl = $linkField->getSubfield('u')->getData();
						$coverUrl = str_replace('size=200', 'size=lg', $coverUrl);
						return $this->processImageURL('zinio', $coverUrl, true);
					}
				}
			}
		}
		return false;
	}

	private function getPalaceProjectCover($id, $createDefaultIfNotFound = false) {
		if (strpos($id, 'palace_project') === 0) {
			$id = str_replace('palace_project:', '', $id);
		}
		require_once ROOT_DIR . '/RecordDrivers/PalaceProjectRecordDriver.php';
		$driver = new PalaceProjectRecordDriver($id);
		if ($driver->isValid()) {
			$coverUrl = $driver->getPalaceProjectBookcoverUrl();
			if ($coverUrl != null) {
				return $this->processImageURL('palace_project', $coverUrl, true);
			} else {
				if ($createDefaultIfNotFound) {
					return $this->getDefaultCover($driver);
				} else {
					return false;
				}
			}
		}
		return false;
	}

	private function getCloudLibraryCover($id, $createDefaultIfNotFound = false) {
		if (strpos($id, ':') !== false) {
			[
				,
				$id,
			] = explode(":", $id);
		}
		require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
		$driver = new CloudLibraryRecordDriver($id);
		if ($driver) {
			$coverUrl = $driver->getCloudLibraryBookcoverUrl();
			if ($coverUrl != null) {
				return $this->processImageURL('cloud_library', $coverUrl, true);
			} else {
				if ($createDefaultIfNotFound) {
					return $this->getDefaultCover($driver);
				} else {
					return false;
				}
			}
		}
		return false;
	}

	private function loadParameters() {
		//Check parameters
		if (!count($_GET)) {
			$this->error = "No parameters provided.";
			return false;
		}
		$this->reload = isset($_GET['reload']);
		// Sanitize incoming parameters to avoid filesystem attacks.  We'll make sure the
		// provided size matches an accepted list, and we'll strip illegal characters from the
		// ISBN.
		$this->size = isset($_GET['size']) ? $_GET['size'] : 'small';
		if (!in_array($this->size, [
			'small',
			'medium',
			'large',
		])) {
			$this->error = "No size provided, please specify small, medium, or large.";
			return false;
		}
		if (isset($_GET['isn']) && is_array($_GET['isn'])) {
			$_GET['isn'] = array_pop($_GET['isn']);
		}
		$this->isn = isset($_GET['isn']) ? preg_replace('/[^0-9xX]/', '', $_GET['isn']) : null;
		if (strlen($this->isn) == 0) {
			$this->isn = null;
		}

		if (isset($_GET['upc']) && is_array($_GET['upc'])) {
			$_GET['upc'] = array_pop($_GET['upc']);
		}
		$this->upc = isset($_GET['upc']) ? ltrim(preg_replace('/[^0-9xX]/', '', $_GET['upc']), '0') : null;
		if (strlen($this->upc) == 0) {
			//Strip any leading zeroes
			$this->upc = null;
		}

		if (isset($_GET['issn']) && is_array($_GET['issn'])) {
			$_GET['issn'] = array_pop($_GET['issn']);
		}
		$this->issn = isset($_GET['issn']) ? preg_replace('/[^0-9xX]/', '', $_GET['issn']) : null;
		if (strlen($this->issn) == 0) {
			$this->issn = null;
		}

		if (isset($_GET['id']) && is_array($_GET['id'])) {
			$_GET['id'] = array_pop($_GET['id']);
		}
		$this->id = isset($_GET['id']) ? $_GET['id'] : '';
		//If this is external eContent, we don't care about that part, just use the remaining id
		$this->id = str_replace('external_econtent:', '', $this->id);
		if (isset($_GET['type'])) {
			$this->type = $_GET['type'];
		} else {
			if (preg_match('/[a-f\\d]{8}-[a-f\\d]{4}-[a-f\\d]{4}-[a-f\\d]{4}-[a-f\\d]{12}/', $this->id)) {
				$this->type = 'grouped_work';
			} else {
				$this->type = 'ils';
			}
		}
		if (strpos($this->id, ':') > 0 && $this->type != 'ebsco_eds' && $this->type != 'ebscohost' && $this->type !='summon') {
			[
				$this->type,
				$this->id,
			] = explode(':', $this->id, 2);
		}

		$this->bookCoverInfo = new BookCoverInfo();
		//First check to see if this has a custom cover due to being an e-book
		if (!empty($this->id)) {
			if ($this->type == 'grouped_work') {
				$this->cacheName = $this->id;
			} else {
				$this->cacheName = $this->type . '_' . $this->id;
			}
			$this->bookCoverInfo->recordId = $this->id;
			$this->bookCoverInfo->recordType = $this->type;
			$this->bookCoverInfo->find(true);
		} elseif (!is_null($this->isn)) {
			$this->cacheName = $this->isn;
			$this->bookCoverInfo->recordId = $this->isn;
			$this->bookCoverInfo->recordType = 'unknown_isbn';
			$this->bookCoverInfo->find(true);
		} elseif (!is_null($this->upc)) {
			$this->cacheName = $this->upc;
			$this->bookCoverInfo->recordId = $this->upc;
			$this->bookCoverInfo->recordType = 'unknown_upc';
		} elseif (!is_null($this->issn)) {
			$this->cacheName = $this->issn;
			$this->bookCoverInfo->recordId = $this->issn;
			$this->bookCoverInfo->recordType = 'unknown_issn';
		} else {
			$this->error = "ISN, UPC, or id must be provided.";
			return false;
		}
		$this->cacheName = preg_replace('/[^a-zA-Z0-9_.-]/', '', $this->cacheName);
		$this->cacheFile = $this->bookCoverPath . '/' . $this->size . '/' . $this->cacheName . '.png';
		/** @var Library */ global $library;
		$this->defaultCoverCacheFile = $this->bookCoverPath . '/' . $this->size . '/' . $library->subdomain . '_' . $this->cacheName . '.png';
		$this->logTime("load parameters");
		return true;
	}

	private function addCachingHeader() {
		//Add caching information
		$expires = 60 * 60 * 24 * 14;  //expire the cover in 2 weeks on the client side
		header("Cache-Control: maxage=" . $expires);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
		$this->log("Added caching header", Logger::LOG_NOTICE);
	}

	private function addModificationHeaders($filename) {
		$timestamp = filemtime($filename);
		$this->logTime("Got file timestamp $timestamp");
		$last_modified = substr(date('r', $timestamp), 0, -5) . 'GMT';
		$etag = '"' . md5($last_modified) . '"';
		$this->logTime("Got last_modified $last_modified and etag $etag");
		// Send the headers
		header("Last-Modified: $last_modified");
		header("ETag: $etag");

		if ($this->reload) {
			return true;
		}
		// See if the client has provided the required headers
		$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;
		$if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : false;
		if (!$if_modified_since && !$if_none_match) {
			$this->log("Caching headers not sent, return full image", Logger::LOG_NOTICE);
			return true;
		}
		// At least one of the headers is there - check them
		if ($if_none_match && $if_none_match != $etag) {
			$this->log("ETAG changed ", Logger::LOG_NOTICE);
			return true; // etag is there but doesn't match
		}
		if ($if_modified_since && $if_modified_since != $last_modified) {
			$this->log("Last modified changed", Logger::LOG_NOTICE);
			return true; // if-modified-since is there but doesn't match
		}
		// Nothing has changed since their last request - serve a 304 and exit
		$this->log("File has not been modified", Logger::LOG_NOTICE);
		header('HTTP/1.0 304 Not Modified');
		return false;
	}

	private function returnImage($localPath) {
		header('Content-type: image/png');
		if ($this->addModificationHeaders($localPath)) {
			$this->logTime("Added modification headers");
			$this->addCachingHeader();
			$this->logTime("Added caching headers");
			ob_clean();
			flush();
			readfile($localPath);
			$this->log("Read file $localPath", Logger::LOG_DEBUG);
			$this->logTime("echo file $localPath");
		} else {
			$this->logTime("Added modification headers");
		}
	}

	private function getCoverFromProvider() {
		// Update to allow retrieval of covers based on upc
		if ((!is_null($this->isn) || !is_null($this->upc) || !is_null($this->issn)) && !$this->bookCoverInfo->disallowThirdPartyCover) {
			$this->log("Looking for picture based on isbn and upc.", Logger::LOG_NOTICE);

			//TODO: Allow these to be sorted
			require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
			global $library;
			$syndeticsSettings = new SyndeticsSetting();
			$syndeticsSettings->id = $library->syndeticsSettingId;
			if ($syndeticsSettings->find(true)) {
				if ($this->syndetics($syndeticsSettings->syndeticsKey)) {
					return true;
				}
			}

			require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
			$contentCafeSettings = new ContentCafeSetting();
			if ($contentCafeSettings->find(true)) {
				if ($contentCafeSettings->enabled) {
					if ($this->contentCafe($contentCafeSettings)) {
						return true;
					}
				}
			}

			require_once ROOT_DIR . '/sys/Enrichment/CoceServerSetting.php';
			$coceServerSettings = new CoceServerSetting();
			if ($coceServerSettings->find(true)) {
				if ($this->coce($coceServerSettings)) {
					return true;
				}
			}
		}
		return false;
	}

	private function getCoverFromMarc($marcRecord = null) {
		$this->log("Looking for picture as part of 856 tag.", Logger::LOG_NOTICE);

		if ($marcRecord == null) {
			//Process the marc record
			require_once ROOT_DIR . '/sys/MarcLoader.php';
			if ($this->type != 'overdrive' && $this->type != 'hoopla') {
				$marcRecord = MarcLoader::loadMarcRecordByILSId($this->type . ':' . $this->id);
			}
		}

		if (!$marcRecord) {
			return false;
		}

		//Get the 856 tags
		$marcFields = $marcRecord->getFields('856');
		if ($marcFields) {
			/** @var File_MARC_Data_Field $marcField */
			foreach ($marcFields as $marcField) {
				//Check to see if this is a cover to use for VuFind
				if ($marcField->getSubfield('2') && (strcasecmp(trim($marcField->getSubfield('2')->getData()), 'Vufind_Image') == 0 || strcasecmp(trim($marcField->getSubfield('2')->getData()), 'Aspen') == 0)) {
					if ($marcField->getSubfield('3') && (strcasecmp(trim($marcField->getSubfield('3')->getData()), 'Cover Image') == 0 || strcasecmp(trim($marcField->getSubfield('3')->getData()), 'CoverImage') == 0)) {
						//Can use either subfield f or subfield u
						if ($marcField->getSubfield('f')) {
							//Just references the file, add the original directory
							$filename = $this->bookCoverPath . '/original/' . trim($marcField->getSubfield('f')->getData());
							if ($this->processImageURL('marcRecord', $filename, false)) {
								//We got a successful match
								return true;
							}
						} elseif ($marcField->getSubfield('u')) {
							//Full url to the image
							if ($this->processImageURL('marcRecord', trim($marcField->getSubfield('u')->getData()), true)) {
								//We got a successful match
								return true;
							}
						}
					}
				}
			}
		}

		//Check for Flatirons covers
		$marcFields = $marcRecord->getFields('962');
		if ($marcFields) {
			$this->log("Found 962 field", Logger::LOG_NOTICE);
			foreach ($marcFields as $marcField) {
				if ($marcField->getSubfield('u')) {
					$this->log("Found 962u subfield", Logger::LOG_NOTICE);
					$subfield_u = $marcField->getSubfield('u')->getData();
					if ($this->processImageURL('marcRecord', $subfield_u, true)) {
						return true;
					}
				}
			}
		}

		return false;
	}

	private function getCachedCover() {
		// Always check for cached covers for default and uploaded covers regardless of useOriginalCoverUrls setting.
		if (SystemVariables::getSystemVariables()->useOriginalCoverUrls &&
			$this->bookCoverInfo->imageSource !== 'default' &&
			$this->bookCoverInfo->imageSource !== 'upload') {
			return false;
		}

		if ($this->bookCoverInfo->imageSource === '') {
			return false;
		}

		$hasCachedImage = false;
		if ($this->bookCoverInfo->getNumResults() == 1) {
			if ($this->size == 'small' && $this->bookCoverInfo->thumbnailLoaded == 1) {
				$hasCachedImage = true;
			} elseif ($this->size == 'medium' && $this->bookCoverInfo->mediumLoaded == 1) {
				$hasCachedImage = true;
			} elseif ($this->size == 'large' && $this->bookCoverInfo->largeLoaded == 1) {
				$hasCachedImage = true;
			}
		}

		if ($hasCachedImage) {
			$this->bookCoverInfo->lastUsed = time();
			$this->bookCoverInfo->update();


			if ($this->bookCoverInfo->imageSource == 'default') {
				/** @var Library */ global $library;
				$fileName = $this->bookCoverPath . '/' . $this->size . '/' . $library->subdomain . '_' . $this->cacheName . '.png';
			} else {
				$fileName = "{$this->bookCoverPath}/{$this->size}/{$this->cacheName}.png";
			}

			if (file_exists($fileName)) {
				$this->log("Checking $fileName", Logger::LOG_NOTICE);
				// Load local cache if available
				$this->logTime("Found cached cover");
				$this->log("$fileName exists, returning", Logger::LOG_NOTICE);
				$this->returnImage($fileName);
			} else {
				$hasCachedImage = false;
			}
		}

		$this->logTime("Finished checking for cached cover.");
		return $hasCachedImage;
	}

	/**
	 * Display a "cover unavailable" graphic and terminate execution.
	 * @param RecordInterface $recordDriver
	 * @return bool
	 */
	function getDefaultCover($recordDriver = null) {
		//Get the resource for the cover so we can load the title and author
		$title = '';
		$author = '';
		if ($this->type == 'grouped_work') {
			$this->loadGroupedWork();
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			if ($this->groupedWork) {
				$title = ucwords($this->groupedWork->getTitle());
				$author = ucwords($this->groupedWork->getPrimaryAuthor());
			}
		} else {
			if ($recordDriver == null) {
				if($this->type=='talpa') {
					$record=array(
						'isbn' => $_GET['isbn'],
						'title' => $_GET['title'],
						'author' => $_GET['author'],
						'upc' => $_GET['upc']
					);
					$recordToString = json_encode($record);
					$this->id = $recordToString;

				}
				require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
				$recordDriver = RecordDriverFactory::initRecordDriverById($this->type . ':' . $this->id);
			}
			if ($recordDriver != null && $recordDriver->isValid()) {
				$title = $recordDriver->getTitle();
				if ($recordDriver instanceof OpenArchivesRecordDriver) {
					$author = '';
				} else {
					$author = $recordDriver->getAuthor();
				}
			}
		}

		require_once ROOT_DIR . '/sys/Covers/DefaultCoverImageBuilder.php';
		$coverBuilder = new DefaultCoverImageBuilder();
		if (strlen($title) === 0) {
			$title = 'Unknown Title';
		}
		$coverBuilder->getCover($title, $author, $this->defaultCoverCacheFile);
		return $this->processImageURL('default', $this->defaultCoverCacheFile, false);
	}

	function processImageURL($source, $url, $attemptRefetch = true) {
		$url = $this->handleOriginalCoverUrl($source, $url);

		$this->log("Processing $url", Logger::LOG_NOTICE);
		$context = stream_context_create([
			'http' => [
				'header' => "User-Agent: {$this->configArray['Catalog']['catalogUserAgent']}\r\n",
			],
		]);

		ExternalRequestLogEntry::logRequest("$source.getCover", 'GET', $url, [], '', 0, '', []);
		if ($image = @file_get_contents($url, false, $context)) {
			// Figure out file paths -- $tempFile will be used to store the downloaded
			// image for analysis.  $finalFile will be used for long-term storage if
			// $cache is true or for temporary display purposes if $cache is false.
			if ($source == 'default') {
				$tempFile = str_replace('.png', uniqid(), $this->defaultCoverCacheFile);
				$finalFile = $this->defaultCoverCacheFile;
			} else {
				$tempFile = str_replace('.png', uniqid(), $this->cacheFile);
				$finalFile = $this->cacheFile;
			}

			//Make sure we don't get an image not found cover
			$imageChecksum = md5($image);
			if ($imageChecksum == 'e89e0e364e83c0ecfba5da41007c9a2c') {
				return false;
			} elseif ($imageChecksum == 'f017f94ed618a86d0fa7cecd7112ab7e' || $imageChecksum == '798f904eabf783405079eaf699414801') {
				//Syndetics Unbound default image at medium size
				return false;
			} elseif ($imageChecksum == 'dadde13fdb5f3775cdbdd25f34c0389b' || $imageChecksum == 'b13e33c0262a3a1f21dd20b826710cbc') {
				//Syndetics Unbound default image at small size
				return false;
			} elseif ($imageChecksum == 'c6ddaf338cf667df0bf60045f05146db' || $imageChecksum == '821d0d442dbee0f51f4c803e8e9fc87a') {
				//Syndetics Unbound default image at large size
				return false;
			}

			$this->log("Processing url $url to $finalFile", Logger::LOG_DEBUG);

			// If some services can't provide an image, they will serve a 1x1 blank
			// or give us invalid image data.  Let's analyze what came back before
			// proceeding.
			if (!@file_put_contents($tempFile, $image)) {
				$this->log("Unable to write to image directory $tempFile.", Logger::LOG_ERROR);
				$this->error = "Unable to write to image directory $tempFile.";
				return false;
			}
			[
				$width,
				$height,
				$type,
			] = @getimagesize($tempFile);

			// File too small -- delete it and report failure.
			if ($width < 2 && $height < 2) {
				@unlink($tempFile);
				return false;
			}

			// Test Image for for partial load
			if (!$imageResource = @imagecreatefromstring($image)) {
				$this->log("Could not create image from string $url", Logger::LOG_ERROR);
				@unlink($tempFile);
				return false;
			}

			// Check the color of the bottom left corner
			$rgb = imagecolorat($imageResource, 0, $height - 1);
			if ($rgb == 8421504) {
				// Confirm by checking the color of the bottom right corner
				$rgb = imagecolorat($imageResource, $width - 1, $height - 1);
				if ($rgb == 8421504) {
					// This is an image with partial gray at the bottom
					// (r:128,g:128,b:128)
//				$r = ($rgb >> 16) & 0xFF;
//				$g = ($rgb >> 8) & 0xFF;
//				$b = $rgb & 0xFF;

					$this->log('Partial Gray image loaded.', Logger::LOG_ERROR);
					if ($attemptRefetch) {
						$this->log('Partial Gray image, attempting refetch.', Logger::LOG_NOTICE);
						return $this->processImageURL($source, $url, false); // Refetch once.
					}
				}
			}

			if ($this->size == 'small') {
				$maxDimension = 100;
			} elseif ($this->size == 'medium') {
				$maxDimension = 200;
			} else {
				$maxDimension = 400;
			}

			//Check to see if the image needs to be re-sized
			if ($width > $maxDimension || $height > $maxDimension) {
				// We no longer need the temp file:
				@unlink($tempFile);

				if ($width > $height) {
					$new_width = $maxDimension;
					$new_height = floor($height * ($maxDimension / $width));
				} else {
					$new_height = $maxDimension;
					$new_width = floor($width * ($maxDimension / $height));
				}

				$this->log("Resizing image New Width: $new_width, New Height: $new_height", Logger::LOG_NOTICE);

				// create a new temporary image
				$tmp_img = imagecreatetruecolor($new_width, $new_height);

				// copy and resize old image into new image
				if (!imagecopyresampled($tmp_img, $imageResource, 0, 0, 0, 0, $new_width, $new_height, $width, $height)) {
					$this->log("Could not resize image $url to $this->localFile", Logger::LOG_ERROR);
					return false;
				}

				// save thumbnail into a file
				if (file_exists($finalFile)) {
					$this->log("File $finalFile already exists, deleting", Logger::LOG_DEBUG);
					unlink($finalFile);
				}

				if (!@imagepng($tmp_img, $finalFile, 9)) {
					$this->log("Could not save re-sized file $$this->localFile", Logger::LOG_ERROR);
					return false;
				}

			} else {
				$this->log("Image is the correct size, not resizing.", Logger::LOG_NOTICE);

				// Conversion needed -- do some normalization for non-PNG images:
				if ($type != IMAGETYPE_PNG) {
					$this->log("Image is not a png, converting to png.", Logger::LOG_NOTICE);

					$conversionOk = true;
					// Try to create a GD image and rewrite as PNG, fail if we can't:
					if (!($imageResource = @imagecreatefromstring($image))) {
						$this->log("Could not create image from string $url", Logger::LOG_ERROR);
						$conversionOk = false;
					}

					if (!@imagepng($imageResource, $finalFile, 9)) {
						$this->log("Could not save image to file $url $this->localFile", Logger::LOG_ERROR);
						$conversionOk = false;
					}
					// We no longer need the temp file:
					@unlink($tempFile);
					imagedestroy($imageResource);
					if (!$conversionOk) {
						return false;
					}
					$this->log("Finished creating png at $finalFile.", Logger::LOG_NOTICE);
				} else {
					// If $tempFile is already a PNG, let's store it in the cache.
					@rename($tempFile, $finalFile);
				}
			}

			// Display the image:
			$this->returnImage($finalFile);

			$this->logTime("Finished processing image url");

			$this->setBookCoverInfo($source, $width, $height);
			return true;
		} else {
			$this->log("Could not load the file as an image $url", Logger::LOG_NOTICE);
			return false;
		}
	}

	function syndetics($key) {
		if (is_null($this->isn) && is_null($this->upc) && is_null($this->issn)) {
			return false;
		}
		switch ($this->size) {
			case 'small':
				$size = 'SC.GIF';
				break;
			case 'medium':
				$size = 'MC.GIF';
				break;
			case 'large':
				$size = 'LC.JPG';
				break;
			default:
				$size = 'SC.GIF';
		}

		$url = 'https://syndetics.com';
		$url .= "/index.aspx?type=xw12&pagename={$size}&client={$key}";
		if (!empty($this->isn)) {
			$url .= "&isbn=" . $this->isn;
		}
		if (!empty($this->upc)) {
			$url .= "&upc=" . $this->upc;
		}
		if (!empty($this->issn)) {
			$url .= "&issn=" . $this->issn;
		}
		$this->log("Syndetics url: $url", Logger::LOG_DEBUG);
		return $this->processImageURL('syndetics', $url, true);
	}

	/**
	 * Retrieve a Content Cafe cover.
	 *
	 * @param ContentCafeSetting $settings
	 *
	 * @return bool      True if image displayed, false otherwise.
	 */
	function contentCafe(ContentCafeSetting $settings) {
		switch ($this->size) {
			case 'medium':
				$size = 'M';
				break;
			case 'large':
				$size = 'L';
				break;
			case 'small':
			default:
				$size = 'S';
				break;
		}
		$url = 'http://contentcafe2.btol.com';
		$url .= "/ContentCafe/Jacket.aspx?UserID={$settings->contentCafeId}&Password={$settings->pwd}&Return=1&Type={$size}&erroroverride=1&Value=";

		if (!empty($this->isn)) {
			if ($this->processImageURL('contentCafe', $url . $this->isn, true)) {
				return true;
			}
		}
		if (!empty($this->upc)) {
			if ($this->processImageURL('contentCafe', $url . $this->upc, true)) {
				return true;
			}
		}
		if (!empty($this->issn)) {
			if ($this->processImageURL('contentCafe', $url . $this->issn, true)) {
				return true;
			}
		}

		return false;
	}

	function coce(CoceServerSetting $coceServerSetting) {
		if (!empty($this->isn)) {
			$url = $coceServerSetting->coceServerUrl;
			if (substr($url, -1, 1) !== '/') {
				$url .= '/';
			}
			//Use ISBN 10 if possible
			$isbn = $this->isn;
			if (strlen($isbn) == 13) {
				require_once ROOT_DIR . '/Drivers/marmot_inc/ISBNConverter.php';
				$isbn = ISBNConverter::convertISBN13to10($isbn);
				if (empty($isbn)) {
					$isbn = $this->isn;
				}
			}

			$url .= "cover?id={$isbn}&provider=gb,aws,ol&all";
			$results = file_get_contents($url);
			$jsonResults = json_decode($results);
			if ($jsonResults) {
				$bookCovers = $jsonResults->$isbn;
				if (!empty($bookCovers->gb)) {
					if ($this->processImageURL('coce_google_books', $bookCovers->gb, true)) {
						//Make sure we aren't getting their image not found image
						return true;
					}
				}
				if (!empty($bookCovers->aws)) {
					if ($this->processImageURL('coce_amazon', $bookCovers->aws, true)) {
						return true;
					}
				}
				if (!empty($bookCovers->ol)) {
					if ($this->processImageURL('coce_open_library', $bookCovers->ol, true)) {
						return true;
					}
				}
			}
		} else {
			return false;
		}

		return false;
	}

	function google(GoogleApiSetting $googleApiSettings, $title = null, $author = null) {
		//Only load from google if we are looking at a grouped work to be sure uploaded covers have a chance to load
		if ($this->type != 'grouped_work') {
			return false;
		}
		if (is_null($this->isn) && is_null($title) && is_null($author)) {
			return false;
		}
		if (is_null($title) && is_null($author)) {
			$source = 'google_isbn';
			$url = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . $this->isn;
		} else {
			$source = 'google_title_author';
			require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
			$title = StringUtils::removeTrailingPunctuation($title);
			$author = StringUtils::removeTrailingPunctuation($author);
			$url = 'https://www.googleapis.com/books/v1/volumes?q=intitle:"' . urlencode($title) . '"';
			if (!is_null($author)) {
				$url .= "+inauthor:" . urlencode($author);
			} else {
				return false;
			}
		}

		if (!empty($googleApiSettings->googleBooksKey)) {
			$url .= '&key=' . $googleApiSettings->googleBooksKey;
		}
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$client = new CurlWrapper();
		$result = $client->curlGetPage($url);
		if ($result !== false) {
			if ($json = json_decode($result, true)) {
				if (array_key_exists('totalItems', $json) && $json['totalItems'] > 0 && count($json['items']) > 0) {
					foreach ($json['items'] as $item) {
						if (!empty($item['volumeInfo']['imageLinks']['thumbnail'])) {
							if ($this->processImageURL($source, $item['volumeInfo']['imageLinks']['thumbnail'], true)) {
								return true;
							}
						} elseif (!empty($item['volumeInfo']['imageLinks']['smallThumbnail'])) {
							if ($this->processImageURL($source, $item['volumeInfo']['imageLinks']['thumbnail'], true)) {
								return true;
							}
						}
					}
				}
			}
		}

		return false;
	}

	function omdb(OMDBSetting $omdbSettings, $title = null, $shortTitle = null, $year = '') {
		//Only load from google if we are looking at a grouped work to be sure uploaded covers have a chance to load
		if ($this->type != 'grouped_work' || $this->bookCoverInfo->disallowThirdPartyCover) {
			return false;
		}

		$source = 'omdb_title_year';
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		$title = StringUtils::removeTrailingPunctuation($title);
		$title = str_replace('.', '', $title);
		$encodedTitle = urlencode($title);
		if (!is_array($year)) {
			$year = [$year];
		}
		foreach ($year as $curYear) {
			if (strpos($curYear, ',')) {
				$years = explode(',', $curYear);
				$year = array_merge($year, $years);
			}
		}

		$foundTitle = $this->searchOmdbForCover($year, $encodedTitle, $omdbSettings, $source);
		if ($foundTitle) {
			return true;
		}

		//Also try the short title
		$shortTitle = StringUtils::removeTrailingPunctuation($shortTitle);
		$shortTitle = str_replace('.', '', $shortTitle);
		$encodedShortTitle = urlencode($shortTitle);
		$foundTitle = $this->searchOmdbForCover($year, $encodedShortTitle, $omdbSettings, $source);
		if ($foundTitle) {
			return true;
		}

		//Next try the title up to anything with an = character
		if (strpos($title, ' = ') !== false) {
			$trimmedTitle = substr($title, 0, strpos($title, ' = '));
			$encodedTrimmedTitle = urlencode($trimmedTitle);
			$foundTitle = $this->searchOmdbForCover($year, $encodedTrimmedTitle, $omdbSettings, $source);
			if ($foundTitle) {
				return true;
			}
		}

		//Try one last time without a year
		if ($omdbSettings->fetchCoversWithoutDates) {
			$source = 'omdb_title';
			$url = "http://www.omdbapi.com/?t=$encodedTitle&apikey={$omdbSettings->apiKey}";
			$client = new CurlWrapper();
			$result = $client->curlGetPage($url);
			if ($result !== false) {
				if ($json = json_decode($result, true)) {
					if (array_key_exists('Poster', $json)) {
						if ($this->processImageURL($source, $json['Poster'], true)) {
							return true;
						}
					}
				}
			}

			//Try short title one last time without a year
			$url = "http://www.omdbapi.com/?t=$encodedShortTitle&apikey={$omdbSettings->apiKey}";
			$client = new CurlWrapper();
			$result = $client->curlGetPage($url);
			if ($result !== false) {
				if ($json = json_decode($result, true)) {
					if (array_key_exists('Poster', $json)) {
						if ($this->processImageURL($source, $json['Poster'], true)) {
							return true;
						}
					}
				}
			}
		}

		//Try to load as a tv show
		$title = preg_replace('/the complete.*season$/i', '', $title);
		$title = preg_replace('/season .*$/i', '', $title);
		$title = preg_replace('/the complete collection$/i', '', $title);
		$encodedTitle = urlencode($title);
		$url = "http://www.omdbapi.com/?t=$encodedTitle&type=series&apikey={$omdbSettings->apiKey}";
		$client = new CurlWrapper();
		$result = $client->curlGetPage($url);
		if ($result !== false) {
			if ($json = json_decode($result, true)) {
				if (array_key_exists('Poster', $json)) {
					if ($this->processImageURL($source, $json['Poster'], true)) {
						return true;
					}
				}
			}
		}

		return false;
	}

	function log($message, $level = Logger::LOG_DEBUG) {
		if ($this->doCoverLogging) {
			$this->logger->log($message, $level);
		}
	}

	function logTime($message) {
		if ($this->doTimings) {
			$this->timer->logTime($message);
		}
	}

	private function getGroupedWorkCover() {
		if ($this->loadGroupedWork()) {
			$oldType = $this->type;
			$this->type = 'grouped_work';
			if ($this->getUploadedGroupedWorkCover($this->groupedWork->getPermanentId())) {
				return true;
			}

			if ($this->getReferencedGroupedWorkCover($this->groupedWork->getPermanentId())) {
				return true;
			}

			//Have not found a grouped work based on isbn or upc, check based on related records
			$relatedRecords = $this->groupedWork->getRelatedRecords(true);
			global $sideLoadSettings;
			foreach ($relatedRecords as $relatedRecord) {
				if (strcasecmp($relatedRecord->source, 'OverDrive') == 0) {
					if ($this->getOverDriveCover($relatedRecord->id)) {
						return true;
					}
				} elseif (strcasecmp($relatedRecord->source, 'Hoopla') == 0) {
					if ($this->getHooplaCover($relatedRecord->id)) {
						return true;
					}
				} elseif (strcasecmp($relatedRecord->source, 'cloud_library') == 0) {
					if ($this->getCloudLibraryCover($relatedRecord->id)) {
						return true;
					}
				} elseif (strcasecmp($relatedRecord->source, 'palace_project') == 0) {
					if ($this->getPalaceProjectCover($relatedRecord->id)) {
						return true;
					}
				} elseif (strcasecmp($relatedRecord->source, 'Colorado State Government Documents') == 0) {
					if ($this->getColoradoGovDocCover()) {
						return true;
					}
				} elseif (strcasecmp($relatedRecord->source, 'Classroom Video on Demand') == 0) {
					if ($this->getClassroomVideoOnDemandCover($relatedRecord->id)) {
						return true;
					}
				} elseif (stripos($relatedRecord->source, 'proquest') !== false || stripos($relatedRecord->source, 'ebrary') !== false) {
					if ($this->getEbraryCover($relatedRecord->id)) {
						return true;
					}
				} elseif (stripos($relatedRecord->source, 'films on demand') !== false) {
					if ($this->getFilmsOnDemandCover($relatedRecord->id)) {
						return true;
					}
				} elseif (stripos($relatedRecord->source, 'zinio') !== false) {
					if ($this->getZinioCover($relatedRecord->id)) {
						return true;
					}
				} elseif (array_key_exists(strtolower($relatedRecord->source), $sideLoadSettings)) {
					if ($this->getSideLoadedCover($relatedRecord->id)) {
						return true;
					}
				}
				$driver = $relatedRecord->getDriver();
				if ($driver != null) {
					//Check to see if there is an uploaded cover
					if ($this->getUploadedRecordCover($relatedRecord->id)){
						return true;
					}

					//First check to see if there is a specific record defined in an 856 etc.
					if ($driver->hasMarcRecord() && $this->getCoverFromMarc($driver->getMarcRecord())) {
						return true;
					} else {
						//Finally, check the isbns if we don't have an override
						$isbns = $driver->getCleanISBNs();
						if ($isbns) {
							foreach ($isbns as $isbn) {
								$this->isn = $isbn;
								if ($this->getCoverFromProvider()) {
									return true;
								}
							}
						}
						$issns = $driver->getISSNs();
						if ($issns) {
							foreach ($issns as $issn) {
								$this->issn = $issn;
								if ($this->getCoverFromProvider()) {
									return true;
								}
							}
						}
						$upcs = $driver->getCleanUPCs();
						$this->isn = null;
						if ($upcs) {
							foreach ($upcs as $upc) {
								$this->upc = ltrim($upc, '0');
								if ($this->getCoverFromProvider()) {
									return true;
								}
								//If we tried trimming the leading zeroes, also try without.
								if ($this->upc !== $upc) {
									$this->upc = $upc;
									if ($this->getCoverFromProvider()) {
										return true;
									}
								}
							}
						}

						require_once ROOT_DIR . '/sys/SystemVariables.php';
						$systemVariables = new SystemVariables();
						if ($systemVariables->find(true) && $systemVariables->loadCoversFrom020z) {
							if (empty($isbns) && empty($upcs)) {
								//Look for an 020$z if we didn't get anything else
								$isbns = $driver->getCancelledIsbns();
								foreach ($isbns as $isbn) {
									$this->isn = $isbn;
									if ($this->getCoverFromProvider()) {
										return true;
									}
								}
							}
						}
					}
				}
			}

			if (!empty($this->groupedWork)) {
				$groupedWork = new GroupedWork();
				$groupedWork->permanent_id = $this->groupedWork->getPermanentId();
				if ($groupedWork->find(true)) {
					$groupedWorkDriver = new GroupedWorkDriver($groupedWork->permanent_id);
					if ($groupedWork->grouping_category == 'book') {
						$hasGoogleSettings = false;
						require_once ROOT_DIR . '/sys/Enrichment/GoogleApiSetting.php';
						$googleApiSettings = new GoogleApiSetting();
						if ($googleApiSettings->find(true)) {
							if (!empty($googleApiSettings->googleBooksKey)) {
								$hasGoogleSettings = true;
							}
						}

						//Only look by ISBN if we don't have Coce support
						require_once ROOT_DIR . '/sys/Enrichment/CoceServerSetting.php';
						$coceServerSettings = new CoceServerSetting();
						$hasCoceSettings = false;
						if ($coceServerSettings->find(true)) {
							$hasCoceSettings = true;
						}
						//Load based on ISBNs first
						$allIsbns = $groupedWorkDriver->getISBNs();
						foreach ($allIsbns as $isbn) {
							$this->isn = $isbn;
							if ($this->getCoverFromProvider()) {
								return true;
							} else {
								if (!$hasCoceSettings && $hasGoogleSettings && $this->google($googleApiSettings)) {
									return true;
								}
							}
						}
						if ($hasGoogleSettings && $this->google($googleApiSettings, $groupedWorkDriver->getTitle(), $groupedWorkDriver->getPrimaryAuthor())) {
							return true;
						}
					}
					if ($groupedWorkDriver->getFormatCategory() == 'Movies') {
						require_once ROOT_DIR . '/sys/Enrichment/OMDBSetting.php';
						$omdbSettings = new OMDBSetting();
						if ($omdbSettings->find(true)) {
							if ($this->omdb($omdbSettings, $groupedWorkDriver->getTitle(), $groupedWorkDriver->getShortTitle(), $groupedWorkDriver->getPublicationDates())) {
								return true;
							}
						}
					}
				}
			}
			$this->type = $oldType;
		}
		return false;
	}

	private function loadGroupedWork() {
		if ($this->groupedWork == null) {
			// Include Search Engine Class
			require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';

			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			if ($this->type == 'grouped_work') {
				$this->groupedWork = new GroupedWorkDriver($this->id);
				if (!$this->groupedWork->isValid) {
					$this->groupedWork = false;
				}
			} else {
				require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
				$recordDriver = RecordDriverFactory::initRecordDriverById($this->type . ':' . $this->id);
				if ($recordDriver && $recordDriver->isValid()) {
					$this->groupedWork = $recordDriver->getGroupedWorkDriver();
					if (!$this->groupedWork->isValid) {
						$this->groupedWork = false;
					}
				}

			}
		}
		return $this->groupedWork;
	}

	private function setBookCoverInfo($source, $width, $height) {
		$this->bookCoverInfo->imageSource = $source;
		if ($this->bookCoverInfo->sourceWidth == null || $width > $this->bookCoverInfo->sourceWidth) {
			$this->bookCoverInfo->sourceWidth = $width;
		}
		if ($this->bookCoverInfo->sourceHeight == null || $width > $this->bookCoverInfo->sourceHeight) {
			$this->bookCoverInfo->sourceHeight = $height;
		}
		$this->bookCoverInfo->lastUsed = time();
		if ($this->size == 'small') {
			$this->bookCoverInfo->thumbnailLoaded = true;
		} elseif ($this->size == 'medium') {
			$this->bookCoverInfo->mediumLoaded = true;
		} elseif ($this->size == 'largeLoaded') {
			$this->bookCoverInfo->largeLoaded = true;
		}
		$this->bookCoverInfo->uploadedImage = false;
		if ($this->bookCoverInfo->getNumResults() == 0) {
			$this->bookCoverInfo->firstLoaded = time();
			$this->bookCoverInfo->insert();
		} else {
			$this->bookCoverInfo->update();
		}
	}

	private function getOpenArchivesCover($id) {
		//The thumbnail is not saved in the metadata.  To get the URL we need to fetch the page
		//and then get the thumbnail from the og:image element
		require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesRecord.php';
		$openArchivesRecord = new OpenArchivesRecord();
		$openArchivesRecord->id = $id;
		if ($openArchivesRecord->find(true)) {
			$url = $openArchivesRecord->permanentUrl;
			//Need the full curl wrapper to handle redirects
			require_once ROOT_DIR . '/sys/CurlWrapper.php';
			$curlWrapper = new CurlWrapper();
			$pageContents = $curlWrapper->curlGetPage($url);
			$curlInfo = curl_getinfo($curlWrapper->curl_connection);
			if ($curlInfo['url'] != $url) {
				//If these don't match, some form of redirect was done.
				$url = $curlInfo['url'];
			}
			$curlWrapper->close_curl();
			$matches = [];
			if (preg_match('~<meta property="og:image" content="(.*?)" />~', $pageContents, $matches)) {
				$bookcoverUrl = $matches[1];
				if ($this->processImageURL('open_archives', $bookcoverUrl, true)){
					return true;
				}
			}
			if (preg_match('~<img src="(.*?)" border="0" alt="Thumbnail image">~', $pageContents, $matches)) {
				$bookcoverUrl = $matches[1];
				if (strpos($bookcoverUrl, 'http') !== 0) {
					$urlComponents = parse_url($url);
					$bookcoverUrl = $urlComponents['scheme'] . '://' . $urlComponents['host'] . $bookcoverUrl;
				}
				if ($this->processImageURL('open_archives', $bookcoverUrl, true)){
					return true;
				}
			}
			if (preg_match('~<div id="item-images">.*<img src="(.*?)".*>~', $pageContents, $matches)) {
				$bookcoverUrl = $matches[1];
				if (strpos($bookcoverUrl, 'http') !== 0) {
					$urlComponents = parse_url($url);
					$bookcoverUrl = $urlComponents['scheme'] . '://' . $urlComponents['host'] . $bookcoverUrl;
				}
				if ($this->processImageURL('open_archives', $bookcoverUrl, true)){
					return true;
				}
			}
			if (preg_match('/\\\\"thumbnailUri\\\\":\\\\"(.*?)\\\\"/', $pageContents, $matches)) {
				$bookcoverUrl = $matches[1];
				if (strpos($bookcoverUrl, 'http') !== 0) {
					$urlComponents = parse_url($url);
					$bookcoverUrl = $urlComponents['scheme'] . '://' . $urlComponents['host'] . '/digital' . $bookcoverUrl;
				}
				$bookcoverUrl = str_replace('\/', '/', $bookcoverUrl);
				if ($this->processImageURL('open_archives', $bookcoverUrl, true)){
					return true;
				}
			}
			if (preg_match('~<img class=".*?img-preview-large.*?".*?src="(.*?)".*>~', $pageContents, $matches)) {
				$bookcoverUrl = $matches[1];
				if (strpos($bookcoverUrl, 'http') !== 0) {
					$urlComponents = parse_url($url);
					$bookcoverUrl = $urlComponents['scheme'] . '://' . $urlComponents['host'] . $bookcoverUrl;
				}
				$bookcoverUrl = str_replace('\/', '/', $bookcoverUrl);
				if ($this->processImageURL('open_archives', $bookcoverUrl, true)){
					return true;
				}
			}
			if (preg_match('~<img class=".*?img-thumbnail.*?".*?src="(.*?)".*>~', $pageContents, $matches)) {
				$bookcoverUrl = $matches[1];
				if (strpos($bookcoverUrl, 'http') !== 0) {
					$urlComponents = parse_url($url);
					$bookcoverUrl = $urlComponents['scheme'] . '://' . $urlComponents['host'] . $bookcoverUrl;
				}
				$bookcoverUrl = str_replace('\/', '/', $bookcoverUrl);
				if ($this->processImageURL('open_archives', $bookcoverUrl, true)){
					return true;
				}
			}

			//Create default image
			require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesCollection.php';
			$sourceCollection = new OpenArchivesCollection();
			$sourceCollection->id = $openArchivesRecord->sourceCollection;
			if ($sourceCollection->find(true)) {
				if (!empty($sourceCollection->imageRegex)) {
					$expressions = preg_split("/[\r\n]+/", $sourceCollection->imageRegex);
					foreach ($expressions as $expression) {
						if (!empty($expression) && preg_match('~' . $expression . '~i', $pageContents, $matches)) {
							$bookcoverUrl = str_replace('&amp;', '&', $matches[1]);
							if ($this->processImageURL('open_archives', $bookcoverUrl, true)) {
								return true;
							}
						}
					}
				}
				if (!empty($sourceCollection->defaultCover)) {
					//Build a cover based on the title of the page
					require_once ROOT_DIR . '/sys/Covers/DefaultCoverImageBuilder.php';
					$coverBuilder = new DefaultCoverImageBuilder();
					require_once ROOT_DIR . '/RecordDrivers/OpenArchivesRecordDriver.php';

					$OAIRecordDriver = new OpenArchivesRecordDriver($id);
					if ($OAIRecordDriver->isValid()) {
						$title = $OAIRecordDriver->getTitle();
						$author = null;

						$image = ROOT_DIR . '/files/original/' . $sourceCollection->defaultCover;
						$coverBuilder->getCover($title, $author, $this->cacheFile, $image);
						if ($this->processImageURL('open_archives',  $this->cacheFile, false)) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}

	private function getListCover($id) {
		//Build a cover based on the titles within a list
		require_once ROOT_DIR . '/sys/Covers/ListCoverBuilder.php';
		$coverBuilder = new ListCoverBuilder();
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$userList = new UserList();
		$userList->id = $id;

		if ($userList->find(true)) {
			if ($this->getUploadedListCover($id)) {
				return true;
			} else {
				$title = $userList->title;
				$listTitles = $userList->getListTitles();
				$coverBuilder->getCover($title, $listTitles, $this->cacheFile);
				return $this->processImageURL('default', $this->cacheFile, false);
			}
		} else {
			return false;
		}
	}

	private function getSeriesCover($id) {
		//Build a cover based on the titles within series
		require_once ROOT_DIR . '/sys/Covers/SeriesCoverBuilder.php';
		$coverBuilder = new SeriesCoverBuilder();
		require_once ROOT_DIR . '/sys/Series/Series.php';
		$series = new Series();
		$series->id = $id;

		if ($series->find(true)) {
			if ($this->getUploadedSeriesCover($series->cover)) {
				return true;
			} else {
				$title = $series->displayName;
				$seriesTitles = $series->getSeriesMembers();
				$coverBuilder->getCover($title, $seriesTitles, $this->cacheFile);
				return $this->processImageURL('default', $this->cacheFile, false);
			}
		} else {
			return false;
		}
	}

	private function getSeriesMemberCover($id) {
		//Build a cover based on the titles within list
		require_once ROOT_DIR . '/sys/Covers/DefaultCoverImageBuilder.php';
		$coverBuilder = new DefaultCoverImageBuilder();
		require_once ROOT_DIR . '/sys/Series/SeriesMember.php';
		$seriesMember = new SeriesMember();
		$seriesMember->id = $id;

		if ($seriesMember->find(true)) {
			if ($this->getUploadedSeriesMemberCover($seriesMember->cover)) {
				return true;
			} else {
				$title = $seriesMember->displayName;
				$author = $seriesMember->author;
				$coverBuilder->getCover($title, $author, $this->cacheFile);
				return $this->processImageURL('default', $this->cacheFile, false);
			}
		} else {
			return false;
		}
	}

	private function getCourseReservesCover($id) {
		if (strpos($id, ':') !== false) {
			[
				,
				$id,
			] = explode(":", $id);
		}
		require_once ROOT_DIR . '/RecordDrivers/CourseReservesRecordDriver.php';
		$driver = new CourseReservesRecordDriver($id);
		if ($driver) {
			require_once ROOT_DIR . '/sys/Covers/CourseReservesCoverBuilder.php';
			$coverBuilder = new CourseReservesCoverBuilder();
			$coverBuilder->getCover($driver->getTitle() . ' - ' . $driver->getPrimaryAuthor(), $this->cacheFile);
			return $this->processImageURL('default_event', $this->cacheFile, false);
		}
		return false;
	}


	private function getLibraryCalendarCover($id) {
		if (strpos($id, ':') !== false) {
			[
				,
				$id,
			] = explode(":", $id);
		}
		require_once ROOT_DIR . '/RecordDrivers/LibraryCalendarEventRecordDriver.php';
		$driver = new LibraryCalendarEventRecordDriver($id);
		if (!($driver->isValid())){ //if driver isn't valid, likely a past event on a list
			require_once ROOT_DIR . '/sys/Covers/EventCoverBuilder.php';
			require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
			$coverBuilder = new EventCoverBuilder();
			$userEntry = new UserEventsEntry();
			$userEntry->sourceId = $id;
			if ($userEntry->find(true)){
				$startDate = new DateTime("@$userEntry->eventDate");
				$startDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
				$props = [
					'eventDate' => $startDate,
					'isPastEvent' => true,
				];
				$title = $userEntry->title;
			} else{
				$props = [
					'eventDate' => $driver->getStartDateFromDB($id),
					'isPastEvent' => true,
				];
				$title = $driver->getTitleFromDB($id);
			}
			$coverBuilder->getCover($title, $this->cacheFile, $props);
			return $this->processImageURL('default_event', $this->cacheFile, false);
		} else if ($driver) {
			require_once ROOT_DIR . '/sys/Covers/EventCoverBuilder.php';
			$coverBuilder = new EventCoverBuilder();
			$isPast = false;
			if (array_key_exists('isPast', $_REQUEST)){
				$isPast = $_REQUEST['isPast'];
			}
			$props = [
				'eventDate' => $driver->getStartDate(),
				'isPastEvent' => $isPast,
			];
			$coverBuilder->getCover($driver->getTitle(), $this->cacheFile, $props);
			return $this->processImageURL('default_event', $this->cacheFile, false);
		}
		return false;
	}

	private function getSpringshareLibCalCover($id) {
		if (strpos($id, ':') !== false) {
			[
				,
				$id,
			] = explode(":", $id);
		}
		require_once ROOT_DIR . '/RecordDrivers/SpringshareLibCalEventRecordDriver.php';
		$driver = new SpringshareLibCalEventRecordDriver($id);
		if (!($driver->isValid())){ //if driver isn't valid, likely a past event on a list
			require_once ROOT_DIR . '/sys/Covers/EventCoverBuilder.php';
			require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
			$coverBuilder = new EventCoverBuilder();
			$userEntry = new UserEventsEntry();
			$userEntry->sourceId = $id;
			if ($userEntry->find(true)){
				$startDate = new DateTime("@$userEntry->eventDate");
				$startDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
				$props = [
					'eventDate' => $startDate,
					'isPastEvent' => true,
				];
				$title = $userEntry->title;
			} else{
				$props = [
					'eventDate' => $driver->getStartDateFromDB($id),
					'isPastEvent' => true,
				];
				$title = $driver->getTitleFromDB($id);
			}
			$coverBuilder->getCover($title, $this->cacheFile, $props);
			return $this->processImageURL('default_event', $this->cacheFile, false);
		} else if ($driver) {
			require_once ROOT_DIR . '/sys/Covers/EventCoverBuilder.php';
			$coverBuilder = new EventCoverBuilder();
			$isPast = false;
			if (array_key_exists('isPast', $_REQUEST)){
				$isPast = $_REQUEST['isPast'];
			}
			$props = [
				'eventDate' => $driver->getStartDate(),
				'isPastEvent' => $isPast,
			];
			$coverBuilder->getCover($driver->getTitle(), $this->cacheFile, $props);
			return $this->processImageURL('default_event', $this->cacheFile, false);
		}
		return false;
	}

	private function getCommunicoCover($id) {
		if (strpos($id, ':') !== false) {
			[
				,
				$id,
			] = explode(":", $id);
		}
		require_once ROOT_DIR . '/RecordDrivers/CommunicoEventRecordDriver.php';
		$driver = new CommunicoEventRecordDriver($id);
		if (!($driver->isValid())){ //if driver isn't valid, likely a past event on a list
			require_once ROOT_DIR . '/sys/Covers/EventCoverBuilder.php';
			require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
			$coverBuilder = new EventCoverBuilder();
			$userEntry = new UserEventsEntry();
			$userEntry->sourceId = $id;
			if ($userEntry->find(true)){
				$startDate = new DateTime("@$userEntry->eventDate");
				$startDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
				$props = [
					'eventDate' => $startDate,
					'isPastEvent' => true,
				];
				$title = $userEntry->title;
			} else{
				$props = [
					'eventDate' => $driver->getStartDateFromDB($id),
					'isPastEvent' => true,
				];
				$title = $driver->getTitleFromDB($id);
			}
			$coverBuilder->getCover($title, $this->cacheFile, $props);
			return $this->processImageURL('default_event', $this->cacheFile, false);
		} else if ($driver) {
			require_once ROOT_DIR . '/sys/Covers/EventCoverBuilder.php';
			$coverBuilder = new EventCoverBuilder();
			$isPast = false;
			if (array_key_exists('isPast', $_REQUEST)){
				$isPast = $_REQUEST['isPast'];
			}
			$props = [
				'eventDate' => $driver->getStartDate(),
				'isPastEvent' => $isPast,
			];
			$coverBuilder->getCover($driver->getTitle(), $this->cacheFile, $props);
			return $this->processImageURL('default_event', $this->cacheFile, false);
		}
		return false;
	}

	private function getAssabetCover($id) {
		if (strpos($id, ':') !== false) {
			[
				,
				$id,
			] = explode(":", $id);
		}
		require_once ROOT_DIR . '/RecordDrivers/AssabetEventRecordDriver.php';
		$driver = new AssabetEventRecordDriver($id);
		if (!($driver->isValid())){ //if driver isn't valid, likely a past event on a list
			require_once ROOT_DIR . '/sys/Covers/EventCoverBuilder.php';
			require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
			$coverBuilder = new EventCoverBuilder();
			$userEntry = new UserEventsEntry();
			$userEntry->sourceId = $id;
			if ($userEntry->find(true)){
				$startDate = new DateTime("@$userEntry->eventDate");
				$startDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
				$props = [
					'eventDate' => $startDate,
					'isPastEvent' => true,
				];
				$title = $userEntry->title;
			} else{
				$props = [
					'eventDate' => $driver->getStartDateFromDB($id),
					'isPastEvent' => true,
				];
				$title = $driver->getTitleFromDB($id);
			}
			$coverBuilder->getCover($title, $this->cacheFile, $props);
			return $this->processImageURL('default_event', $this->cacheFile, false);
		} else if ($driver) {
			require_once ROOT_DIR . '/sys/Covers/EventCoverBuilder.php';
			$coverBuilder = new EventCoverBuilder();
			$isPast = false;
			if (array_key_exists('isPast', $_REQUEST)){
				$isPast = $_REQUEST['isPast'];
			}
			$props = [
				'eventDate' => $driver->getStartDate(),
				'isPastEvent' => $isPast,
			];
			$coverBuilder->getCover($driver->getTitle(), $this->cacheFile, $props);
			return $this->processImageURL('default_event', $this->cacheFile, false);
		}
		return false;
	}

	private function getAspenEventsDateCover($id) {
		if (strpos($id, ':') !== false) {
			[
				,
				$id,
			] = explode(":", $id);
		}
		require_once ROOT_DIR . '/RecordDrivers/AspenEventRecordDriver.php';
		$driver = new AspenEventRecordDriver($id);
		if (!($driver->isValid())){ //if driver isn't valid, likely a past event on a list
			require_once ROOT_DIR . '/sys/Covers/EventCoverBuilder.php';
			require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
			$coverBuilder = new EventCoverBuilder();
			$userEntry = new UserEventsEntry();
			$userEntry->sourceId = $id;
			if ($userEntry->find(true)){
				$startDate = new DateTime("@$userEntry->eventDate");
				$startDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
				$props = [
					'eventDate' => $startDate,
					'isPastEvent' => true,
				];
				$title = $userEntry->title;
			} else{
				$props = [
					'eventDate' => $driver->getStartDateFromDB($id),
					'isPastEvent' => true,
				];
				$title = $driver->getTitleFromDB($id);
			}
			$coverBuilder->getCover($title, $this->cacheFile, $props);
			return $this->processImageURL('default_event', $this->cacheFile, false);
		} else if ($driver) {
			require_once ROOT_DIR . '/sys/Covers/EventCoverBuilder.php';
			$coverBuilder = new EventCoverBuilder();
			$isPast = false;
			if (array_key_exists('isPast', $_REQUEST)){
				$isPast = $_REQUEST['isPast'];
			}
			$props = [
				'eventDate' => $driver->getStartDate(),
				'isPastEvent' => $isPast,
			];
			$coverBuilder->getCover($driver->getTitle(), $this->cacheFile, $props);
			return $this->processImageURL('default_event', $this->cacheFile, false);
		}
		return false;
	}

	private function getAspenEventsImageCover($id) {
		if (strpos($id, ':') !== false) {
			[
				,
				$id,
			] = explode(":", $id);
		}
		require_once ROOT_DIR . '/RecordDrivers/AspenEventRecordDriver.php';
		$driver = new AspenEventRecordDriver($id);
		if (($driver->isValid()) && $driver->getCoverImagePath()) {
			$uploadedImage = $driver->getCoverImagePath();
			if (file_exists($uploadedImage)) {
				return $this->processImageURL('upload', $uploadedImage);
			}
		}
		return false;
	}

	private function getWebPageCover($id) {
		//Build a cover based on the title of the page
		require_once ROOT_DIR . '/sys/Covers/WebPageCoverBuilder.php';
		$coverBuilder = new WebPageCoverBuilder();

		$recordDriver = null;
		if ($this->type == 'WebPage') {
			require_once ROOT_DIR . '/RecordDrivers/WebsitePageRecordDriver.php';
			$recordDriver = new WebsitePageRecordDriver($this->type . ':' . $id);
		} elseif ($this->type == 'BasicPage') {
			require_once ROOT_DIR . '/RecordDrivers/BasicPageRecordDriver.php';
			$recordDriver = new BasicPageRecordDriver($this->type . ':' . $id);
		} elseif ($this->type == 'PortalPage') {
			require_once ROOT_DIR . '/RecordDrivers/PortalPageRecordDriver.php';
			$recordDriver = new PortalPageRecordDriver($this->type . ':' . $id);
		} elseif ($this->type == 'WebResource') {
			require_once ROOT_DIR . '/RecordDrivers/WebResourceRecordDriver.php';
			$recordDriver = new WebResourceRecordDriver($this->type . ':' . $id);
		} elseif ($this->type == 'GrapesPage') {
			require_once ROOT_DIR . '/RecordDrivers/GrapesPageRecordDriver.php';
			$recordDriver = new GrapesPageRecordDriver($this->type . ':' . $id);
		} elseif ($this->type == 'ResourceAudiencePage' ||$this->type == 'ResourceCategoryPage' || $this->type == 'CustomResourcePage') {
			require_once ROOT_DIR . '/RecordDrivers/CustomResourcePagesRecordDriver.php';
			$recordDriver = new CustomResourcePagesRecordDriver($this->type . ':' . $id);
		} elseif ($this->type == 'WebResourcesAtoZ') {
			require_once ROOT_DIR . '/RecordDrivers/CustomResourcePagesRecordDriver.php';
			$recordDriver = new CustomResourcePagesRecordDriver($this->type);
		}

		if ($recordDriver != null && $recordDriver->isValid()) {
			$title = $recordDriver->getTitle();
			$coverBuilder->getCover($title, $this->cacheFile);
			return $this->processImageURL('default_webpage', $this->cacheFile, false);
		} else {
			return false;
		}
	}

	private function getUploadedListCover($id) {
		$uploadedImage = $this->bookCoverPath . '/original/lists/' . $id . '.png';
		$source = $this->bookCoverInfo->imageSource ?? '';
		if (($source == 'upload' || $source == '') && file_exists($uploadedImage)) {
			return $this->processImageURL($source, $uploadedImage);
		}
		return false;
	}

	private function getUploadedSeriesCover($cover) {
		$uploadedImage = $this->bookCoverPath . '/original/series/' . $cover;
		if (file_exists($uploadedImage)) {
			return $this->processImageURL('upload', $uploadedImage);
		}
		return false;
	}

	private function getUploadedSeriesMemberCover($cover) {
		$uploadedImage = $this->bookCoverPath . '/original/seriesMember/' . $cover;
		if (file_exists($uploadedImage)) {
			return $this->processImageURL('upload', $uploadedImage);
		}
		return false;
	}

	private function getUploadedGroupedWorkCover($permanentId) {
		$okToLoad = false;
		if($this->type == 'grouped_work' && $this->bookCoverInfo->imageSource == 'upload') {
			$okToLoad = true;
		}else{
			$bookCoverInfo = new BookCoverInfo();
			$bookCoverInfo->recordType = 'grouped_work';
			$bookCoverInfo->recordId = $permanentId;
			if ($bookCoverInfo->find(true)) {
				if ($bookCoverInfo->imageSource == 'upload') {
					$okToLoad = true;
				}
			}
			if (!$okToLoad && strlen($permanentId) == 40) {
				$bookCoverInfo = new BookCoverInfo();
				$bookCoverInfo->recordType = 'grouped_work';
				$bookCoverInfo->recordId = substr($permanentId, 0, 36);
				if ($bookCoverInfo->find(true)) {
					if ($bookCoverInfo->imageSource == 'upload') {
						$okToLoad = true;
					}
				}
			}
		}

		if ($okToLoad) {
			$uploadedImage = $this->bookCoverPath . '/original/' . $permanentId . '.png';
			if (file_exists($uploadedImage)) {
				return $this->processImageURL('upload', $uploadedImage);
			} elseif (strlen($permanentId) == 40) {
				$permanentId = substr($permanentId, 0, 36);
				$uploadedImage = $this->bookCoverPath . '/original/' . $permanentId . '.png';
				if (file_exists($uploadedImage)) {
					return $this->processImageURL('upload', $uploadedImage);
				}
			}
		}
		return false;
	}

	private function getUploadedRecordCover($id) {
		if (strpos($id, ':') !== false) {
			[
				,
				$id,
			] = explode(':', $id);
		}
		if ($this->bookCoverInfo->getRecordType() != 'series' && $this->bookCoverInfo->getRecordType() != 'seriesMember' && $this->bookCoverInfo->getRecordType() != 'list') {
			$uploadedImage = $this->bookCoverPath . '/original/' . $id . '.png';
			if (file_exists($uploadedImage)) {
				return $this->processImageURL('upload', $uploadedImage);
			}
		}

		return false;
	}

	private function getReferencedGroupedWorkCover($permanentId) {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $permanentId;
		if ($groupedWork->find(true)) {
			$referenceId = $groupedWork->referenceCover;
			require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
			$recordDriver = RecordDriverFactory::initRecordDriverById($referenceId);
			if ($recordDriver && $recordDriver->isValid()) {
				$referencedCover = str_replace(':', '_', $referenceId);

				$referencedCoverURL_lg = $this->bookCoverPath . '/large/' . $referencedCover . '.png';
				$referencedCoverURL_md = $this->bookCoverPath . '/medium/' . $referencedCover . '.png';

				if (file_exists($referencedCoverURL_lg)) {
					return $this->processImageURL('reference ' . $referenceId, $referencedCoverURL_lg);
				} elseif (file_exists($referencedCoverURL_md)) {
					return $this->processImageURL('reference ' . $referenceId, $referencedCoverURL_md);
				} else {
					return false;
				}
			}
		}
		return false;
	}

	private function getEbscoEdsCover($id) {
		//Build a cover based on the title of the page
		require_once ROOT_DIR . '/sys/Covers/EbscoCoverBuilder.php';
		$coverBuilder = new EbscoCoverBuilder();
		require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';

		$edsRecordDriver = new EbscoRecordDriver($id);
		if ($edsRecordDriver->isValid()) {
			$title = $edsRecordDriver->getTitle();
			$props = [
				'format' => $edsRecordDriver->getFormats(),
			];
			$coverBuilder->getCover($title, $this->cacheFile, $props);
			return $this->processImageURL('default_ebsco', $this->cacheFile, false);
		} else {
			return false;
		}
	}

	private function getSummonCover($id) {
		//Build a cover based on the title of the page
		require_once ROOT_DIR . '/sys/Covers/SummonCoverBuilder.php';
		$coverBuilder = new SummonCoverBuilder();
		require_once ROOT_DIR . '/RecordDrivers/SummonRecordDriver.php';

		$summonRecordDriver = new SummonRecordDriver($id);
		if ($summonRecordDriver->isValid()) {
			$title = $summonRecordDriver->getTitle();
			$props = [
				'format' => $summonRecordDriver->getFormats(),
			];
			$coverBuilder->getCover($title, $this->cacheFile, $props);
			return $this->processImageURL('default_summon', $this->cacheFile, false);
		} else {
			return false;
		}
	}

	private function getEbscohostCover($id) {
		//Build a cover based on the title of the page
		require_once ROOT_DIR . '/sys/Covers/DefaultCoverImageBuilder.php';
		$coverBuilder = new DefaultCoverImageBuilder();
		require_once ROOT_DIR . '/RecordDrivers/EbscohostRecordDriver.php';

		$ebscohostRecordDriver = new EbscohostRecordDriver($id);
		if ($ebscohostRecordDriver->isValid()) {
			$title = $ebscohostRecordDriver->getTitle();
			$author = $ebscohostRecordDriver->getAuthor();
			$idParts = explode(':', $id);
			$db = $idParts[0];

			$ebscohostdb = new EBSCOhostDatabase();
			$ebscohostdb->shortName = $db;
			$ebscohostdb->find();
			while ($ebscohostdb->fetch()) {
				$image = $ebscohostdb->logo;
			}
			if (empty($image)) {
				$coverBuilder->getCover($title, $author, $this->cacheFile);
			} else {
				$image = ROOT_DIR . '/files/original/' . $image;
				$coverBuilder->getCover($title, $author, $this->cacheFile, $image);
			}

			return $this->processImageURL('default_ebscohost', $this->cacheFile, false);
		} else {
			return false;
		}
	}

	/**
	 * @param $year
	 * @param string $encodedShortTitle
	 * @param OMDBSetting $omdbSettings
	 * @param string $source
	 * @return bool
	 */
	protected function searchOmdbForCover($year, string $encodedShortTitle, OMDBSetting $omdbSettings, string $source): bool {
		$foundTitle = false;
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		foreach ($year as $curYear) {
			$url = "http://www.omdbapi.com/?t=$encodedShortTitle&y=$curYear&apikey={$omdbSettings->apiKey}";
			$client = new CurlWrapper();
			$result = $client->curlGetPage($url);
			if ($result !== false) {
				if ($json = json_decode($result, true)) {
					if (array_key_exists('Poster', $json)) {
						if ($this->processImageURL($source, $json['Poster'], true)) {
							$foundTitle = true;
						}
					}
				}
			}
			if ($foundTitle) {
				break;
			}
		}
		return $foundTitle;
	}

	/**
	 * Validates a cover URL to ensure it returns a valid, usable image.
	 *
	 * @param string $url The URL to validate.
	 * @return bool True if the URL is valid and returns a usable image, false otherwise.
	 */
	private function validateCoverUrl(string $url, string $source, bool $forceValidation = false): array|bool {
		// Check if we should validate the URL or if we can use the cached validation.
		// Always validate if reload flag is set or if forceValidation is true.
		if (!$forceValidation && !$this->reload && $this->bookCoverInfo && $this->bookCoverInfo->getLastUrlValidation()) {
			// Hardcoded expiration time: 24 hours (86400 seconds).
			$expirationTime = $this->bookCoverInfo->getLastUrlValidation() + 86400;
			if (time() < $expirationTime) {
				// URL validation hasn't expired yet, consider it valid.
				return true;
			}
		}

		// Check HTTP headers.
		$headers = @get_headers($url);
		if (!$headers || !preg_match('/^HTTP\/\d+\.\d+\s+(200|30[1-9])/', $headers[0])) {
			$this->log("Cover URL $url did not return a valid HTTP response; falling back.", Logger::LOG_DEBUG);
			return false;
		}

		// Fetch the image content (in memory) to perform additional checks.
		$context = stream_context_create([
			'http' => [
				'header' => "User-Agent: {$this->configArray['Catalog']['catalogUserAgent']}\r\n",
			],
		]);
		$image = @file_get_contents($url, false, $context);
		if ($image === false) {
			$this->log("Could not retrieve image content from cover URL $url; falling back.", Logger::LOG_DEBUG);
			return false;
		}

		// Check against known dummy image MD5 checksums.
		$imageChecksum = md5($image);
		$dummyChecksums = [
			'e89e0e364e83c0ecfba5da41007c9a2c',
			'f017f94ed618a86d0fa7cecd7112ab7e',
			'798f904eabf783405079eaf699414801',
			'dadde13fdb5f3775cdbdd25f34c0389b',
			'b13e33c0262a3a1f21dd20b826710cbc',
			'c6ddaf338cf667df0bf60045f05146db',
			'821d0d442dbee0f51f4c803e8e9fc87a'
		];
		if (in_array($imageChecksum, $dummyChecksums)) {
			$this->log("Cover URL $url returned a known dummy image (checksum: $imageChecksum); falling back.", Logger::LOG_DEBUG);
			return false;
		}

		// Verify that the image is not too small.
		$imageInfo = @getimagesizefromstring($image);
		if ($imageInfo === false || ($imageInfo[0] < 2 && $imageInfo[1] < 2)) {
			$this->log("Cover URL $url returned an image that is too small; falling back.", Logger::LOG_DEBUG);
			return false;
		}

		// Check the color of the bottom left and bottom right corners.
		if ($imageResource = @imagecreatefromstring($image)) {
			$width = $imageInfo[0];
			$height = $imageInfo[1];
			$rgbLeft = imagecolorat($imageResource, 0, $height - 1);
			$rgbRight = imagecolorat($imageResource, $width - 1, $height - 1);
			imagedestroy($imageResource);
			if ($rgbLeft == 8421504 && $rgbRight == 8421504) {
				$this->log("Cover URL $url returned an image with partial gray at the bottom; falling back.", Logger::LOG_DEBUG);
				return false;
			}

			$this->setBookCoverInfo($source, $width, $height);

			// Update the last validation timestamp.
			if ($this->bookCoverInfo) {
				$this->bookCoverInfo->setLastUrlValidation(time());
				$this->bookCoverInfo->update();
			}

			// All checks passed.
			return true;
		} else {
			$this->log("Cover URL $url returned invalid image data; falling back.", Logger::LOG_DEBUG);
			return false;
		}
	}

	/**
	 * If the system is configured to use the original cover URLs and the source is not an 'upload' or 'default',
	 * check for a cached URL in the database, write it if not present, and then
	 * (if the URL appears to be valid and not a dummy) immediately issue a 301 redirect.
	 *
	 * @param string $source The source label.
	 * @param string $url The URL to process.
	 * @return string Returns the (possibly unmodified) URL.
	 */
	private function handleOriginalCoverUrl(string $source, string $url): string {
		if (SystemVariables::getSystemVariables()->useOriginalCoverUrls &&
			$source !== 'default' &&
			$source !== 'upload' &&
			preg_match('/^https?:\/\//i', $url)
		) {
			// Always validate the URL when first storing it or when reload is requested
			$validationResult = $this->validateCoverUrl($url, $source, true);
			if ($validationResult !== false) {
				// All checks passed; store the URL in the database and issue the redirect.
				// First set the basic cover info to ensure consistency.

				// Now calculate the validation hash with the updated values.
				$validationFields = [
					$this->bookCoverInfo->imageSource,
					$this->bookCoverInfo->sourceWidth,
					$this->bookCoverInfo->sourceHeight,
					$this->bookCoverInfo->uploadedImage,
					$this->bookCoverInfo->disallowThirdPartyCover
				];
				$validationHash = md5(implode('|', $validationFields));
				// Store hash with URL for future validation.
				$urlToStore = $validationHash . $url;
				$this->bookCoverInfo->setOriginalUrl($urlToStore);
				$this->bookCoverInfo->update();

				//$this->log("Using new original URL: $url", Logger::LOG_DEBUG);
				//$this->log("Debug - Storage: Hash: $validationHash", Logger::LOG_DEBUG);
				//$this->log("Debug - Storage fields: " . implode('|', $validationFields), Logger::LOG_DEBUG);

				header("HTTP/1.1 301 Moved Permanently");
				header("Location: $url");
				$this->addCachingHeader();
				exit;
			}
		}
		return $url;
	}

	/**
	 * If the system is configured to use the original cover URLs and the source is not an 'upload' or 'default',
	 * check for a valid, cached URL in the database. This bypasses all logic that builds the URL, as
	 * it is not needed unless certain fields on the BookCoverInfo object have been modified.
	 *
	 * Runs if the user's browser has not cached the image.
	 *
	 * @return bool Returns true if a redirect was issued, false otherwise.
	 */
	private function checkForEarlyRedirect(): bool {
		if ($this->bookCoverInfo &&
			!empty($this->bookCoverInfo->getOriginalUrl()) &&
			SystemVariables::getSystemVariables()->useOriginalCoverUrls
		) {
			$validationFields = [
				$this->bookCoverInfo->imageSource,
				$this->bookCoverInfo->sourceWidth,
				$this->bookCoverInfo->sourceHeight,
				$this->bookCoverInfo->uploadedImage,
				$this->bookCoverInfo->disallowThirdPartyCover
			];
			$currentHash = md5(implode('|', $validationFields));

			// Get stored hash from first 32 characters of originalUrl.
			$storedHash = substr($this->bookCoverInfo->getOriginalUrl(), 0, 32);
			$url = substr($this->bookCoverInfo->getOriginalUrl(), 32);

			//$this->log("Debug - Validation check: Current hash: $currentHash, Stored hash: $storedHash", Logger::LOG_DEBUG);
			//$this->log("Debug - Validation fields: " . implode('|', $validationFields), Logger::LOG_DEBUG);
			//$this->log("Debug - Original URL: " . $this->bookCoverInfo->getOriginalUrl(), Logger::LOG_DEBUG);

			if ($currentHash === $storedHash && !empty($url)) {
				// Check if we need to validate the URL based on the expiration time
				// Force validation if reload flag is set
				$forceValidation = $this->reload;
				$validationResult = $this->validateCoverUrl($url, $this->bookCoverInfo->imageSource, $forceValidation);
				if ($validationResult !== false) {
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: $url");
					$this->addCachingHeader();
					exit;
				} else {
					$this->log("URL validation failed for stored URL: $url", Logger::LOG_DEBUG);
				}
			} else {
				$this->log("Debug - Hash validation failed: " . ($currentHash === $storedHash ? "Hashes match" : "Hashes don't match") . ", URL empty: " . (empty($url) ? "Yes" : "No"), Logger::LOG_DEBUG);
			}
		} else {
			$this->log("Debug - Early conditions failed: BookCoverInfo exists: " . ($this->bookCoverInfo ? "Yes" : "No") .
				", Original URL exists: " . (!empty($this->bookCoverInfo) && !empty($this->bookCoverInfo->getOriginalUrl()) ? "Yes" : "No") .
				", useOriginalCoverUrls enabled: " . (SystemVariables::getSystemVariables()->useOriginalCoverUrls ? "Yes" : "No"), Logger::LOG_DEBUG);
		}
		return false;
	}
}