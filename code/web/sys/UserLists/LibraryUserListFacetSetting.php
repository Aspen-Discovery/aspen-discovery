<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/UserLists/UserListFacetGroup.php';

class LibraryUserListFacetSetting extends DataObject {
	public $__table = 'library_user_list_facet_setting';
	public $id;
	public $userListFacetGroupId;
	public $libraryId;

	private $_facetGroup = false;

	/** @return UserListFacet[] */
	public function getFacets() : array {
		try {
			if (!is_null($this->getFacetGroup())) {
				return $this->getFacetGroup()->getFacets();
			} else {
				return [];
			}
		} catch (Exception) {
			return [];
		}
	}

	public function getFacetGroup(): ?UserListFacetGroup {
		try {
			if ($this->_facetGroup === false) {
				$this->_facetGroup = new UserListFacetGroup();
				$this->_facetGroup->id = $this->userListFacetGroupId;
				if (!$this->_facetGroup->find(true)) {
					$this->_facetGroup = null;
				}
			}
			return $this->_facetGroup;
		} catch (Exception) {
			return null;
		}
	}
}