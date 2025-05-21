<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoad.php';

class SideLoads_DownloadMarc extends Admin_Admin {
	function launch() {
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);
		$sideLoadConfiguration = new SideLoad();
		$sideLoadConfiguration->id = $id;
		if ($sideLoadConfiguration->find(true) && !empty($sideLoadConfiguration->marcPath)) {
			$interface->assign('sideload', $sideLoadConfiguration);
			$marcPath = $sideLoadConfiguration->marcPath;
			$file = $_REQUEST['file'];
			$fullName = $marcPath . DIRECTORY_SEPARATOR . $file;
			if (file_exists($fullName)) {
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header("Content-Disposition: attachment; filename=$file");
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');

				header('Content-Length: ' . filesize($fullName));
				ob_clean();
				flush();
				readfile($fullName);
				die();
			} else {
				$interface->assign('error', 'Could not find the file to download.');
			}
		} else {
			$interface->assign('error', 'Could not find the Side Load for this file.');
		}
		$this->display('sideloadError.tpl', 'Error Downloading File');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#side_loads', 'Side Load');
		$breadcrumbs[] = new Breadcrumb('/SideLoads/SideLoads', 'Side Loads');
		if (!empty($this->activeObject) && $this->activeObject instanceof SideLoad) {
			$breadcrumbs[] = new Breadcrumb('/SideLoads/SideLoads?objectAction=edit&id=' . $this->activeObject->id, $this->activeObject->name);
		}
		$breadcrumbs[] = new Breadcrumb('', 'Download MARC Record');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'side_loads';
	}

	function canView(): bool {
		return UserAccount::userHasPermission(['Administer Side Loads', 'Administer Side Loads for Home Library']);
	}
}