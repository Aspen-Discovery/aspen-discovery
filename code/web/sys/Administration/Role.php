<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class Role extends DataObject
{
	public $__table = 'roles';// table name
	public $__primaryKey = 'roleId';
	public $roleId;
	public $name;
	public $description;
	private $_permissions;

	function keys()
	{
		return array('roleId');
	}

	static function getObjectStructure()
	{
		$permissionsList = [];
		return [
			'roleId' => ['property' => 'roleId', 'type' => 'label', 'label' => 'Role Id', 'description' => 'The unique id of the role within the database'],
			'name' => ['property' => 'name', 'type' => 'text', 'label' => 'Name', 'maxLength' => 50, 'description' => 'The full name of the role.'],
			'description' => ['property' => 'name', 'type' => 'text', 'label' => 'Name', 'maxLength' => 100, 'description' => 'The full name of the role.'],

			'permissions' => [
				'property' => 'permissions',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Permissions',
				'description' => 'Define permissions for the role',
				'values' => $permissionsList,
				'forcesReindex' => false
			],
		];
	}

	static function getLookup()
	{
		$role = new Role();
		$role->orderBy('name');
		$role->find();
		$roleList = [];
		while ($role->fetch()) {
			$roleList[$role->roleId] = $role->name . ' - ' . $role->description;
		}
		return $roleList;
	}

	function getPermissions(){
		if ($this->_permissions == null){
			$this->_permissions = [];
			//TODO: Load from the database

			//If we don't have permissions in the database, load defaults (this happens during conversion)
			$this->_permissions = $this->getDefaultPermissions();
		}
		return $this->_permissions;
	}

	private function getDefaultPermissions()
	{
		switch ($this->name){
		case 'opacAdmin':
			return array_flip([
				'administer_sendgrid',
				'block_patron_account_linking',
				'delete_themes',
				'edit_account_profiles',
				'edit_all_themes',
				'edit_modules',
				'edit_system_variables',
				'group_works',
				'run_database_maintenance',
				'view_account_profiles',
				'view_all_browse_categories',
				'view_all_collection_spotlights',
				'view_all_grouped_work_display_settings',
				'view_all_grouped_work_facets',
				'view_all_layout_settings',
				'view_all_library_settings',
				'view_all_location_settings',
				'view_all_placards',
				'view_all_themes',
				'view_ar_settings',
				'view_contentcafe_settings',
				'view_dpla_settings',
				'view_google_api_settings',
				'view_ip_addresses',
				'view_languages',
				'view_modules',
				'view_novelist_settings',
				'view_nyt_lists',
				'view_nyt_settings',
				'view_omdb_settings',
				'view_patron_types',
				'view_recaptcha_settings',
				'view_rosen_levelup_settings',
				'view_syndetics_settings',
				'view_system_reports',
				'view_system_variables',
				'view_translations',
				'view_wikipedia_author_enrichment',
				'view_indexing_profiles',
				'view_translation_maps',
				'view_loan_rules',
				'view_ils_indexing_log',
				'view_ils_dashboard',
				'view_overdrive_settings',
				'view_overdrive_indexing_log',
				'view_overdrive_dashboard',
				'view_overdrive_api_data',
				'view_hoopla_settings',
				'view_hoopla_indexing_log',
				'view_hoopla_dashboard',
				'view_rbdigital_settings',
				'view_rbdigital_indexing_log',
				'view_rbdigital_dashboard',
				'view_axis360_settings',
				'view_axis360_indexing_log',
				'view_axis360_dashboard',
				'view_cloud_library_settings',
				'view_cloud_library_indexing_log',
				'view_cloud_library_dashboard',
				'view_side_loads_settings',
				'view_side_loads_indexing_log',
				'view_side_loads_dashboard',
				'view_ebsco_settings',
				'view_ebsco_dashboard',
				'view_archive_materials_requests',
				'view_archive_authorship_claims',
				'view_archive_subject_control',
				'clear_archive_cache',
				'view_archive_private_collections',
				'view_archive_usage',
				'view_library_market_calendar_settings',
				'view_events_indexing_log',
				'view_open_archives_collections',
				'view_open_archives_indexing_log',
				'view_open_archives_dashboard',
				'view_web_indexer_settings',
				'view_web_indexer_indexing_log',
				'view_web_indexer_dashboard',
				'view_aspen_help_manual',
				'view_aspen_release_notes',
				'submit_aspen_ticket',
				'view_ils_offline_holds_report'
			]);
		case 'userAdmin':
			return array_flip([
				'view_administrators',
				'edit_administrators',
				'view_roles',
				'edit_roles',
				'view_aspen_help_manual',
				'view_aspen_release_notes'
			]);
		case 'libraryAdmin':
			return array_flip([
				'view_library_themes',
				'edit_library_themes',
				'view_library_layout_settings',
				'view_library_grouped_work_display_settings',
				'view_library_grouped_work_facets',
				'view_library_browse_categories',
				'view_library_collection_spotlights',
				'view_my_library_settings',
				'view_library_location_settings',
				'view_my_location_settings',
				'block_patron_account_linking',
				'view_library_placards',
				'view_aspen_help_manual',
				'view_aspen_release_notes',
				'submit_aspen_ticket'
			]);
		case 'libraryManager':
			return array_flip([
				'view_library_browse_categories',
				'view_library_collection_spotlights',
				'view_my_library_settings',
				'view_library_location_settings',
				'block_patron_account_linking',
				'view_aspen_help_manual',
				'view_aspen_release_notes'
			]);
		case 'locationManager':
			return array_flip([
				'view_my_location_settings',
				'block_patron_account_linking',
				'view_aspen_help_manual',
				'view_aspen_release_notes'
			]);
		case 'translator':
			return array_flip([
				'view_languages',
				'view_translations',
				'view_aspen_help_manual',
				'view_aspen_release_notes'
			]);
		case 'library_material_requests':
			return array_flip([
				'manage_library_materials_requests',
				'view_materials_requests_reports',
				'manage_library_materials_request_statuses',
				'view_aspen_help_manual',
				'view_aspen_release_notes'
			]);
		case 'superCataloger':
			return array_flip([
				'group_works',
				'view_indexing_profiles',
				'view_translation_maps',
				'view_loan_rules',
				'view_ils_indexing_log',
				'view_ils_dashboard',
				'view_overdrive_indexing_log',
				'view_overdrive_dashboard',
				'view_aspen_help_manual',
				'view_aspen_release_notes'
			]);
		case 'cataloging':
			return array_flip([
				'group_works',
				'view_aspen_help_manual',
				'view_aspen_release_notes'
			]);
		case 'archives':
			return array_flip([
				'view_archive_materials_requests',
				'view_archive_authorship_claims',
				'view_archive_subject_control',
				'view_aspen_help_manual',
				'view_aspen_release_notes'
			]);
		}
		return [];
	}
}