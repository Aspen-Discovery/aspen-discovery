<?php
require_once ROOT_DIR . '/sys/LibraryLocation/FacetSetting.php';

class LibraryArchiveSearchFacetSetting extends FacetSetting {
	public $__table = 'library_archive_search_facet_setting';    // table name
	public $libraryId;

	static $defaultFacetList = array (
		'mods_subject_topic_ms' => 'Subject',
		'mods_genre_s' => 'Type',
		'RELS_EXT_isMemberOfCollection_uri_ms' => 'Archive Collection',
		'mods_extension_marmotLocal_relatedEntity_person_entityTitle_ms' => 'Related People',
		'mods_extension_marmotLocal_relatedEntity_place_entityTitle_ms' => 'Related Places',
		'mods_extension_marmotLocal_relatedEntity_event_entityTitle_ms' => 'Related Events',
		'mods_extension_marmotLocal_describedEntity_entityTitle_ms' => 'Described Entity',
		'mods_extension_marmotLocal_picturedEntity_entityTitle_ms' => 'Pictured Entity',
		'namespace_s' => 'Contributing Library',
		//'ancestors_ms' => "Included In"
	);

	static function getObjectStructure($availableFacets = NULL){
		$library = new Library();
		$library->orderBy('displayName');
		if (!UserAccount::userHasPermission('Administer All Libraries')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$library->libraryId = $homeLibrary->libraryId;
		}
		$library->find();
		while ($library->fetch()){
			$libraryList[$library->libraryId] = $library->displayName;
		}

		$structure = parent::getObjectStructure(self::getAvailableFacets());
		$structure['libraryId'] = array('property'=>'libraryId', 'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'The id of a library');
		//TODO: needed? for copy facets button?

		return $structure;
	}

	function getEditLink(){
		return '/Admin/LibraryArchiveSearchFacetSettings?objectAction=edit&id=' . $this->id;
	}

	public static function getAvailableFacets(){
		$config            = getExtraConfigArray('islandoraFacets');
		return isset($config['Results']) ? $config['Results'] : self::$defaultFacetList;
	}
}


