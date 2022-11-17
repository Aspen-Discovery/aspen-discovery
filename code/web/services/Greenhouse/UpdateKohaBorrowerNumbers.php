<?php
require_once ROOT_DIR . '/services/Greenhouse/UserMerger.php';
class UpdateKohaBorrowerNumbers extends UserMerger
{
	function launch()
	{
		parent::launch();
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
}