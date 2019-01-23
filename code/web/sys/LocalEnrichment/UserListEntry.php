<?php
/**
 * An entry within the User Id
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 3/10/14
 * Time: 3:50 PM
 */
require_once 'DB/DataObject.php';
class UserListEntry extends DB_DataObject{
	public $__table = 'user_list_entry';     // table name
	public $id;                              // int(11)  not_null primary_key auto_increment
	public $groupedWorkPermanentId;          // int(11)  not_null multiple_key
	public $listId;                          // int(11)  multiple_key
	public $notes;                           // blob(65535)  blob
	public $dateAdded;                       // timestamp(19)  not_null unsigned zerofill binary timestamp
	public $weight;                          //Where to position the entry in the overall list

	/**
	 * @return bool
	 */
	function insert()
	{
		$result = parent::insert();
		if ($result) {
			$this->flushUserListBrowseCategory();
		}
		return $result;
	}

	/**
	 * @param bool $dataObject
	 * @return bool|int|mixed
	 */
	function update($dataObject = false)
	{
		$result = parent::update($dataObject);
		if ($result) {
			$this->flushUserListBrowseCategory();
		}
		return $result;
	}

	/**
	 * @param bool $useWhere
	 * @return bool|int|mixed
	 */
	function delete($useWhere = false)
	{
		$result = parent::delete($useWhere);
		if ($result) {
			$this->flushUserListBrowseCategory();
		}
		return $result;
	}

	private function flushUserListBrowseCategory(){
		// Check if the list is a part of a browse category and clear the cache.
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$userListBrowseCategory = new BrowseCategory();
		$userListBrowseCategory->sourceListId = $this->listId;
		if ($userListBrowseCategory->find()) {
			while ($userListBrowseCategory->fetch()) {
				$userListBrowseCategory->deleteCachedBrowseCategoryResults();
			}
		}
	}
}
