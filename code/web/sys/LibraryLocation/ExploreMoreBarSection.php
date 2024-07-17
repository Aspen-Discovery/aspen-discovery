<?php

abstract class ExploreMoreBarSection extends DataObject {
    public $__displayNameColumn = 'displayName';
    public $id;
    public $displayName;
    public $weight;
    public $source;
    
    static function getObjectStructure($context = ''): array {
        global $configArray;
        global $library;
        global $enabledModules;
        $validResultSources = [];
        $validResultSources['catalog'] = 'Catalog Results';

        if (array_key_exists('EBSCO EDS', $enabledModules) && $library->edsSettingsId != -1) {
			$validResultSources['ebsco_eds'] = 'EBSCO EDS';
		} elseif (array_key_exists('EBSCOhost', $enabledModules) && $library->edsSettingsId == -1) {
			$validResultSources['ebscohost'] = 'EBSCOhost';
		}
		if (array_key_exists('Summon', $enabledModules) && $library->summonSettingsId != -1) {
			$validResultSources['summon'] = 'Summon';
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

    
}