<?php
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
class CommunityEngagement_Leaderboard extends Action {
	function launch() {
		global $interface;
		global $library;

		$campaign = new Campaign();
		$campaigns = $campaign->getAllCampaigns();
		$interface->assign('campaigns', $campaigns);

		$user = userAccount::getActiveUserObj();
		$userIsAdmin = $user->isUserAdmin();
		if ($user->getHomeLibrary() != null) {
			$campaignLeaderboardDisplay = $user->getHomeLibrary()->campaignLeaderboardDisplay;
		} else {
			$campaignLeaderboardDisplay = $library->campaignLeaderboardDisplay;
		}
		$interface->assign('campaignLeaderboardDisplay', $campaignLeaderboardDisplay);
		$interface->assign('userIsAdmin', $userIsAdmin);

		$template = $this->getLeaderBoardTemplate();

		if ($template) {
			$leaderboardHTML = str_ireplace(['<body>', '</body>'], '', $template->htmlData);
			$leaderboardCss = $template->cssData;
			$interface->assign('leaderboardHtml', $leaderboardHTML);
			$interface->assign('leaderboardCss', $leaderboardCss);
			$this->display('updatedLeaderboard.tpl', 'Leaderboard');
		} else {
			$this->display('leaderboard.tpl', 'Leaderboard');
		}
	}
	public function getLeaderboardTemplate() {
		$template = new GrapesTemplate();
		$template->templateName = 'leaderboard_template';

		if ($template->find(true)) {
			return $template;
		}
		return null;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('/MyAccount/MyCampaigns', 'Your Campaigns');
		return $breadcrumbs;
	}
}