<?php

require_once ROOT_DIR . '/sys/Plugins/Plugin.php';

/**
 * Base class for all Aspen Discovery plugins
 * All plugins should extend this class
 */
abstract class AspenPlugin {

	/** @var Plugin */
	protected $pluginData;

	/** @var array|null Cached manifest data from plugin.yaml */
	private $manifest = null;

	// Default permission requirements for standard method types
	protected const DEFAULT_METHOD_PERMISSIONS = [
		'configure' => 'Administer Plugins',
	];
	
	public function __construct(Plugin $pluginData) {
		$this->pluginData = $pluginData;
	}

	/**
	 * Load and cache the plugin.yaml manifest
	 * @return array Parsed manifest data, or empty array if not found
	 */
	protected function loadManifest(): array {
		if ($this->manifest !== null) {
			return $this->manifest;
		}

		$manifestPath = $this->getPluginDirectory() . '/plugin.yaml';
		if (!file_exists($manifestPath)) {
			$this->manifest = [];
			return $this->manifest;
		}

		try {
			require_once ROOT_DIR . '/sys/Yaml.php';
			$yaml = new Yaml();
			$this->manifest = $yaml->load($manifestPath);
			if (!is_array($this->manifest)) {
				$this->manifest = [];
			}
		} catch (Exception $e) {
			$this->log("Error loading plugin.yaml: " . $e->getMessage(), Logger::LOG_ERROR);
			$this->manifest = [];
		}

		return $this->manifest;
	}
	
	/**
	 * Get the plugin data object
	 */
	public function getPluginData(): Plugin {
		return $this->pluginData;
	}
	
	/**
	 * Get plugin configuration
	 */
	public function getConfig(): array {
		return $this->pluginData->getConfigArray();
	}
	
	/**
	 * Get a specific configuration value
	 */
	public function getConfigValue(string $key, $default = null) {
		$config = $this->getConfig();
		return $config[$key] ?? $default;
	}
	
	/**
	 * Set a configuration value
	 */
	public function setConfigValue(string $key, $value): void {
		$config = $this->getConfig();
		$config[$key] = $value;
		$this->pluginData->setConfigArray($config);
		$this->pluginData->update();
	}
	
	/**
	 * Get the plugin directory path
	 */
	public function getPluginDirectory(): string {
		return $this->pluginData->getPluginDirectory();
	}
	
	/**
	 * Get URL path to plugin assets
	 */
	public function getPluginUrl(): string {
		return "/plugins/{$this->pluginData->slug}";
	}
	
	/**
	 * Called when the plugin is installed
	 * Override this method to perform installation tasks
	 */
	public function onInstall(): void {
		// Default implementation does nothing
	}
	
	/**
	 * Called when the plugin is uninstalled
	 * Override this method to perform cleanup tasks
	 */
	public function onUninstall(): void {
		// Default implementation does nothing
	}
	
	/**
	 * Called when the plugin is enabled
	 * Override this method to perform activation tasks
	 */
	public function onEnable(): void {
		// Default implementation does nothing
	}
	
	/**
	 * Called when the plugin is disabled
	 * Override this method to perform deactivation tasks
	 */
	public function onDisable(): void {
		// Default implementation does nothing
	}
	
	/**
	 * Hook: add JavaScript
	 * Called to inject custom JavaScript
	 * 
	 * @param array $data Contains 'page', 'interface' keys
	 * @return string|null JavaScript code to inject
	 */
	public function injectJavaScript(array $data): ?string {
		// Default implementation returns nothing
		return null;
	}
	
	/**
	 * Hook: add CSS
	 * Called to inject custom CSS
	 * 
	 * @param array $data Contains 'page', 'interface' keys
	 * @return string|null CSS code to inject
	 */
	public function injectCSS(array $data): ?string {
		// Default implementation returns nothing
		return null;
	}
	
	/**
	 * Get JavaScript files to inject - override this in your plugin
	 * @return array Array of relative file paths (e.g., ['js/example.js'])
	 */
	public function getJavaScriptFiles(): array {
		return [];
	}

	/**
	 * Get CSS files to inject - override this in your plugin
	 * @return array Array of relative file paths (e.g., ['css/example.css'])
	 */
	public function getCssFiles(): array {
		return [];
	}
	
	/**
	 * Log a message to Aspen's logging system
	 */
	protected function log(string $message, int $level = Logger::LOG_DEBUG): void {
		global $logger;
		if (isset($logger)) {
			$logger->log("[Plugin:{$this->pluginData->slug}] $message", $level);
		}
	}
	
	/**
	 * Check if the plugin is enabled
	 */
	public function isEnabled(): bool {
		return $this->pluginData->isEnabled();
	}
	
