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
		return false;
	}

	function canDelete(): bool {
		return UserAccount::userHasPermission('Administer Plugins');
	}

	function canEdit(): bool {
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
			$pluginManager = PluginManager::getInstance();

			// Configure action (if plugin has configure method)
			if ($pluginManager->hasMethod($existingObject, 'configure')) {
				$actions[] = [
					'text' => 'Configure',
					'url' => "/plugins/{$existingObject->slug}/configure",
				];
			}

			// Enable/Disable toggle
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

			// Store message on user object so it survives the redirect
			$user = UserAccount::getActiveUserObj();
			if ($user) {
				$user->updateMessage = $result['message'];
				$user->updateMessageIsError = !$result['success'];
				$user->update();
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

			// Store message on user object so it survives the redirect
			$user = UserAccount::getActiveUserObj();
			if ($user) {
				$user->updateMessage = $result['message'];
				$user->updateMessageIsError = !$result['success'];
				$user->update();
			}

			// Redirect back to list
			header('Location: /Admin/Plugins');
			exit();
		}

		$interface->assign('instructions', 'Upload a .plugzip file to install a plugin.');
		$this->display('uploadPlugin.tpl', 'Upload Plugin');
	}

	function enablePlugin(): void {
		$id = $_REQUEST['id'] ?? null;
		if ($id) {
			$plugin = new Plugin();
			$plugin->id = $id;
			if ($plugin->find(true)) {
				$user = UserAccount::getActiveUserObj();
				if ($plugin->enable()) {
					// Call plugin onEnable hook - safe now that infinite loop is fixed
					$pluginManager = PluginManager::getInstance();
					$pluginManager->callHook($plugin, 'onEnable');

					// Audit log: Plugin enabled
					global $logger;
					if (isset($logger)) {
						$username = $user ? $user->username : 'unknown';
						$userId = $user ? $user->id : 'unknown';
						$logger->log("Plugin enabled: {$plugin->name} (slug: {$plugin->slug}) by user {$username} (ID: {$userId})", Logger::LOG_NOTICE, true);
					}

					if ($user) {
						$user->updateMessage = 'Plugin enabled successfully';
						$user->updateMessageIsError = false;
						$user->update();
					}
				} else {
					if ($user) {
						$user->updateMessage = 'Failed to enable plugin';
						$user->updateMessageIsError = true;
						$user->update();
					}
				}
			}
		}

		header('Location: /Admin/Plugins?objectAction=edit&id=' . $id);
		exit();
	}

	function disablePlugin(): void {
		$id = $_REQUEST['id'] ?? null;
		if ($id) {
			$plugin = new Plugin();
			$plugin->id = $id;
			if ($plugin->find(true)) {
				// Call plugin onDisable hook before disabling - safe now that infinite loop is fixed
				if ($plugin->isEnabled()) {
					$pluginManager = PluginManager::getInstance();
					$pluginManager->callHook($plugin, 'onDisable');
				}

				$user = UserAccount::getActiveUserObj();
				if ($plugin->disable()) {
					// Audit log: Plugin disabled
					global $logger;
					if (isset($logger)) {
						$username = $user ? $user->username : 'unknown';
						$userId = $user ? $user->id : 'unknown';
						$logger->log("Plugin disabled: {$plugin->name} (slug: {$plugin->slug}) by user {$username} (ID: {$userId})", Logger::LOG_NOTICE, true);
					}

					if ($user) {
						$user->updateMessage = 'Plugin disabled successfully';
						$user->updateMessageIsError = false;
						$user->update();
					}
				} else {
					if ($user) {
						$user->updateMessage = 'Failed to disable plugin';
						$user->updateMessageIsError = true;
						$user->update();
					}
				}
			}
		}

		header('Location: /Admin/Plugins?objectAction=edit&id=' . $id);
		exit();
	}

	function uninstallPlugin(): void {
		if (isset($_REQUEST['id'])) {
			$plugin = new Plugin();
			$plugin->id = $_REQUEST['id'];
			if ($plugin->find(true)) {
				$pluginManager = PluginManager::getInstance();
				$result = $pluginManager->uninstallPlugin($plugin->slug);

				$user = UserAccount::getActiveUserObj();
				if ($user) {
					$user->updateMessage = $result['message'];
					$user->updateMessageIsError = !$result['success'];
					$user->update();
				}
			}
		}

		header('Location: /Admin/Plugins');
		exit();
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#plugins', 'Plugins');
		$breadcrumbs[] = new Breadcrumb('', 'Plugin Management');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'plugins';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Plugins');
	}

	public function getViewPermissions() : array {
		return ['Administer Plugins'];
	}

} 