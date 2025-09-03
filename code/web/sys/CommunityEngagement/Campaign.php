<?php
/** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/CommunityEngagement/Milestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Reward.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignPatronTypeAccess.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignLibraryAccess.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignExtraCredit.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignLocationAccess.php';

class Campaign extends DataObject {
	public $__table = 'ce_campaign';
	public $id;
	public $name;
	public $description;
	public $milestones;
	public $startDate;
	public $endDate;
	public $enrollmentStartDate;
	public $enrollmentEndDate;
	public $enrollmentCounter;
	public $unenrollmentCounter;
	public $currentEnrollments;
	public $campaignReward;
	public $userAgeRange;
	public $extraCreditActivities;
	public $addExtraCreditActivities;

	/** @var CampaignMilestone[] */
	private $_availableMilestones;

	/** @var CampaignExtraCredit[] */
	private $_availableExtraCreditActivities;

	protected $_allowPatronTypeAccess;
	protected $_allowLibraryAccess;
	protected $_allowLocationAccess;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$milestoneList = Milestone::getMilestoneList();
		$milestoneStructure = CampaignMilestone::getObjectStructure($context);
		unset($milestoneStructure['campaignId']);
		unset($milestoneStructure['weight']);

		$extraCreditStructure = CampaignExtraCredit::getObjectStructure($context);
		unset($extraCreditStructure['campaignId']);
		unset($extraCreditStructure['weight']);

		$libraryList = Library::getLibraryList(false);
		$patronTypeList = PType::getPatronTypeList();
		$rewardList = Reward::getRewardList();
		$locationList = Location::getLocationList(false);
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'maxLength' => 50,
				'description' => 'A name for the campaign',
				'required' => true,
			],
			'description' => [
				'property' => 'description',
				'type' => 'translatableTextBlock',
				'label' => 'Description',
				'maxLength' => 255,
				'description' => 'A description of the campaign',
				'defaultTextFile' => 'Campaign_description.MD',
				'hideInLists'=> true,
			],
			'availableMilestones' => [
				'property' => 'availableMilestones',
				'type' => 'oneToMany',
				'label' => 'Milestones',
				'note' => 'Each milestone can only be used once per campaign',
				'renderAsHeading' => true,
				'description' => 'The Milestones to be linked to this campaign',
				'keyThis' => 'campaignId',
				'keyOther' => 'campaignId',
				'subObjectType' => 'CampaignMilestone',
				'structure' => $milestoneStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
			],
			'addExtraCreditActivities' => [
				'property' => 'addExtraCreditActivities',
				'type' => 'checkbox',
				'label' => 'Add Extra Credit Activities to the Campaign',
				'description' => 'Whether or not to add activities that earn rewards but do not count towards the completion of the campaign',
				'deafult' => false,
				'onchange' => 'AspenDiscovery.CommunityEngagement.displayExtraCreditBentoBox()',
			],
			'availableExtraCreditActivities' => [
				'property' => 'availableExtraCreditActivities',
				'type' => 'oneToMany',
				'label' => 'Extra Credit Activities',
				'renderAsHeading' => true,
				'description' => 'The Extra Credit Activities to be linked to this campaign',
				'keyThis' => 'campaignId',
				'keyOther' => 'campaignId',
				'subObjectType' => 'CampaignExtraCredit',
				'structure' => $extraCreditStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
			],
			'startDate' => [
				'property' => 'startDate',
				'type' => 'date',
				'label' => 'Campaign Start Date (campaigns with no start date will not be visible to patrons)',
				'description' => 'The date the campaign starts',
			],
			'endDate' => [
				'property' => 'endDate',
				'type' => 'date',
				'label' => 'Campaign End Date (campaigns with no end date will not be visible to patrons)',
				'description' => 'The date the campaign ends',
			],
			'campaignReward' => [
				'property' => 'campaignReward',
				'type' => 'enum',
				'label' => 'Reward for Completing Campaign',
				'values' => $rewardList, 
				'description' => 'The reward given for completing the campaign.',
				'required' => true,
			],
			'enrollmentStartDate' => [
				'property' => 'enrollmentStartDate',
				'type' => 'date',
				'label' => 'Enrollment Period Start Date',
				'description' => 'The date from which patrons can enroll in the campaign',
			],
			'enrollmentEndDate' => [
				'property' => 'enrollmentEndDate',
				'type' => 'date',
				'label' => 'Enrollment Period End Date',
				'description' => 'The date patrons can enroll in the campaign until',
			],
			'allowPatronTypeAccess' => [
				'property' => 'allowPatronTypeAccess',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Patron Type Access',
				'description' => 'Define what patron types should have access to this campaign',
				'values' => $patronTypeList,
				'hideInLists' => false,
			],
			'allowLibraryAccess' => [
				'property' => 'allowLibraryAccess',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Library Access',
				'description' => 'Define what libraries should have access to this campaign',
				'values' => $libraryList,
				'hideInLists' => false,
			],
			'allowLocationAccess' => [
				'property' => 'allowLocationAccess',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Location Access',
				'description' => 'Define what locations should have access to this campaign',
				'values' => $locationList,
				'hideInLists' => false,
			],
			'userAgeRange' => [
				'property' => 'userAgeRange',
				'type' => 'text',
				'label' => 'User Age Range ',
				'note' => 'Applies to Koha Only',
				'description' => 'Define the age range for this campaign e.g. &quot;14-18&quot;, &quot;14+&quot;, &quot;Over 14&quot;, &quot;Under 14&quot;, &quot;All Ages&quot;',
				'default' => 'All Ages',
				'maxLength' => 255,
				'hideInLists' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function getPatronTypeAccess() {
		if (!isset($this->_allowPatronTypeAccess) && $this->id) {
			$this->_allowPatronTypeAccess = [];
			$patronTypeLink = new CampaignPatronTypeAccess();
			$patronTypeLink->campaignId = $this->id;
			$patronTypeLink->find();
			while ($patronTypeLink->fetch()) {
				$this->_allowPatronTypeAccess[$patronTypeLink->patronTypeId] = $patronTypeLink->patronTypeId;
			}
		}
		return $this->_allowPatronTypeAccess;
	}

	public function getLibraryAccess(): ?array {
		if (!isset($this->_allowLibraryAccess) && $this->id) {
			$this->_allowLibraryAccess = [];
			$libraryLink = new CampaignLibraryAccess();
			$libraryLink->campaignId = $this->id;
			$libraryLink->find();
			while ($libraryLink->fetch()) {
				$this->_allowLibraryAccess[$libraryLink->libraryId] = $libraryLink->libraryId;
			}
		}
		return $this->_allowLibraryAccess;
	}

		public function getLocationAccess(): ?array {
		if (!isset($this->_allowLocationAccess) && $this->id) {
			$this->_allowLocationAccess = [];
			$locationLink = new CampaignLocationAccess();
			$locationLink->campaignId = $this->id;
			$locationLink->find();
			while ($locationLink->fetch()) {
				$this->_allowLocationAccess[$locationLink->locationId] = $locationLink->locationId;
			}
		}
		return $this->_allowLocationAccess;
	}

	public function savePatronTypeAccess() {
		if (isset($this->_allowPatronTypeAccess) && is_array($this->_allowPatronTypeAccess)) {
			$this->clearPatronTypeAccess();

			foreach ($this->_allowPatronTypeAccess as $patronTypeId) {
				$link = new CampaignPatronTypeAccess();
				$link->campaignId = $this->id;
				$link->patronTypeId = $patronTypeId;
				$link->insert();
			}
			unset($this->_allowPatronTypeAccess);
		}
	}

	public function saveLibraryAccess() {
		if (isset($this->_allowLibraryAccess) && is_array($this->_allowLibraryAccess)) {
			$this->clearLibraryAccess();

			foreach ($this->_allowLibraryAccess as $libraryId) {
				$libraryLink = new CampaignLibraryAccess();
				$libraryLink->campaignId = $this->id;
				$libraryLink->libraryId = $libraryId;
				$libraryLink->insert();
			}
			unset($this->_allowLibraryAccess);
		}
	}

	public function saveLocationAccess() {
		if (isset($this->_allowLocationAccess) && is_array($this->_allowLocationAccess)) {
			$this->clearLocationAccess();

			foreach ($this->_allowLocationAccess as $locationId) {
				$locationLink = new CampaignLocationAccess();
				$locationLink->campaignId = $this->id;
				$locationLink->locationId = $locationId;
				$locationLink->insert();
			}
			unset($this->_allowLocationAccess);
		}
	}

	private function clearPatronTypeAccess() {
		//Delete links to the patron types
		$link = new CampaignPatronTypeAccess();
		$link->campaignId = $this->id;
		return $link->delete(true);
	}

	private function clearLibraryAccess() {
		//Delete links to the libraries
		$libraryLink = new CampaignLibraryAccess();
		$libraryLink->campaignId = $this->id;
		return $libraryLink->delete(true);
	}

	private function clearLocationAccess() {
		//Delete links to the libraries
		$locationLink = new CampaignLocationAccess();
		$locationLink->campaignId = $this->id;
		return $locationLink->delete(true);
	}

	public function getUsers() {
		if (is_null($this->_users)) {
			$this->_users = [];

			require_once ROOT_DIR . '/sys/Account/User.php';

			if ($this->id) {
				$escapedId = $this->escape($this->id);
				$user = new User();
				$user->query("SELECT user.* FROM user INNER JOIN ce_user_campaign ON user.id = ce_user_campaign.userId WHERE ce_user_campaign.campaignId = $escapedId ORDER BY user.username");

				while($user->fetch()) {
					$this->_users[$user->id] = clone $user;
				}
			}
		}
		return $this->_users;
	}

	public function getUsersForCampaign() {
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
		$users = [];

		if ($this->id) {
			$userCampaign = new UserCampaign();

			$userCampaign->campaignId = $this->id;

			if ($userCampaign->find()) {
				while ($userCampaign->fetch()) {
					$user = new User();
					$user->id = $userCampaign->userId;
					if ($user->find(true)) {
						$users[] = clone $user;
					}
				}
			}
		}
		return $users;
	}

	public function __get($name) {
		if ($name == 'allowPatronTypeAccess') {
			return $this->getPatronTypeAccess();
		} else if ($name == 'allowLibraryAccess') {
			return $this->getLibraryAccess();
		} else if ($name == 'availableMilestones') {
			return $this->getMilestones();
		} else if ($name == 'availableExtraCreditActivities') {
			return $this->getExtraCreditActivities();
		} else if ($name == 'allowLocationAccess') {
			return $this->getLocationAccess();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'allowPatronTypeAccess') {
			$this->_allowPatronTypeAccess = $value;
		} else if ($name == 'allowLibraryAccess') {
			$this->_allowLibraryAccess = $value;
		} else if ($name == 'availableMilestones') {
			$this->_availableMilestones = $value;
		} else if ($name == 'availableExtraCreditActivities') {
			$this->_availableExtraCreditActivities = $value;
		} else if ($name == 'allowLocationAccess') {
			$this->_allowLocationAccess = $value;
		} else {
			parent::__set($name, $value);
		}
	}

 
	public function getMilestones(){
		if (!isset($this->_availableMilestones)) {
			$this->_availableMilestones = [];
			if (!empty($this->id)) {
				$campaignMilestone = new CampaignMilestone();
				$campaignMilestone->campaignId = $this->id;
				$campaignMilestone->orderBy('weight');
				if ($campaignMilestone->find()) {
					while ($campaignMilestone->fetch()) {
						$this->_availableMilestones[$campaignMilestone->id] = clone($campaignMilestone);
					}
				}
			}
		}
		return $this->_availableMilestones;
	}

	public function getExtraCreditActivities(){
		if (!isset($this->_availableExtraCreditActivities)) {
			$this->_availableExtraCreditActivities = [];
			if (!empty($this->id)) {
				$campaignExtraCredit = new CampaignExtraCredit();
				$campaignExtraCredit->campaignId = $this->id;
				$campaignExtraCredit->orderBy('weight');
				if ($campaignExtraCredit->find()) {
					while ($campaignExtraCredit->fetch()) {
						$this->_availableExtraCreditActivities[$campaignExtraCredit->id] = clone($campaignExtraCredit);
					}
				}
			}
		}
		return $this->_availableExtraCreditActivities;
	}

	public function getRewardDetails() {
		global $activeLanguageCode;

		$reward = new Reward();
		$reward->id = $this->campaignReward;
		if ($reward->find(true)) {
			return [
				'id' => $reward->id,
				'name' => $reward->name,
				'rewardType' => $reward->rewardType,
				'badgeImage' => $reward->getDisplayUrl(),
				'rewardExists' => !empty($reward->badgeImage),
				'displayName' => $reward->displayName,
				'awardAutomatically' =>$reward->awardAutomatically,
				'rewardDescription' => $reward->getTextBlockTranslation('description', $activeLanguageCode),
			];
		}
		return null;
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->savePatronTypeAccess();
			$this->saveLibraryAccess();
			$this->saveMilestones();
			$this->saveTextBlockTranslations('description');
			$this->saveExtraCreditActivities();
			$this->saveLocationAccess();
		}
		return $ret;
	}

	/**
	 * Override the insert functionality to save related objects
	 * 
	 * @see DB/DB_Data_Object::insert()
	 */
	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->savePatronTypeAccess();
			$this->saveLibraryAccess();
			$this->saveMilestones();
			$this->saveTextBlockTranslations('description');
			$this->saveExtraCreditActivities();
			$this->saveLocationAccess();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && !empty($this->id)) {
			$this->clearPatronTypeAccess();
			$this->clearLibraryAccess();
			$this->clearLocationAccess();
		}
		return $ret;
	}

	public static function getAllCampaigns() : array {
		$campaign = new Campaign();
		$campaignList = [];

		if ($campaign->find()) {
			while ($campaign->fetch()) {
				$campaignList[$campaign->id] = clone $campaign;
			}
		}
		return $campaignList;
	}

	public static function getAllCampaignsWithEnrolledUsers() {
		$campaign = new Campaign();
		$campaignList = [];
		$campaign->whereAdd("currentEnrollments > 0");
		if ($campaign->find()) {
			while ($campaign->fetch()) {
				$campaignList[$campaign->id] = clone $campaign;
			}
		}
		return $campaignList;
	}

	public static function getCampaignById($id) {
		$campaign = new Campaign();
		$campaign->whereAdd("id = '" .$id . "'");
		if ($campaign->find() && $campaign->fetch()) {
			return $campaign;
		}
		return null;
	}

	/**
	 * Finds and retrieves campaign records based on user access and login status.
	 *
	 * If the user is not logged in or is an Aspen admin user, it performs a standard find operation.
	 * Otherwise, it filters campaigns based on the user's patron type, ensuring that the user has
	 * access to the campaign. This function supports optional fetching of the first match and
	 * requires at least one match to return results if specified.
	 *
	 * @param bool $fetchFirst Optional. Whether to fetch the first matching record. Default is false.
	 * @param bool $requireOneMatchToReturn Optional. Whether one match is required to return results. Default is true.
	 * @return bool True if a record is found, false otherwise.
	 */
	public function find($fetchFirst = false, $requireOneMatchToReturn = true): bool {
		if (!UserAccount::isLoggedIn() || UserAccount::getActiveUserObj()->isAspenAdminUser() || UserAccount::getActiveUserObj()->isUserAdmin())
			 return parent::find($fetchFirst, $requireOneMatchToReturn);
		$this->applyUserFiltering(UserAccount::getActiveUserObj());
		return parent::find($fetchFirst, $requireOneMatchToReturn);
	}

	/**
	 * Retrieves a list of active campaigns.
	 *
	 * An active campaign is one that has started and not yet ended.
	 *
	 * @return array An associative array of active campaigns, where the keys
	 * are the campaign IDs and the values are the campaign names.
	 */
	public static function getActiveCampaignsList(): array
	{
		$campaign = new Campaign();
		$campaign->whereAdd("startDate <= '" . date("Y-m-d") . "'");
		$campaign->whereAdd("endDate >='" . date("Y-m-d") . "'");
		$campaignList = [];
		if ($campaign->find()) {
			while ($campaign->fetch()) {
				$campaignList[$campaign->id] = clone $campaign;
			}
		}
		return $campaignList;
	}

	public static function getUpcomingCampaigns():array {
		$campaign = new Campaign();

		//Work out the date one month from today
		$today = date("Y-m-d");
		$nextMonth = date("Y-m-d", strtotime("+1 month"));

		$campaign->whereAdd("startDate > '" . $today . "'");
		$campaign->whereAdd("startDate <= '" . $nextMonth . "'");

		$campaignList = [];
		if ($campaign->find()) {
			while ($campaign->fetch()) {
				$campaignList[$campaign->id] = $campaign;
			}
		}
		return $campaignList;
	}

	public static function getCampaignsEndingThisMonth(): array {
		$campaign = new Campaign();

		$startOfMonth = date('Y-m-01');
		$endOfMonth = date('Y-m-t'); //Last day of current month

		$campaign->whereAdd("endDate >= '$startOfMonth'");
		$campaign->whereAdd("endDate <= '$endOfMonth'");

		$campaignList = [];
		if ($campaign->find()) {
			while ($campaign->fetch()) {
				$campaignList[$campaign->id] = clone $campaign;
			}
		}
		return $campaignList;
	}

	public static function getMilestoneRewards(int $campaignId, int $userId): array {
		$milestoneRewards = [];
		$milestones = CampaignMilestone::getMilestoneByCampaign($campaignId);

		foreach ($milestones as $milestone) {
			$reward = new Reward();
			$reward->id = $milestone->rewardId;
			$rewardName = null;
			if ($reward->find(true)) {
				$rewardName = $reward->name;
			}

			$rewardGiven = 0;

			$userCampaign = new UserCampaign();
			$userCampaign->userId = $userId;
			$userCampaign->campaignId = $campaignId;

			if ($userCampaign->find(true)) {
				$rewardGiven = CampaignMilestoneUsersProgress::getRewardGivenForMilestone($milestone->id, $campaignId, $userId);
			}

			$milestoneRewards[$milestone->id] = [
				'name'=> $milestone->name,
				'rewardName' => $rewardName,
				'rewardGiven' => $rewardGiven
			];
		}
		return $milestoneRewards;
	}

	public static function getAllUsersInCampaigns(): array {
		require_once ROOT_DIR . '/sys/Account/User.php';
	
		$userCampaign = new UserCampaign();
		$userCampaign->selectAdd('userId');
		$userCampaign->groupBy('userId'); 
		$userCampaign->find();
	
		$userIds = [];
		while ($userCampaign->fetch()) {
			$userIds[] = $userCampaign->userId;
		}
	
		// Fetch user details for the unique user IDs
		$users = [];
		if (!empty($userIds)) {
			$userIdList = implode(',', array_map('intval', $userIds));
			$user = new User();
			$user->whereAdd("id IN ($userIdList)");
			if ($user->find()) {
				while ($user->fetch()) {
					$users[$user->id] = clone $user; // Or store as needed
				}
			}
		}
		return $users;
	}

	public static function getUserInfo($userId) {
		$user = new User();
		$user->whereAdd("id = $userId");
		if ($user->find() && $user->fetch()){
			return $user;
		}
		return null;
	}
	

	public function getPastCampaigns(int $userId): array {
		$campaign = new Campaign();
		$currentDate = date('Y-m-d H:i:s');
	
		$campaign->whereAdd("endDate < '$currentDate'");
		$pastCampaignList = [];
	
		if ($campaign->find()) {
			while ($campaign->fetch()) {
				$pastCampaignList[$campaign->id] = clone $campaign;
	
				// Fetch campaign reward
				$campaignReward = new Reward();
				$campaignReward->id = $campaign->campaignReward;
				if ($campaignReward->find(true)) {
					$pastCampaignList[$campaign->id]->rewardId = $campaignReward->id;
					$pastCampaignList[$campaign->id]->rewardName = $campaignReward->name;
					$pastCampaignList[$campaign->id]->displayName = $campaignReward->displayName;
					$pastCampaignList[$campaign->id]->rewardType = $campaignReward->rewardType;
					$pastCampaignList[$campaign->id]->rewardImage = $campaignReward->getDisplayUrl();
					$pastCampaignList[$campaign->id]->awardAutomatically = $campaignReward->awardAutomatically;
					if (!empty($campaignReward->badgeImage)) {
						$pastCampaignList[$campaign->id]->rewardExists = true;
					}
				}
	
				// Fetch campaign milestones and their rewards using mapping
				$milestones = CampaignMilestone::getMilestoneByCampaign($campaign->id);
				$pastCampaignList[$campaign->id]->milestones = $milestones;
				$extraCreditActivities = $this->getFormattedExtraCreditActivities($campaign->id, $userId);
				$pastCampaignList[$campaign->id]->extraCreditActivities = $extraCreditActivities;
				$pastCampaignList[$campaign->id]->numExtraCreditActivities = count($extraCreditActivities);


				usort($pastCampaignList[$campaign->id]->milestones, function($a, $b) {
					return $a->weight <=> $b->weight;
				});
	
				// Check if user is enrolled
				$pastCampaignList[$campaign->id]->enrolled = $campaign->isUserEnrolled($userId);
	
				// If user is enrolled, fetch their progress for each milestone
				if ($pastCampaignList[$campaign->id]->enrolled) {
					$userCampaign = new UserCampaign();
					$userCampaign->userId = $userId;
					$userCampaign->campaignId = $campaign->id;
	
					if ($userCampaign->find(true)) {
						$milestoneCompletionStatus = $userCampaign->checkMilestoneCompletionStatus();
						$pastCampaignList[$campaign->id]->campaignRewardGiven = (int)$userCampaign->rewardGiven;
						$pastCampaignList[$campaign->id]->isComplete = $userCampaign->checkCompletionStatus();

	
						// Update milestone details based on user progress
						foreach ($pastCampaignList[$campaign->id]->milestones as $milestone) {
							$milestoneProgress = CampaignMilestone::getMilestoneProgress($campaign->id, $userId, $milestone->id);
							$milestone->userProgress = CampaignMilestoneUsersProgress::getProgressByMilestoneId($milestone->id, $campaign->id, $userId);
							$milestone->isComplete = $milestoneCompletionStatus[$milestone->id] ?? false;
							$milestone->rewardGiven = CampaignMilestoneUsersProgress::getRewardGivenForMilestone($milestone->id, $userId, $campaign->id);
							$milestone->progress = $milestoneProgress['progress'];
							$milestone->extraProgress = $milestoneProgress['extraProgress'];
						}
					}
				}
			}
		}
		return $pastCampaignList;
	}
	


	

	public static function getUserEnrolledCampaigns($userId): array {
		$campaign = new Campaign();

		$campaign->joinAdd(new UserCampaign(), 'INNER', 'ce_user_campaign', 'id', 'campaignId');

		//Filter by the userId
		$campaign->whereAdd("ce_user_campaign.userId = " . $userId);

		$campaignList = [];
		if ($campaign->find()) {
			while ($campaign->fetch()) {
				$campaignList[] = clone $campaign;
			}
		}
		return $campaignList;
	}

	public function getCompletedUsersCount() {
		$userCampaign = new UserCampaign();
		$completedUsers = [];
		$userCampaign->whereAdd("campaignId = {$this->id}");
		if ($userCampaign->find()) {
			while($userCampaign->fetch()) {
				if ($userCampaign->checkCompletionStatus())	 {
					$completedUsers[$userCampaign->userId] = true;
				}			
			}
		}
		return count($completedUsers);
	}

	public function isUserEnrolled($userId) {
		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $this->id;

		return $userCampaign->find(true);
	}

	public function saveMilestones() {
		if (isset($this->_availableMilestones) && is_array($this->_availableMilestones)) {
			$seen = [];
			$filtered = [];
	
			foreach ($this->_availableMilestones as $milestoneData) {
				$milestoneId = $milestoneData->milestoneId ?? null;
	
				if ($milestoneId && !isset($seen[$milestoneId])) {
					$seen[$milestoneId] = true;
					$filtered[] = $milestoneData;
				}
			}
	
			$this->saveOneToManyOptions($filtered, 'campaignId');
			unset($this->_availableMilestones);

			$campaignMilestones = CampaignMilestone::getMilestoneByCampaign($this->id);

			$userCampaign = new UserCampaign();
			$userCampaign->campaignId = $this->id;
			$userCampaign->find();

			while ($userCampaign->fetch()) {
				$userId = $userCampaign->userId;

				foreach ($campaignMilestones as $campaignMilestone) {
					$progress = new CampaignMilestoneUsersProgress();
					$progress->ce_milestone_id = $campaignMilestone->milestoneId;
					$progress->ce_campaign_id = $this->id;
					$progress->userId = $userId;

					if (!$progress->find(true)) {
						$progress->progress = 0;
						$progress->insert();
					}
				}
			}

			require_once ROOT_DIR . '/services/MyAccount/AJAX.php';

			$ajax = new MyAccount_AJAX();

			$userCampaign = new UserCampaign();
			$userCampaign->campaignId = $this->id;
			$userCampaign->find();

			while ($userCampaign->fetch()) {
				$userId = $userCampaign->userId;
				$ajax->applyCampaignProgress($userId, $this->id);
				$userCampaign->checkAndHandleCampaignCompletion($userId, $this->id);
			}
		}
	}

	public function saveExtraCreditActivities() {
		if (isset($this->_availableExtraCreditActivities) && is_array($this->_availableExtraCreditActivities)) {
			$this->saveOneToManyOptions($this->_availableExtraCreditActivities, 'campaignId');
			unset($this->_availableExtraCreditActivities);
		}
	}
	
	 /**
	 * Return an overall leaderboard based on the number of milestones completed by each user across all campaigns.
	 * 
	 * @return array An array of users ranked by the number of completed milestones.
	 */
	public function getOverallLeaderboard() {
		$userCampaign = new UserCampaign();
		$users = $this->getAllUsersInCampaigns();
		global $logger;
		$logger->log($users, Logger::LOG_ERROR);
		$leaderboard = [];
		foreach ($users as $user) {
			if ($user->optInToAllCampaignLeaderboards == 0) {
				continue;
			}
			$totalCompletedMilestones = $userCampaign->calculateUserCompletedMilestones($user->id);
			$leaderboard[] = [
				'user' => $user->displayName,
				'completedMilestones' => $totalCompletedMilestones
			];
		}
		usort($leaderboard, function ($a, $b) {
			if ($b['completedMilestones'] !== $a['completedMilestones']) {
				return $b['completedMilestones'] <=> $a['completedMilestones'];
			}
			return strcasecmp($a['user'], $b['user']);
		});
		$currentRank = 1;
		$previousRankValue = null;
		foreach ($leaderboard as $index => $entry) {
			if ($entry['completedMilestones'] === 0) {
				$leaderboard[$index]['rankDisplayed'] = '-';
				continue;
			}
			if ($entry['completedMilestones'] !== $previousRankValue) {
				$currentRank = $index + 1;
				$previousRankValue = $entry['completedMilestones'];
			}
			$leaderboard[$index]['rankDisplayed'] = $this->getRankDisplayed($currentRank);
		}
		return $leaderboard;
		
	}
	/**
	 * Return a leaderboard for each individual campaign based on the number of milestones completed by the user.
	 * 
	 * @param int $campaignId The ID of the campaign for which to fetch the leaderboard.
	 * @return array An array of users ranked by the number of completed milestones.
	 *
	 */
	public function getLeaderboardByCampaign($campaignId) {
		$userCampaign = new UserCampaign();
		$leaderboard = [];
		$userCampaignRecords = [];
		if (!$campaignId) {
			return [];
		}

		$userCampaign->whereAdd("campaignId = '$campaignId'");
		$userCampaign->find();
		while ($userCampaign->fetch()) {
			$userCampaignRecords[] = clone $userCampaign;
		}

		foreach ($userCampaignRecords as $userCampaignRecord) {
		$user = new User();
		$user->id = $userCampaignRecord->userId;
		if (!$user->find(true)) {
			continue;
		}

		if ($userCampaignRecord->optInToCampaignLeaderboard === 0 ||($userCampaignRecord->optInToCampaignLeaderboard === null && $user->optInToAllCampaignLeaderboards === 0)) {
			continue;
		}
			$milestoneCompletionStatus = $userCampaignRecord->checkMilestoneCompletionStatus();
			$completedMilestones = count(array_filter($milestoneCompletionStatus, function($status) {
				return $status === true;
			}));
			$leaderboard[] = [
				'user' => $user->displayName,
				'completedMilestones' => $completedMilestones,
			];
		}
		usort($leaderboard, function($a, $b) {
		if ($b['completedMilestones'] !== $a['completedMilestones']) {
			return $b['completedMilestones']<=> $a['completedMilestones'];
		}
		return $a['user']<=> $b['user'];
		});
		//Add displayed rank after sorting, skip users with 0 completed milestones
		$currentRank = 1;
		$previousRankValue = null;
		foreach ($leaderboard as $index =>$entry) {
			if ($entry['completedMilestones'] === 0) {
				$leaderboard[$index]['rankDisplayed'] = '-';
				continue;
			}
			if ($entry['completedMilestones'] !== $previousRankValue) {
				$currentRank = $index + 1;
				$previousRankValue = $entry['completedMilestones'];
			}
			$leaderboard[$index]['rankDisplayed'] = $this->getRankDisplayed($currentRank);
		}
		return $leaderboard;
	}
	
	private function getRankDisplayed($completedMilestones) {
		$suffix = 'th';
		if ($completedMilestones % 10 == 1 && $completedMilestones % 100 != 11) {
			$suffix= 'st';
		} elseif ($completedMilestones % 10 == 2 && $completedMilestones % 100 != 12) {
			$suffix = 'nd';
		} elseif ($completedMilestones % 10 == 3 && $completedMilestones % 100 != 13) {
			$suffix = 'rd';
		}
		return $completedMilestones . $suffix;
	}

	 /**
	 * Return an overall leaderboard based on the number of milestones completed by each branch across all campaigns.
	 * 
	 * @return array An array of branches ranked by the number of completed milestones.
	 */
	public function getOverallLeaderboardByBranch() {
		$userCampaign = new UserCampaign();
		$user = UserAccount::getActiveUserObj();
		$users = $this->getAllUsersInCampaigns();
		$branchMilestones = [];

		foreach ($users as $user) {
			$totalCompletedMilestones = $userCampaign->calculateUserCompletedMilestones($user->id);
			$branch = $user->getHomeLocationName();

			if (!isset($branchMilestones[$branch])) {
				$branchMilestones[$branch] = 0;
			}
			$branchMilestones[$branch] += $totalCompletedMilestones;
		}

		$leaderboard = [];
		foreach ($branchMilestones as $branch => $totalMilestones) {
			$leaderboard[] = [
				'branch' =>$branch,
				'completedMilestones' => $totalMilestones
			];
		}

		usort($leaderboard, function ($a, $b) {
			if ($b['completedMilestones'] !== $a['completedMilestones']) {
				return $b['completedMilestones'] <=> $a['completedMilestones'];
			}
			return strcasecmp($a['branch'], $b['branch']);
		});

		$currentRank = 1;
		$previousRankValue = null;
		foreach ($leaderboard as $index => $entry) {
			if ($entry['completedMilestones'] === 0) {
				$leaderboard[$index]['rankDisplayed'] = '-';
				continue;
			}
			if ($entry['completedMilestones'] !== $previousRankValue) {
				$currentRank = $index + 1;
				$previousRankValue = $entry['completedMilestones'];
			}
			$leaderboard[$index]['rankDisplayed'] = $this->getRankDisplayed($currentRank);
		}
		return $leaderboard;
	}

	/**
	 * Return a leaderboard for each individual campaign based on the number of milestones completed by the branch.
	 * 
	 * @param int $campaignId The ID of the campaign for which to fetch the leaderboard.
	 * @return array An array of users branches ranked by the number of completed milestones.
	 *
	 */
	public function getLeaderboardByBranchForCampaign($campaignId) {
		if (!$campaignId) {
			return [];
		}

		$userCampaign = new UserCampaign();
		$branchLeaderboard = [];
		$userCampaignRecords = [];

		$userCampaign->whereAdd("campaignId = '$campaignId'");
		$userCampaign->find();

		while ($userCampaign->fetch()) {
			$userCampaignRecords[] = clone $userCampaign;
		}

		foreach ($userCampaignRecords as $userCampaignRecord) {
			$milestoneCompletionStatus = $userCampaignRecord->checkMilestoneCompletionStatus();
			$userId = $userCampaignRecord->userId;

			$user = new User();
			$user->id = $userId;
			if (!$user->find(true)) {
				continue;
			}

			$branch = $user->getHomeLocationName();
			$completedMilestones = count(array_filter($milestoneCompletionStatus, function ($status) {
				return $status === true;
			}));

			if (!isset($branchLeaderboard[$branch])) {
				$branchLeaderboard[$branch] = 0;
			}

			$branchLeaderboard[$branch] += $completedMilestones;
		}
		$leaderboard = [];
		foreach ($branchLeaderboard as $branch => $completedMilestones) {
			$leaderboard[] = [
				'branch' => $branch,
				'completedMilestones' => $completedMilestones
			];
		}

		usort($leaderboard, function ($a, $b) {
			if ($b['completedMilestones'] !== $a['completedMilestones']) {
				return $b['completedMilestones'] <=> $a['completedMilestones'];
			}
			return strcasecmp($a['branch'], $b['branch']);
		});

		$currentRank = 1;
		$previousRankValue = null;
		foreach ($leaderboard as $index => $entry) {
			if ($entry['completedMilestones'] === 0) {
				$leaderboard[$index]['rankDisplayed'] = '-';
				continue;
			}
			if ($entry['completedMilestones'] !== $previousRankValue) {
				$currentRank = $index + 1;
				$previousRankValue = $entry['completedMilestones'];
			}
			$leaderboard[$index]['rankDisplayed'] = $this->getRankDisplayed($currentRank);
		}
		return $leaderboard;
	}

	function getCampaigns($userId = null, $applyUserFiltering = false) {
		global $activeLanguage;

		$campaign = new Campaign();
		$campaignList = [];

		if ($userId === null) {
			if (!UserAccount::isLoggedIn()) {
				return $campaignList;
			}
			$user = UserAccount::getLoggedInUser();
			$userId = $user->id;
		} else {
			$user = new User();
			$user->id = $userId;
			if (!$user->find(true)) {
				return $campaignList;
			}
		}

		if ($applyUserFiltering) {
			$campaign->applyUserFiltering($user);
		}

		//Get active campaigns
		$activeCampaigns = Campaign::getActiveCampaignsList();

		//Get upcoming campaigns - those starting in the next month
		$upcomingCampaigns = Campaign::getUpcomingCampaigns();

		//Get campaigns
		$campaign->find();
		while ($campaign->fetch()) {
			$campaignId = $campaign->id;

			//Find out if user is enrolled in campaign
			$campaign->enrolled = $campaign->isUserEnrolled($userId);
			//Find out if campaign is active
			$campaign->isActive = isset($activeCampaigns[$campaignId]);

			//Find out if campaign in upcoming
			$campaign->isUpcoming = isset($upcomingCampaigns[$campaignId]);
			//Get campaign reward name
			$rewardDetails = $campaign->getRewardDetails();
			if ($rewardDetails) {
				$campaign->rewardName = $rewardDetails['name'];
				$campaign->rewardId = $rewardDetails['id'];
				$campaign->rewardType = $rewardDetails['rewardType'];
				$campaign->badgeImage = $rewardDetails['badgeImage'];
				$campaign->rewardExists = $rewardDetails['rewardExists'];
				$campaign->displayName = $rewardDetails['displayName'];
				$campaign->awardAutomatically = $rewardDetails['awardAutomatically'];
				$campaign->rewardDescription = $rewardDetails['rewardDescription'];
			}

				//Fetch milestones for this campaign
				$milestones = CampaignMilestone::getMilestoneByCampaign($campaignId);
				$completedMilestonesCount = 0;
				$numCampaignMilestones = 0;
				$milestoneProgressData = [];

				//Store progress for each milestone
				$campaign->milestoneProgress = [];


				foreach ($milestones as $milestone) {
					$milestoneId = $milestone->id;
					$numCampaignMilestones++;

					//Calculate milestone progress
					$milestoneProgress = CampaignMilestone::getMilestoneProgress($campaignId, $userId, $milestone->id);
					$progressData = CampaignMilestoneProgressEntry::getUserProgressDataByMilestoneId($userId, $milestoneId, $campaignId);
					require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
					require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
					foreach ($progressData as &$entry) {
					if (empty($entry['title']) && !empty($entry['groupedRecordPermanentId'])) {
							$driver = new GroupedWorkDriver($entry['groupedRecordPermanentId']);
							if ($driver && $driver->isValid()) {
								$entry['title'] = $driver->getTitle();

								$review = new UserWorkReview();
								$review->groupedRecordPermanentId = $entry['groupedRecordPermanentId'];
								$review->userId = $entry['userId'];
								if ($review->find(true)) {
									$review->title = $entry['title'];
									$review->update();
								}
							}
						}
					}
					unset($entry);

					usort($progressData, function ($a, $b) {
						$aDate = $a['checkoutDate'] ?? '';
						$bDate = $b['checkoutDate'] ?? '';
						return $aDate <=> $bDate;
					});
					$milestone->progress = $milestoneProgress['progress'];
					$milestone->extraProgress = $milestoneProgress['extraProgress'];
					$milestone->completedGoals = $milestoneProgress['completed'];
					$milestone->totalGoals = CampaignMilestone::getMilestoneGoalCountByCampaign($campaignId, $milestoneId);
					$milestone->progressData = $progressData;
					$milestone->rewardGiven = CampaignMilestoneUsersProgress::getRewardGivenForMilestone($milestone->id, $userId, $campaign->id);


					if ($milestone->completedGoals >= $milestone->totalGoals) {
						$milestone->milestoneComplete = true;
					} else {
						$milestone->milestoneComplete = false;
					}
				}
				usort($milestones, function($a, $b) {
					return $a->weight <=> $b->weight;
				});
				//Add completed milestones count to campaign object
				// $campaign->numCompletedMilestones = $completedMilestonesCount;
				$campaign->numCampaignMilestones = $numCampaignMilestones;
				$extraCreditActivities = $this->getFormattedExtraCreditActivities($campaign->id, $userId);

				$campaign->numExtraCreditActivities = count($extraCreditActivities);

				$currentDate = date('Y-m-d');
				$canEnroll = (
					(!$campaign->enrollmentStartDate || $currentDate >= $campaign->enrollmentStartDate) &&
					(!$campaign->enrollmentEndDate || $currentDate <= $campaign->enrollmentEndDate) &&
					($currentDate <= $campaign->endDate)
				);
				$campaign->canEnroll = $canEnroll;
				$userCampaign = new UserCampaign();
				$userCampaign->userId = $userId;
				$userCampaign->campaignId = $campaignId;
				$userCampaign->find();
				while($userCampaign->fetch()) {
					$campaign->campaignRewardGiven = (int)$userCampaign->rewardGiven;
					$campaign->isComplete = $userCampaign->checkCompletionStatus();
					if ($userCampaign->optInToCampaignLeaderboard === null) {
						$campaign->optInToCampaignLeaderboard = $user->optInToAllCampaignLeaderboards;
					}else{
						$campaign->optInToCampaignLeaderboard = $userCampaign->optInToCampaignLeaderboard;
					}
					if ($userCampaign->optInToCampaignEmailNotifications == null) {
						$campaign->optInToCampaignEmailNotifications = $user->campaignNotificationsByEmail;
					} else {
						$campaign->optInToCampaignEmailNotifications = $userCampaign->optInToCampaignEmailNotifications;
					}
				}
				$milestoneCompletionStatus = $userCampaign->checkMilestoneCompletionStatus();
				$campaign->numCompletedMilestones = count(array_filter($milestoneCompletionStatus));

				//Add milestones to campaign object
				$campaign->milestones = $milestones;
				$campaign->extraCreditActivities = $extraCreditActivities;
				$campaign->translatedDescription = $campaign->getTextBlockTranslation('description', $activeLanguage->code);

				//Add the campaign to the list
			$campaignList[] = clone $campaign;
		}
		return $campaignList;
	}

	function getLinkedUserCampaigns($userId) {
		if (empty($userId)){
			throw new InvalidArgumentException("User ID is required");
		}
		$user = new User();
		$user->id = $userId;

		if (!$user->find(true)) {
			throw new RuntimeException("User not found.");
		}

		$linkedUsers = $user->getLinkedUsers();
		if (empty($linkedUsers)) {
			return [];
		}

		$groupedLinkedCampaigns = [];

		foreach ($linkedUsers as $linkedUser) {
			$eligibleCampaigns = [];
			$campaign = new Campaign();

			if ($campaign->find()) {
				while ($campaign->fetch()) {
					if (empty($campaign->startDate) || empty($campaign->endDate)) {
						continue;
					}
					$userCampaign = new UserCampaign();
					$userCampaign->userId = $linkedUser->id;
					$userCampaign->campaignId = $campaign->id;
	
					$isEnrolled = $userCampaign->find(true);
					$rewardGiven = (int)$userCampaign->rewardGiven;
					$campaignReward = null;
					$rewardDetails = $campaign->getRewardDetails();
					if ($rewardDetails != null) {
						$campaignReward = [
							'rewardName' => $rewardDetails['name'],
							'rewardType' => $rewardDetails['rewardType'], 
							'badgeImage' => $rewardDetails['badgeImage'],
							'rewardExists' => $rewardDetails['rewardExists'],
							'displayName' => $rewardDetails['displayName'],
							'rewardDescription' => $rewardDetails['rewardDescription'],
							'awardAutomatically' =>$rewardDetails['awardAutomatically'],

						];
					}

					$startDate = $campaign->startDate;
					$endDate = $campaign->endDate;

					$currentDate = date('Y-m-d');
					$canEnroll = (
						(!$campaign->enrollmentStartDate || $currentDate >= $campaign->enrollmentStartDate) &&
						(!$campaign->enrollmentEndDate || $currentDate <= $campaign->enrollmentEndDate)
					);
					$milestones = CampaignMilestone::getMilestoneByCampaign($campaign->id);
					$numCampaignMilestones = count($milestones);
					$numCompletedMilestones = 0;
					$milestoneRewards = [];

					foreach ($milestones as $milestone) {
						$milestoneProgress = CampaignMilestone::getMilestoneProgress($campaign->id, $linkedUser->id, $milestone->id);
						$completedGoals = $milestoneProgress['completed'];
						$totalGoals = CampaignMilestone::getMilestoneGoalCountByCampaign($campaign->id, $milestone->id);
						$progressData = CampaignMilestoneProgressEntry::getUserProgressDataByMilestoneId($linkedUser->id, $milestone->id, $campaign->id);
						require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
						require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
						foreach ($progressData as &$entry) {
							if (empty($entry['title']) && !empty($entry['groupedRecordPermanentId'])) {
								$driver = new GroupedWorkDriver($entry['groupedRecordPermanentId']);
								if ($driver && $driver->isValid()) {
									$entry['title'] = $driver->getTitle();

									$review = new UserWorkReview();
									$review->groupedRecordPermanentId = $entry['groupedRecordPermanentId'];
									$review->userId = $entry['userId'];
									if ($review->find(true)) {
										$review->title = $entry['title'];
										$review->update();
									}
								}
							}
						}
						unset($entry);

						usort($progressData, function ($a, $b) {
							$aDate = $a['checkoutDate'] ?? '';
							$bDate = $b['checkoutDate'] ?? '';
							return $aDate <=> $bDate;
						});

						$milestoneRewardGiven = CampaignMilestoneUsersProgress::getRewardGivenForMilestone($milestone->id, $linkedUser->id, $campaign->id);

						if ($milestoneProgress['progress'] >= 100) {
							$numCompletedMilestones++;
						}


						$milestoneRewards[] = [
							'id' => $milestone->id,
							'weight' => $milestone->weight,
							'milestoneName' => $milestone->name,
							'rewardName' => $milestone->rewardName, 
							'rewardType' => $milestone->rewardType, 
							'awardAutomatically' => $milestone->awardAutomatically, 
							'displayName' => $milestone->displayName,
							'badgeImage' => $milestone->rewardImage,
							'rewardExists' => $milestone->rewardExists,
							'rewardDescription' => $milestone->rewardDescription,
							'progress' => $milestoneProgress['progress'],
							'extraProgress' => $milestoneProgress['extraProgress'],
							'completedGoals' => $completedGoals,
							'totalGoals' => $totalGoals,
							'progressData' => $progressData,
							// 'progressData' => $milestoneProgress['data'],
							'progressBeyondOneHundredPercent' => $milestone->progressBeyondOneHundredPercent,
							'allowPatronProgressInput' => $milestone->allowPatronProgressInput,
							'milestoneComplete' => ($completedGoals >= $totalGoals),
							'rewardGiven' => $milestoneRewardGiven,
						];
					}
					usort($milestoneRewards, function($a, $b) {
						return $a['weight'] <=> $b['weight'];
					});

					$extraCreditActivities = $this->getFormattedExtraCreditActivities($campaign->id, $linkedUser->id);

					$eligibleCampaigns[] = [
						'campaignId' => $campaign->id,
						'campaignName' => $campaign->name,
						'isEnrolled' => $isEnrolled,
						'campaignReward' => $campaignReward,
						'milestones' => $milestoneRewards,
						'numCompletedMilestones' => $numCompletedMilestones,
						'numCampaignMilestones' => $numCampaignMilestones,
						'startDate' => $startDate,
						'endDate' => $endDate,
						'canEnroll' => $canEnroll,
						'isComplete' => $numCompletedMilestones >= $numCampaignMilestones,
						'rewardGiven' => $rewardGiven,
						'extraCreditActivities' => $extraCreditActivities,
						'numExtraCreditActivities' => count($extraCreditActivities)
					];
				}
			}

			$groupedLinkedCampaigns[] = [
				'linkedUserName' => $linkedUser->displayName, 
				'linkedUserId' => $linkedUser->id,
				'campaigns' => $eligibleCampaigns
			];
		}
		return $groupedLinkedCampaigns;
	}

	public function getCampaignEmailParameters($user, $campaignId) {
		require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';

		$campaign = new Campaign();
		$campaign->id = $campaignId;
		if (!$campaign->find(true)) {
			return [];
		}

		$rewardDetails = $campaign->getRewardDetails();
		$campaignReward = $rewardDetails['name'] ?? 'None';

		$milestones = CampaignMilestone::getMilestoneByCampaign($campaignId);
		$milestoneSummaryLines = [];

		foreach ($milestones as $milestone) {
			$name = $milestone->name;
			$reward = $milestone->rewardName ?? 'None';
			$milestoneSummaryLines[] = "$name -> $reward";
		}

		$milestoneSummary = implode("\n", $milestoneSummaryLines);

		return [
			'user' => $user,
			'campaignName' => $campaign->name,
			'library' => $user->getHomeLibrary(),
			'campaignReward' => $campaignReward,
			'milestoneSummary' => $milestoneSummary,
		];
	}

	private function applyUserFiltering($user) {
		$this->joinAdd(new CampaignPatronTypeAccess(), 'LEFT', 'ce_campaign_patron_type_access', 'id', 'campaignId');
		$this->whereAdd("ce_campaign_patron_type_access.patronTypeId = '" . $user->getPTypeObj()->id . "' OR ce_campaign_patron_type_access.patronTypeId IS NULL");
		
		$this->joinAdd(new CampaignLibraryAccess(), 'LEFT', 'ce_campaign_library_access', 'id', 'campaignId');
		$this->whereAdd("ce_campaign_library_access.libraryId = '" . $user->getHomeLibrary()->libraryId . "' OR NOT EXISTS (SELECT 1 FROM ce_campaign_library_access WHERE ce_campaign_library_access.campaignId = ce_campaign.id)");

		$homeLocation = UserAccount::getActiveUserObj()->getHomeLocation();
		if ($homeLocation != null) {
			$this->joinAdd(new CampaignLocationAccess(), 'LEFT', 'ce_campaign_location_access', 'id', 'campaignId');
			$this->whereAdd("ce_campaign_location_access.locationId = '{$homeLocation->locationId}' OR NOT EXISTS (SELECT 1 FROM ce_campaign_location_access WHERE ce_campaign_location_access.campaignId = ce_campaign.id)");
		}
		
		$userAge = (int)$user->getAge();
		$ageCondition = "(
			userAgeRange IS NULL OR
			userAgeRange = '' OR
			userAgeRange = 'All Ages' OR
			(userAgeRange LIKE 'Under %' AND $userAge < CAST(SUBSTRING_INDEX(userAgeRange, ' ', -1) AS UNSIGNED)) OR
			(userAgeRange LIKE 'Over %' AND $userAge > CAST(SUBSTRING_INDEX(userAgeRange, ' ', -1) AS UNSIGNED)) OR
			(userAgeRange LIKE '%+' AND $userAge >= CAST(LEFT(userAgeRange, LOCATE('+', userAgeRange) -1) AS UNSIGNED)) OR
			(userAgeRange LIKE '%-%' AND $userAge BETWEEN
				CAST(LEFT(userAgeRange, LOCATE('-', userAgeRange) -1) AS UNSIGNED) AND
				CAST(SUBSTRING_INDEX(userAgeRange, '-', -1) AS UNSIGNED)
			)
		)";
		$this->whereAdd($ageCondition);
	}

	private function formatExtraCreditActivity($extraCreditActivity, int $userId, int $campaignId): array {
		$progressData = CampaignExtraCredit::getExtraCreditActivityProgress($campaignId, $userId, $extraCreditActivity->id);
		$totalGoals = CampaignExtraCredit::getExtraCreditGoalCountByCampaign($campaignId, $extraCreditActivity->id);
		$completedGoals = $progressData['completed'] ?? 0;
		$progress = $progressData['progress'] ?? 0;

		return [
			'type' => 'activity',
			'id' => $extraCreditActivity->id,
			'name' => $extraCreditActivity->name,
			'displayName' => $extraCreditActivity->displayName,
			'rewardName' => $extraCreditActivity->rewardName,
			'rewardId' => $extraCreditActivity->rewardId,
			'rewardType' => $extraCreditActivity->rewardType,
			'rewardImage' => $extraCreditActivity->rewardImage,
			'rewardExists' => $extraCreditActivity->rewardExists,
			'awardAutomatically' => $extraCreditActivity->awardAutomatically,
			'progress' => $progress,
			'completedGoals' => $completedGoals,
			'totalGoals' => $totalGoals,
			'isComplete' => $progress >= 100,
			'allowPatronProgressInput' => $extraCreditActivity->allowPatronProgressInput,
			'rewardGiven' => CampaignExtraCreditActivityUsersProgress::getRewardGivenForExtraCreditActivity(
				$extraCreditActivity->id,
				$userId,
				$campaignId
			),
		];
	}

	private function getFormattedExtraCreditActivities(int $campaignId, int $userId): array {
		$rawExtraCreditActivities = CampaignExtraCredit::getExtraCreditByCampaign($campaignId);
		$formatted = [];

		foreach ($rawExtraCreditActivities as $extraCreditActivity) {
			$formatted[] = $this->formatExtraCreditActivity($extraCreditActivity, $userId, $campaignId);
		}

		return $formatted;
	}

	public static function filterByCanEnroll(array $campaigns, int $userId): array {
		$filteredCampaigns = [];
		$currentDate = date('Y-m-d');

		foreach ($campaigns as $campaign) {
			if ($campaign->endDate && $campaign->endDate < $currentDate) {
				continue;
			}

			if (!$campaign->isActive && !$campaign->isUpcoming) {
				continue;
			}

			if ($campaign->isUserEnrolled($userId)) {
				continue;
			}

			$canEnroll = 
				(!$campaign->enrollmentStartDate && !$campaign->enrollmentEndDate) || (!$campaign->enrollmentStartDate && $campaign->enrollmentEndDate >= $currentDate) || ($campaign->enrollmentStartDate <= $currentDate && !$campaign->enrollmentEndDate) || ($campaign->enrollmentStartDate <= $currentDate && $campaign->enrollmentEndDate >= $currentDate);

				if ($canEnroll) {
					$filteredCampaigns[] = $campaign;
				}
		}
		return $filteredCampaigns;
	}

	public static function getImageDisplaySettings($user, $library) {
		global $logger;
		$homeLibrary = $user->getHomeLibrary();
		if (!empty($homeLibrary)) {

			return [
				'displayPlaceholderImage' => $homeLibrary->displayDigitalRewardOnlyWhenAwarded,
				'placeholderImage' => $homeLibrary->digitalRewardPlaceholderImage
			];
		} else {
			return [
				'displayPlaceholderImage' => $library->displayDigitalRewardOnlyWhenAwarded,
				'placeholderImage' => $library->digitalRewardPlaceholderImage
			];
		}
	}

	public static function setDisplayImageForArray(&$item, $settings, $rewardGiven, $awardAutomatically, $isComplete) {
		$itemType = isset($item['campaignId']) ? 'CAMPAIGN' : 
			(isset($item['id']) && isset($item['type']) && $item['type'] === 'milestone' ? 'MILESTONE' :
			(isset($item['id']) && isset($item['type']) && $item['type'] === 'activity' ? 'EXTRACREDIT' : 'UNKNOWN'));
		$itemId = $item['campaignId'] ?? $item['id'] ?? 'NO_ID';

		$rewardGivenBool = (bool)$rewardGiven;
		$awardAutomaticallyBool = (bool)$awardAutomatically;
		$isCompleteBool = (bool)$isComplete;

		$condition1 = !$settings['displayPlaceholderImage'];
		$condition2 = $rewardGivenBool;
		$condition3 = ($awardAutomaticallyBool && $isCompleteBool);


		$shouldShowActual = $condition1 || $condition2 || $condition3;


		if (!$shouldShowActual) {
			if ($settings['placeholderImage']) {
				$item['badgeImage'] = '/files/original/' . $settings['placeholderImage'];
				$item['useTplPlaceholder'] = false;
			} else {
				$item['badgeImage'] = '';
				$item['useTplPlaceholder'] = true;
			}
			$item['isPlaceholderImage'] = true;
		} else {
			$item['isPlaceholderImage'] = false;
			$item['useTplPlaceholder'] = false;
		}
	}
}
