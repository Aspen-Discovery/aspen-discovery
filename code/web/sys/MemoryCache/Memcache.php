<?php

require_once ROOT_DIR . '/sys/MemoryCache/CachedValue.php';

class Memcache
{
	private $enableDbCache = true;
	private $vars = array();

	public function __destruct()
	{
		//Clear old expired memory cache entries.  This can be done VERY sporadically.
		$random = rand(0, 1000);
		if ($random == 1) {
			try {
				$cachedValue = new CachedValue();
				$cachedValue->whereAdd(false);
				$cachedValue->whereAdd("expirationTime <= " . time());
				$cachedValue->delete(true);
			} catch (Exception $e) {
				global $logger;
				$logger->log("Could not clear cache of old values", Logger::LOG_DEBUG);
			}
		}
	}

	public function get($name)
	{
		if (array_key_exists($name, $this->vars)) {
			return $this->vars[$name];
		} elseif ($this->enableDbCache) {
			try {
				$cachedValue = new CachedValue();
				$cachedValue->cacheKey = $name;
				if ($cachedValue->find(true)) {
					if ($cachedValue->expirationTime != 0 && $cachedValue->expirationTime < time()) {
						return false;
					} else {
						/** @noinspection PhpUnnecessaryLocalVariableInspection */
						$unSerializedValue = unserialize($cachedValue->value);
						return $unSerializedValue;
					}
				}
			} catch (Exception $e) {
				//Table has not been created ignore
			}
		}
		return false;
	}

	public function set($name, $value, $timeout)
	{
		$this->vars[$name] = $value;
		if ($this->enableDbCache) {
			$valueToCache = serialize($value);
			if (strlen($valueToCache) <= 16384 && strlen($name) < 200) {
				try {
					$cachedValue = new CachedValue();
					$cachedValue->cacheKey = $name;
					$isNew = true;
					if ($cachedValue->find(true)) {
						$isNew = false;
					}
					$cachedValue->value = $valueToCache;
					if ($timeout == 0) {
						$cachedValue->expirationTime = 0;
					} else {
						$cachedValue->expirationTime = time() + $timeout;
					}

					if ($isNew) {
						/** @noinspection PhpUnusedLocalVariableInspection */
						$result = $cachedValue->insert();
					} else {
						/** @noinspection PhpUnusedLocalVariableInspection */
						$result = $cachedValue->update();
					}
				} catch (Exception $e) {
					//Table has not been created ignore
					global $logger;
					$logger->log("error caching data", Logger::LOG_DEBUG);
				}
			} else {
				global $logger;
				$logger->log("data for $name was too large to be cached", Logger::LOG_WARNING);
				return false;
			}
		}
		return true;
	}

	/** @var CachedValue  */
	static $cachedValueCleaner = null;

	public function delete($name)
	{
		unset($this->vars[$name]);
		if ($this->enableDbCache) {
			try {
				if (Memcache::$cachedValueCleaner == null) {
					Memcache::$cachedValueCleaner = new CachedValue();
				}

				Memcache::$cachedValueCleaner->whereAdd();
				Memcache::$cachedValueCleaner->cacheKey = $name;
				Memcache::$cachedValueCleaner->delete(true);
			} catch (Exception $e) {
				//Table has not been created ignore
			}
		}
	}
}