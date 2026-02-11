<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Plugins/Plugin.php';
require_once ROOT_DIR . '/sys/Plugins/PluginManager.php';
require_once ROOT_DIR . '/sys/Plugins/AspenPlugin.php';

/**
 * Plugin Runner - Unified handler for plugin methods and static assets
 *
 * URL patterns:
 *   /plugins/{slug}/{method}     - Execute plugin method (configure, tool, report)
 *   /plugins/{slug}/path/to/file - Serve static asset from plugin directory
 *
 * Legacy support:
 *   /Plugins/Runner?slug={slug}&method={method}
 */
class Plugins_Runner extends Action {

	/** @var Plugin|null */
	private $plugin = null;

	/** @var string|null */
	private $methodName = null;

	/** @var string|null */
	private $slug = null;

	/** @var string|null */
	private $path = null;

	/**
	 * MIME types for static file serving
	 */
	private static array $mimeTypes = [
		'js' => 'application/javascript',
		'css' => 'text/css',
		'png' => 'image/png',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'gif' => 'image/gif',
		'svg' => 'image/svg+xml',
		'ico' => 'image/x-icon',
		'html' => 'text/html',
		'json' => 'application/json',
		'woff' => 'font/woff',
		'woff2' => 'font/woff2',
		'ttf' => 'font/ttf',
		'eot' => 'application/vnd.ms-fontobject',
	];

	/**
	 * Static file extensions that get caching headers
	 */
	private static array $cacheableExtensions = ['js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];

	/**
	 * Main entry point - route plugin requests
	 */
	public function launch(): void {
		// Parse parameters from either path-based or query string format
		$this->parseRequest();

		if (!$this->slug) {
			$this->display404('Missing plugin slug');
			return;
		}

		// Security: validate slug format (alphanumeric, underscore, hyphen only)
		if (!preg_match('/^[a-zA-Z0-9_-]+$/', $this->slug)) {
			$this->display404('Invalid plugin slug');
			return;
		}

		// Load plugin from database
		$plugin = new Plugin();
		$plugin->slug = $this->slug;
		if (!$plugin->find(true)) {
			$this->display404('Plugin not found');
			return;
		}
		$this->plugin = $plugin;

		// Determine if this is a method call or static file request
		if ($this->isStaticFileRequest()) {
			$this->serveStaticFile();
		} else {
			$this->executeMethod();
		}
	}

	/**
	 * Parse the request to extract slug and path/method
	 */
	private function parseRequest(): void {
		// Check for path-based URL first: /plugins/{slug}/{path...}
		$requestUri = $_SERVER['REQUEST_URI'] ?? '';
		$urlPath = parse_url($requestUri, PHP_URL_PATH);

		if (preg_match('~^/plugins/([^/]+)(?:/(.*))?$~i', $urlPath, $matches)) {
			$this->slug = $matches[1];
			$this->path = $matches[2] ?? '';
		} else {
			// Fall back to query string format: ?slug=xxx&method=yyy
			$this->slug = $_GET['slug'] ?? null;
			$this->path = $_GET['method'] ?? '';
		}
	}

	/**
	 * Check if the request is for a static file (vs a method call)
	 */
	private function isStaticFileRequest(): bool {
		if (empty($this->path)) {
			return false;
		}

		// If path has a file extension, it's likely a static file
		$extension = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
		if (!empty($extension)) {
			return true;
		}

		// Check if it's a registered plugin method
		$pluginManager = PluginManager::getInstance();
		if ($pluginManager->hasMethod($this->plugin, $this->path)) {
			return false;
		}

		// Default to treating as potential static file path
		return true;
	}

	/**
	 * Serve a static file from the plugin directory
	 */
	private function serveStaticFile(): void {
		// Security: prevent directory traversal
		if (strpos($this->path, '..') !== false || strpos($this->path, "\0") !== false) {
			$this->displayStaticFile404('Invalid file path');
			return;
		}

		// Build the full file path
		global $serverName;
		$pluginDataPath = "/data/aspen-discovery/$serverName/plugins";
		$fullFilePath = "$pluginDataPath/{$this->slug}/{$this->path}";

		// Normalize path and verify it's still within plugin directory
		$realPath = realpath($fullFilePath);
		$realPluginPath = realpath("$pluginDataPath/{$this->slug}");

		if ($realPath === false || $realPluginPath === false) {
			$this->displayStaticFile404('File not found');
			return;
		}

		if (strpos($realPath, $realPluginPath) !== 0) {
			$this->displayStaticFile404('Invalid file path');
			return;
		}

		if (!is_file($realPath) || !is_readable($realPath)) {
			$this->displayStaticFile404('File not found');
			return;
		}

		// Determine MIME type
		$extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
		$mimeType = self::$mimeTypes[$extension] ?? 'application/octet-stream';

		// Set headers
		header("Content-Type: $mimeType");
		header('Content-Length: ' . filesize($realPath));

		// Add caching headers for static assets
		if (in_array($extension, self::$cacheableExtensions)) {
			header('Cache-Control: public, max-age=86400'); // 1 day
			header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
		}

		// Output the file
		readfile($realPath);
		exit;
	}

	/**
	 * Execute a plugin method
	 */
	private function executeMethod(): void {
		global $interface;

		$method = $this->path;

		// Validate method exists
		if (empty($method)) {
			$this->display404('Missing method name');
			return;
		}

		// Security: validate method format (valid PHP identifier)
		if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $method)) {
			$this->display404('Invalid method name');
			return;
		}

