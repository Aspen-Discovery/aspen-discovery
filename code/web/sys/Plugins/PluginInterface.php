<?php

require_once ROOT_DIR . '/sys/Plugins/Plugin.php';

abstract class PluginInterface {
	public abstract function getName() : string;

	public abstract function getAuthor() : string;

	public abstract function getDescription() : string;

	public abstract function getVersion() : string;

	public abstract function getMinAspenVersionRequired();

	public abstract function handlesModuleAction($module, $action);

	public abstract function handleAction($module, $action) : bool;

	public abstract function getPath() : string;

	public abstract function getDatabaseUpdates() : array;

	public abstract function getAdminActions() : array;


	private ?Plugin $_pluginObject = null;

	protected function getPluginObject() : Plugin {
		if ($this->_pluginObject === null) {
			$plugin = new Plugin();
			$plugin->name = $this->getName();
			if (!$plugin->find(true)) {
				$this->_pluginObject = $this->updatePluginInDB();
			} else {
				$this->_pluginObject = $plugin;
			}
		}
		return $this->_pluginObject;
	}

	public function isEnabled() : bool {
		$plugin = $this->getPluginObject();
		return $plugin->enabled;
	}

	protected function updatePluginInDB() : Plugin {
		$plugin = new Plugin();
		$plugin->name = $this->getName();
		if (!$plugin->find(true)){
			$plugin->enabled = 1;
			$plugin->version = $this->getVersion();
			$plugin->minAspenVersion = $this->getMinAspenVersionRequired();
			$plugin->description = $this->getDescription();
			$plugin->author = $this->getAuthor();
			$plugin->insert();
		}else{
			$plugin->__set('version', $this->getVersion());
			$plugin->__set('minAspenVersion', $this->getMinAspenVersionRequired());
			$plugin->__set('description', $this->getDescription());
			$plugin->__set('author', $this->getAuthor());
			$plugin->update();
		}
		return $plugin;
	}
}