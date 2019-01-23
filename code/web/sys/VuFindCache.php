<?php
/**
 * Cache functionality.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2011.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Support_Classes
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/system_classes Wiki
 */

/**
 * Cache functionality.
 *
 * @category VuFind
 * @package  Support_Classes
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/system_classes Wiki
 */
class VuFindCache
{
    protected $type;
    protected $name;

    /**
     * Constructor
     *
     * @param string $type Type of cache to use (APC or File)
     * @param string $name String identifier for this cache (may be used for
     * directory or key naming purposes).
     *
     * @access public
     */
    public function __construct($type, $name)
    {
        $this->type = $type;
        $this->name = $name;
    }

    /**
     * Support method -- get disk-based path for storing/retrieving data in file
     * mode.
     *
     * @param string $key       Cache key to store
     * @param bool   $createDir If true, create directory if it does not exist
     *
     * @return string
     * @access protected
     */
    protected function getFilename($key, $createDir = true)
    {
        $dirName = dirname(__FILE__) . '/../interface/cache/' . $this->name;
        if ($createDir && !is_dir($dirName)) {
            mkdir($dirName);
        }
        return $dirName . '/' . $key;
    }

    /**
     * Save an object to the cache.
     *
     * @param mixed  $data Data to store
     * @param string $key  Cache key to use
     *
     * @return void
     * @access public
     */
    public function save($data, $key)
    {
        switch ($this->type) {
        case 'APC':
            apc_store($key, serialize($data));
            break;
        case 'File':
            file_put_contents($this->getFilename($key), serialize($data));
            break;
        }
    }

    /**
     * Load an object from the cache.
     *
     * @param string $key Cache key to retrieve
     *
     * @return mixed      Requested data on success, boolean false if unavailable.
     * @access public
     */
    public function load($key)
    {
        switch ($this->type) {
        case 'APC':
            $data = apc_fetch($key);
            break;
        case 'File':
            $path = $this->getFilename($key, false);
            $data = file_exists($path) ?
                file_get_contents($path) : false;
            break;
        default:
            $data = false;
            break;
        }
        return $data === false ? false : unserialize($data);
    }
}
?>
