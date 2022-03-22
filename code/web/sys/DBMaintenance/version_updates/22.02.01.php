<?php
/** @noinspection PhpUnused */
function getUpdates22_02_01() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'course_reserves_unique_index' => [
			'title' => 'Update Course Reserves Unique Index',
			'description' => 'Fix which fields make a course reserve unique and make them case sensitive',
			'sql' => [
				'ALTER TABLE course_reserve CHANGE courseInstructor courseInstructor VARCHAR(100) COLLATE utf8_bin',
				'ALTER TABLE course_reserve CHANGE courseNumber courseNumber VARCHAR(50) COLLATE utf8_bin',
				'ALTER TABLE course_reserve CHANGE courseTitle courseTitle VARCHAR(200) COLLATE utf8_bin',
				'ALTER TABLE course_reserve DROP INDEX course ',
				'ALTER TABLE course_reserve ADD UNIQUE course(courseLibrary, courseNumber, courseInstructor, courseTitle)'
			]
		], //course_reserves_unique_index
		'configurable_solr_timeouts' => [
			'title' => 'Configurable Solr Timeouts',
			'description' => 'Setup Configurable Solr Timeouts',
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN solrConnectTimeout INT DEFAULT 2',
				'ALTER TABLE system_variables ADD COLUMN solrQueryTimeout INT DEFAULT 10',
			]
		], //configurable_solr_timeouts
		'solrTimeoutStats' => [
			'title' => 'Solr Timeout Stats',
			'description' => 'Add stats when solr times out or has errors',
			'sql' => [
				'ALTER TABLE aspen_usage ADD COLUMN timedOutSearches INT DEFAULT 0',
				'ALTER TABLE aspen_usage ADD COLUMN timedOutSearchesWithHighLoad INT DEFAULT 0',
				'ALTER TABLE aspen_usage ADD COLUMN searchesWithErrors INT DEFAULT 0',
			]
		], //solrTimeoutStats
	];
}
