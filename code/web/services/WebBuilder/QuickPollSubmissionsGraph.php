<?php
require_once ROOT_DIR . '/services/Admin/Dashboard.php';
require_once ROOT_DIR . '/sys/WebBuilder/QuickPoll.php';
require_once ROOT_DIR . '/sys/WebBuilder/QuickPollSubmission.php';
require_once ROOT_DIR . '/sys/WebBuilder/QuickPollSubmissionSelection.php';

class WebBuilder_QuickPollSubmissionsGraph extends Admin_Dashboard {
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

		$interface->assign('graphTitle', $quickPoll->title);
		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);
		$interface->assign('translateDataSeries', true);
		$interface->assign('translateColumnLabels', false);

		$this->display('../Admin/pollResultsGraph.tpl', 'Results for ' . $quickPoll->title);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/QuickPolls?id=' . $_REQUEST['pollId'], 'Quick Poll');
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