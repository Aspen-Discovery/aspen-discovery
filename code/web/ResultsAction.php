<?php


abstract class ResultsAction extends Action
{
	function getResultsBreadcrumbs($searchType)
	{
		global $interface;
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb(null, $searchType);
		$recordCount = $interface->getVariable('recordCount');
		if (empty($recordCount)) {
			$resultCountText = translate(["No Results Found", "isPublicFacing" => true]);
		}else{
			if ($interface->getVariable('displayMode') == 'covers') {
				$resultCountText = translate(['text' => "There are %1% total results.", 1=>number_format($recordCount), "isPublicFacing" => true]);
			}else{
				$recordStart = number_format($interface->getVariable('recordStart'));
				$recordEnd = number_format($interface->getVariable('recordEnd'));
				$recordCount = number_format($interface->getVariable('recordCount'));
				$resultCountText = translate(['text'=>"Showing %1% - %2% of %3%", 1=>$recordStart, 2=>$recordEnd, 3=>$recordCount, "isPublicFacing" => true]);
			}
		}
		$breadcrumbs[] = new Breadcrumb(null, $resultCountText, false);
		return $breadcrumbs;
	}
}