<?php
/** @noinspection PhpUnused */
function getUpdates23_08_00(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				''
			]
		], //name*/


		//mark - ByWater
		'custom_facets' => [
			'title' => 'Add custom facet indexing information to Indexing Profiles',
			'description' => 'Add custom facet indexing information to Indexing Profiles',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet1SourceField VARCHAR(50) DEFAULT ''",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet1ValuesToInclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet1ValuesToExclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet2SourceField VARCHAR(50) DEFAULT ''",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet2ValuesToInclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet2ValuesToExclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet3SourceField VARCHAR(50) DEFAULT ''",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet3ValuesToInclude TEXT",
				"ALTER TABLE indexing_profiles ADD COLUMN customFacet3ValuesToExclude TEXT",
				"UPDATE indexing_profiles set customFacet1ValuesToInclude = '.*'",
				"UPDATE indexing_profiles set customFacet2ValuesToInclude = '.*'",
				"UPDATE indexing_profiles set customFacet3ValuesToInclude = '.*'",
			]
		],

		//kirstien - ByWater


		//kodi - ByWater


		//other organizations

	];
}