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

	public function loadCover($configArray, $timer, $logger)
	{
		$this->configArray = $configArray;
		$this->timer = $timer;
		$this->doTimings = $this->configArray['System']['coverTimings'];
		$this->timer->enableTimings($this->doTimings);
		$this->logger = $logger;

		$this->log("Starting to load cover", Logger::LOG_NOTICE);
		$this->bookCoverPath = $configArray['Site']['coverPath'];
		if (!$this->loadParameters()) {
			return;
		}

		if (!$this->reload) {
			$this->log("Looking for Cached cover", Logger::LOG_NOTICE);
			if ($this->getCachedCover()) {
				return;
			}
		}
		if ($this->type == 'open_archives') {
			if ($this->getOpenArchivesCover($this->id)) {
				return;
			}
		} elseif ($this->type == 'list') {
			if ($this->getListCover($this->id)) {
				return;
			}
		} elseif ($this->type == 'webpage') {
			if ($this->getWebPageCover($this->id)) {
				return;
			}
		} else {
			if ($this->type == 'overdrive') {
				//Will exit if we find a cover
				if ($this->getOverDriveCover()) {
					return;
				}
			} else if ($this->type == 'hoopla') {
				//Will exit if we find a cover
				if ($this->getHooplaCover($this->id)) {
					return;
				}
			} else if ($this->type == 'rbdigital') {
				//Will exit if we find a cover
				if ($this->getRBdigitalCover($this->id)) {
					return;
				}
			} else if ($this->type == 'rbdigital_magazine') {
				//Will exit if we find a cover
				if ($this->getRBdigitalMagazineCover($this->id)) {
					return;
				}
			} else if ($this->type == 'cloud_library') {
				//Will exit if we find a cover
				if ($this->getCloudLibraryCover($this->id, true)) {
					return;
				}
			} elseif ($this->type == 'Colorado State Government Documents') {
				if ($this->getColoradoGovDocCover()) {
					return;
				}
			} elseif ($this->type == 'Classroom Video on Demand') {
				if ($this->getClassroomVideoOnDemandCover($this->id)) {
					return;
				}
			} elseif (stripos($this->type, 'films on demand') !== false) {
				if ($this->getFilmsOnDemandCover($this->id)) {
					return;
				}
			} elseif (stripos($this->type, 'proquest') !== false || stripos($this->type, 'ebrary') !== false) {
				if ($this->getEbraryCover($this->id)) {
					return;
				}
				// Any Side-loaded Collection that has a cover in the 856 tag (and additional conditionals)
			} elseif (stripos($this->type, 'kanopy') !== false) {
				if ($this->getSideLoadedCover($this->type . ':' . $this->id)) {
					return;
				}
			} elseif (stripos($this->type, 'bookflix') !== false) {
				if ($this->getSideLoadedCover($this->type . ':' . $this->id)) {
					return;
				}
			} elseif (stripos($this->type, 'boombox') !== false) {
				if ($this->getSideLoadedCover($this->type . ':' . $this->id)) {
					return;
				}
			} elseif (stripos($this->type, 'biblioboard') !== false) {
				if ($this->getSideLoadedCover($this->type . ':' . $this->id)) {
					return;
				}
			} elseif (stripos($this->type, 'lynda') !== false) {
				if ($this->getSideLoadedCover($this->type . ':' . $this->id)) {
					return;
				}
			} elseif (stripos($this->type, 'odilo') !== false) {
				if ($this->getSideLoadedCover($this->type . ':' . $this->id)) {
					return;
				}
				// Cloud Library
			} elseif (stripos($this->type, 'zinio') !== false) {
				if ($this->getZinioCover($this->type . ':' . $this->id)) {
					return;
				}
			}

			if ($this->type == 'grouped_work' && $this->getUploadedGroupedWorkCover($this->id)){
				return;
			}

			if ($this->type != 'grouped_work' && $this->getCoverFromMarc()) {
				return;
			}

			$this->log("Looking for cover from providers", Logger::LOG_NOTICE);
			if ($this->getCoverFromProvider()) {
				return;
			}

			if ($this->getGroupedWorkCover()) {
				return;
			}
		}

		$this->log("No image found, using default image", Logger::LOG_NOTICE);
		$this->getDefaultCover();

	}

	private function getHooplaCover($id){
		if (strpos($id, ':') !== false){
			list(, $id) = explode(":", $id);
		}
		require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
		$driver = new HooplaRecordDriver($id);
		if ($driver->isValid()){
			$coverUrl = $driver->getHooplaCoverUrl();
			return $this->processImageURL('hoopla', $coverUrl, true);
		}

		return false;
	}

	private function getSideLoadedCover($sourceAndId){
		if (strpos($sourceAndId, ':') !== false){
			// Side-loaded Record requires both source & id

			require_once ROOT_DIR . '/RecordDrivers/SideLoadedRecord.php';
			$driver = new SideLoadedRecord($sourceAndId);
			if ($driver) {
				/** @var File_MARC_Data_Field[] $linkFields */
				$linkFields = $driver->getMarcRecord()->getFields('856');
				foreach ($linkFields as $linkField) {
					if ($linkField->getIndicator(1) == 4 && $linkField->getIndicator(2) == 2) {
						$coverUrl = $linkField->getSubfield('u')->getData();
						return $this->processImageURL('sideload', $coverUrl, true);
					}
				}
			}
		}
		return false;
	}

	private function getColoradoGovDocCover(){
		$filename = "interface/themes/responsive/images/state_flag_of_colorado.png";
		if ($this->processImageURL('coloradoGovDoc', $filename, false)){
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
		if ($this->processImageURL('classroomVideoOnDemand', $coverUrl,true)){
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
				return $this->processImageURL('overdrive', $filename, true);
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	private function getZinioCover($sourceAndId) {
		if (strpos($sourceAndId, ':') !== false){
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

	private function getRBdigitalCover($id)
	{
		if (strpos($id, ':') !== false) {
			list(, $id) = explode(":", $id);
		}
		require_once ROOT_DIR . '/RecordDrivers/RBdigitalRecordDriver.php';
		$driver = new RBdigitalRecordDriver($id);
		if ($driver) {
			$coverUrl = $driver->getRBdigitalBookcoverUrl('large');
			return $this->processImageURL('rbdigital', $coverUrl, true);
		}
		return false;
	}

	private function getRBdigitalMagazineCover($id)
	{
		if (strpos($id, ':') !== false) {
			list(, $id) = explode(":", $id);
		}
		require_once ROOT_DIR . '/RecordDrivers/RBdigitalMagazineDriver.php';
		$driver = new RBdigitalMagazineDriver($id);
		if ($driver) {
			$coverUrl = $driver->getRBdigitalBookcoverUrl();
			return $this->processImageURL('rbdigital_magazine', $coverUrl, true);
		}
		return false;
	}

	private function getCloudLibraryCover($id, $createDefaultIfNotFound = false){
		if (strpos($id, ':') !== false){
			list(, $id) = explode(":", $id);
		}
		require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
		$driver = new CloudLibraryRecordDriver($id);
		if ($driver) {
			$coverUrl = $driver->getCloudLibraryBookcoverUrl();
			if ($coverUrl != null) {
				return $this->processImageURL('cloud_library', $coverUrl, true);
			}else{
				if ($createDefaultIfNotFound){
					return $this->getDefaultCover($driver);
				}else{
					return false;
				}
			}
		}
		return false;
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
		$this->id = isset($_GET['id']) ? $_GET['id'] : '';
		//If this is external eContent, we don't care about that part, just use the remaining id
		$this->id = str_replace('external_econtent:', '', $this->id);
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
		/** @var Library */
		global $library;
		$this->defaultCoverCacheFile = $this->bookCoverPath . '/' . $this->size . '/' . $library->subdomain . '_' . $this->cacheName . '.png';
		$this->logTime("load parameters");
		return true;
	}

	private function addCachingHeader(){
		//Add caching information
		$expires = 60*60*24*14;  //expire the cover in 2 weeks on the client side
		header("Cache-Control: maxage=".$expires);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
		$this->log("Added caching header", Logger::LOG_NOTICE);
	}

	private function addModificationHeaders($filename){
		$timestamp = filemtime($filename);
		$this->logTime("Got file timestamp $timestamp");
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

	private function returnImage($localPath){
		header('Content-type: image/png');
		if ($this->addModificationHeaders($localPath)){
			$this->logTime("Added modification headers");
			$this->addCachingHeader();
			$this->logTime("Added caching headers");
			ob_clean();
			flush();
			readfile($localPath);
			$this->log("Read file $localPath", Logger::LOG_DEBUG);
			$this->logTime("echo file $localPath");
		}else{
			$this->logTime("Added modification headers");
		}
	}

	private static $providers = null;
	private function getCoverFromProvider(){
		// Update to allow retrieval of covers based on upc
		if (!is_null($this->isn) || !is_null($this->upc) || !is_null($this->issn)) {
			$this->log("Looking for picture based on isbn and upc.", Logger::LOG_NOTICE);

			//TODO: Allow these to be sorted
			require_once ROOT_DIR . '/sys/Enrichment/SyndeticsSetting.php';
			$syndeticsSettings = new SyndeticsSetting();
			if ($syndeticsSettings->find(true)){
				if ($this->syndetics($syndeticsSettings->syndeticsKey)){
					return true;
				}
			}

			require_once ROOT_DIR . '/sys/Enrichment/ContentCafeSetting.php';
			$contentCafeSettings = new ContentCafeSetting();
			if ($contentCafeSettings->find(true)){
				if ($this->contentCafe($contentCafeSettings)){
					return true;
				}
			}

			require_once ROOT_DIR . '/sys/Enrichment/GoogleApiSetting.php';
			$googleApiSettings = new GoogleApiSetting();
			if ($googleApiSettings->find(true)){
				if ($this->google($googleApiSettings)){
					return true;
				}
			}

		}
		return false;
	}

	private function getCoverFromMarc($marcRecord = null){
		$this->log("Looking for picture as part of 856 tag.", Logger::LOG_NOTICE);

		if ($marcRecord == null){
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
							if ($this->processImageURL('marcRecord', $filename, false)){
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
			$this->log("Found 690 field", Logger::LOG_NOTICE);
			foreach ($marcFields as $marcField){
				if ($marcField->getSubfield('a')){
					$this->log("Found 690a subfield", Logger::LOG_NOTICE);
					$subfield_a = $marcField->getSubfield('a')->getData();
					if (preg_match('/seed library.*/i', $subfield_a, $matches)){
						$this->log("Title is a seed library title", Logger::LOG_NOTICE);
						$filename = "interface/themes/responsive/images/seed_library_logo.jpg";
						if ($this->processImageURL('seedLibrary', $filename, false)){
							return true;
						}
					}
				}
			}
		}

		//Check for Flatirons covers
        $marcFields = $marcRecord->getFields('962');
		if ($marcFields){
			$this->log("Found 962 field", Logger::LOG_NOTICE);
			foreach ($marcFields as $marcField){
				if ($marcField->getSubfield('u')){
					$this->log("Found 962u subfield", Logger::LOG_NOTICE);
					$subfield_u = $marcField->getSubfield('u')->getData();
					if ($this->processImageURL('marcRecord', $subfield_u, true)){
						return true;
					}
				}
			}
		}

		return false;
	}

	private function getCachedCover()
	{
		$hasCachedImage = false;
		if ($this->bookCoverInfo->getNumResults() == 1) {
			if ($this->size == 'small' && $this->bookCoverInfo->thumbnailLoaded == 1) {
				$hasCachedImage = true;
			} else if ($this->size == 'medium' && $this->bookCoverInfo->mediumLoaded == 1) {
				$hasCachedImage = true;
			} else if ($this->size == 'large' && $this->bookCoverInfo->largeLoaded == 1) {
				$hasCachedImage = true;
			}
		}

		if ($hasCachedImage) {
			$this->bookCoverInfo->lastUsed = time();
			$this->bookCoverInfo->update();


			if ($this->bookCoverInfo->imageSource == 'default') {
				/** @var Library */
				global $library;
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
	 */
	function getDefaultCover($recordDriver = null){
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
			if ($recordDriver == null){
				require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
				$recordDriver = RecordDriverFactory::initRecordDriverById($this->type . ':' . $this->id);
			}
		    if ($recordDriver->isValid()){
				$title = $recordDriver->getTitle();
				if ($recordDriver instanceof OpenArchivesRecordDriver) {
					$author = '';
				}else{
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
		$this->log("Processing $url", Logger::LOG_NOTICE);
		$context = stream_context_create(array('http'=>array(
			'header' => "User-Agent: {$this->configArray['Catalog']['catalogUserAgent']}\r\n"
		)));

		if ($image = @file_get_contents($url, false, $context)) {
			// Figure out file paths -- $tempFile will be used to store the downloaded
			// image for analysis.  $finalFile will be used for long-term storage if
			// $cache is true or for temporary display purposes if $cache is false.
			if ($source == 'default'){
				$tempFile = str_replace('.png', uniqid(), $this->defaultCoverCacheFile);
				$finalFile = $this->defaultCoverCacheFile;
			}else{
				$tempFile = str_replace('.png', uniqid(), $this->cacheFile);
				$finalFile = $this->cacheFile;
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
			list($width, $height, $type) = @getimagesize($tempFile);

			// File too small -- delete it and report failure.
			if ($width < 2 && $height < 2) {
				@unlink($tempFile);
				return false;
			}

			// Test Image for for partial load
			if(!$imageResource = @imagecreatefromstring($image)){
				$this->log("Could not create image from string $url", Logger::LOG_ERROR);
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

					$this->log('Partial Gray image loaded.', Logger::LOG_ERROR);
					if ($attemptRefetch) {
						$this->log('Partial Gray image, attempting refetch.', Logger::LOG_NOTICE);
						return $this->processImageURL($source, $url, false); // Refetch once.
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

			//Check to see if the image needs to be re-sized
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

				$this->log("Resizing image New Width: $new_width, New Height: $new_height", Logger::LOG_NOTICE);

				// create a new temporary image
				$tmp_img = imagecreatetruecolor( $new_width, $new_height );

				// copy and resize old image into new image
				if (!imagecopyresampled( $tmp_img, $imageResource, 0, 0, 0, 0, $new_width, $new_height, $width, $height )){
					$this->log("Could not resize image $url to $this->localFile", Logger::LOG_ERROR);
					return false;
				}

				// save thumbnail into a file
				if (file_exists($finalFile)){
					$this->log("File $finalFile already exists, deleting", Logger::LOG_DEBUG);
					unlink($finalFile);
				}

				if (!@imagepng( $tmp_img, $finalFile, 9)){
					$this->log("Could not save re-sized file $$this->localFile", Logger::LOG_ERROR);
					return false;
				}

			}else{
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
					if (!$conversionOk){
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

		$url = 'https://syndetics.com';
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
		$this->log("Syndetics url: $url", Logger::LOG_DEBUG);
		return $this->processImageURL('syndetics', $url, true);
	}

	function librarything($key)
	{
		if (is_null($this->isn)){
			return false;
		}
		$url = 'http://covers.librarything.com/devkey/' . $key . '/' . $this->size . '/isbn/' . $this->isn;
		return $this->processImageURL('libraryThing', $url, true);
	}

	/**
	 * Retrieve a Content Cafe cover.
	 *
	 * @param ContentCafeSetting $id
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
		$url = 'https://contentcafe2.btol.com';

		$lookupCode = $this->isn;
		if (!$lookupCode) {
			$lookupCode = $this->issn;
			if (!$lookupCode & $this->upc) {
				$lookupCode = $this->upc;
			}
		}

		$url .= "/ContentCafe/Jacket.aspx?UserID={$settings->contentCafeId}&Password={$settings->pwd}&Return=1&Type={$size}&Value={$lookupCode}&erroroverride=1";

		return $this->processImageURL('contentCafe', $url, true);
	}

	function google(GoogleApiSetting $googleApiSettings,$title = null, $author = null)
	{
		if (is_null($this->isn) && is_null($title) && is_null($author)){
			return false;
		}
		if (is_null($title) && is_null($author)){
			$source = 'google_isbn';
			$url = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . $this->isn;
		}else{
			$source = 'google_title_author';
			$url = 'https://www.googleapis.com/books/v1/volumes?q=intitle:"' . urlencode($title) . '"';
			if (!is_null($author)){
				$url .= "+inauthor:" . urlencode($author);
			}else{
				return false;
			}
		}

		if (!empty($googleApiSettings->googleBooksKey)){
			$url .= '&key=' . $googleApiSettings->googleBooksKey;
		}
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$client = new CurlWrapper();
		$result = $client->curlGetPage($url);
		if ($result !== false) {
			if ($json = json_decode($result, true)) {
				if ($json['totalItems'] > 0 && count($json['items']) > 0){
					foreach ($json['items'] as $item){
						if (!empty($item['volumeInfo']['imageLinks']['thumbnail'])){
							if ($this->processImageURL($source, $item['volumeInfo']['imageLinks']['thumbnail'], true)){
								return true;
							}
						}elseif (!empty($item['volumeInfo']['imageLinks']['smallThumbnail'])){
							if ($this->processImageURL($source, $item['volumeInfo']['imageLinks']['thumbnail'], true)){
								return true;
							}
						}
					}
				}
			}
		}

		return false;
	}

	function log($message, $level = Logger::LOG_DEBUG){
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
			if ($this->getUploadedGroupedWorkCover($this->groupedWork->getPermanentId())){
				return true;
			}
			//Have not found a grouped work based on isbn or upc, check based on related records
			$relatedRecords = $this->groupedWork->getRelatedRecords(true);
			foreach ($relatedRecords as $relatedRecord){
				if (strcasecmp($relatedRecord->source, 'OverDrive') == 0){
					if ($this->getOverDriveCover($relatedRecord->id)){
						return true;
					}
				}elseif (strcasecmp($relatedRecord->source, 'Hoopla') == 0){
					if ($this->getHooplaCover($relatedRecord->id)){
						return true;
					}
				} elseif (strcasecmp($relatedRecord->source, 'rbdigital_magazine') == 0){
					if ($this->getRBdigitalMagazineCover($relatedRecord->id)) {
						return true;
					}
				} elseif (strcasecmp($relatedRecord->source, 'rbdigital') == 0){
					if ($this->getRBdigitalCover($relatedRecord->id)) {
						return true;
					}
				} elseif (strcasecmp($relatedRecord->source, 'cloud_library') == 0){
					if ($this->getCloudLibraryCover($relatedRecord->id)) {
						return true;
					}
				}elseif (strcasecmp($relatedRecord->source, 'Colorado State Government Documents') == 0){
					if ($this->getColoradoGovDocCover()){
						return true;
					}
				}elseif (strcasecmp($relatedRecord->source, 'Classroom Video on Demand') == 0){
					if ($this->getClassroomVideoOnDemandCover($relatedRecord->id)){
						return true;
					}
				}elseif (stripos($relatedRecord->source, 'proquest') !== false || stripos($relatedRecord->source, 'ebrary') !== false){
					if ($this->getEbraryCover($relatedRecord->id)){
						return true;
					}
				}elseif (stripos($relatedRecord->source, 'films on demand') !== false){
					if ($this->getFilmsOnDemandCover($relatedRecord->id)){
						return true;
					}
				}elseif (stripos($relatedRecord->source, 'kanopy') !== false){
					if ($this->getSideLoadedCover($relatedRecord->id)){
						return true;
					}
				} elseif (stripos($relatedRecord->source, 'bookflix') !== false){
					if ($this->getSideLoadedCover($relatedRecord->id)) {
						return true;
					}
				} elseif (stripos($relatedRecord->source, 'boombox') !== false){
					if ($this->getSideLoadedCover($relatedRecord->id)) {
						return true;
					}
				} elseif (stripos($relatedRecord->source, 'biblioboard') !== false){
					if ($this->getSideLoadedCover($relatedRecord->id)) {
						return true;
					}
				} elseif (stripos($relatedRecord->source, 'lynda') !== false){
					if ($this->getSideLoadedCover($relatedRecord->id)) {
						return true;
					}
				} elseif (stripos($relatedRecord->source, 'Odilo') !== false){
					if ($this->getSideLoadedCover($relatedRecord->id)) {
						return true;
					}
				} elseif (stripos($relatedRecord->source, 'zinio') !== false){
					if ($this->getZinioCover($relatedRecord->id)) {
						return true;
					}
                }else{
					/** @var GroupedWorkSubDriver $driver */
					$driver = $relatedRecord->_driver;
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

			if (!empty($driver)) {
				$groupedWork = new GroupedWork();
				$groupedWork->permanent_id = $this->groupedWork->getPermanentId();
				if ($groupedWork->find(true)) {
					if ($groupedWork->grouping_category == 'book') {
						require_once ROOT_DIR . '/sys/Enrichment/GoogleApiSetting.php';
						$googleApiSettings = new GoogleApiSetting();
						if ($googleApiSettings->find(true)) {
							if ($this->google($googleApiSettings, $driver->getTitle(), $driver->getPrimaryAuthor())) {
								return true;
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

	private function getOpenArchivesCover($id)
	{
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
			$curlWrapper->close_curl();
			$matches = [];
			if (preg_match('~<meta property="og:image" content="(.*?)" />~', $pageContents, $matches)) {
				$bookcoverUrl = $matches[1];
				return $this->processImageURL('open_archives', $bookcoverUrl, true);
			} /** @noinspection HtmlDeprecatedAttribute */
			elseif (preg_match('~<img src="(.*?)" border="0" alt="Thumbnail image">~', $pageContents, $matches)) {
				$bookcoverUrl = $matches[1];
				if (strpos($bookcoverUrl, 'http') !== 0) {
					$urlComponents = parse_url($url);
					$bookcoverUrl = $urlComponents['scheme'] . '://' . $urlComponents['host'] . $bookcoverUrl;
				}
				return $this->processImageURL('open_archives', $bookcoverUrl, true);
			}elseif (preg_match('~<div id="item-images">.*<img src="(.*?)".*>~', $pageContents, $matches)) {
				$bookcoverUrl = $matches[1];
				if (strpos($bookcoverUrl, 'http') !== 0) {
					$urlComponents = parse_url($url);
					$bookcoverUrl = $urlComponents['scheme'] . '://' . $urlComponents['host'] . $bookcoverUrl;
				}
				return $this->processImageURL('open_archives', $bookcoverUrl, true);
			}
		}
		return false;
	}

	private function getListCover($id)
	{
		//Build a cover based on the titles within list
		require_once ROOT_DIR . '/sys/Covers/ListCoverBuilder.php';
		$coverBuilder = new ListCoverBuilder();
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
		$userList = new UserList();
		$userList->id = $id;
		if ($userList->find(true)) {
			$title = $userList->title;
			$listTitles = $userList->getListTitles();
			$coverBuilder->getCover($title, $listTitles, $this->cacheFile);
			return $this->processImageURL('default', $this->cacheFile, false);
		} else {
			return false;
		}
	}

	private function getWebPageCover($id)
	{
		//Build a cover based on the title of the page
		require_once ROOT_DIR . '/sys/Covers/WebPageCoverBuilder.php';
		$coverBuilder = new WebPageCoverBuilder();
		require_once ROOT_DIR . '/RecordDrivers/WebsitePageRecordDriver.php';

		$webPageDriver = new WebsitePageRecordDriver($id);
		if ($webPageDriver->isValid()) {
			$title = $webPageDriver->getTitle();
			$coverBuilder->getCover($title, $this->cacheFile);
			return $this->processImageURL('default', $this->cacheFile, false);
		} else {
			return false;
		}
	}

	private function getUploadedGroupedWorkCover($permanentId)
	{
		$uploadedImage = $this->bookCoverPath . '/original/' . $permanentId . '.png';
		if (file_exists($uploadedImage)){
			return $this->processImageURL('upload', $uploadedImage);
		}
		return false;
	}
}
