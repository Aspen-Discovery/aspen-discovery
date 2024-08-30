<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
abstract class UsageGraphs_UsageGraphs extends Admin_Admin {

	// method specific enough to be worth writing an implementation for per section
	abstract function getBreadcrumbs(): array;
	abstract function getActiveAdminSection(): string;
	abstract protected function assignGraphSpecificTitle(string $stat): void;
	abstract protected function getAndSetInterfaceDataSeries(string $stat, string $instanceName): void;
}