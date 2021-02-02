<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class SearchEntry extends DataObject
{
	public $__table = 'search';
	public $id;
	public $user_id;
	public $created;
	public $saved;
	public $search_object;
	public $session_id;
	public $searchSource;
	public $searchUrl;

    /**
     * Get an array of SearchEntry objects for the specified user.
     *
     * @access    public
     * @param     string $searchUrl
     * @param     int $sid Session ID of current user.
     * @param     int $uid User ID of current user (optional).
     * @return    SearchEntry  Matching SearchEntry objects.
     */
    function getSavedSearchByUrl($searchUrl, $sid, $uid = null)
    {
        $sql = "SELECT * FROM search WHERE searchUrl = " . $this->escape($searchUrl) . " AND (session_id = " . $this->escape($sid);
        if ($uid != null) {
            $sql .= " OR user_id = " . $this->escape($uid);
        }
        $sql .= ")";

        $s = new SearchEntry();
        $s->query($sql);
        if ($s->getNumResults()) {
            while ($s->fetch()) {
                return clone($s);
            }
        }

        return null;
    }

    /**
     * Get an array of SearchEntry objects for the specified user.
     *
     * @access    public
     * @param int $sid Session ID of current user.
     * @param int $uid User ID of current user (optional).
     * @return    array                                             Matching SearchEntry objects.
     */
	function getSearches($sid, $uid = null)
	{
			$searches = array();

			$sql = "SELECT * FROM search WHERE (session_id = " . $this->escape($sid);
			if ($uid != null) {
					$sql .= " OR user_id = " . $this->escape($uid);
			}
			$sql .= ") ORDER BY id";

			$s = new SearchEntry();
			$s->query($sql);
			if ($s->getNumResults()) {
					while ($s->fetch()) {
							$searches[] = clone($s);
					}
			}

			return $searches;
	}

    /**
     * Get an array of SearchEntry objects for the specified user.
     *
     * @access    public
     * @param string $searchSource
     * @param int $sid Session ID of current user.
     * @param int $uid User ID of current user (optional).
     * @return    array                                             Matching SearchEntry objects.
     */
    function getSearchesWithNullUrl($searchSource, $sid, $uid = null)
    {
        $searches = array();

        $sql = "SELECT * FROM search WHERE searchSource = " . $this->escape($searchSource) . " AND searchUrl is NULL AND (session_id = " . $this->escape($sid);
        if ($uid != null) {
            $sql .= " OR user_id = " . $this->escape($uid);
        }
        $sql .= ") ORDER BY id";

        $s = new SearchEntry();
        $s->query($sql);
        if ($s->getNumResults()) {
            while ($s->fetch()) {
                $searches[] = clone($s);
            }
        }

        return $searches;
    }

	/**
	 * Get an array of SearchEntry objects representing expired, unsaved searches.
	 *
	 * @access	public
	 * @param	 int				 $daysOld				Age in days of an "expired" search.
	 * @return	array											 Matching SearchEntry objects.
	 */
	function getExpiredSearches($daysOld = 2)
	{
		// Determine the expiration date:
		$expirationDate = date('Y-m-d', time() - $daysOld * 24 * 60 * 60);

		// Find expired, unsaved searches:
		/** @noinspection SqlResolve */
		$sql = 'SELECT * FROM search WHERE saved=0 AND created<"' . $expirationDate . '"';
		$s = new SearchEntry();
		$s->query($sql);
		$searches = array();
		if ($s->getNumResults()) {
				while ($s->fetch()) {
					$searches[] = clone($s);
				}
		}
		return $searches;
	}
}
