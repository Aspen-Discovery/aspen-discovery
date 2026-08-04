<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Milestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneUsersProgress.php';

class UserCampaign extends DataObject {
	public $__table = 'ce_user_campaign';
	public $id;
	public $userId;
	public $campaignId;
	public $enrollmentDate;
	public $unenerollmentDate;
	public $completed;
	public $rewardGiven;
	public $optInToCampaignLeaderboard;
	public $optInToCampaignEmailNotifications;
	public $campaignCompleteEmailSent;
	public $staffCampaignCompleteEmailSent;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'userId' => [
				'property' => 'userId',
				'type' => 'label',
				'label' => 'User Id',
				'description' => 'The unique id of the user',
			],
			'campaignId' => [
				'property' => 'campaignId',
				'type' => 'label',
				'label' => 'Campaign Id',
				'description' => 'The unique id of the campaign',
			],
			'enrollmentDate' => [
				'property' => 'enrollmentDate',
				'type' => 'date',
				'label' => 'Enrollment Date',
				'description' => 'The Date of Enrollment',
			],
			'unenrollmentDate' => [
				'property' => 'unenrollmentDate',
				'type' => 'date',
				'label' => 'Unenrollment Date',
				'description' => 'The Date of Unenrollment',
			],
			'completed' => [
				'property' => 'completed',
				'type' => 'checkbox',
				'label' => 'Campaign Complete',
				'description' => 'Whether or not the campaign is complete',
				'default' => false,
			],
			'rewardGiven' => [
				'property' => 'rewardGiven',
				'type' => 'checkbox',
				'label' => 'Reward Given',
				'description' => 'Whether or not the reward for completing the campaign has been given',
				'default' => false,
			],
			'optInToCampaignLeaderboard' => [
				'property' => 'optInToCampaignLeaderboard',
				'type' => 'checkbox',
				'label' => 'Opt In To Campaign Leaderboard',
				'description' => 'Whether or not to opt into the being displayed on the leaderboard for this campaign',
			],
			'optInToCampaignEmailNotifications' => [
				'property' => 'optInToCampaignEmailNotifications',
				'type' => 'checkbox',
				'label' => 'Opt In To Campaign Notification Emails',
				'description' => 'Whether or not to opt in to email notifications for this campaign',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	//Check if the user has completed the campaign
	public function checkCompletionStatus() {
		//Get campaign milestones for campaign
		$campaignMilestones = CampaignMilestone::getCampaignMilestoneByCampaign($this->campaignId);
		$isComplete = true;

		foreach ($campaignMilestones as $campaignMilestone) {
			$userProgress = CampaignMilestoneUsersProgress::getProgressByCampaignMilestoneId($campaignMilestone->id, $this->userId);
			$goal = CampaignMilestone::getCampaignMilestoneGoalCountByCampaign($campaignMilestone->id);
			if ($userProgress < $goal) {
				$isComplete = false;
				break;
			}
		}
		return $isComplete;
	}

	public function checkCampaignMilestoneCompletionStatus() {
		$campaignMilestones = CampaignMilestone::getCampaignMilestoneByCampaign($this->campaignId);
		$campaignMilestoneCompletionStatus = [];

		foreach ($campaignMilestones as $campaignMilestone) {
			//User's progress for this campaign milestone
			$userProgress = CampaignMilestoneUsersProgress::getProgressByCampaignMilestoneId($campaignMilestone->id, $this->userId);

			//Goal for this campaign milestone
			$goal = CampaignMilestone::getCampaignMilestoneGoalCountByCampaign($campaignMilestone->id);
			//Check if campaign milestone is complete
			$isCampaignMilestoneComplete = ($userProgress >= $goal);

			//Add to array
			$campaignMilestoneCompletionStatus[$campaignMilestone->id] = $isCampaignMilestoneComplete;
		}

		return $campaignMilestoneCompletionStatus;
	}

	 /**
	 * Calculate the total number of completed campaign milestones for a user
	 * @param int $userId
	 * @return int
	 */
	public function calculateUserCompletedCampaignMilestones($userId) {
		$userCampaign = new UserCampaign();
		$userEnrolledCampaigns = [];
		$userCampaign->whereAdd("userId = '$userId'");
		$userCampaign->find();
		while ($userCampaign->fetch()) {
			$userEnrolledCampaigns[] = clone $userCampaign;
		}
		$totalCompletedCampaignMilestones = 0;
		foreach ($userEnrolledCampaigns as $userEnrolledCampaign) {
			$campaignMilestoneCompletionStatus = $userEnrolledCampaign->checkCampaignMilestoneCompletionStatus();
			$completedCampaignMilestones = array_filter($campaignMilestoneCompletionStatus, function($status) {
				return $status === true;
			});
			//Add the completed milestones count to the total 
			$totalCompletedCampaignMilestones += count($completedCampaignMilestones);
			return $totalCompletedCampaignMilestones;
		}
	}
	/**
	 * Calculate the user's rank based on completed milestones
	 * @param int $userId
	 * @return int
	 */
	public function getUserRank($userId) {
		$campaign = new Campaign();
		$allUsers = $campaign->getAllUsersInCampaigns();
		$userCompletedMilestones = [];
		foreach ($allUsers as $user) {
			$totalCompletedMilestones = $this->calculateUserCompletedCampaignMilestones($userId);
			$userCompletedMilestones[$user] = $totalCompletedMilestones;
		}
		arsort($userCompletedMilestones);
		$rank = 1;
		$previousCompletedMilestones = null;
		$userRank = null;
		foreach ($userCompletedMilestones as $userId => $completedMilestones) {
			if ($completedMilestones != $previousCompletedMilestones) {
				$rank = $rank;
			}
			if ($user == $userId) {
				$userRank = $rank;
				break;
			}
			$previousCompletedMilestones = $completedMilestones;
			$rank++;
		}
		return $userRank;
	}

	public function checkAndHandleCampaignCompletion($userId, $campaignId) {
		require_once ROOT_DIR . '/sys/Email/EmailTemplate.php';
		require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
		global $logger;

		$userCampaign = new UserCampaign();
		$userCampaign->userId = $userId;
		$userCampaign->campaignId = $campaignId;

		if ($userCampaign->find(true)) {

				$user = new User();
				$user->id = $userId;
				if (!$user->find(true)) {
					return;
				}

				$campaign = new Campaign();
				$campaign->id = $campaignId;
				if (!$campaign->find(true)) {
					return;
				}
				$campaignName = $campaign->name;
				$isCampaignComplete = $this->checkCompletionStatus();
				if ($isCampaignComplete) {
					$userCampaign->completed = 1;
					$userCampaign->update();
				}

				if ($userCampaign->completed == 1) {

					$library = $user->getHomeLibrary();
					if ($library == null) {
						global $library;
					}
					
					if ($library->sendStaffEmailOnCampaignCompletion == 1 && $userCampaign->staffCampaignCompleteEmailSent == 0) {
						$emailTemplate = EmailTemplate::getActiveTemplate('staffCampaignComplete');
						
						if (!$emailTemplate) {
							$logger->log("No active email template found for 'campaignComplete'", Logger::LOG_ERROR);
						}
						if ($emailTemplate) {
							$parameters = [
								'user' => $user,
								'campaignName' => $campaignName,
								'library' => $library,
							];
							try {
								$emailTemplate->sendEmail($library->campaignCompletionNewEmail, $parameters);
								$userCampaign->staffCampaignCompleteEmailSent = 1;
								$userCampaign->update();
							} catch (Exception $e) {
								$logger->log("Exception while sending email to {$library->campaignCompletionNewEmail}: " . $e->getMessage(), Logger::LOG_ERROR);
							}
						}
					}
					if ($userCampaign->optInToCampaignEmailNotifications == 1 && $userCampaign->campaignCompleteEmailSent == 0) {
						$emailTemplate = EmailTemplate::getActiveTemplate('campaignComplete');
						if ($emailTemplate) {
							$parameters = $campaign->getCampaignEmailParameters($user, $campaignId);
							try {
								$emailTemplate->sendEmail($user->email, $parameters);
								$userCampaign->campaignCompleteEmailSent = 1;
								$userCampaign->update();
							} catch (Exception $e) {
								$logger->log("Exception while sending email to {$user->email}: " . $e->getMessage(), Logger::LOG_ERROR);
							}
						}
					}
				}
				$campaignMilestone = new CampaignMilestone();
				$campaignMilestone->campaignId = $campaignId;
				$campaignMilestones = [];

				if ($campaignMilestone->find()) {
					while ($campaignMilestone->fetch()) {
						$milestoneObj = new Milestone();
						$milestoneObj->id = $campaignMilestone->milestoneId;
						if ($milestoneObj->find(true)) {
							$campaignMilestone->name = $milestoneObj->name;

							if (!empty($campaignMilestone->reward)) {
								require_once ROOT_DIR . '/sys/CommunityEngagement/Reward.php';

								$reward = new Reward();
								$reward->id = $campaignMilestone->reward;

								if ($reward->find(true)) {
									$campaignMilestone->rewardName = $reward->name;
								} else {
									$campaignMilestone->rewardName = '';
								}
							} else {
								$campaignMilestone->rewardName = '';
							}
						}
						$campaignMilestones[] = clone $campaignMilestone;
					}
				}

				foreach ($campaignMilestones as $campaignMilestone) {
					$campaignMilestoneUsersProgress = new CampaignMilestoneUsersProgress();
					$campaignMilestoneUsersProgress->userId = $userId;
					$campaignMilestoneUsersProgress->ce_campaign_milestone_id = $campaignMilestone->id;

					if ($campaignMilestoneUsersProgress->find(true)) {

						if ($campaignMilestoneUsersProgress->progress >= $campaignMilestone->goal && !$campaignMilestoneUsersProgress->milestoneCompleteEmailSent && $userCampaign->optInToCampaignEmailNotifications == 1){

							$emailTemplate = EmailTemplate::getActiveTemplate('milestoneComplete');

							if ($emailTemplate) {
								$parameters = [
									'user' => $user,
									'campaignName' => $campaign->name,
									'milestoneName' => $campaignMilestone->name,
									'milestoneReward' => $campaignMilestone->rewardName,
									'library' => $user->getHomeLibrary(),
								];

								try {
									$emailTemplate->sendEmail($user->email, $parameters);
									$campaignMilestoneUsersProgress->milestoneCompleteEmailSent = 1;
									$campaignMilestoneUsersProgress->update();
								} catch (Exception $e) {
									$logger->log("Error sending milestone email to {$user->email}: " . $e->getMessage(), Logger::LOG_ERROR);
								}
							}
						}
					}
				}
		}
	}

	public function checkExtraCreditActivityCompletionStatus() {
		$extraCreditActivities = CampaignExtraCredit::getExtraCreditByCampaign($this->campaignId);
		$extraCreditCompletionStatus = [];

		foreach ($extraCreditActivities as $extraCreditActivity) {
			$userProgressInActivity = CampaignExtraCreditActivityUsersProgress::getProgressByExtraCreditId($extraCreditActivity->id, $this->campaignId, $this->userId);
			$goal = CampaignExtraCredit::getExtraCreditGoalCountByCampaign($this->campaignId, $extraCreditActivity->id);
			$isExtraCreditActivityComplete = ($userProgressInActivity >= $goal);
			$extraCreditCompletionStatus[$extraCreditActivity->id] = $isExtraCreditActivityComplete;
		}

		return $extraCreditCompletionStatus;
	}
}