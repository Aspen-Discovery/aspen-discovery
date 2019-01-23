<?php
/**
 * A superclass for Digital Archive Objects
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 9/9/2015
 * Time: 4:13 PM
 */

require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
abstract class Archive_Object extends Action {
	protected $pid;
	/** @var  FedoraObject $archiveObject */
	protected $archiveObject;
	/** @var IslandoraDriver $recordDriver */
	protected $recordDriver;
	//protected $dcData;
	protected $modsData;
	//Data with a namespace of mods
	protected $modsModsData;
	protected $relsExtData;

	protected $formattedSubjects;
	protected $links;

	/**
	 * @param string $mainContentTemplate Name of the SMARTY template file for the main content of the Full Record View Pages
	 * @param string $pageTitle What to display is the html title tag
	 */
	function display($mainContentTemplate, $pageTitle = null) {
		global $interface;
		global $logger;

		$pageTitle = $pageTitle == null ? $this->archiveObject->label : $pageTitle;
		$interface->assign('breadcrumbText', $pageTitle);

		// Set Search Navigation
		// Retrieve User Search History
		//Get Next/Previous Links
//		$this->initializeExhibitContextDataFromCookie();

		$isExhibitContext = !empty($_SESSION['ExhibitContext']) and $this->recordDriver->getUniqueID() != $_SESSION['ExhibitContext'];
		if ($isExhibitContext && empty($_COOKIE['exhibitNavigation'])) {
			$isExhibitContext = false;
			$this->endExhibitContext();
		}
		if ($isExhibitContext) {
			$logger->log("In exhibit context, setting exhibit navigation", PEAR_LOG_DEBUG);
			$this->setExhibitNavigation();
		} elseif (isset($_SESSION['lastSearchURL'])) {
			$logger->log("In search context, setting search navigation", PEAR_LOG_DEBUG);
			$this->setArchiveSearchNavigation();
		} else {
			$logger->log("Not in any context, not setting navigation", PEAR_LOG_DEBUG);
		}

		//Check to see if usage is restricted or not.
		$viewingRestrictions = $this->recordDriver->getViewingRestrictions();
		if (count($viewingRestrictions) > 0){
			$canView = false;
			$validHomeLibraries = array();
			$userPTypes = array();

			$user = UserAccount::getLoggedInUser();
			if ($user && $user->getHomeLibrary()){
				$validHomeLibraries[] = $user->getHomeLibrary()->subdomain;
				$userPTypes = $user->getRelatedPTypes();
				$linkedAccounts = $user->getLinkedUsers();
				foreach ($linkedAccounts as $linkedAccount){
					$validHomeLibraries[] = $linkedAccount->getHomeLibrary()->subdomain;
				}
			}

			global $locationSingleton;
			$physicalLocation = $locationSingleton->getPhysicalLocation();
			$physicalLibrarySubdomain = null;
			if ($physicalLocation){
				$physicalLibrary = new Library();
				$physicalLibrary->libraryId = $physicalLocation->libraryId;
				if ($physicalLibrary->find(true)) {
					$physicalLibrarySubdomain = $physicalLibrary->subdomain;
				}
			}

			foreach ($viewingRestrictions as $restriction){
				$restrictionType = 'homeLibraryOrIP';
				if (strpos($restriction, ':') !== false){
					list($restrictionType, $restriction) = explode(':', $restriction, 2);
				}
				$restrictionType = strtolower(trim($restrictionType));
				$restrictionType = str_replace(' ', '', strtolower($restrictionType));
				$restriction = trim($restriction);
				$restrictionLower = strtolower($restriction);
				if ($restrictionLower == 'anonymousmasterdownload' || $restrictionLower == 'verifiedmasterdownload'){
					continue;
				}

				if ($restrictionType == 'homelibraryorip' || $restrictionType == 'patronsfrom') {
					$libraryDomain = trim($restriction);
					if ($restrictionLower == 'default' || array_search($libraryDomain, $validHomeLibraries) !== false){
						//User is valid based on their login
						$canView = true;
						break;
					}
				}
				if ($restrictionType == 'homelibraryorip' || $restrictionType == 'withinlibrary') {
					$libraryDomain = trim($restriction);
					if ($libraryDomain == $physicalLibrarySubdomain){
						//User is valid based on being in the library
						$canView = true;
						break;
					}
				}
				if ($restrictionType == 'ptypes' || $restrictionType == 'ptype'){
					$validPTypes = explode(',', $restriction);
					foreach ($validPTypes as $pType){
						if (array_search($pType, $userPTypes) !== false){
							$canView = true;
							break;
						}
					}
					if ($canView){
						break;
					}
				}
			}

		}else{
			$canView = true;
		}

		$interface->assign('canView', $canView);

		$showClaimAuthorship = $this->recordDriver->getShowClaimAuthorship();
		$interface->assign('showClaimAuthorship', $showClaimAuthorship);

//		$this->updateCookieForExhibitContextData();

		parent::display($mainContentTemplate, $pageTitle);
	}

