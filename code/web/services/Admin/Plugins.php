<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Plugins/Plugin.php';
require_once ROOT_DIR . '/sys/Plugins/PluginManager.php';

class Admin_Plugins extends ObjectEditor {

	function getObjectType(): string {
		return 'Plugin';
	}

	function getToolName(): string {
		return 'Plugins';
	}

	function getPageTitle(): string {
		return 'Plugin Management';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$plugin = new Plugin();
		$plugin->orderBy($this->getSort());
		$this->applyFilters($plugin);
		$plugin->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$plugin->find();
		$plugins = [];
		while ($plugin->fetch()) {
			$plugins[$plugin->id] = clone $plugin;
		}
		return $plugins;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return Plugin::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew(): bool {
		return UserAccount::userHasPermission('Administer Plugins');
	}

	function canDelete(): bool {
		return UserAccount::userHasPermission('Administer Plugins');
	}

	function canEdit($object): bool {
		return UserAccount::userHasPermission('Administer Plugins');
	}

	function customListActions(): array {
		$actions = [];
		if (UserAccount::userHasPermission('Administer Plugins')) {
			$actions[] = [
				'label' => 'Install Plugin from Directory',
				'action' => 'installPlugin',
			];
			$actions[] = [
				'label' => 'Upload Plugin (.plugzip)',
				'action' => 'uploadPlugin',
			];
		}
		return $actions;
	}

	function getAdditionalObjectActions($existingObject): array {
		$actions = [];
		
		if (UserAccount::userHasPermission('Administer Plugins') && $existingObject instanceof Plugin) {
			if ($existingObject->isEnabled()) {
				$actions[] = [
					'text' => 'Disable',
					'url' => '/Admin/Plugins?objectAction=disablePlugin&id=' . $existingObject->id,
				];
			} else {
				$actions[] = [
					'text' => 'Enable',
					'url' => '/Admin/Plugins?objectAction=enablePlugin&id=' . $existingObject->id,
				];
			}
			
			$actions[] = [
				'text' => 'Uninstall',
				'url' => '/Admin/Plugins?objectAction=uninstallPlugin&id=' . $existingObject->id,
				'onclick' => 'return confirm("Are you sure you want to uninstall this plugin? This action cannot be undone.")',
			];
		}
		
		return $actions;
	}

	function installPlugin(): void {
		global $interface;
		
		if (isset($_POST['pluginPath'])) {
			$pluginPath = $_POST['pluginPath'];
			$pluginManager = PluginManager::getInstance();
			$result = $pluginManager->installPlugin($pluginPath);
			
			if ($result['success']) {
				$interface->assign('updateMessage', $result['message']);
				$interface->assign('updateMessageIsError', false);
			} else {
				$interface->assign('updateMessage', $result['message']);
				$interface->assign('updateMessageIsError', true);
			}
			
			// Redirect back to list
			header('Location: /Admin/Plugins');
			exit();
		}
		
		$interface->assign('instructions', 'Enter the path to the plugin directory to install it.');
		$this->display('installPlugin.tpl', 'Install Plugin');
	}

	function uploadPlugin(): void {
		global $interface;
		
		if (isset($_FILES['pluginFile'])) {
			$uploadedFile = $_FILES['pluginFile'];
			$pluginManager = PluginManager::getInstance();
			$result = $pluginManager->installPluginFromUpload($uploadedFile);
			
			if ($result['success']) {
				$interface->assign('updateMessage', $result['message']);
				$interface->assign('updateMessageIsError', false);
			} else {
				$interface->assign('updateMessage', $result['message']);
				$interface->assign('updateMessageIsError', true);
			}
			
			// Redirect back to list
			header('Location: /Admin/Plugins');
			exit();
		}
		
		$interface->assign('instructions', 'Upload a .plugzip file to install a plugin.');
		$this->display('uploadPlugin.tpl', 'Upload Plugin');
	}

	function enablePlugin(): void {
		if (isset($_REQUEST['id'])) {
			$plugin = new Plugin();
			$plugin->id = $_REQUEST['id'];
			if ($plugin->find(true)) {
				if ($plugin->enable()) {
					$_SESSION['updateMessage'] = 'Plugin enabled successfully';
					$_SESSION['updateMessageIsError'] = false;
				} else {
					$_SESSION['updateMessage'] = 'Failed to enable plugin';
					$_SESSION['updateMessageIsError'] = true;
				}
			}
		}
		
		header('Location: /Admin/Plugins');
		exit();
	}

	function disablePlugin(): void {
		if (isset($_REQUEST['id'])) {
			$plugin = new Plugin();
			$plugin->id = $_REQUEST['id'];
			if ($plugin->find(true)) {
				if ($plugin->disable()) {
					$_SESSION['updateMessage'] = 'Plugin disabled successfully';
					$_SESSION['updateMessageIsError'] = false;
				} else {
					$_SESSION['updateMessage'] = 'Failed to disable plugin';
					$_SESSION['updateMessageIsError'] = true;
				}
			}
		}
		
		header('Location: /Admin/Plugins');
		exit();
	}

	function uninstallPlugin(): void {
		if (isset($_REQUEST['id'])) {
			$plugin = new Plugin();
			$plugin->id = $_REQUEST['id'];
			if ($plugin->find(true)) {
				$pluginManager = PluginManager::getInstance();
				$result = $pluginManager->uninstallPlugin($plugin->slug);
				
				if ($result['success']) {
					$_SESSION['updateMessage'] = $result['message'];
					$_SESSION['updateMessageIsError'] = false;
				} else {
					$_SESSION['updateMessage'] = $result['message'];
					$_SESSION['updateMessageIsError'] = true;
				}
			}
		}
		
		header('Location: /Admin/Plugins');
		exit();
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_administration', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('', 'Plugin Management');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_administration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Plugins');
	}


} 