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
	];
}