	//TODO: This should eventually move onto a Record Driver
	function loadArchiveObjectData() {
		global $interface;
		global $configArray;
		$fedoraUtils = FedoraUtils::getInstance();

		// Replace 'object:pid' with the PID of the object to be loaded.
		$this->pid = urldecode($_REQUEST['id']);
		$interface->assign('pid', $this->pid);

		list($namespace) = explode(':', $this->pid);
		//Find the owning library
		$owningLibrary = new Library();
		$owningLibrary->archiveNamespace = $namespace;
		if ($owningLibrary->find(true) && $owningLibrary->N == 1) {
			$interface->assign('allowRequestsForArchiveMaterials', $owningLibrary->allowRequestsForArchiveMaterials);
		} else {
			$interface->assign('allowRequestsForArchiveMaterials', false);
		}

		$this->archiveObject = $fedoraUtils->getObject($this->pid);
		if ($this->archiveObject == null){
			PEAR_Singleton::raiseError(new PEAR_Error("Could not load object for PID {$this->pid}"));
		}
		$this->recordDriver = RecordDriverFactory::initRecordDriver($this->archiveObject);
		$interface->assign('recordDriver', $this->recordDriver);

		//Load the MODS data stream
		$this->modsData = $this->recordDriver->getModsData();
		$interface->assign('mods', $this->modsData);

		$location = $this->recordDriver->getModsValue('location', 'mods');
		if (strlen($location) > 0) {
			$interface->assign('primaryUrl', $this->recordDriver->getModsValue('url', 'mods', $location));
		}

		$alternateNames = $this->recordDriver->getModsValues('alternateName', 'marmot');
		$interface->assign('alternateNames', FedoraUtils::cleanValues($alternateNames));

		$this->recordDriver->loadRelatedEntities();

		$addressInfo = array();
		$latitude = $this->recordDriver->getModsValue('latitude', 'marmot');
		$longitude = $this->recordDriver->getModsValue('longitude', 'marmot');
		$addressStreetNumber = $this->recordDriver->getModsValue('addressStreetNumber', 'marmot');
		$addressStreet = $this->recordDriver->getModsValue('addressStreet', 'marmot');
		$address2 = $this->recordDriver->getModsValue('address2', 'marmot');
		$addressCity = $this->recordDriver->getModsValue('addressCity', 'marmot');
		$addressCounty = $this->recordDriver->getModsValue('addressCounty', 'marmot');
		$addressState = $this->recordDriver->getModsValue('addressState', 'marmot');
		$addressZipCode = $this->recordDriver->getModsValue('addressZipCode', 'marmot');
		$addressCountry = $this->recordDriver->getModsValue('addressCountry', 'marmot');
		$addressOtherRegion = $this->recordDriver->getModsValue('addressOtherRegion', 'marmot');
		if (strlen($latitude) ||
				strlen($longitude) ||
				strlen($addressStreetNumber) ||
				strlen($addressStreet) ||
				strlen($address2) ||
				strlen($addressCity) ||
				strlen($addressCounty) ||
				strlen($addressState) ||
				strlen($addressZipCode) ||
				strlen($addressOtherRegion)
		) {

			if (strlen($latitude) > 0) {
				$addressInfo['latitude'] = $latitude;
			}
			if (strlen($longitude) > 0) {
				$addressInfo['longitude'] = $longitude;
			}

			if (strlen($addressStreetNumber) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressStreetNumber'] = $addressStreetNumber;
			}
			if (strlen($addressStreet) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressStreet'] = $addressStreet;
			}
			if (strlen($address2) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['address2'] = $address2;
			}
			if (strlen($addressCity) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressCity'] = $addressCity;
			}
			if (strlen($addressState) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressState'] = $addressState;
			}
			if (strlen($addressCounty) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressCounty'] = $addressCounty;
			}
			if (strlen($addressZipCode) > 0) {
				$addressInfo['hasDetailedAddress'] = true;
				$addressInfo['addressZipCode'] = $addressZipCode;
			}
			if (strlen($addressCountry) > 0) {
				$addressInfo['addressCountry'] = $addressCountry;
			}
			if (strlen($addressOtherRegion) > 0) {
				$addressInfo['addressOtherRegion'] = $addressOtherRegion;
			}
			$interface->assign('addressInfo', $addressInfo);
		}//End verifying checking for address information

		//Load information about dates
		$startDate = $this->recordDriver->getModsValue('placeDateStart', 'marmot');
		if ($startDate) {
			$interface->assign('placeStartDate', $startDate);
		}
		$startDate = $this->recordDriver->getModsValue('dateEstablished', 'marmot');
		if ($startDate) {
			$interface->assign('organizationStartDate', $startDate);
		}
		$startDate = $this->recordDriver->getModsValue('eventStartDate', 'marmot');
		if ($startDate) {
			$interface->assign('eventStartDate', $startDate);
		}
		$startDate = $this->recordDriver->getModsValue('startDate', 'marmot');
		$formattedDate = DateTime::createFromFormat('Y-m-d', $startDate);
		if ($formattedDate != false) {
			$startDate = $formattedDate->format('m/d/Y');
		}
		if ($startDate){
			if ($this->recordDriver instanceof PlaceDriver){
				$interface->assign('placeStartDate', $startDate);
			}elseif ($this->recordDriver instanceof EventDriver){
				$interface->assign('eventStartDate', $startDate);
			}elseif ($this->recordDriver instanceof OrganizationDriver){
				$interface->assign('organizationStartDate', $startDate);
			}elseif ($this->recordDriver instanceof PersonDriver){
				$interface->assign('birthDate', $startDate);
			}
		}

		$endDate = $this->recordDriver->getModsValue('placeDateEnd', 'marmot');
		if ($endDate) {
			$interface->assign('placeEndDate', $endDate);
		}
		$endDate = $this->recordDriver->getModsValue('eventEndDate', 'marmot');
		if ($endDate) {
			$interface->assign('eventEndDate', $endDate);
		}
		$endDate = $this->recordDriver->getModsValue('dateDisbanded', 'marmot');
		if ($endDate) {
			$interface->assign('organizationEndDate', $endDate);
		}
		$endDate = $this->recordDriver->getModsValue('endDate', 'marmot');
		$formattedDate = DateTime::createFromFormat('Y-m-d', $endDate);
		if ($formattedDate != false) {
			$endDate = $formattedDate->format('m/d/Y');
		}
		if ($endDate){
			if ($this->recordDriver instanceof PlaceDriver){
				$interface->assign('placeEndDate', $endDate);
			}elseif ($this->recordDriver instanceof EventDriver){
				$interface->assign('eventEndDate', $endDate);
			}elseif ($this->recordDriver instanceof OrganizationDriver){
				$interface->assign('organizationEndDate', $endDate);
			}elseif ($this->recordDriver instanceof PersonDriver){
				$interface->assign('deathDate', $endDate);
			}
		}


		$title = $this->recordDriver->getFullTitle();

		$interface->assign('title', $title);
		$interface->setPageTitle($title);


		$interface->assign('original_image', $this->recordDriver->getBookcoverUrl('original'));
		$interface->assign('large_image', $this->recordDriver->getBookcoverUrl('large'));
		$interface->assign('medium_image', $this->recordDriver->getBookcoverUrl('medium'));

		$repositoryLink = $configArray['Islandora']['repositoryUrl'] . '/islandora/object/' . $this->pid;
		$interface->assign('repositoryLink', $repositoryLink);

		//Check for display restrictions
		if ($this->recordDriver instanceof BasicImageDriver || $this->recordDriver instanceof LargeImageDriver || $this->recordDriver instanceof BookDriver || $this->recordDriver instanceof PageDriver || $this->recordDriver instanceof AudioDriver || $this->recordDriver instanceof VideoDriver) {
			/** @var CollectionDriver $collection */
			$anonymousMasterDownload = true;
			$verifiedMasterDownload = true;
			$anonymousLcDownload = true;
			$verifiedLcDownload = true;
			foreach ($this->recordDriver->getRelatedCollections() as $collection) {
				$collectionDriver = RecordDriverFactory::initRecordDriver($collection['object']);
				if (!$collectionDriver->canAnonymousDownloadMaster()) {
					$anonymousMasterDownload = false;
				}
				if (!$collectionDriver->canVerifiedDownloadMaster()) {
					$verifiedMasterDownload = false;
				}
				if (!$collectionDriver->canAnonymousDownloadLC()) {
					$anonymousLcDownload = false;
				}
				if (!$collectionDriver->canVerifiedDownloadLC()) {
					$verifiedLcDownload = false;
				}
			}

			$viewingRestrictions = $this->recordDriver->getViewingRestrictions();
			foreach ($viewingRestrictions as $viewingRestriction){
				$restrictionLower = str_replace(' ', '', strtolower($viewingRestriction));
				if ($restrictionLower == 'preventanonymousmasterdownload'){
					$anonymousMasterDownload = false;
				}
				if ($restrictionLower == 'preventverifiedmasterdownload'){
					$verifiedMasterDownload = false;
					$anonymousMasterDownload = false;
				}
				if ($restrictionLower == 'anonymousmasterdownload'){
					$anonymousMasterDownload = true;
					$verifiedMasterDownload = true;
				}
				if ($restrictionLower == 'verifiedmasterdownload'){
					$anonymousMasterDownload = true;
				}
			}
			$interface->assign('anonymousMasterDownload', $anonymousMasterDownload);
			if ($anonymousMasterDownload){
				$verifiedMasterDownload = true;
			}
			$interface->assign('verifiedMasterDownload', $verifiedMasterDownload);
			$interface->assign('anonymousLcDownload', $anonymousLcDownload);
			if ($anonymousLcDownload){
				$verifiedLcDownload = true;
			}
			$interface->assign('verifiedLcDownload', $verifiedLcDownload);
		}
	}

