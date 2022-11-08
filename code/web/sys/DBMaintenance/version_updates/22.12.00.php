<?php
/** @noinspection PhpUnused */
function getUpdates22_12_00(): array
{
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
        ], //sample*/

		//mark
		'custom_form_includeIntroductoryTextInEmail' => [
			'title' => 'Custom Form - includeIntroductoryTextInEmail',
			'description' => 'Allow introductory text to be included in the response email',
			'sql' => [
				'ALTER TABLE web_builder_custom_form ADD COLUMN includeIntroductoryTextInEmail TINYINT(1) default 0'
			]
        ], //sample

		//kirstien

		//kodi

		//other
	];
}