<?php

require_once ROOT_DIR . '/sys/Plugins/AspenPlugin.php';

/**
 * Example Plugin for Aspen Discovery
 * Demonstrates various plugin capabilities
 */
class ExamplePlugin extends AspenPlugin {

	/**
	 * Called when the plugin is installed
	 */
	public function onInstall(): void {
		$this->log("Example Plugin installed successfully", Logger::LOG_NOTICE);
	}

	/**
	 * Called when the plugin is uninstalled
	 */
	public function onUninstall(): void {
		$this->log("Example Plugin uninstalled", Logger::LOG_NOTICE);
	}

	/**
	 * Called when the plugin is enabled
	 */
	public function onEnable(): void {
		$this->log("Example Plugin enabled", Logger::LOG_DEBUG);
	}

	/**
	 * Called when the plugin is disabled
	 */
	public function onDisable(): void {
		$this->log("Example Plugin disabled", Logger::LOG_DEBUG);
	}

	/**
	 * Hook: before page load
	 */
	public function beforePageLoad(array $data): void {
		// Example: Log when specific pages are accessed
		if (isset($data['module']) && $data['module'] === 'Search') {
			$this->log("Search page accessed", Logger::LOG_DEBUG);
		}
	}

	/**
	 * Hook: after page load
	 */
	public function afterPageLoad(array $data): void {
		// Example: Modify interface variables based on page
		if (isset($data['interface']) && isset($data['module'])) {
			$interface = $data['interface'];
			
			// Add a custom variable to all pages
			$interface->assign('examplePluginEnabled', true);
			
			// Add welcome message to home page if enabled in config
			if ($data['module'] === 'Search' && $this->getConfigValue('enableWelcomeMessage', false)) {
				$welcomeText = $this->getConfigValue('welcomeText', 'Welcome!');
				$interface->assign('exampleWelcomeMessage', $welcomeText);
			}
		}
	}

	/**
	 * Hook: inject JavaScript
	 */
	public function injectJavaScript(array $data): ?string {
		// Example: Add custom JavaScript to all pages
		return "
			console.log('Example Plugin loaded');
			
			// Add a custom function
			window.ExamplePlugin = {
				showWelcome: function() {
					var message = '" . addslashes($this->getConfigValue('welcomeText', 'Welcome to Aspen Discovery!')) . "';
					if ('" . ($this->getConfigValue('enableWelcomeMessage', false) ? 'true' : 'false') . "' === 'true') {
						console.log(message);
					}
				}
			};
			
			// Initialize when DOM is ready
			document.addEventListener('DOMContentLoaded', function() {
				window.ExamplePlugin.showWelcome();
			});
		";
	}

	/**
	 * Hook: inject CSS
	 */
	public function injectCSS(array $data): ?string {
		// Example: Add custom CSS
		return "
			.example-plugin-highlight {
				background-color: #fffacd;
				border: 1px solid #ffd700;
				padding: 5px;
				border-radius: 3px;
			}
			
			.example-plugin-banner {
				background: linear-gradient(90deg, #4CAF50, #45a049);
				color: white;
				padding: 10px;
				text-align: center;
				font-weight: bold;
			}
		";
	}

	/**
	 * Hook: user login
	 */
	public function onUserLogin(array $data): void {
		if (isset($data['user'])) {
			$user = $data['user'];
			$this->log("User {$user->id} logged in", Logger::LOG_DEBUG);
		}
	}

	/**
	 * Hook: user logout
	 */
	public function onUserLogout(array $data): void {
		if (isset($data['user'])) {
			$user = $data['user'];
			$this->log("User {$user->id} logged out", Logger::LOG_DEBUG);
		}
	}

	/**
	 * Hook: search results modification
	 */
	public function modifySearchResults(array $data): void {
		// Example: Log search terms
		if (isset($data['searchTerm'])) {
			$this->log("Search performed: " . $data['searchTerm'], Logger::LOG_DEBUG);
		}
	}
} 