	protected function endExhibitContext()
	{
		global $logger;
		$logger->log("Ending exhibit context", PEAR_LOG_DEBUG);
		$_SESSION['ExhibitContext']  = null;
		$_SESSION['exhibitSearchId'] = null;
		$_SESSION['placePid']        = null;
		$_SESSION['placeLabel']      = null;
		$_SESSION['dateFilter']      = null;

		$_COOKIE['ExhibitContext']             = null;
		$_COOKIE ['exhibitSearchId']           = null;
		$_COOKIE['placePid']                   = null;
		$_COOKIE['placeLabel']                 = null;
		$_COOKIE['exhibitInAExhibitParentPid'] = null;
	}

	/**
	 *
	 */
	protected function setExhibitNavigation()
	{
		global $interface;
		global $logger;

		$interface->assign('isFromExhibit', true);

		// Return to Exhibit URLs
		$exhibitObject = RecordDriverFactory::initRecordDriver(array('PID' => $_SESSION['ExhibitContext']));
		$exhibitUrl    = $exhibitObject->getLinkUrl();
		$exhibitName   = $exhibitObject->getTitle();
		$isMapExhibit  = !empty($_SESSION['placePid']);
		if ($isMapExhibit) {
			$exhibitUrl .= '?style=map&placePid=' . urlencode($_SESSION['placePid']);
			if (!empty($_SESSION['placeLabel'])) {
				$exhibitName .= ' - ' . $_SESSION['placeLabel'];
			}
			$logger->log("Navigating from a map exhibit", PEAR_LOG_DEBUG);
		}else{
			$logger->log("Navigating from a NON map exhibit", PEAR_LOG_DEBUG);
		}

		//TODO: rename to template vars exhibitName and exhibitUrl;  does it affect other navigation contexts

		$interface->assign('lastCollection', $exhibitUrl);
		$interface->assign('collectionName', $exhibitName);
		$isExhibit = get_class($this) == 'Archive_Exhibit';
		if (!empty($_COOKIE['exhibitInAExhibitParentPid']) && $_COOKIE['exhibitInAExhibitParentPid'] == $_SESSION['ExhibitContext']) {
			$_COOKIE['exhibitInAExhibitParentPid'] = null;
		}

		if (!empty($_COOKIE['exhibitInAExhibitParentPid'])) {
			/** @var CollectionDriver $parentExhibitObject */
			$parentExhibitObject = RecordDriverFactory::initRecordDriver(array('PID' => $_COOKIE['exhibitInAExhibitParentPid']));
			$parentExhibitUrl    = $parentExhibitObject->getLinkUrl();
			$parentExhibitName   = $parentExhibitObject->getTitle();
			$interface->assign('parentExhibitUrl', $parentExhibitUrl);
			$interface->assign('parentExhibitName', $parentExhibitName);

			if ($isExhibit) { // If this is a child exhibit page
				//
				$interface->assign('lastCollection', $parentExhibitUrl);
				$interface->assign('collectionName', $parentExhibitName);
				$parentExhibitObject->getNextPrevLinks($this->pid);
			}
		}
		if (!empty($_COOKIE['collectionPid'])) {
			$fedoraUtils = FedoraUtils::getInstance();
			$collectionToLoadFromObject = $fedoraUtils->getObject($_COOKIE['collectionPid']);
			/** @var CollectionDriver $collectionDriver */
			$collectionDriver = RecordDriverFactory::initRecordDriver($collectionToLoadFromObject);
			$collectionDriver->getNextPrevLinks($this->pid);

		} elseif (!empty($_SESSION['exhibitSearchId']) && !$isExhibit) {
			$recordIndex = isset($_COOKIE['recordIndex']) ? $_COOKIE['recordIndex'] : null;
			$page        = isset($_COOKIE['page']) ? $_COOKIE['page'] : null;
			// Restore Islandora Search
			/** @var SearchObject_Islandora $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject('Islandora');
			$searchObject->init('islandora');
			$searchObject->getNextPrevLinks($_SESSION['exhibitSearchId'], $recordIndex, $page, $isMapExhibit);
			// pass page and record index info
			$logger->log("Setting exhibit navigation for exhibit {$_SESSION['ExhibitContext']} from search id {$_SESSION['exhibitSearchId']}", PEAR_LOG_DEBUG);
		}else{
			$logger->log("Exhibit search id was not provided", PEAR_LOG_DEBUG);
		}
	}

	private function setArchiveSearchNavigation()
	{
		global $interface;
		global $logger;
		$interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ? $_SESSION['lastSearchURL'] : false);
		$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'islandora';
		//TODO: What if it ain't islandora? (direct navigation to archive object page)
		/** @var SearchObject_Islandora $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init($searchSource);
		$searchObject->getNextPrevLinks();
		$logger->log("Setting search navigation for archive search", PEAR_LOG_DEBUG);
	}

	private function initializeExhibitContextDataFromCookie() {
		global $logger;
		$logger->log("Initializing exhibit context from Cookie Data", PEAR_LOG_DEBUG);
		$_SESSION['ExhibitContext']             = empty($_COOKIE['ExhibitContext'])             ? $_SESSION['ExhibitContext'] : $_COOKIE['ExhibitContext'];
		$_SESSION['exhibitSearchId']            = empty($_COOKIE['exhibitSearchId'])            ? $_SESSION['exhibitSearchId'] : $_COOKIE['exhibitSearchId'];
		$_SESSION['placePid']                   = empty($_COOKIE['placePid'])                   ? $_SESSION['placePid'] : $_COOKIE['placePid'];
		$_SESSION['placeLabel']                 = empty($_COOKIE['placeLabel'])                 ? $_SESSION['placeLabel'] : $_COOKIE['placeLabel'];
		$_SESSION['exhibitInAExhibitParentPid'] = empty($_COOKIE['exhibitInAExhibitParentPid']) ? $_SESSION['exhibitInAExhibitParentPid'] : $_COOKIE['exhibitInAExhibitParentPid'];
//		$_SESSION['dateFilter']      = null;

//		$_SESSION['ExhibitContext']             = empty($_COOKIE['ExhibitContext'])             ? null : $_COOKIE['ExhibitContext'];
//		$_SESSION['exhibitSearchId']            = empty($_COOKIE['exhibitSearchId'])            ? null : $_COOKIE['exhibitSearchId'];
//		$_SESSION['placePid']                   = empty($_COOKIE['placePid'])                   ? null : $_COOKIE['placePid'];
//		$_SESSION['placeLabel']                 = empty($_COOKIE['placeLabel'])                 ? null : $_COOKIE['placeLabel'];
//		$_SESSION['exhibitInAExhibitParentPid'] = empty($_COOKIE['exhibitInAExhibitParentPid']) ? null : $_COOKIE['exhibitInAExhibitParentPid'];
////		$_SESSION['dateFilter']      = null;
	}

	private function updateCookieForExhibitContextData() {
		global $logger;
		$logger->log("Initializing exhibit context from Cookie Data", PEAR_LOG_DEBUG);
		$_COOKIE['ExhibitContext']             = empty($_SESSION['ExhibitContext'])             ? null : $_SESSION['ExhibitContext'];
		$_COOKIE['exhibitSearchId']            = empty($_SESSION['exhibitSearchId'])            ? null : $_SESSION['exhibitSearchId'];
		$_COOKIE['placePid']                   = empty($_SESSION['placePid'])                   ? null : $_SESSION['placePid'];
		$_COOKIE['placeLabel']                 = empty($_SESSION['placeLabel'])                 ? null : $_SESSION['placeLabel'];
		$_COOKIE['exhibitInAExhibitParentPid'] = empty($_SESSION['exhibitInAExhibitParentPid']) ? null : $_SESSION['exhibitInAExhibitParentPid'];
//		$_SESSION['dateFilter']      = null;

		foreach ($_COOKIE as $cookieName => $cookieValue) {
			setcookie($cookieName, $cookieValue, time() + 3600);
		}
	}

	protected function archiveCollectionDisplayMode($displayMode = null) {
		if (empty($displayMode)) {
			global $library;
			if (!empty($_REQUEST['archiveCollectionView'])) {
				$displayMode = $_REQUEST['archiveCollectionView'];
			} elseif (!empty($_SESSION['archiveCollectionDisplayMode'])) {
				$displayMode = $_SESSION['archiveCollectionDisplayMode'];
			} elseif (!empty($library->defaultArchiveCollectionBrowseMode)) {
				$displayMode = $library->defaultArchiveCollectionBrowseMode;
			} else {
				$displayMode = 'covers'; // Pika default mode is covers
			}
		}

		$_SESSION['archiveCollectionDisplayMode'] = $displayMode;

		global $interface;
		$interface->assign('displayMode', $displayMode);
		return $displayMode;
	}

}