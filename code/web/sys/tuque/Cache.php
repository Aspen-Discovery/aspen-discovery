<?php
/**
 * @file
 * Simple set of classes defining caches. An abstract cache is defined so that
 * we can use a more interesting caching setup in the future like memcached or
 * the native drupal cache.
 */

/**
 * Simple abstract Cache defintion providing basic key value caching
 * functionality.
 */
abstract class AbstractCache {
  /**
   * Add data to the cache.
   *
   * @param string $key
   *   The key to add to the cache.
   * @param mixed $data
   *   The data to store with the key.
   *
   * @return boolean
   *   TRUE if the data was added to the cache. FALSE if $key already exists in
   *   the cache or if there was an error.
   */
  abstract public function add($key, $data);

  /**
   * Retrieve data from the cache.
   *
   * @param string $key
   *   They key to retrieve from the cache.
   *
   * @return mixed
   *   FALSE if the data wasn't found in the cache. Otherwise it returns the
   *   data assoctiated with the key.
   */
  abstract public function get($key);

  /**
   * Set data in the cache.
   *
   * This will create new keys if they don't already exist, or update existing
   * keys.
   *
   * @param string $key
   *   The key to add/update.
   * @param mixed $data
   *   The data to store with the key.
   *
   * @return boolean
   *   TRUE on success. FALSE on failure.
   */
  abstract public function set($key, $data);

  /**
   * Delete key from the cache.
   *
   * @param string $key
   *   The key to delete.
   *
   * @return boolean
   *   TRUE if they key existed and was deleted. FALSE otherwise.
   */
  abstract public function delete($key);
}

/**
 * This is a simple cache that uses a static array to hold the cached values.
 * This means that it will cache across instantiations in the same PHP runtime
 * but not across runtimes. The cache has 100 slots and uses a simple LIFO
 * caching strategy.
 *
 * @todo Replace this with something more interesting like memcached
 * @todo Try some other intersting caching strategies like LRU.
 */
class SimpleCache extends AbstractCache {
  const CACHESIZE = 100;

  protected static $cache = array();
  protected static $entries = array();
  protected static $size = SimpleCache::CACHESIZE;

  /**
   * Set the cache size for the cache. If the size if bigger the cache size
   * is just made bigger. If its smaller, the cache is flushed and the cache
   * size is made smaller.
   *
   * @param int $size
   *   The new size of the cache.
   */
  static function setCacheSize($size) {
    if ($size > self::$size) {
      self::$size = $size;
    }
    else {
      self::$cache = array();
      self::$entries = array();
      self::$size = $size;
    }
  }

  /**
   * Reset the cache flushing it and returning it to its default size.
   */
  static function resetCache() {
    self::$cache = array();
    self::$entries = array();
    self::$size = self::CACHESIZE;
  }

  /**
   * @see AbstractCache::get
   */
  function get($key) {
    if (array_key_exists($key, self::$cache)) {
      return self::$cache[$key];
    }
    return FALSE;
  }

  /**
   * @see AbstractCache::add
   */
  function add($key, $data) {
    if ($this->get($key) !== FALSE) {
      return FALSE;
    }
    self::$cache[$key] = $data;
    $num = array_push(self::$entries, $key);

    if ($num > self::$size) {
      $evictedkey = array_shift(self::$entries);
      unset(self::$cache[$evictedkey]);
    }

    return TRUE;
  }

  /**
   * @see AbstractCache::set
   */
  function set($key, $data) {
    if ($this->add($key, $data) === FALSE) {
      self::$cache[$key] = $data;
    }
    return TRUE;
  }

  /**
   * @see AbstractCache::delete
   */
  function delete($key) {
    if (!array_key_exists($key, self::$cache)) {
      return FALSE;
    }
    $entrykey = array_search($key, self::$entries);
    unset(self::$cache[$key]);
    unset(self::$entries[$entrykey]);
    return TRUE;
  }
}
