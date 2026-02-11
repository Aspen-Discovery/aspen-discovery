<?php

require_once ROOT_DIR . '/sys/Plugins/Plugin.php';

class PluginManager {
	private static $instance = null;
	private $loadedPlugins = [];
	private $hooks = [];

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
				// Convert slug to proper class name (example_plugin -> ExamplePlugin)
				$slugParts = explode('_', $plugin->slug);
				$pluginClassName = '';
				foreach ($slugParts as $part) {
					$pluginClassName .= ucfirst($part);
				}
				$pluginClassName .= 'Plugin';
					
					if (class_exists($pluginClassName)) {
						$pluginInstance = new $pluginClassName($plugin);
						$this->loadedPlugins[$plugin->slug] = $pluginInstance;
						
						// Register hooks for this plugin
						$hookPoints = $plugin->getHookPointsArray();
						foreach ($hookPoints as $hookPoint) {
							if (!isset($this->hooks[$hookPoint])) {
								$this->hooks[$hookPoint] = [];
							}
							$this->hooks[$hookPoint][] = $pluginInstance;
						}
					}
				} catch (Exception $e) {
					// Log error but continue
					global $logger;
					if (isset($logger)) {
						$logger->log("Error loading plugin {$plugin->slug}: " . $e->getMessage(), Logger::LOG_ERROR);
					}
				}
			}
		}
	}

	/**
	 * Install a plugin from directory or .plugzip file
	 */
	public function installPlugin(string $pluginPath): array {
		$isZipFile = false;
		$tempExtractDir = null;
		
		// Check if it's a .plugzip file
		if (is_file($pluginPath) && strtolower(pathinfo($pluginPath, PATHINFO_EXTENSION)) === 'plugzip') {
			$isZipFile = true;
			$tempExtractDir = sys_get_temp_dir() . '/aspen_plugin_' . time() . '_' . rand(1000, 9999);
			
			if (!$this->extractPluginZip($pluginPath, $tempExtractDir)) {
				return ['success' => false, 'message' => 'Failed to extract plugin zip file'];
			}
			
			$pluginPath = $tempExtractDir;
		}

		if (!is_dir($pluginPath)) {
			return ['success' => false, 'message' => 'Plugin directory not found'];
		}

		$manifestFile = $pluginPath . '/manifest.json';
		if (!file_exists($manifestFile)) {
			if ($tempExtractDir) {
				$this->removeDirectory($tempExtractDir);
			}
			return ['success' => false, 'message' => 'Plugin manifest.json not found'];
		}

		$manifest = json_decode(file_get_contents($manifestFile), true);
		if (!$manifest) {
			return ['success' => false, 'message' => 'Invalid manifest.json'];
		}

		// Validate required manifest fields
		$requiredFields = ['name', 'slug', 'version', 'description', 'author'];
		foreach ($requiredFields as $field) {
			if (empty($manifest[$field])) {
				return ['success' => false, 'message' => "Missing required field: $field"];
			}
		}

		// Check if plugin already exists
		$existingPlugin = new Plugin();
		$existingPlugin->slug = $manifest['slug'];
		if ($existingPlugin->find(true)) {
			if ($tempExtractDir) {
				$this->removeDirectory($tempExtractDir);
			}
			return ['success' => false, 'message' => 'Plugin with this slug already exists'];
		}

		// Create plugin directory in the correct location
		global $configArray;
		$local = $configArray['Site']['local'];
		$targetPath = "$local/plugins/{$manifest['slug']}";
		
		if (!is_dir("$local/plugins")) {
			mkdir("$local/plugins", 0755, true);
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
		$plugin->name = $manifest['name'];
		$plugin->slug = $manifest['slug'];
		$plugin->version = $manifest['version'];
		$plugin->description = $manifest['description'];
		$plugin->author = $manifest['author'];
		$plugin->status = 0; // disabled by default
		$plugin->hasJsInjection = isset($manifest['jsFiles']) && !empty($manifest['jsFiles']) ? 1 : 0;
		
		if (isset($manifest['jsFiles'])) {
			$plugin->setJsFilesArray($manifest['jsFiles']);
		}
		if (isset($manifest['cssFiles'])) {
			$plugin->setCssFilesArray($manifest['cssFiles']);
		}
		if (isset($manifest['hookPoints'])) {
			$plugin->setHookPointsArray($manifest['hookPoints']);
		}
		if (isset($manifest['config'])) {
			$plugin->setConfigArray($manifest['config']);
		}

		if ($plugin->insert()) {
			// Run plugin installation hook if it exists
			$pluginClassFile = $targetPath . "/{$manifest['slug']}.php";
			if (file_exists($pluginClassFile)) {
				require_once $pluginClassFile;
				// Convert slug to proper class name (example_plugin -> ExamplePlugin)
				$slugParts = explode('_', $manifest['slug']);
				$pluginClassName = '';
				foreach ($slugParts as $part) {
					$pluginClassName .= ucfirst($part);
				}
				$pluginClassName .= 'Plugin';
				if (class_exists($pluginClassName) && method_exists($pluginClassName, 'onInstall')) {
					$pluginInstance = new $pluginClassName($plugin);
					$pluginInstance->onInstall();
				}
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
		
		// Clean up temporary extraction directory if we used one
		if ($tempExtractDir) {
			$this->removeDirectory($tempExtractDir);
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

		// Run plugin uninstall hook if it exists
		if ($plugin->pluginDirectoryExists() && $plugin->pluginClassFileExists()) {
			try {
				require_once $plugin->getPluginClassFile();
				// Convert slug to proper class name (example_plugin -> ExamplePlugin)
				$slugParts = explode('_', $slug);
				$pluginClassName = '';
				foreach ($slugParts as $part) {
					$pluginClassName .= ucfirst($part);
				}
				$pluginClassName .= 'Plugin';
				if (class_exists($pluginClassName) && method_exists($pluginClassName, 'onUninstall')) {
					$pluginInstance = new $pluginClassName($plugin);
					$pluginInstance->onUninstall();
				}
			} catch (Exception $e) {
				// Log error but continue with uninstall
				global $logger;
				if (isset($logger)) {
					$logger->log("Error running uninstall hook for plugin $slug: " . $e->getMessage(), Logger::LOG_ERROR);
				}
			}
		}

		// Remove plugin directory
		if ($plugin->pluginDirectoryExists()) {
			$this->removeDirectory($plugin->getPluginDirectory());
		}

		// Remove database entry
		if ($plugin->delete()) {
			return ['success' => true, 'message' => 'Plugin uninstalled successfully'];
		} else {
			return ['success' => false, 'message' => 'Failed to remove plugin database entry'];
		}
	}

	/**
	 * Execute hooks for a given hook point
	 */
	public function executeHook(string $hookPoint, array $data = []): array {
		$results = [];
		
		if (isset($this->hooks[$hookPoint])) {
			foreach ($this->hooks[$hookPoint] as $pluginInstance) {
				try {
					if (method_exists($pluginInstance, $hookPoint)) {
						$result = $pluginInstance->$hookPoint($data);
						$results[] = $result;
					}
				} catch (Exception $e) {
					global $logger;
					if (isset($logger)) {
						$logger->log("Error executing hook $hookPoint: " . $e->getMessage(), Logger::LOG_ERROR);
					}
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
			$plugin = new Plugin();
			$plugin->slug = $slug;
			if ($plugin->find(true) && $plugin->hasJsInjection) {
				$pluginJsFiles = $plugin->getJsFilesArray();
				foreach ($pluginJsFiles as $jsFile) {
					$jsFiles[] = "/plugins/$slug/$jsFile";
				}
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
			$plugin = new Plugin();
			$plugin->slug = $slug;
			if ($plugin->find(true)) {
				$pluginCssFiles = $plugin->getCssFilesArray();
				foreach ($pluginCssFiles as $cssFile) {
					$cssFiles[] = "/plugins/$slug/$cssFile";
				}
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
	 * Create a .plugzip file from a plugin directory
	 */
	public function createPluginZip(string $pluginDirectory, string $outputPath): bool {
		if (!class_exists('ZipArchive')) {
			return false;
		}

		if (!is_dir($pluginDirectory)) {
			return false;
		}

		$zip = new ZipArchive();
		$result = $zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
		
		if ($result !== TRUE) {
			return false;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($pluginDirectory, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $file) {
			if ($file->isDir()) {
				$zip->addEmptyDir($iterator->getSubPathName());
			} else {
				$zip->addFile($file, $iterator->getSubPathName());
			}
		}

		return $zip->close();
	}
} 