<?php
function loadPlugins() : array {
	global $configArray;
	$plugins = [];
	if (!empty($configArray['Plugins']) && !empty($configArray['Plugins']['enabled'])) {
		$pluginPath = $configArray['Plugins']['path'];
		if (file_exists($pluginPath)) {
			$pluginDirs = scandir($pluginPath);
			foreach ($pluginDirs as $pluginDir) {
				if ($pluginDir != '.' && $pluginDir != '..') {
					if (is_dir($pluginPath . '/' . $pluginDir)) {
						if (file_exists($pluginPath . '/' . $pluginDir . "/$pluginDir.php")) {
							require_once $pluginPath . '/' . $pluginDir . "/$pluginDir.php";
							$plugin = new $pluginDir($configArray['Site']['local']);
							if ($plugin instanceof PluginInterface && $plugin->isEnabled()) {
								$plugins[$pluginDir] = $plugin;
							}
						}
					}
				}
			}
		}
	}
	return $plugins;
}