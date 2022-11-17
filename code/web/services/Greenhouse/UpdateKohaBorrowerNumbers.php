<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
class UpdateKohaBorrowerNumbers extends Admin_Admin
{
	function launch()
	{
		global $interface;

		require_once ROOT_DIR . '/CatalogFactory.php';
		global $logger;
		$logger->log('fetching num of Holds from MarcRecord', Logger::LOG_DEBUG);

		$setupErrors = [];
		$catalog = CatalogFactory::getCatalogConnectionInstance();
		if (isset($catalog->status) && $catalog->status) {
			$driver = $catalog->driver;
			if ($driver instanceof Koha){
				if (isset($_REQUEST['submit'])){
					set_time_limit(-1);
					$results = $driver->updateBorrowerNumbers();
					$interface->assign('results', $results);
				}
			}else{
				$setupErrors[] = 'This tool does not work unless the ILS is Koha';
			}
		} else {
			$setupErrors[] = 'Could not connect to the ILS';
		}
		$interface->assign('setupErrors', $setupErrors);

		$this->display('updateKohaBorrowerNumbers.tpl', 'Update Koha Borrower Numbers',false);
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'Update Koha Borrower Numbers');

		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'greenhouse';
	}

	function canView() : bool
	{
		if (UserAccount::isLoggedIn()){
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin'){
				return true;
			}
		}
		return false;
	}
}