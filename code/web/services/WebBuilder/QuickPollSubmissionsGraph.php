<?php
require_once ROOT_DIR . '/sys/WebBuilder/QuickPoll.php';
require_once ROOT_DIR . '/sys/WebBuilder/QuickPollSubmission.php';
require_once ROOT_DIR . '/sys/WebBuilder/QuickPollSubmissionSelection.php';

class WebBuilder_QuickPollSubmissionsGraph extends Action {
	function launch() {
		global $interface;
		$dataSeries = [];
		$columnLabels = [];
		require_once ROOT_DIR . '/sys/Utils/GraphingUtils.php';

		$results = [];
		$quickPoll = new QuickPoll();
		$quickPoll->id = $_REQUEST['pollId'];
		if($quickPoll->find(true)) {
			$results = $quickPoll->getPollResultsForGraph();
		}

		foreach($results as $result) {
			$id = $result['id'];
			$label = $result['label'];
			$count = $result['count'];

			$dataSeries[$label] = GraphingUtils::getDataSeriesArray(count($dataSeries));

			if(!in_array($label, $columnLabels)) {
				$columnLabels[] = $label;
			}

			$dataSeries[$label]['data'][$label] = $count;
			$dataSeries[$label]['displayLabel'] = $label;
			$dataSeries[$label]['displayCount'] = $count;
		}

		//Avoiding user with no permissions access to results
		$resultsURL ='/WebBuilder/QuickPollSubmissionsGraph?pollId=' . $quickPoll->id;
		$canViewResults = $quickPoll->userCanViewResults();
		if (!$canViewResults && $quickPoll->showResultsToPatrons == 0 && $_SERVER['REQUEST_URI'] == $resultsURL) {
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle403');
			require_once ROOT_DIR . "/services/Error/Handle403.php";
			$actionClass = new Error_Handle403();
			$actionClass->launch();
			die();
		}

		$interface->assign('canViewResults', $canViewResults);
		$interface->assign('showResultsToPatrons',$quickPoll->showResultsToPatrons);
		$interface->assign('graphTitle', $quickPoll->title);
		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);
		$interface->assign('translateDataSeries', true);
		$interface->assign('translateColumnLabels', false);
		$interface->assign('showContentAsFullWidth', true);

		$isAdmin = UserAccount::isStaff();
		$isLoggedIn = UserAccount::isLoggedIn();
		if ($isAdmin){
			$adminActions = UserAccount::getActiveUserObj()->getAdminActions();
			$interface->assign('adminActions', $adminActions);
			$interface->assign('activeAdminSection', 'web_builder');
			$interface->assign('activeMenuOption', 'admin');
			$this->display('../Admin/pollResultsGraph.tpl', 'Results for ' . $quickPoll->title,'Admin/admin-sidebar.tpl');
		} elseif($isLoggedIn){
			$this->display('../Admin/pollResultsGraph.tpl', 'Results for ' . $quickPoll->title,);
		} else{
			$this->display('../Admin/pollResultsGraph.tpl', 'Results for ' . $quickPoll->title,false);
		}
	}

	function getBreadcrumbs(): array {
		$user = UserAccount::getLoggedInUser();
		$breadcrumbs = [];
		if ($user && $this->canView()){
			$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
			$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/QuickPolls?id=' . $_REQUEST['pollId'], 'Quick Poll');
		}
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/QuickPollSubmissionsGraph?pollId=' . $_REQUEST['pollId'], 'Graph');
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Quick Polls',
			'Administer Library Quick Polls',
		]);
	}

	function getActiveAdminSection(): string {
		return 'web_builder';
	}
}