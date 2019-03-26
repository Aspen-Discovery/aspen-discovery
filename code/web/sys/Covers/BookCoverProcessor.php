<?php
require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
class BookCoverProcessor{
    /**
     * @var BookCoverInfo
     */
    private $bookCoverInfo;
	private $bookCoverPath;
	private $localFile;
	private $category;
	private $format;
	private $size;
	private $id;
	private $isn;
	private $issn;
	private $upc;
	private $isEContent;
	private $type;
	private $cacheName;
	private $cacheFile;
	public $error;
	/** @var null|GroupedWorkDriver */
	private $groupedWork = null;
	private $reload;
	/** @var  Logger $logger */
	private $logger;
	private $doCoverLogging;
	private $configArray;
	/** @var  Timer $timer */
	private $timer;
	private $doTimings;
	public function loadCover($configArray, $timer, $logger){
		$this->configArray = $configArray;
		$this->timer = $timer;
		$this->doTimings = $this->configArray['System']['coverTimings'];
		$this->logger = $logger;
		$this->doCoverLogging = $this->configArray['Logging']['coverLogging'];

		$this->log("Starting to load cover", PEAR_LOG_INFO);
		$this->bookCoverPath = $configArray['Site']['coverPath'];
		if (!$this->loadParameters()){
			return;
		}

		if (!$this->reload){
			$this->log("Looking for Cached cover", PEAR_LOG_INFO);
			if ($this->getCachedCover()){
				return;
			}
		}

		if ($this->type == 'overdrive'){
			$this->initDatabaseConnection();
			//Will exit if we find a cover
			if ($this->getOverDriveCover()){
				return;
			}
		}else if ($this->type == 'hoopla'){
			//Will exit if we find a cover
			if ($this->getHooplaCover($this->id)){
				return;
			}
		}elseif ($this->type == 'Colorado State Government Documents'){
			if ($this->getColoradoGovDocCover()){
				return;
			}
		}elseif ($this->type == 'Classroom Video on Demand'){
			if ($this->getClassroomVideoOnDemandCover($this->id)){
				return;
			}
		} elseif (stripos($this->type, 'films on demand') !== false){
			if ($this->getFilmsOnDemandCover($this->id)) {
				return;
			}
		} elseif (stripos($this->type, 'proquest') !== false || stripos($this->type, 'ebrary') !== false){
			if ($this->getEbraryCover($this->id)) {
				return;
			}
			// Any Sideloaded Collection that has a cover in the 856 tag (and additional conditionals)
		} elseif (stripos($this->type, 'kanopy') !== false){
			if ($this->getSideLoadedCover($this->type.':'.$this->id)) {
				return;
			}
		} elseif (stripos($this->type, 'bookflix') !== false){
			if ($this->getSideLoadedCover($this->type.':'.$this->id)) {
				return;
			}
		} elseif (stripos($this->type, 'boombox') !== false){
			if ($this->getSideLoadedCover($this->type.':'.$this->id)) {
				return;
			}
		} elseif (stripos($this->type, 'biblioboard') !== false){
			if ($this->getSideLoadedCover($this->type.':'.$this->id)) {
				return;
			}
		} elseif (stripos($this->type, 'lynda') !== false){
			if ($this->getSideLoadedCover($this->type.':'.$this->id)) {
				return;
			}
		} elseif (stripos($this->type, 'odilo') !== false){
			if ($this->getSideLoadedCover($this->type.':'.$this->id)) {
				return;
			}
			// Cloud Library
		} elseif (stripos($this->type, 'cloud') !== false){
			if ($this->getSideLoadedCover($this->type.':'.$this->id)) {
				return;
			}
		} elseif (stripos($this->type, 'rbdigital') !== false || stripos($this->type, 'zinio') !== false){
			if ($this->getZinioCover($this->type.':'.$this->id)) {
				return;
			}
		} elseif ($this->type == 'open_archives') {
            if ($this->getOpenArchivesCover($this->id)) {
                return;
            }
        }
		$this->log("Looking for cover from providers", PEAR_LOG_INFO);
		if ($this->getCoverFromProvider()){
			return;
		}

		if ($this->type != 'grouped_work' && $this->getCoverFromMarc()){
			return;
		}

		if ($this->getGroupedWorkCover()){
			return;
		}

		$this->log("No image found, using die image", PEAR_LOG_INFO);
		$this->getDefaultCover();

	}

	private function getHooplaCover($id){
		require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
		if (strpos($id, ':') !== false){
			list(, $id) = explode(":", $id);
		}

		$driver = new HooplaRecordDriver($id);
		if ($driver->isValid()){
			/** @var File_MARC_Data_Field[] $linkFields */
			$linkFields = $driver->getMarcRecord()->getFields('856');
			foreach($linkFields as $linkField){
                try {
                    if ($linkField->getIndicator(1) == 4 && $linkField->getIndicator(2) == 2) {
                        $coverUrl = $linkField->getSubfield('u')->getData();
                        return $this->processImageURL('hoopla', $coverUrl, true);
                    }
                } catch (File_MARC_Exception $e) {
                    log("MARC record did not have proper indicators " . $e, PEAR_LOG_WARNING);
                }
            }
		}

		return false;
	}

