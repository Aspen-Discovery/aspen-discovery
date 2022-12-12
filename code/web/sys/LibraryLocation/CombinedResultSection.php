<?php /** @noinspection PhpUnused */

abstract class CombinedResultSection extends DataObject {
	public $__displayNameColumn = 'displayName';
	public $id;
	public $displayName;
	public $weight;
	public $source;
	public $numberOfResultsToShow;

	static function getObjectStructure($context = ''): array {
		global $configArray;
		global $enabledModules;
		global $library;
		$validResultSources = [];
		$validResultSources['catalog'] = 'Catalog Results';
		require_once ROOT_DIR . '/sys/Enrichment/DPLASetting.php';
		$dplaSetting = new DPLASetting();
		if ($dplaSetting->find(true)) {
			$validResultSources['dpla'] = 'DP.LA';
		}
		if (array_key_exists('EBSCO EDS', $enabledModules) && $library->edsSettingsId != -1) {
			$validResultSources['ebsco_eds'] = 'EBSCO EDS';
		} elseif (array_key_exists('EBSCOhost', $enabledModules) && $library->edsSettingsId == -1) {
			$validResultSources['ebscohost'] = 'EBSCOhost';
		}
		if (array_key_exists('Events', $enabledModules)) {
			$validResultSources['events'] = 'Events';
		}
		if (array_key_exists('Genealogy', $enabledModules)) {
			$validResultSources['genealogy'] = 'Genealogy';
		}
		if (array_key_exists('Open Archives', $enabledModules)) {
			$validResultSources['open_archives'] = 'Open Archives';
		}
		if ($configArray['Content']['Prospector']) {
			$validResultSources['prospector'] = 'Prospector';
		}
		if (array_key_exists('Web Indexer', $enabledModules)) {
			$validResultSources['websites'] = 'Website Search';
		}
		$validResultSources['lists'] = 'User Lists';

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of this section',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'integer',
				'label' => 'Weight',
				'description' => 'The sort order',
				'default' => 0,
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'text',
				'label' => 'Display Name',
				'description' => 'The full name of the section for display to the user',
				'maxLength' => 255,
			],
			'numberOfResultsToShow' => [
				'property' => 'numberOfResultsToShow',
				'type' => 'integer',
				'label' => 'Num Results',
				'description' => 'The number of results to show in the box.',
				'default' => '5',
			],
			'source' => [
				'property' => 'source',
				'type' => 'enum',
				'label' => 'Source',
				'values' => $validResultSources,
				'description' => 'The source of results in the section.',
				'default' => 'catalog',
			],
		];
	}

	function getResultsLink($searchTerm, $searchType) {
		if ($this->source == 'archive') {
			return "/Archive/Results?lookfor=$searchTerm";
		} elseif ($this->source == 'catalog') {
			return "/Search/Results?lookfor=$searchTerm&searchSource=local";
		} elseif ($this->source == 'dpla') {
			return "https://dp.la/search?q=$searchTerm";
		} elseif ($this->source == 'ebsco_eds') {
			return "/EBSCO/Results?lookfor=$searchTerm&searchSource=ebsco_eds";
		} elseif ($this->source == 'ebscohost') {
			global $library;
			require_once ROOT_DIR . '/sys/Ebsco/EBSCOhostSearchSetting.php';
			$searchSettings = new EBSCOhostSearchSetting();
			$filters = '';
			if ($library->ebscohostSearchSettingId > 0) {
				$searchSettings->id = $library->ebscohostSearchSettingId;
				if ($searchSettings->find(true)) {
					foreach ($searchSettings->getDatabases() as $database) {
						if ($database->allowSearching && $database->showInCombinedResults) {
							$filters .= ('&filter[]=db:"' . $database->shortName . '"');
						}
					}
				}
			}
			return "/EBSCOhost/Results?lookfor=$searchTerm&searchSource=ebscohost$filters";
		} elseif ($this->source == 'events') {
			return "/Events/Results?lookfor=$searchTerm&searchSource=events";
		} elseif ($this->source == 'genealogy') {
			return "/Genealogy/Results?lookfor=$searchTerm&searchSource=genealogy";
		} elseif ($this->source == 'lists') {
			return "/Lists/Results?lookfor=$searchTerm&searchSource=lists";
		} elseif ($this->source == 'open_archives') {
			return "/OpenArchives/Results?lookfor=$searchTerm&searchSource=open_archives";
		} elseif ($this->source == 'prospector') {
			require_once ROOT_DIR . '/Drivers/marmot_inc/Prospector.php';
			$prospector = new Prospector();
			$search = [
				[
					'lookfor' => $searchTerm,
					'index' => $searchType,
				],
			];
			return $prospector->getSearchLink($search);
		} elseif ($this->source == 'websites') {
			return "/Websites/Results?lookfor=$searchTerm&searchSource=websites";
		} else {
			return '';
		}
	}
}