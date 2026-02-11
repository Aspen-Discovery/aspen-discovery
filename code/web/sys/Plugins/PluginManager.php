<?php

require_once ROOT_DIR . '/sys/Plugins/Plugin.php';

class PluginManager {
	private static $instance = null;
	private $loadedPlugins = [];

	private function __construct() {
		$this->loadEnabledPlugins();
	}

	public static function getInstance(): PluginManager {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Convert plugin slug to class name (example_plugin -> ExamplePlugin)
	 */
	public function getPluginClassName(string $slug): string {
		$slugParts = explode('_', $slug);
		$pluginClassName = '';
		foreach ($slugParts as $part) {
			$pluginClassName .= ucfirst($part);
		}
		// Don't add 'Plugin' suffix if it's already there
		if (substr($pluginClassName, -6) !== 'Plugin') {
			$pluginClassName .= 'Plugin';
		}
		return $pluginClassName;
	}
	
	/**
	 * Call a hook method on a plugin
	 * Centralizes all the complex hook-calling logic
	 */
	public function callHook(Plugin $plugin, string $hookMethod): bool {
		if (!$plugin->pluginDirectoryExists() || !$plugin->pluginClassFileExists()) {
			return false;
		}
		
		try {
			$pluginClassName = $this->getPluginClassName($plugin->slug);
			
			// Only require the file if the class doesn't already exist
			if (!class_exists($pluginClassName)) {
				require_once $plugin->getPluginClassFile();
			}
			
			// Check if class exists and has the hook method
			if (class_exists($pluginClassName, false) && method_exists($pluginClassName, $hookMethod)) {
				$pluginInstance = new $pluginClassName($plugin);
				$pluginInstance->$hookMethod();
				return true;
			}
		} catch (Exception $e) {
			// Log error but don't fail
			global $logger;
			if (isset($logger)) {
				$logger->log("Error calling $hookMethod hook for plugin {$plugin->slug}: " . $e->getMessage(), Logger::LOG_ERROR);
			}
		}
		
		return false;
	}

	/**
	 * Load all enabled plugins
	 */
	private function loadEnabledPlugins(): void {
		$plugin = new Plugin();
		$plugin->status = 1; // enabled
		$plugin->find();

		while ($plugin->fetch()) {
			if ($plugin->pluginDirectoryExists() && $plugin->pluginClassFileExists()) {
				try {
					require_once $plugin->getPluginClassFile();
					$pluginClassName = $this->getPluginClassName($plugin->slug);
					
					if (class_exists($pluginClassName, false)) {
						$pluginInstance = new $pluginClassName($plugin);
						$this->loadedPlugins[$plugin->slug] = $pluginInstance;
					} else {
						// Log that class doesn't exist
						global $logger;
						if (isset($logger)) {
							$logger->log("Plugin class {$pluginClassName} not found for plugin {$plugin->slug}", Logger::LOG_ERROR);
						}
					}
				} catch (Exception $e) {
					// Log error but continue
					global $logger;
					if (isset($logger)) {
						$logger->log("Error loading plugin {$plugin->slug}: " . $e->getMessage(), Logger::LOG_ERROR);
					}
				} catch (Error $e) {
					// Log fatal errors but continue
					global $logger;
					if (isset($logger)) {
						$logger->log("Fatal error loading plugin {$plugin->slug}: " . $e->getMessage(), Logger::LOG_ERROR);
					}
				}
			} else {
				// Plugin directory or file doesn't exist, just log and skip (don't disable to avoid infinite loop)
				global $logger;
				if (isset($logger)) {
					$logger->log("Plugin {$plugin->slug} files missing, skipping load (plugin remains enabled in database)", Logger::LOG_WARNING);
				}
			}
		}
	}

	/**
	 * Install a plugin from directory or .plugzip file
	 */
	public function installPlugin(string $pluginPath): array {
		$tempExtractDir = null;
		
		// Check if it's a .plugzip file
		if (is_file($pluginPath) && strtolower(pathinfo($pluginPath, PATHINFO_EXTENSION)) === 'plugzip') {
			$tempExtractDir = sys_get_temp_dir() . '/aspen_plugin_' . time() . '_' . rand(1000, 9999);
			
			if (!$this->extractPluginZip($pluginPath, $tempExtractDir)) {
				return ['success' => false, 'message' => 'Failed to extract plugin zip file'];
			}
			
			$pluginPath = $tempExtractDir;
		}

		if (!is_dir($pluginPath)) {
			return ['success' => false, 'message' => 'Plugin directory not found'];
		}

		// Extract metadata from plugin.yaml manifest
		$pluginMetadata = $this->extractPluginMetadata($pluginPath);
		if (!$pluginMetadata) {
			if ($tempExtractDir) {
				$this->removeDirectory($tempExtractDir);
			}
			return ['success' => false, 'message' => 'No valid plugin.yaml manifest found, or manifest is missing required fields (name, slug, version)'];
		}

		// Check if plugin already exists by slug
		$existingPlugin = new Plugin();
		$existingPlugin->slug = $pluginMetadata['slug'];
		if ($existingPlugin->find(true)) {
			$result = $this->updatePlugin($existingPlugin, $pluginMetadata, $pluginPath);

			// Clean up temporary extraction directory if we used one
			if ($tempExtractDir) {
				$this->removeDirectory($tempExtractDir);
			}

			return $result;
		}

		// Create plugin directory in the correct location
		$pluginBaseDir = Plugin::getPluginDataPath();
		$targetPath = "$pluginBaseDir/{$pluginMetadata['slug']}";
		
		if (!is_dir($pluginBaseDir)) {
			mkdir($pluginBaseDir, 0755, true);
		}

		// Copy plugin files
		if (!$this->copyDirectory($pluginPath, $targetPath)) {
			if ($tempExtractDir) {
				$this->removeDirectory($tempExtractDir);
			}
			return ['success' => false, 'message' => 'Failed to copy plugin files'];
		}

		// Create database entry
		$plugin = new Plugin();
		$plugin->status = 0; // disabled by default

		// Properties to exclude from automatic metadata mapping
		$excludedProperties = ['config', 'status'];

		// Copy all matching properties from metadata
		foreach ($pluginMetadata as $key => $value) {
			if (property_exists($plugin, $key) && !in_array($key, $excludedProperties)) {
				$plugin->$key = $value;
			}
		}

		if (!empty($pluginMetadata['config'])) {
			$plugin->setConfigArray($pluginMetadata['config']);
		}

		if ($plugin->insert()) {
			// Run plugin installation hook if it exists
			$this->callHook($plugin, 'onInstall');

			// Audit log: Plugin installed
			global $logger;
			if (isset($logger)) {
				$user = UserAccount::getActiveUserObj();
				$username = $user ? $user->username : 'unknown';
				$userId = $user ? $user->id : 'unknown';
				$logger->log("Plugin installed: {$plugin->name} (slug: {$plugin->slug}, version: {$plugin->version}) by user {$username} (ID: {$userId})", Logger::LOG_NOTICE, true);
			}

			// Clean up temporary extraction directory if we used one
			if ($tempExtractDir) {
				$this->removeDirectory($tempExtractDir);
			}

			return ['success' => true, 'message' => 'Plugin installed successfully'];
		} else {
			// Clean up files if database insert failed
			$this->removeDirectory($targetPath);
			if ($tempExtractDir) {
				$this->removeDirectory($tempExtractDir);
			}
			return ['success' => false, 'message' => 'Failed to create plugin database entry'];
		}
	}

	/**
	 * Update an existing plugin to a newer version
	 * @param Plugin $existingPlugin The existing plugin database record
	 * @param array $newMetadata Metadata extracted from the new plugin version
	 * @param string $pluginPath Path to the new plugin files
	 * @return array Result array with success status and message
	 */
	private function updatePlugin(Plugin $existingPlugin, array $newMetadata, string $pluginPath): array {
		// Compare versions
		$existingVersion = $existingPlugin->version;
		$newVersion = $newMetadata['version'];

		$versionComparison = version_compare($newVersion, $existingVersion);

		// Reject if version is not newer
		if ($versionComparison <= 0) {
			$message = $versionComparison === 0
				? "Cannot update: installed version ({$existingVersion}) is the same as uploaded version ({$newVersion})"
				: "Cannot update: installed version ({$existingVersion}) is newer than uploaded version ({$newVersion})";
			return ['success' => false, 'message' => $message];
		}

		// Create backup directory path in case we need to rollback
		$pluginBaseDir = Plugin::getPluginDataPath();
		$targetPath = "$pluginBaseDir/{$existingPlugin->slug}";
		$backupPath = "$pluginBaseDir/.backup_{$existingPlugin->slug}_" . time();

		// Backup existing plugin directory
		if (is_dir($targetPath)) {
			if (!rename($targetPath, $backupPath)) {
				return ['success' => false, 'message' => 'Failed to backup existing plugin files'];
			}
		}

		// Copy new plugin files
		if (!$this->copyDirectory($pluginPath, $targetPath)) {
			// Restore backup on failure
			if (is_dir($backupPath)) {
				rename($backupPath, $targetPath);
			}
			return ['success' => false, 'message' => 'Failed to copy new plugin files'];
		}

		// Update database record with new metadata
		// Exclude properties that should be preserved from existing plugin
		$excludedProperties = ['id', 'status', 'config', 'configData'];

		foreach ($newMetadata as $key => $value) {
			if (property_exists($existingPlugin, $key) && !in_array($key, $excludedProperties)) {
				$existingPlugin->$key = $value;
			}
		}

		if (!$existingPlugin->update()) {
			// Rollback file changes on database update failure
			$this->removeDirectory($targetPath);
			if (is_dir($backupPath)) {
				rename($backupPath, $targetPath);
			}
			return ['success' => false, 'message' => 'Failed to update plugin database record'];
		}

		// Clean up backup directory on success
		if (is_dir($backupPath)) {
			$this->removeDirectory($backupPath);
		}

		// Audit log: Plugin updated
		global $logger;
		if (isset($logger)) {
			$user = UserAccount::getActiveUserObj();
			$username = $user ? $user->username : 'unknown';
			$userId = $user ? $user->id : 'unknown';
			$logger->log("Plugin updated: {$existingPlugin->name} (slug: {$existingPlugin->slug}) from version {$existingVersion} to version {$newVersion} by user {$username} (ID: {$userId})", Logger::LOG_NOTICE, true);
		}

		return [
			'success' => true,
			'message' => "Plugin updated successfully from version {$existingVersion} to version {$newVersion}"
		];
	}

	/**
	 * Uninstall a plugin
	 */
	public function uninstallPlugin(string $slug): array {
		$plugin = new Plugin();
		$plugin->slug = $slug;
		if (!$plugin->find(true)) {
			return ['success' => false, 'message' => 'Plugin not found'];
		}

		// Store plugin info for audit log before deletion
		$pluginName = $plugin->name;
		$pluginSlug = $plugin->slug;

		// Run plugin uninstall hook if it exists
		$this->callHook($plugin, 'onUninstall');

		// Clean up plugin data
		$this->cleanupPluginData($slug);

		// Remove plugin directory
		if ($plugin->pluginDirectoryExists()) {
			$this->removeDirectory($plugin->getPluginDirectory());
		}

		// Remove database entry
		if ($plugin->delete()) {
			// Audit log: Plugin uninstalled
			global $logger;
			if (isset($logger)) {
				$user = UserAccount::getActiveUserObj();
				$username = $user ? $user->username : 'unknown';
				$userId = $user ? $user->id : 'unknown';
				$logger->log("Plugin uninstalled: {$pluginName} (slug: {$pluginSlug}) by user {$username} (ID: {$userId})", Logger::LOG_NOTICE, true);
			}

			return ['success' => true, 'message' => 'Plugin uninstalled successfully'];
		} else {
			return ['success' => false, 'message' => 'Failed to remove plugin database entry'];
		}
	}

	/**
	 * Execute hooks for all enabled plugins
	 */
	public function executeHook(string $hookPoint, array $data = []): array {
		$results = [];
		
		foreach ($this->loadedPlugins as $slug => $pluginInstance) {
			try {
				if (method_exists($pluginInstance, $hookPoint)) {
					$result = $pluginInstance->$hookPoint($data);
					if ($result !== null) {
						$results[] = $result;
					}
				}
			} catch (Exception $e) {
				global $logger;
				if (isset($logger)) {
					$logger->log("Error executing hook $hookPoint on plugin $slug: " . $e->getMessage(), Logger::LOG_ERROR);
				}
			}
		}
		
		return $results;
	}

	/**
	 * Get JS files to inject from all enabled plugins
	 */
	public function getJsFilesToInject(): array {
		$jsFiles = [];
		
		foreach ($this->loadedPlugins as $slug => $pluginInstance) {
			$pluginJsFiles = $pluginInstance->getJavaScriptFiles();
			foreach ($pluginJsFiles as $jsFile) {
				$jsFiles[] = "/plugins/$slug/$jsFile";
			}
		}
		
		return $jsFiles;
	}

	/**
	 * Get CSS files to inject from all enabled plugins
	 */
	public function getCssFilesToInject(): array {
		$cssFiles = [];
		
		foreach ($this->loadedPlugins as $slug => $pluginInstance) {
			$pluginCssFiles = $pluginInstance->getCssFiles();
			foreach ($pluginCssFiles as $cssFile) {
				$cssFiles[] = "/plugins/$slug/$cssFile";
			}
		}
		
		return $cssFiles;
	}

	/**
	 * Copy directory recursively
	 */
	private function copyDirectory(string $source, string $destination): bool {
		if (!is_dir($source)) {
			return false;
		}

		if (!is_dir($destination)) {
			mkdir($destination, 0755, true);
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			$target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
			if ($item->isDir()) {
				if (!is_dir($target)) {
					mkdir($target, 0755, true);
				}
			} else {
				copy($item, $target);
			}
		}

		return true;
	}

	/**
	 * Remove directory recursively
	 */
	private function removeDirectory(string $directory): bool {
		if (!is_dir($directory)) {
			return false;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iterator as $item) {
			if ($item->isDir()) {
				rmdir($item->getRealPath());
			} else {
				unlink($item->getRealPath());
			}
		}

		return rmdir($directory);
	}

	/**
	 * Extract a .plugzip file to a temporary directory
	 */
	private function extractPluginZip(string $zipFile, string $extractPath): bool {
		if (!class_exists('ZipArchive')) {
			return false;
		}

		$zip = new ZipArchive();
		$result = $zip->open($zipFile);
		
		if ($result !== TRUE) {
			return false;
		}

		// Create extraction directory
		if (!mkdir($extractPath, 0755, true)) {
			$zip->close();
			return false;
		}

		// Extract all files
		$extracted = $zip->extractTo($extractPath);
		$zip->close();

		return $extracted;
	}

	/**
	 * Install a plugin from uploaded file
	 */
	public function installPluginFromUpload(array $uploadedFile): array {
		// Validate file upload
		if (!isset($uploadedFile['tmp_name']) || !is_uploaded_file($uploadedFile['tmp_name'])) {
			return ['success' => false, 'message' => 'Invalid file upload'];
		}

		// Validate file extension
		$filename = $uploadedFile['name'];
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		
		if ($extension !== 'plugzip') {
			return ['success' => false, 'message' => 'Invalid file type. Only .plugzip files are allowed.'];
		}

		// Move uploaded file to temporary location
		$tempUploadPath = sys_get_temp_dir() . '/aspen_upload_' . time() . '_' . rand(1000, 9999) . '.plugzip';
		
		if (!move_uploaded_file($uploadedFile['tmp_name'], $tempUploadPath)) {
			return ['success' => false, 'message' => 'Failed to process uploaded file'];
		}

		// Install plugin from the temporary file
		$result = $this->installPlugin($tempUploadPath);

		// Clean up temporary upload file
		if (file_exists($tempUploadPath)) {
			unlink($tempUploadPath);
		}

		return $result;
	}

	/**
	 * Find the main plugin PHP file in a directory
	 */
	private function findPluginFile(string $pluginPath): ?string {
		$phpFiles = glob($pluginPath . '/*.php');
		
		foreach ($phpFiles as $file) {
			$content = file_get_contents($file);
			// Look for a class that extends AspenPlugin
			if (preg_match('/class\s+(\w+)\s+extends\s+AspenPlugin/', $content)) {
				return $file;
			}
		}
		
		return null;
	}

	/**
	 * Find the plugin.yaml manifest file in a directory
	 */
	private function findPluginManifest(string $pluginPath): ?string {
		$manifestPath = $pluginPath . '/plugin.yaml';
		if (file_exists($manifestPath)) {
			return $manifestPath;
		}
		return null;
	}

	/**
	 * Extract metadata from a plugin.yaml manifest file
	 */
	private function extractPluginMetadata(string $pluginPath): ?array {
		try {
			$manifestFile = $this->findPluginManifest($pluginPath);
			if (!$manifestFile) {
				return null;
			}

			require_once ROOT_DIR . '/sys/Yaml.php';
			$yaml = new Yaml();
			$manifest = $yaml->load($manifestFile);

			if (empty($manifest) || !is_array($manifest)) {
				return null;
			}

			// Validate required fields
			if (empty($manifest['name']) || empty($manifest['slug']) || empty($manifest['version'])) {
				global $logger;
				if (isset($logger)) {
					$logger->log("Plugin manifest missing required fields (name, slug, version): $manifestFile", Logger::LOG_ERROR);
				}
				return null;
			}

			// Verify a PHP class file exists for this plugin
			$pluginFile = $this->findPluginFile($pluginPath);
			if (!$pluginFile) {
				global $logger;
				if (isset($logger)) {
					$logger->log("Plugin manifest found but no PHP class file extending AspenPlugin in: $pluginPath", Logger::LOG_ERROR);
				}
				return null;
			}

			return [
				'name' => $manifest['name'],
				'slug' => $manifest['slug'],
				'version' => $manifest['version'],
				'description' => $manifest['description'] ?? 'No description provided',
				'author' => $manifest['author'] ?? 'Unknown Author',
				'modifiedDate' => $manifest['lastModified'] ?? null,
				'minAspenVersion' => $manifest['minAspenVersion'] ?? null,
				'maxAspenVersion' => $manifest['maxAspenVersion'] ?? null,
				'config' => $manifest['config'] ?? [],
			];
		} catch (Exception $e) {
			global $logger;
			if (isset($logger)) {
				$logger->log("Error extracting plugin metadata: " . $e->getMessage(), Logger::LOG_ERROR);
			}
			return null;
		}
	}

	/**
	 * Check if a plugin class has a specific method
	 */
	public function pluginHasMethod(Plugin $plugin, string $method): bool {
		$className = $this->getPluginClassName($plugin->slug);

		if (!$plugin->pluginClassFileExists()) {
			return false;
		}

		if (!class_exists($className)) {
			require_once $plugin->getPluginClassFile();
		}

		return class_exists($className, false) && method_exists($className, $method);
	}

	/**
	 * Clean up all data for a plugin (called during uninstall)
	 */
	public function cleanupPluginData(string $slug): void {
		$className = $this->getPluginClassName($slug);

		global $aspen_db;

		$stmt = $aspen_db->prepare("DELETE FROM plugin_data WHERE plugin_class = ?");
		$stmt->execute([$className]);
	}
} 