	private function getSideLoadedCover($sourceAndId){
		if (strpos($sourceAndId, ':') !== false){
			// Sideloaded Record requires both source & id

			require_once ROOT_DIR . '/RecordDrivers/SideLoadedRecord.php';
			$driver = new SideLoadedRecord($sourceAndId);
			if ($driver) {
				/** @var File_MARC_Data_Field[] $linkFields */
				$linkFields = $driver->getMarcRecord()->getFields('856');
				foreach ($linkFields as $linkField) {
                    try {
                        if ($linkField->getIndicator(1) == 4 && $linkField->getIndicator(2) == 2) {
                            $coverUrl = $linkField->getSubfield('u')->getData();
                            return $this->processImageURL('sideload', $coverUrl, true);
                        }
                    } catch (File_MARC_Exception $e) {
                        log("MARC record did not have proper indicators " . $e, PEAR_LOG_WARNING);
                    }
                }
			}
		}
		return false;
	}

	private function getColoradoGovDocCover(){
		$filename = "interface/themes/responsive/images/state_flag_of_colorado.png";
		if ($this->processImageURL('coloradoGovDoc', $filename, true)){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @param string $id  When using a grouped work, the Ebrary Id should be passed to this function
	 * @return bool
	 */
	private function getEbraryCover($id) {
		if (strpos($id, ':') !== false){
			list(, $id) = explode(":", $id);
		}
		$coverId = preg_replace('/^[a-zA-Z]+/', '', $id);
		$coverUrl = "http://ebookcentral.proquest.com/covers/$coverId-l.jpg";
		if ($this->processImageURL('ebrary', $coverUrl, true)){
			return true;
		}else{
			return false;
		}
	}

	private function getClassroomVideoOnDemandCover($id) {
		if (strpos($id, ':') !== false){
			list(, $id) = explode(":", $id);
		}
		$coverId = preg_replace('/^10+/', '', $id);
		$coverUrl = "http://cvod.infobase.com/image/$coverId";
		if ($this->processImageURL('classroomVideoOnDemand', $coverUrl, true)){
			return true;
		}else{
			return false;
		}
	}

	private function getFilmsOnDemandCover($id) {
		if (strpos($id, ':') !== false){
			list(, $id) = explode(":", $id);
		}
		$coverId = preg_replace('/^10+/', '', $id);
		$coverUrl = "http://fod.infobase.com/image/$coverId";
		if ($this->processImageURL('filmsOnDemand',$coverUrl, true)){
			return true;
		}else{
			return false;
		}
	}

	private function getOverDriveCover($id = null){
		require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
		require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductMetaData.php';
		$overDriveProduct = new OverDriveAPIProduct();
		list(, $id) = explode(":", $id);
		$overDriveProduct->overdriveId = $id == null ? $this->id : $id;
		if ($overDriveProduct->find(true)){
			$overDriveMetadata = new OverDriveAPIProductMetaData();
			$overDriveMetadata->productId = $overDriveProduct->id;
			$overDriveMetadata->find(true);
			$filename = $overDriveMetadata->cover;
			if ($filename != null){
				return $this->processImageURL('overdrive', $filename);
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	private function getZinioCover($sourceAndId) {
		if (strpos($sourceAndId, ':') !== false){
			// Sideloaded Record requires both source & id

			require_once ROOT_DIR . '/RecordDrivers/SideLoadedRecord.php';
			$driver = new SideLoadedRecord($sourceAndId);
			if ($driver) {
				/** @var File_MARC_Data_Field[] $linkFields */
				$linkFields = $driver->getMarcRecord()->getFields('856');
				foreach ($linkFields as $linkField) {
					try {
                        if ($linkField->getIndicator(1) == 4 && $linkField->getSubfield('3') != NULL && $linkField->getSubfield('3')->getData() == 'Image') {
                            $coverUrl = $linkField->getSubfield('u')->getData();
                            $coverUrl = str_replace('size=200', 'size=lg', $coverUrl);
                            return $this->processImageURL('zinio', $coverUrl, true);
                        }
                    } catch (File_MARC_Exception $e) {
                        log("MARC record did not have proper indicators " . $e, PEAR_LOG_WARNING);
                    }
                }
			}
		}
		return false;
	}

	private function getRbdigitalCover($id){
        if (strpos($id, ':') !== false){
            list(, $id) = explode(":", $id);
        }
        require_once ROOT_DIR . '/RecordDrivers/RbdigitalRecordDriver.php';
        $driver = new RbdigitalRecordDriver($id);
        if ($driver) {
            $coverUrl = $driver->getBookcoverUrl('large');
            return $this->processImageURL('rbdigital', $coverUrl, true);
        }
        return false;
    }

	private function initDatabaseConnection(){
		// Setup Local Database Connection
		if (!defined('DB_DATAOBJECT_NO_OVERLOAD')){
			define('DB_DATAOBJECT_NO_OVERLOAD', 0);
		}
		$options =& PEAR_Singleton::getStaticProperty('DB_DataObject', 'options');
		$options = $this->configArray['Database'];
		$this->logTime("Connect to database");
		require_once ROOT_DIR . '/Drivers/marmot_inc/Library.php';
	}

	private function initMemcache(){
		global $memCache;
		global $configArray;
		if (!isset($memCache)){
			// Set defaults if nothing set in config file.
			$host = isset($configArray['Caching']['memcache_host']) ? $configArray['Caching']['memcache_host'] : 'localhost';
			$port = isset($configArray['Caching']['memcache_port']) ? $configArray['Caching']['memcache_port'] : 11211;
			$timeout = isset($configArray['Caching']['memcache_connection_timeout']) ? $configArray['Caching']['memcache_connection_timeout'] : 1;

			// Connect to Memcache:
			$memCache = new Memcache();
			if (!$memCache->pconnect($host, $port, $timeout)) {
				PEAR_Singleton::raiseError(new PEAR_Error("Could not connect to Memcache (host = {$host}, port = {$port})."));
			}
			$this->logTime("Initialize Memcache");
		}
	}

	private function loadParameters(){
		//Check parameters
		if (!count($_GET)) {
			$this->error = "No parameters provided.";
			return false;
		}
		$this->reload = isset($_GET['reload']);
		// Sanitize incoming parameters to avoid filesystem attacks.  We'll make sure the
		// provided size matches a whitelist, and we'll strip illegal characters from the
		// ISBN.
		$this->size = isset($_GET['size']) ? $_GET['size'] : 'small';
		if (!in_array($this->size, array('small', 'medium', 'large'))) {
			$this->error = "No size provided, please specify small, medium, or large.";
			return false;
		}
		if (isset($_GET['isn']) && is_array($_GET['isn'])){
			$_GET['isn'] = array_pop($_GET['isn']);
		}
		$this->isn = isset($_GET['isn']) ? preg_replace('/[^0-9xX]/', '', $_GET['isn']) : null;
		if (strlen($this->isn) == 0){
			$this->isn = null;
		}

		if (isset($_GET['upc']) && is_array($_GET['upc'])){
			$_GET['upc'] = array_pop($_GET['upc']);
		}
		$this->upc = isset($_GET['upc']) ? ltrim(preg_replace('/[^0-9xX]/', '', $_GET['upc']), '0') : null;
		if (strlen($this->upc) == 0){
			//Strip any leading zeroes
			$this->upc = null;
		}

		if (isset($_GET['issn']) && is_array($_GET['issn'])){
			$_GET['issn'] = array_pop($_GET['issn']);
		}
		$this->issn = isset($_GET['issn']) ? preg_replace('/[^0-9xX]/', '', $_GET['issn']) : null;
		if (strlen($this->issn) == 0){
			$this->issn = null;
		}

		if (isset($_GET['id']) && is_array($_GET['id'])){
			$_GET['id'] = array_pop($_GET['id']);
		}
		$this->id = isset($_GET['id']) ? $_GET['id'] : null;
		if (isset($_GET['type'])){
			$this->type =  $_GET['type'];
		}else{
			if (preg_match('/[a-f\\d]{8}-[a-f\\d]{4}-[a-f\\d]{4}-[a-f\\d]{4}-[a-f\\d]{12}/', $this->id)){
				$this->type = 'grouped_work';
			}else{
				$this->type = 'ils';
			}
		}
		if (strpos($this->id, ':') > 0){
			list($this->type, $this->id) = explode(':', $this->id);
		}
        $this->bookCoverInfo = new BookCoverInfo();
        $this->bookCoverInfo->recordId = $this->id;
        $this->bookCoverInfo->recordType = $this->type;
        $this->bookCoverInfo->find(true);

		$this->category = !empty($_GET['category']) ? strtolower($_GET['category']) : null;
		$this->format   = !empty($_GET['format']) ? strtolower($_GET['format']) : null;
		//First check to see if this has a custom cover due to being an e-book
		if (!is_null($this->id)){
			if ($this->isEContent){
				$this->cacheName = 'econtent' . $this->id;
			}else{
				$this->cacheName = $this->id;
			}
		}else if (!is_null($this->isn)){
			$this->cacheName = $this->isn;
		}else if (!is_null($this->upc)){
			$this->cacheName = $this->upc;
		}else if (!is_null($this->issn)){
			$this->cacheName = $this->issn;
		}else{
			$this->error = "ISN, UPC, or id must be provided.";
			return false;
		}
		$this->cacheName = preg_replace('/[^a-zA-Z0-9_.-]/', '', $this->cacheName);
		$this->cacheFile = $this->bookCoverPath . '/' . $this->size . '/' . $this->cacheName . '.png';
		$this->logTime("load parameters");
		return true;
	}

	private function addCachingHeader(){
		//Add caching information
		$expires = 60*60*24*14;  //expire the cover in 2 weeks on the client side
		header("Cache-Control: maxage=".$expires);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
		$this->log("Added caching header", PEAR_LOG_INFO);
	}

	private function addModificationHeaders($filename){
		$timestamp = filemtime($filename);
		$this->logTime("Got filetimestamp $timestamp");
		$last_modified = substr(date('r', $timestamp), 0, -5).'GMT';
		$etag = '"'.md5($last_modified).'"';
		$this->logTime("Got last_modified $last_modified and etag $etag");
		// Send the headers
		header("Last-Modified: $last_modified");
		header("ETag: $etag");

		if ($this->reload){
			return true;
		}
		// See if the client has provided the required headers
		$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;
		$if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? 	stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : 	false;
		if (!$if_modified_since && !$if_none_match) {
			$this->log("Caching headers not sent, return full image", PEAR_LOG_INFO);
			return true;
		}
		// At least one of the headers is there - check them
		if ($if_none_match && $if_none_match != $etag) {
			$this->log("ETAG changed ", PEAR_LOG_INFO);
			return true; // etag is there but doesn't match
		}
		if ($if_modified_since && $if_modified_since != $last_modified) {
			$this->log("Last modified changed", PEAR_LOG_INFO);
			return true; // if-modified-since is there but doesn't match
		}
		// Nothing has changed since their last request - serve a 304 and exit
		$this->log("File has not been modified", PEAR_LOG_INFO);
		header('HTTP/1.0 304 Not Modified');
		return false;
	}

	private function returnImage($localPath){
		header('Content-type: image/png');
		if ($this->addModificationHeaders($localPath)){
			$this->logTime("Added modification headers");
			$this->addCachingHeader();
			$this->logTime("Added caching headers");
			ob_clean();
			flush();
			readfile($localPath);
			$this->log("Read file $localPath", PEAR_LOG_DEBUG);
			$this->logTime("echo file $localPath");
		}else{
			$this->logTime("Added modification headers");
		}
	}

	private function getCoverFromProvider(){
		// Update to allow retrieval of covers based on upc
		if (!is_null($this->isn) || !is_null($this->upc) || !is_null($this->issn)) {
			$this->log("Looking for picture based on isbn and upc.", PEAR_LOG_INFO);

			// Fetch from provider
			if (isset($this->configArray['Content']['coverimages'])) {
				$providers = explode(',', $this->configArray['Content']['coverimages']);
				foreach ($providers as $provider) {
					$provider = explode(':', $provider);
					$this->log("Checking provider ".$provider[0], PEAR_LOG_INFO);
					$func = $provider[0];
					$key = isset($provider[1]) ? $provider[1] : '';
					if (method_exists($this, $func) && $this->$func($key)) {
						$this->log("Found image from $provider[0]", PEAR_LOG_INFO);
						$this->logTime("Checked $func");
						return true;
					}else{
						$this->logTime("Checked $func");
					}
				}
			}
		}
		return false;
	}

	private function getCoverFromMarc($marcRecord = null){
		$this->log("Looking for picture as part of 856 tag.", PEAR_LOG_INFO);

		if ($marcRecord == null){
			$this->initDatabaseConnection();
			//Process the marc record
			require_once ROOT_DIR . '/sys/MarcLoader.php';
			if ($this->type != 'overdrive' && $this->type != 'hoopla'){
				$marcRecord = MarcLoader::loadMarcRecordByILSId($this->type . ':' . $this->id);
			}
		}

		if (!$marcRecord) {
			return false;
		}

		//Get the 856 tags
		$marcFields = $marcRecord->getFields('856');
		if ($marcFields){
			/** @var File_MARC_Data_Field $marcField */
			foreach ($marcFields as $marcField){
				//Check to see if this is a cover to use for VuFind
				if ($marcField->getSubfield('2') && strcasecmp(trim($marcField->getSubfield('2')->getData()), 'Vufind_Image') == 0){
					if ($marcField->getSubfield('3') && (strcasecmp(trim($marcField->getSubfield('3')->getData()), 'Cover Image') == 0 || strcasecmp(trim($marcField->getSubfield('3')->getData()), 'CoverImage') == 0)){
						//Can use either subfield f or subfield u
						if ($marcField->getSubfield('f')){
							//Just references the file, add the original directory
							$filename = $this->bookCoverPath . '/original/' . trim($marcField->getSubfield('f')->getData());
							if ($this->processImageURL('marcRecord', $filename, true)){
								//We got a successful match
								return true;
							}
						}elseif ($marcField->getSubfield('u')){
							//Full url to the image
							if ($this->processImageURL('marcRecord', trim($marcField->getSubfield('u')->getData()), true)){
								//We got a successful match
								return true;
							}
						}
					}
				}
			}
		}

		//Check the 690 field to see if this is a seed catalog entry
		$marcFields = $marcRecord->getFields('690');
		if ($marcFields){
			$this->log("Found 690 field", PEAR_LOG_INFO);
			foreach ($marcFields as $marcField){
				if ($marcField->getSubfield('a')){
					$this->log("Found 690a subfield", PEAR_LOG_INFO);
					$subfield_a = $marcField->getSubfield('a')->getData();
					if (preg_match('/seed library.*/i', $subfield_a, $matches)){
						$this->log("Title is a seed library title", PEAR_LOG_INFO);
						$filename = "interface/themes/responsive/images/seed_library_logo.jpg";
						if ($this->processImageURL('seedLibrary', $filename, true)){
							return true;
						}
					}
				}
			}
		}

		//Check for Flatirons covers
        $marcFields = $marcRecord->getFields('962');
		if ($marcFields){
			$this->log("Found 962 field", PEAR_LOG_INFO);
			foreach ($marcFields as $marcField){
				if ($marcField->getSubfield('u')){
					$this->log("Found 962u subfield", PEAR_LOG_INFO);
					$subfield_u = $marcField->getSubfield('u')->getData();
					if ($this->processImageURL('marcRecord', $subfield_u, true)){
						return true;
					}
				}
			}
		}

		return false;
	}

	private function getCachedCover(){
	    $hasCachedImage = false;
	    if ($this->bookCoverInfo->N == 1){
	        if ($this->size == 'small' && $this->bookCoverInfo->thumbnailLoaded == 1) {
                $hasCachedImage = true;
            }else if ($this->size == 'medium' && $this->bookCoverInfo->mediumLoaded == 1) {
                $hasCachedImage = true;
            }else if ($this->size == 'large' && $this->bookCoverInfo->largeLoaded == 1) {
                $hasCachedImage = true;
            }
        }

		if ($hasCachedImage){
            $this->bookCoverInfo->lastUsed = time();
            $this->bookCoverInfo->update();
            $fileName = "{$this->bookCoverPath}/{$this->size}/{$this->cacheName}.png";

            $this->log("Checking $fileName", PEAR_LOG_INFO);
            // Load local cache if available
            $this->logTime("Found cached cover");
            $this->log("$fileName exists, returning", PEAR_LOG_INFO);
            $this->returnImage($fileName);
        }


		$this->logTime("Finished checking for cached cover.");
		return $hasCachedImage;
	}

	/**
	 * Display a "cover unavailable" graphic and terminate execution.
	 */
	function getDefaultCover(){
		//Get the resource for the cover so we can load the title and author
		$title = '';
		$author = '';
		if($this->type == 'grouped_work'){
			$this->loadGroupedWork();
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			if ($this->groupedWork){
				$title = ucwords($this->groupedWork->getTitle());
				$author = ucwords($this->groupedWork->getPrimaryAuthor());
				$this->category = 'blank';
			}
		}else{
		    //TODO: Do we need to check other types of record drivers/
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$recordDriver = new MarcRecordDriver($this->id);
			if ($recordDriver->isValid()){
				$title = $recordDriver->getTitle();
				$author = $recordDriver->getAuthor();
			}
		}

		require_once ROOT_DIR . '/sys/Covers/DefaultCoverImageBuilder.php';
		$coverBuilder = new DefaultCoverImageBuilder();
		if (strlen($title) ===0){
            $title = 'Unknown Title';
		}
        $coverBuilder->getCover($title, $author, $this->cacheFile);
        return $this->processImageURL('default', $this->cacheFile);
	}

	function processImageURL($source, $url, $cache = true, $attemptRefetch = true) {
		$this->log("Processing $url", PEAR_LOG_INFO);
		$context = stream_context_create(array('http'=>array(
			'header' => "User-Agent: {$this->configArray['Catalog']['catalogUserAgent']}\r\n"
//			'header' => "User-Agent: {$this->configArray['Catalog']['genericUserAgent']}\r\n"
		)));

		if ($image = @file_get_contents($url, false, $context)) {
			// Figure out file paths -- $tempFile will be used to store the downloaded
			// image for analysis.  $finalFile will be used for long-term storage if
			// $cache is true or for temporary display purposes if $cache is false.
			$tempFile = str_replace('.png', uniqid(), $this->cacheFile);
			$finalFile = $cache ? $this->cacheFile : $tempFile . '.png';
			$this->log("Processing url $url to $finalFile", PEAR_LOG_DEBUG);

			// If some services can't provide an image, they will serve a 1x1 blank
			// or give us invalid image data.  Let's analyze what came back before
			// proceeding.
			if (!@file_put_contents($tempFile, $image)) {
				$this->log("Unable to write to image directory $tempFile.", PEAR_LOG_ERR);
				$this->error = "Unable to write to image directory $tempFile.";
				return false;
			}
			list($width, $height, $type) = @getimagesize($tempFile);

			// File too small -- delete it and report failure.
			if ($width < 2 && $height < 2) {
				@unlink($tempFile);
				return false;
			}

			// Test Image for for partial load
			if(!$imageResource = @imagecreatefromstring($image)){
				$this->log("Could not create image from string $url", PEAR_LOG_ERR);
				@unlink($tempFile);
				return false;
			}

			// Check the color of the bottom left corner
			$rgb = imagecolorat($imageResource, 0, $height-1);
			if ($rgb == 8421504) {
				// Confirm by checking the color of the bottom right corner
				$rgb = imagecolorat($imageResource, $width-1, $height-1);
				if ($rgb == 8421504) {
					// This is an image with partial gray at the bottom
					// (r:128,g:128,b:128)
//				$r = ($rgb >> 16) & 0xFF;
//				$g = ($rgb >> 8) & 0xFF;
//				$b = $rgb & 0xFF;

					$this->log('Partial Gray image loaded.', PEAR_LOG_ERR);
					if ($attemptRefetch) {
						$this->log('Partial Gray image, attempting refetch.', PEAR_LOG_INFO);
						return $this->processImageURL($url, $cache, false); // Refetch once.
					}
				}
			}

			if ($this->size == 'small'){
				$maxDimension = 100;
			}elseif ($this->size == 'medium'){
				$maxDimension = 200;
			}else{
				$maxDimension = 400;
			}

			//Check to see if the image needs to be resized
			if ($width > $maxDimension || $height > $maxDimension){
				// We no longer need the temp file:
				@unlink($tempFile);

				if ($width > $height){
					$new_width = $maxDimension;
					$new_height = floor( $height * ( $maxDimension / $width ) );
				}else{
					$new_height = $maxDimension;
					$new_width = floor( $width * ( $maxDimension / $height ) );
				}

				$this->log("Resizing image New Width: $new_width, New Height: $new_height", PEAR_LOG_INFO);

				// create a new temporary image
				$tmp_img = imagecreatetruecolor( $new_width, $new_height );

				// copy and resize old image into new image
				if (!imagecopyresampled( $tmp_img, $imageResource, 0, 0, 0, 0, $new_width, $new_height, $width, $height )){
					$this->log("Could not resize image $url to $this->localFile", PEAR_LOG_ERR);
					return false;
				}

				// save thumbnail into a file
				if (file_exists($finalFile)){
					$this->log("File $finalFile already exists, deleting", PEAR_LOG_DEBUG);
					unlink($finalFile);
				}

				if (!@imagepng( $tmp_img, $finalFile, 9)){
					$this->log("Could not save resized file $$this->localFile", PEAR_LOG_ERR);
					return false;
				}

			}else{
				$this->log("Image is the correct size, not resizing.", PEAR_LOG_INFO);

				// Conversion needed -- do some normalization for non-PNG images:
				if ($type != IMAGETYPE_PNG) {
					$this->log("Image is not a png, converting to png.", PEAR_LOG_INFO);

					$conversionOk = true;
					// Try to create a GD image and rewrite as PNG, fail if we can't:
					if (!($imageResource = @imagecreatefromstring($image))) {
						$this->log("Could not create image from string $url", PEAR_LOG_ERR);
						$conversionOk = false;
					}

					if (!@imagepng($imageResource, $finalFile, 9)) {
						$this->log("Could not save image to file $url $this->localFile", PEAR_LOG_ERR);
						$conversionOk = false;
					}
					// We no longer need the temp file:
					@unlink($tempFile);
					imagedestroy($imageResource);
					if (!$conversionOk){
						return false;
					}
					$this->log("Finished creating png at $finalFile.", PEAR_LOG_INFO);
				} else {
					// If $tempFile is already a PNG, let's store it in the cache.
					@rename($tempFile, $finalFile);
				}
			}

			// Display the image:
			$this->returnImage($finalFile);

			// If we don't want to cache the image, delete it now that we're done.
			if (!$cache) {
				@unlink($finalFile);
			}
			$this->logTime("Finished processing image url");

            $this->setBookCoverInfo($source, $width, $height);
			return true;
		} else {
			$this->log("Could not load the file as an image $url", PEAR_LOG_INFO);
			return false;
		}
	}

	function syndetics($key)
	{
		if (is_null($this->isn) && is_null($this->upc) && is_null($this->issn)){
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

		$url = isset($this->configArray['Syndetics']['url']) ? $this->configArray['Syndetics']['url'] : 'http://syndetics.com';
		$url .= "/index.aspx?type=xw12&pagename={$size}&client={$key}";
		if ($this->isn){
			$url .= "&isbn=" . (!is_null($this->isn) ? $this->isn : '');
		}
		if ($this->upc){
			$url .= "&upc=" . (!is_null($this->upc) ? $this->upc : '');
		}
		if ($this->issn){
			$url .= "&issn=" . (!is_null($this->issn) ? $this->issn : '');
		}
		$this->log("Syndetics url: $url", PEAR_LOG_DEBUG);
		return $this->processImageURL('syndetics', $url);
	}

	function librarything($key)
	{
		if (is_null($this->isn)){
			return false;
		}
		$url = 'http://covers.librarything.com/devkey/' . $key . '/' . $this->size . '/isbn/' . $this->isn;
		return $this->processImageURL('libraryThing', $url);
	}

	/**
	 * Retrieve a Content Cafe cover.
	 *
	 * @param string $id Content Cafe client ID.
	 *
	 * @return bool      True if image displayed, false otherwise.
	 */
	function contentCafe($id = null) {
		global $configArray;

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
		if (!$id) {
			$id = $configArray['Contentcafe']['id']; // alternate way to pass the content cafe id to this method.
		}
		$pw = $configArray['Contentcafe']['pw'];
		$url = isset($configArray['Contentcafe']['url']) ?
							$configArray['Contentcafe']['url'] : 'http://contentcafe2.btol.com';

	$lookupCode = $this->isn;
	if (!$lookupCode) {
		$lookupCode = $this->issn;
		if (!$lookupCode & $this->upc) {
			$lookupCode = $this->upc;
		}
	}

		$url .= "/ContentCafe/Jacket.aspx?UserID={$id}&Password={$pw}&Return=1&Type={$size}&Value={$lookupCode}&erroroverride=1";

	return $this->processImageURL('contentCafe', $url);
}

	function google()
	{
		if (is_null($this->isn)){
			return false;
		}
		if (is_callable('json_decode')) {
			$url = 'http://books.google.com/books?jscmd=viewapi&bibkeys=ISBN:' . $this->isn . '&callback=addTheCover';
			require_once ROOT_DIR . '/sys/HTTP/HTTP_Request.php';
			$client = new HTTP_Request();
			$client->setMethod('GET');
			$client->setURL($url);

			$result = $client->sendRequest();
			if (!PEAR_Singleton::isError($result)) {
				$json = $client->getResponseBody();

				// strip off addthecover( -- note that we need to account for length of ISBN (10 or 13)
				$json = substr($json, 21 + strlen($this->isn));
				// strip off );
				$json = substr($json, 0, -3);
				// convert \x26 to &
				$json = str_replace("\\x26", "&", $json);
				if ($json = json_decode($json, true)) {
					//The google API always returns small images by default, but we can manipulate the URL to get larger images
					$size = $this->size;
					if (isset($json['thumbnail_url'])){
						$imageUrl = $json['thumbnail_url'];
						if ($size == 'small'){

						}else if ($size == 'medium'){
							$imageUrl = preg_replace('/zoom=\d/', 'zoom=1', $imageUrl);
						}else{ //large
							$imageUrl = preg_replace('/zoom=\d/', 'zoom=0', $imageUrl);
						}
						return $this->processImageURL('google', $imageUrl, true);
					}
				}
			}
		}
		return false;
	}

	function log($message, $level = PEAR_LOG_DEBUG){
		if ($this->doCoverLogging){
			$this->logger->log($message, $level);
		}
	}

	function logTime($message){
		if ($this->doTimings){
			$this->timer->logTime($message);
		}
	}

	private function getGroupedWorkCover() {
		if ($this->loadGroupedWork()){
			//Have not found a grouped work based on isbn or upc, check based on related records
			$relatedRecords = $this->groupedWork->getRelatedRecords(true);
			foreach ($relatedRecords as $relatedRecord){
				if (strcasecmp($relatedRecord['source'], 'OverDrive') == 0){
					if ($this->getOverDriveCover($relatedRecord['id'])){
						return true;
					}
				}elseif (strcasecmp($relatedRecord['source'], 'Hoopla') == 0){
					if ($this->getHooplaCover($relatedRecord['id'])){
						return true;
					}
				}elseif (strcasecmp($relatedRecord['source'], 'Colorado State Government Documents') == 0){
					if ($this->getColoradoGovDocCover()){
						return true;
					}
				}elseif (strcasecmp($relatedRecord['source'], 'Classroom Video on Demand') == 0){
					if ($this->getClassroomVideoOnDemandCover($relatedRecord['id'])){
						return true;
					}
				}elseif (stripos($relatedRecord['source'], 'proquest') !== false || stripos($relatedRecord['source'], 'ebrary') !== false){
					if ($this->getEbraryCover($relatedRecord['id'])){
						return true;
					}
				}elseif (stripos($relatedRecord['source'], 'films on demand') !== false){
					if ($this->getFilmsOnDemandCover($relatedRecord['id'])){
						return true;
					}
				}elseif (stripos($relatedRecord['source'], 'kanopy') !== false){
					if ($this->getSideLoadedCover($relatedRecord['id'])){
						return true;
					}
				} elseif (stripos($relatedRecord['source'], 'bookflix') !== false){
					if ($this->getSideLoadedCover($relatedRecord['id'])) {
						return true;
					}
				} elseif (stripos($relatedRecord['source'], 'boombox') !== false){
					if ($this->getSideLoadedCover($relatedRecord['id'])) {
						return true;
					}
				} elseif (stripos($relatedRecord['source'], 'biblioboard') !== false){
					if ($this->getSideLoadedCover($relatedRecord['id'])) {
						return true;
					}
				} elseif (stripos($relatedRecord['source'], 'lynda') !== false){
					if ($this->getSideLoadedCover($relatedRecord['id'])) {
						return true;
					}
				} elseif (stripos($relatedRecord['source'], 'Odilo') !== false){
					if ($this->getSideLoadedCover($relatedRecord['id'])) {
						return true;
					}
				} elseif (stripos($relatedRecord['source'], 'cloud') !== false){
					if ($this->getSideLoadedCover($relatedRecord['id'])) {
						return true;
					}
				} elseif (stripos($relatedRecord['source'], 'zinio') !== false){
					if ($this->getZinioCover($relatedRecord['id'])) {
						return true;
					}
                } elseif (stripos($relatedRecord['source'], 'rbdigital') !== false){
                    if ($this->getRbdigitalCover($relatedRecord['id'])) {
                        return true;
                    }
				}else{
					/** @var GroupedWorkSubDriver $driver */
					$driver = $relatedRecord['driver'];
					//First check to see if there is a specific record defined in an 856 etc.
					if (method_exists($driver, 'getMarcRecord') && $this->getCoverFromMarc($driver->getMarcRecord())){
						return true;
					}else{
						//Finally, check the isbns if we don't have an override
						$isbns = $driver->getCleanISBNs();
						if ($isbns){
							foreach ($isbns as $isbn){
								$this->isn = $isbn;
								if ($this->getCoverFromProvider()){
									return true;
								}
							}
						}
						$upcs = $driver->getCleanUPCs();
						$this->isn = null;
						if ($upcs){
							foreach ($upcs as $upc){
								$this->upc = ltrim($upc, '0');
								if ($this->getCoverFromProvider()){
									return true;
								}
								//If we tried trimming the leading zeroes, also try without.
								if ($this->upc !== $upc){
									$this->upc = $upc;
									if ($this->getCoverFromProvider()){
										return true;
									}
								}
							}
						}
					}
				}
			}
		}
		return false;
	}

	private function loadGroupedWork(){
		if ($this->groupedWork == null){
			// Include Search Engine Class
			require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';
			$this->initMemcache();

			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			if ($this->type == 'grouped_work'){
				$this->groupedWork = new GroupedWorkDriver($this->id);
				if (!$this->groupedWork->isValid){
					$this->groupedWork = false;
				}
			}else{
				require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
				$recordDriver = RecordDriverFactory::initRecordDriverById($this->type . ':' . $this->id);
				if ($recordDriver && $recordDriver->isValid()){
					$this->groupedWork = $recordDriver->getGroupedWorkDriver();
					if (!$this->groupedWork->isValid){
						$this->groupedWork = false;
					}
				}

			}


		}
		return $this->groupedWork;
	}

    private function setBookCoverInfo($source, $width, $height)
    {
        $this->bookCoverInfo->imageSource = $source;
        if ($this->bookCoverInfo->sourceWidth == null || $width > $this->bookCoverInfo->sourceWidth){
            $this->bookCoverInfo->sourceWidth = $width;
        }
        if ($this->bookCoverInfo->sourceHeight == null || $width > $this->bookCoverInfo->sourceHeight){
            $this->bookCoverInfo->sourceHeight = $height;
        }
        $this->bookCoverInfo->lastUsed = time();
        if ($this->size == 'small') {
            $this->bookCoverInfo->thumbnailLoaded = true;
        }elseif ($this->size == 'medium') {
            $this->bookCoverInfo->mediumLoaded = true;
        }elseif ($this->size == 'largeLoaded') {
            $this->bookCoverInfo->largeLoaded = true;
        }
        $this->bookCoverInfo->uploadedImage = false;
        if ($this->bookCoverInfo->N == 0) {
            $this->bookCoverInfo->firstLoaded = time();
            $this->bookCoverInfo->insert();
        }else{
            $this->bookCoverInfo->update();
        }
    }

    private function getOpenArchivesCover($id)
    {
        //The thumbnail is not saved in the metadata.  To get the URL we need to fetch the page
        //and then get the thumbnail from the og:image element
        require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesRecord.php';
        $openArchivesRecord = new OpenArchivesRecord();
        $openArchivesRecord->id = $this->id;
        if ($openArchivesRecord->find(true)){
            $url = $openArchivesRecord->permanentUrl;
            $pageContents = file_get_contents($url);
            $matches = [];
            if (preg_match('~<meta property="og:image" content="(.*?)" />~', $pageContents, $matches)){
                $bookcoverUrl = $matches[1];
                return $this->processImageURL('open_archives', $bookcoverUrl, true);
            }
        }
        return false;
    }
}
