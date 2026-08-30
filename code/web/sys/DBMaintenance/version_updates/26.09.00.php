<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_09_00(): array {
	$now = time();

	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark n

		//kirstien

		//kodi
		'theme_font_size' => [
			'title' => 'Theme Font Size',
			'description' => 'Add setting to control the base font size for a theme',
			'sql' => [
				"ALTER TABLE themes ADD COLUMN fontSize VARCHAR(10) NOT NULL DEFAULT 'small'",
			]
		], // theme_font_size
		'regenerate_themes' => [
			'title' => 'Regenerate Themes',
			'description' => 'Regenerate themes to accommodate new font size settings.',
			'sql' => [
				'regenerateThemeCssForFontSize',
			]
		], // theme_font_size
		'user_preferred_text_size' => [
			'title' => 'User Preferred Text Size',
			'description' => 'Allow a user to override the text size of the applied theme',
			'sql' => [
				"ALTER TABLE user ADD COLUMN preferredTextSize VARCHAR(10) NOT NULL DEFAULT ''",
			]
		], // user_preferred_text_size

		//yanjun

		//imani

		//galen

		//chloe
	
		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//jacob - OpenFifth


	];
}

/**
 * The generated CSS for each theme is cached in themes.generatedCss, so adding the font size rules to
 * theme.css.tpl has no effect until every theme is regenerated.
 */
function regenerateThemeCssForFontSize(&$update): void {
	require_once ROOT_DIR . '/sys/Theming/Theme.php';
	$theme = new Theme();
	$theme->find();
	$numUpdated = 0;
	while ($theme->fetch()) {
		$themeToUpdate = clone $theme;
		$themeToUpdate->generateCss(true);
		$numUpdated++;
	}
	$update['success'] = true;
	$update['status'] = translate([
		'text' => 'Regenerated CSS for %1% themes',
		1 => $numUpdated,
		'isAdminFacing' => true,
	]);
}
