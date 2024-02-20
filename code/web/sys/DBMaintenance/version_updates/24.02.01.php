<?php

function getUpdates24_02_01(): array {
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

		'increase_course_reserves_instructor' => [
			'title'=> 'Increase Course Reserves Instructor field length',
			'description' => 'Increase Course Reserves Instructor field length',
			'sql' => [
				'ALTER TABLE course_reserve CHANGE courseInstructor courseInstructor VARCHAR(255) COLLATE utf8_bin',
			]
		], //increase_course_reserves_instructor
    ];
}