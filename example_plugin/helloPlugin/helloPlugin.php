<?php /** @noinspection PhpUnused */
const HELLOPLUGIN_ROOT = __DIR__;
global $aspen_root;
require_once ROOT_DIR . '/sys/Plugins/PluginInterface.php';

class helloPlugin extends PluginInterface {
	public function __construct(string $aspenRoot) {
		global $aspen_root;
		$aspen_root = $aspenRoot;
	}
	public function getName() : string {
		return 'Hello Plugin';
	}

	public function getAuthor() : string{
		return 'Aspen Community';
	}

	public function getDescription() : string{
		return "A sample reference plugin, doesn't have actual functionality.";
	}

	public function getVersion() : string {
		return '1.0.0';
	}

	public function getMinAspenVersionRequired() : string {
		return '26.05.00';
	}

	public function handlesModuleAction($module, $action) : bool {
		if (file_exists(HELLOPLUGIN_ROOT . '/services/' . $module . '/' . $action . '.php')) {
			return true;
		}
		return false;
	}

	public function handleAction($module, $action) : bool {
		require_once(HELLOPLUGIN_ROOT . '/services/' . $module . '/' . $action . '.php');
		$moduleActionClass = "{$module}_$action";
		if (class_exists($moduleActionClass, false)) {
			/** @var Action $service */
			$service = new $moduleActionClass();
			global $timer;
			$timer->logTime('Start launch of action');
			try {
				$service->launch();
			} catch (Error $e) {
				$backtrace[] = [
					'file' => $e->getFile(),
					'line' => $e->getLine(),
				];
				$backtrace = array_merge($backtrace, $e->getTrace());
				AspenError::raiseError(new AspenError($e->getMessage(), $backtrace));
			} catch (Exception $e) {
				AspenError::raiseError(new AspenError($e->getMessage(), $e->getTrace()));
			}
			$timer->logTime('Finish launch of action');
		}
		return false;
	}

	public function getPath() : string {
		return HELLOPLUGIN_ROOT;
	}

	public function getDatabaseUpdates() : array {
		return [];
	}

	public function getAdminActions() : array {
		return [];
	}
}