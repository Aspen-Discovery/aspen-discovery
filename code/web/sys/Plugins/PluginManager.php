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

		// Find PHP plugin file - look for files ending with .php that contain a class extending AspenPlugin
		$pluginFile = $this->findPluginFile($pluginPath);
		if (!$pluginFile) {
			if ($tempExtractDir) {
				$this->removeDirectory($tempExtractDir);
			}
			return ['success' => false, 'message' => 'No valid plugin PHP file found'];
		}

		// Load and validate the plugin class
		$pluginMetadata = $this->extractPluginMetadata($pluginFile);
		if (!$pluginMetadata) {
			if ($tempExtractDir) {
				$this->removeDirectory($tempExtractDir);
			}
			return ['success' => false, 'message' => 'Could not extract plugin metadata from PHP file'];
		}

		// Check if plugin already exists
		$existingPlugin = new Plugin();
		$existingPlugin->slug = $pluginMetadata['slug'];
		if ($existingPlugin->find(true)) {
			if ($tempExtractDir) {
				$this->removeDirectory($tempExtractDir);
			}
			return ['success' => false, 'message' => 'Plugin with this slug already exists'];
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
		$plugin->name = $pluginMetadata['name'];
		$plugin->slug = $pluginMetadata['slug'];
		$plugin->version = $pluginMetadata['version'];
		$plugin->description = $pluginMetadata['description'];
		$plugin->author = $pluginMetadata['author'];
		$plugin->status = 0; // disabled by default
		$plugin->modifiedDate = $pluginMetadata['modifiedDate'] ?? null;
		$plugin->minAspenVersion = $pluginMetadata['minAspenVersion'] ?? null;
		$plugin->maxAspenVersion = $pluginMetadata['maxAspenVersion'] ?? null;
		
		// Hook points, JS files, and CSS files are now auto-detected from PHP methods
		if (!empty($pluginMetadata['config'])) {
			$plugin->setConfigArray($pluginMetadata['config']);
		}

		if ($plugin->insert()) {
			// Register plugin methods in the method registry
			$this->registerPluginMethods($plugin);

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

		// Clean up plugin data and method registry
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
	 * Extract metadata from a plugin PHP file
	 */
	private function extractPluginMetadata(string $pluginFile): ?array {
		try {
			// Parse the file to find the class name first
			$content = file_get_contents($pluginFile);
			if (!preg_match('/class\s+(\w+)\s+extends\s+AspenPlugin/', $content, $matches)) {
				return null;
			}
			
			$className = $matches[1];
			
			// Only include the file if the class doesn't already exist
			if (!class_exists($className)) {
				require_once $pluginFile;
			}
			
			if (!class_exists($className, false)) {
				return null;
			}
			
			// Create a temporary instance to get metadata
			// We need a temporary Plugin object for the constructor
			$tempPlugin = new Plugin();
			$pluginInstance = new $className($tempPlugin);
			
			$metadata = $pluginInstance->getMetadata();
			
			return [
				'name' => $metadata['name'] ?? 'Unknown Plugin',
				'slug' => $pluginInstance->getSlug(),
				'version' => $metadata['version'] ?? '1.0.0',
				'description' => $metadata['description'] ?? 'No description provided',
				'author' => $metadata['author'] ?? 'Unknown Author',
				'modifiedDate' => $metadata['lastModified'] ?? null,
				'minAspenVersion' => $metadata['minAspenVersion'] ?? null,
				'maxAspenVersion' => $metadata['maxAspenVersion'] ?? null,
				'className' => $className,
				'jsFiles' => $pluginInstance->getJavaScriptFiles(),
				'cssFiles' => $pluginInstance->getCssFiles(),
				'config' => [] // Plugins can override getConfig() for default config
			];
		} catch (Exception $e) {
			global $logger;
			if (isset($logger)) {
				$logger->log("Error extracting plugin metadata: " . $e->getMessage(), Logger::LOG_ERROR);
			}
			return null;
		}
	}

	// ============================================================
	// Method Registry Methods
	// ============================================================

	/**
	 * Register all methods for a plugin in plugin_methods table
	 * Uses reflection to discover public methods
	 * @param Plugin $plugin Plugin to register methods for
	 */
	public function registerPluginMethods(Plugin $plugin): void {
		$className = $this->getPluginClassName($plugin->slug);

		if (!$plugin->pluginClassFileExists()) {
			return;
		}

		// Only require the file if the class doesn't already exist
		if (!class_exists($className)) {
			require_once $plugin->getPluginClassFile();
		}

		if (!class_exists($className, false)) {
			return;
		}

		global $aspen_db;

		// Clear existing methods for this plugin
		$stmt = $aspen_db->prepare("DELETE FROM plugin_methods WHERE plugin_class = ?");
		$stmt->execute([$className]);

		$reflection = new ReflectionClass($className);
		$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
		$timestamp = time();

		foreach ($methods as $method) {
			// Skip inherited methods from base class
			if ($method->class !== $className) {
				continue;
			}

			// Skip magic methods
			if (strpos($method->name, '__') === 0) {
				continue;
			}

			// Determine method type
			$methodType = $this->determineMethodType($method->name);

			// Register method
			$stmt = $aspen_db->prepare(
				"INSERT INTO plugin_methods (plugin_class, plugin_method, method_type, created)
				 VALUES (?, ?, ?, ?)"
			);
			$stmt->execute([$className, $method->name, $methodType, $timestamp]);
		}
	}

	/**
	 * Check if a plugin has a specific method
	 * @param Plugin $plugin Plugin to check
	 * @param string $method Method name to check
	 * @return bool True if method exists and is registered
	 */
	public function hasMethod(Plugin $plugin, string $method): bool {
		$className = $this->getPluginClassName($plugin->slug);

		global $aspen_db;

		$stmt = $aspen_db->prepare(
			"SELECT COUNT(*) as cnt FROM plugin_methods WHERE plugin_class = ? AND plugin_method = ?"
		);
		$stmt->execute([$className, $method]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row && $row['cnt'] > 0;
	}

	/**
	 * Determine method type based on method name
	 * @param string $methodName Method name
	 * @return string Method type ('lifecycle', 'page', 'hook', 'api')
	 */
	private function determineMethodType(string $methodName): string {
		$lifecycleMethods = [
			'onInstall', 'onUninstall', 'onEnable', 'onDisable'
		];

		$pageMethods = [
			'configure', 'tool', 'report', 'settings'
		];

		if (in_array($methodName, $lifecycleMethods)) {
			return 'lifecycle';
		}

		if (in_array($methodName, $pageMethods)) {
			return 'page';
		}

		if (strpos($methodName, 'api') === 0) {
			return 'api';
		}

		return 'hook';
	}

	/**
	 * Convert class name to slug
	 * @param string $className Class name
	 * @return string Plugin slug
	 */
	private function slugFromClassName(string $className): string {
		// Remove 'Plugin' suffix if present
		if (substr($className, -6) === 'Plugin') {
			$className = substr($className, 0, -6);
		}

		// Convert CamelCase to snake_case
		return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className));
	}

	/**
	 * Clean up all data for a plugin (called during uninstall)
	 * @param string $slug Plugin slug
	 */
	public function cleanupPluginData(string $slug): void {
		$className = $this->getPluginClassName($slug);

		global $aspen_db;

		// Clean up plugin_data table
		$stmt = $aspen_db->prepare("DELETE FROM plugin_data WHERE plugin_class = ?");
		$stmt->execute([$className]);

		// Clean up plugin_methods table
		$stmt = $aspen_db->prepare("DELETE FROM plugin_methods WHERE plugin_class = ?");
		$stmt->execute([$className]);
	}
} 