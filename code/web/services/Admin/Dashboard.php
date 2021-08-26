<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';

abstract class Admin_Dashboard extends Admin_Admin {
	protected $thisMonth;
	protected $thisYear;
	protected $lastMonth;
	protected $lastMonthYear;
	protected $lastYear;

	/**
	 * @param string $statsClassname The name of the class to load instance information for
	 * @return string selected instance
	 */
	function loadInstanceInformation(string $statsClassname){
		global $interface;

		//Get a list of instances that we have stats for.
		$allInstances = [];
		$allInstances[''] = translate(['text'=>'All', 'isAdminFacing'=>true, 'inAttribute'=>true]);
		/** @var DataObject $statsInstance */
		$statsInstance = new $statsClassname();
		$statsInstance->selectAdd(null);
		$statsInstance->selectAdd("DISTINCT(instance) as instance");
		$statsInstance->orderBy('instance');
		$statsInstance->find();
		if ($statsInstance->getNumResults() > 1) {
			while ($statsInstance->fetch()) {
				if (!empty($statsInstance->instance)) {
					$allInstances[$statsInstance->instance] = $statsInstance->instance;
				}
			}
		}
		$interface->assign('allInstances', $allInstances);

		if (!empty($_REQUEST['instance'])){
			$instanceName = $_REQUEST['instance'];
		}else{
			$instanceName = '';
		}
		$interface->assign('selectedInstance', $instanceName);

		return $instanceName;
	}

	function loadDates(){
		$this->thisMonth = date('n');
		$this->thisYear = date('Y');
		$this->lastMonth = $this->thisMonth - 1;
		$this->lastMonthYear = $this->thisYear;
		if ($this->lastMonth == 0) {
			$this->lastMonth = 12;
			$this->lastMonthYear--;
		}
		$this->lastYear = $this->thisYear - 1;
	}
}