	/**
	 * Get plugin metadata from plugin.yaml manifest
	 * @return array
	 */
	public function getMetadata(): array {
		$manifest = $this->loadManifest();
		return [
			'name' => $manifest['name'] ?? 'Unknown Plugin',
			'version' => $manifest['version'] ?? '1.0.0',
			'description' => $manifest['description'] ?? 'No description provided',
			'author' => $manifest['author'] ?? 'Unknown Author',
			'lastModified' => $manifest['lastModified'] ?? null,
			'minAspenVersion' => $manifest['minAspenVersion'] ?? null,
			'maxAspenVersion' => $manifest['maxAspenVersion'] ?? null,
		];
	}

	/**
	 * Get plugin slug from plugin.yaml manifest
	 * @return string
	 */
	public function getSlug(): string {
		$manifest = $this->loadManifest();
		if (!empty($manifest['slug'])) {
			return $manifest['slug'];
		}
		// Fallback: derive slug from class name
		$className = get_class($this);
		if (substr($className, -6) === 'Plugin') {
			$className = substr($className, 0, -6);
		}
		return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className));
	}

	/**
	 * Get plugin version
	 */
	public function getVersion(): string {
		$manifest = $this->loadManifest();
		return $manifest['version'] ?? '1.0.0';
	}

	/**
	 * Get plugin name
	 */
	public function getName(): string {
		$manifest = $this->loadManifest();
		return $manifest['name'] ?? 'Unknown Plugin';
	}

	// ============================================================
	// Data Storage Methods (plugin_data table)
	// ============================================================

	/**
	 * Store multiple key-value pairs to plugin_data table
	 * @param array $data Key-value pairs to store
	 */
	public function storeData(array $data): void {
		global $aspen_db;
		$pluginClass = get_class($this);
		$timestamp = time();

		foreach ($data as $key => $value) {
			// Insert new records or update existing ones, preserving the original 'created' timestamp
			$stmt = $aspen_db->prepare(
				"INSERT INTO plugin_data (plugin_class, plugin_key, plugin_value, created, updated)
				 VALUES (?, ?, ?, ?, ?)
				 ON DUPLICATE KEY UPDATE plugin_value = VALUES(plugin_value), updated = VALUES(updated)"
			);
			$stmt->execute([$pluginClass, $key, $value, $timestamp, $timestamp]);
		}
	}

	/**
	 * Retrieve a single value by key from plugin_data table
	 * @param string $key The key to retrieve
	 * @return string|null The value or null if not found
	 */
	public function retrieveData(string $key): ?string {
		global $aspen_db;
		$pluginClass = get_class($this);

		$stmt = $aspen_db->prepare(
			"SELECT plugin_value FROM plugin_data WHERE plugin_class = ? AND plugin_key = ?"
		);
		$stmt->execute([$pluginClass, $key]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ? $row['plugin_value'] : null;
	}

	/**
	 * Retrieve all data for this plugin from plugin_data table
	 * @return array Associative array of key => value pairs
	 */
	public function retrieveAllData(): array {
		global $aspen_db;
		$pluginClass = get_class($this);

		$stmt = $aspen_db->prepare(
			"SELECT plugin_key, plugin_value FROM plugin_data WHERE plugin_class = ?"
		);
		$stmt->execute([$pluginClass]);

		$data = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$data[$row['plugin_key']] = $row['plugin_value'];
		}
		return $data;
	}

	/**
	 * Delete a specific key from plugin_data table
	 * @param string $key The key to delete
	 * @return bool True if deleted, false if not found
	 */
	public function deleteData(string $key): bool {
		global $aspen_db;
		$pluginClass = get_class($this);

		$stmt = $aspen_db->prepare(
			"DELETE FROM plugin_data WHERE plugin_class = ? AND plugin_key = ?"
		);
		$stmt->execute([$pluginClass, $key]);

		return $stmt->rowCount() > 0;
	}

	/**
	 * Delete all data for this plugin from plugin_data table
	 * @return bool True if successful
	 */
	public function deleteAllData(): bool {
		global $aspen_db;
		$pluginClass = get_class($this);

		$stmt = $aspen_db->prepare(
			"DELETE FROM plugin_data WHERE plugin_class = ?"
		);
		return $stmt->execute([$pluginClass]);
	}

	/**
	 * Check if a key exists in plugin_data table
	 * @param string $key The key to check
	 * @return bool True if exists
	 */
	public function hasData(string $key): bool {
		global $aspen_db;
		$pluginClass = get_class($this);

		$stmt = $aspen_db->prepare(
			"SELECT COUNT(*) as cnt FROM plugin_data WHERE plugin_class = ? AND plugin_key = ?"
		);
		$stmt->execute([$pluginClass, $key]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row && $row['cnt'] > 0;
	}

	// ============================================================
	// Template System Methods
	// ============================================================

	/**
	 * Get Smarty interface configured for plugin use
	 * @return UInterface Configured Smarty interface
	 */
	protected function getInterface(): UInterface {
		global $interface;

		// Add plugin template directory to Smarty search path
		$pluginTemplateDir = $this->getPluginDirectory() . '/templates';
		if (is_dir($pluginTemplateDir)) {
			// Get current template dir(s) and prepend plugin dir
			$currentDirs = $interface->getTemplateDir();
			if (is_string($currentDirs)) {
				$currentDirs = [$currentDirs];
			}
			array_unshift($currentDirs, $pluginTemplateDir);
			$interface->setTemplateDir($currentDirs);
		}

		// Assign plugin-specific variables
		$interface->assign('PLUGIN_CLASS', get_class($this));
		$interface->assign('PLUGIN_SLUG', $this->pluginData->slug);
		$interface->assign('PLUGIN_NAME', $this->getName());
		$interface->assign('PLUGIN_PATH', $this->getPluginUrl());
		$interface->assign('PLUGIN_DIR', $this->getPluginDirectory());

		return $interface;
	}

	/**
	 * Render a template within Aspen's admin layout (with sidebar)
	 * @param string $template Template filename relative to plugin templates directory
	 * @param string|null $pageTitle Optional page title
	 */
	protected function displayTemplate(string $template, string $pageTitle = null): void {
		global $library;
		$interface = $this->getInterface();

		$title = $pageTitle ?? $this->getName();
		if ($library) {
			$title .= ' | ' . $library->displayName;
		}

		$interface->assign('pageTitle', $title);
		$interface->setPageTitle($title);
		$interface->assign('sidebar', 'Admin/admin-sidebar.tpl');

		// Set up admin context for the sidebar to render properly
		$user = UserAccount::getActiveUserObj();
		if ($user) {
			$adminActions = $user->getAdminActions();
			$interface->assign('adminActions', $adminActions);
		}
		$interface->assign('activeAdminSection', 'system_administration');
		$interface->assign('activeMenuOption', 'admin');
		$interface->assign('showContentAsFullWidth', true);

		// Set up breadcrumbs
		require_once ROOT_DIR . '/sys/Breadcrumb.php';
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_administration', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/Plugins', 'Plugin Management');
		$breadcrumbs[] = new Breadcrumb('', $this->getName());
		$interface->assign('breadcrumbs', $breadcrumbs);
		$interface->assign('showBreadcrumbs', true);

		// Clear module so Smarty looks directly in template search path (including plugin templates dir)
		$interface->assign('module', '');
		$interface->setTemplate($template);
		$interface->display('layout.tpl');
		exit;
	}

	/**
	 * Render a standalone template without Aspen's layout (no header, sidebar, or footer)
	 * Use this for full-page plugin experiences or custom layouts
	 * @param string $template Template filename relative to plugin templates directory
	 * @param string|null $pageTitle Optional page title (available as $pageTitle in template)
	 */
	protected function displayStandaloneTemplate(string $template, string $pageTitle = null): void {
		$interface = $this->getInterface();

		if ($pageTitle) {
			$interface->assign('pageTitle', $pageTitle);
		}

		// Fetch and output the template directly, bypassing layout.tpl
		$html = $interface->fetch($template);
		$this->outputHtml($html);
	}

	/**
	 * Get URL for a plugin method
	 * @param string $method Method name
	 * @param array $params Optional query parameters
	 * @return string Full URL
	 */
	protected function getMethodUrl(string $method, array $params = []): string {
		$url = "/plugins/{$this->pluginData->slug}/$method";

		if (!empty($params)) {
			$url .= '?' . http_build_query($params);
		}

		return $url;
	}

	// ============================================================
	// HTTP Response Methods
	// ============================================================

	/**
	 * Output JSON response
	 * @param array $data Data to encode as JSON
	 * @param int $statusCode HTTP status code (default 200)
	 */
	protected function outputJson(array $data, int $statusCode = 200): void {
		http_response_code($statusCode);
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		exit;
	}

	/**
	 * Output HTML directly
	 * @param string $html HTML content
	 * @param int $statusCode HTTP status code (default 200)
	 */
	protected function outputHtml(string $html, int $statusCode = 200): void {
		http_response_code($statusCode);
		header('Content-Type: text/html; charset=UTF-8');
		echo $html;
		exit;
	}

	/**
	 * Redirect to URL
	 * @param string $url Target URL
	 */
	protected function redirect(string $url): void {
		header("Location: $url");
		exit;
	}

	/**
	 * Get required permission for a method
	 * Override in plugin to customize
	 * @param string $method Method name
	 * @return string|null Permission name or null for no check
	 */
	public function getRequiredPermission(string $method): ?string {
		return self::DEFAULT_METHOD_PERMISSIONS[$method] ?? 'Use Plugins';
	}
} 