		$this->methodName = $method;

		// Check if plugin is enabled (skip for configure method - allow configuring disabled plugins)
		if (!$this->plugin->isEnabled() && $method !== 'configure') {
			$this->displayError('Plugin is disabled');
			return;
		}

		// Get plugin manager
		$pluginManager = PluginManager::getInstance();

		// Check method exists in registry
		if (!$pluginManager->hasMethod($this->plugin, $method)) {
			$this->display404('Method not found for this plugin');
			return;
		}

		// Load and instantiate plugin
		try {
			$className = $pluginManager->getPluginClassName($this->slug);

			if (!$this->plugin->pluginClassFileExists()) {
				$this->displayError('Plugin class file not found');
				return;
			}

			require_once $this->plugin->getPluginClassFile();

			if (!class_exists($className, false)) {
				$this->displayError('Plugin class not found');
				return;
			}

			$pluginInstance = new $className($this->plugin);

			// Check method is public
			$reflection = new ReflectionMethod($pluginInstance, $method);
			if (!$reflection->isPublic()) {
				$this->display404('Method not accessible');
				return;
			}

			// Check method-specific permission
			$requiredPermission = $pluginInstance->getRequiredPermission($method);
			if ($requiredPermission && !UserAccount::userHasPermission($requiredPermission)) {
				$this->displayError('Insufficient permissions for this action');
				return;
			}

			// Set plugin context in interface for templates
			$interface->assign('plugin', $this->plugin);
			$interface->assign('pluginInstance', $pluginInstance);
			$interface->assign('pluginSlug', $this->slug);
			$interface->assign('pluginMethod', $method);

			// Execute plugin method (plugin handles output)
			$pluginInstance->$method();

		} catch (ReflectionException $e) {
			global $logger;
			if (isset($logger)) {
				$logger->log(
					"Plugin reflection error [{$this->slug}::{$method}]: " . $e->getMessage(),
					Logger::LOG_ERROR
				);
			}
			$this->display404('Method not found');
		} catch (Exception $e) {
			global $logger;
			if (isset($logger)) {
				$logger->log(
					"Plugin execution error [{$this->slug}::{$method}]: " . $e->getMessage(),
					Logger::LOG_ERROR
				);
			}
			$this->displayError('Plugin execution error: ' . $e->getMessage());
		} catch (Error $e) {
			global $logger;
			if (isset($logger)) {
				$logger->log(
					"Plugin fatal error [{$this->slug}::{$method}]: " . $e->getMessage(),
					Logger::LOG_ERROR
				);
			}
			$this->displayError('Plugin error occurred');
		}
	}

	/**
	 * Get breadcrumbs for navigation
	 * @return array Array of Breadcrumb objects
	 */
	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Plugins', 'Plugins');

		// Add plugin-specific breadcrumb if available
		if ($this->plugin) {
			$methodLabel = ucfirst($this->methodName ?? 'Plugin');
			$breadcrumbs[] = new Breadcrumb('', $this->plugin->name . ' - ' . $methodLabel);
		}

		return $breadcrumbs;
	}

	/**
	 * Display 404 error page (for method/page requests)
	 * Uses the standard Aspen Error_Handle404 pattern
	 * @param string $message Error message
	 */
	private function display404(string $message): void {
		global $interface;
		$interface->assign('module', 'Error');
		$interface->assign('action', 'Handle404');
		require_once ROOT_DIR . "/services/Error/Handle404.php";
		$actionClass = new Error_Handle404();
		$actionClass->launch();
		die();
	}

	/**
	 * Display simple 404 for static file requests
	 * Returns minimal response appropriate for asset requests
	 * @param string $message Error message
	 */
	private function displayStaticFile404(string $message): void {
		http_response_code(404);
		header('Content-Type: text/plain');
		echo $message;
		exit;
	}

	/**
	 * Display error page
	 * Uses the standard Aspen error handling pattern
	 * @param string $message Error message
	 */
	private function displayError(string $message): void {
		AspenError::raiseError(new AspenError($message));
	}
}
