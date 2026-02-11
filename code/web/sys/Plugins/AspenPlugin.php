<?php

require_once ROOT_DIR . '/sys/Plugins/Plugin.php';

/**
 * Base class for all Aspen Discovery plugins
 * All plugins should extend this class
 */
abstract class AspenPlugin {
	
	/** @var Plugin */
	protected $pluginData;
	
	public function __construct(Plugin $pluginData) {
		$this->pluginData = $pluginData;
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
	 * Get plugin metadata - override this in your plugin
	 * @return array
	 */
	public function getMetadata(): array {
		return [
			'name' => 'Unknown Plugin',
			'version' => '1.0.0',
			'description' => 'No description provided',
			'author' => 'Unknown Author',
			'dateCreated' => null,
			'lastModified' => null,
			'minAspenVersion' => null,
			'maxAspenVersion' => null,
		];
	}

	/**
	 * Get plugin slug - override this in your plugin
	 * @return string
	 */
	public function getSlug(): string {
		// Default implementation derives slug from class name
		$className = get_class($this);
		// Remove 'Plugin' suffix if present
		if (substr($className, -6) === 'Plugin') {
			$className = substr($className, 0, -6);
		}
		// Convert CamelCase to snake_case
		return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className));
	}

	/**
	 * Get plugin version
	 */
	public function getVersion(): string {
		$metadata = $this->getMetadata();
		return $metadata['version'] ?? '1.0.0';
	}

	/**
	 * Get plugin name
	 */
	public function getName(): string {
		$metadata = $this->getMetadata();
		return $metadata['name'] ?? 'Unknown Plugin';
	}
} 