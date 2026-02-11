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
	 * Hook: before page load
	 * Called before any page is loaded
	 * 
	 * @param array $data Contains 'module', 'action', 'method' keys
	 */
	public function beforePageLoad(array $data): void {
		// Default implementation does nothing
	}
	
	/**
	 * Hook: after page load
	 * Called after page is loaded but before display
	 * 
	 * @param array $data Contains 'module', 'action', 'method', 'interface' keys
	 */
	public function afterPageLoad(array $data): void {
		// Default implementation does nothing
	}
	
	/**
	 * Hook: before template display
	 * Called before the template is displayed
	 * 
	 * @param array $data Contains 'template', 'interface' keys
	 */
	public function beforeTemplateDisplay(array $data): void {
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
	 * Hook: search results modification
	 * Called to modify search results
	 * 
	 * @param array $data Contains 'results', 'searchTerm', 'interface' keys
	 */
	public function modifySearchResults(array $data): void {
		// Default implementation does nothing
	}
	
	/**
	 * Hook: user login
	 * Called when a user logs in
	 * 
	 * @param array $data Contains 'user', 'interface' keys
	 */
	public function onUserLogin(array $data): void {
		// Default implementation does nothing
	}
	
	/**
	 * Hook: user logout
	 * Called when a user logs out
	 * 
	 * @param array $data Contains 'user', 'interface' keys
	 */
	public function onUserLogout(array $data): void {
		// Default implementation does nothing
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
	 * Get plugin version
	 */
	public function getVersion(): string {
		return $this->pluginData->version;
	}
	
	/**
	 * Get plugin name
	 */
	public function getName(): string {
		return $this->pluginData->name;
	}
	
	/**
	 * Get plugin slug
	 */
	public function getSlug(): string {
		return $this->pluginData->slug;
	}
} 