<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_11_00(): array {
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

		//yanjun
		'paypal_client_credentials_length' => [
			'title' => 'Expand PayPal Client Credential Lengths',
			'description' => 'Increase the maximum length of PayPal Client ID and Client Secret to 100 characters.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE paypal_settings MODIFY COLUMN clientId VARCHAR(100) DEFAULT NULL',
				'ALTER TABLE paypal_settings MODIFY COLUMN clientSecret VARCHAR(100) DEFAULT NULL',
			]
		], //paypal_client_credentials_length

		//imani

		//galen

		//chloe

		//pedro

		//mark j

		//lucas
		'theme_font_sources' => [
			'title' => 'Theme Font Sources',
			'description' => 'Store where each theme font comes from (0 Google Fonts, 1 default, 2 uploaded file) in headingFontDefault and bodyFontDefault, keeping the font each theme displays today, including uploaded fonts inherited from parent themes.',
			'sql' => [
				"UPDATE themes SET headingFontDefault = 2 WHERE customHeadingFont IS NOT NULL AND customHeadingFont <> ''",
				"UPDATE themes SET headingFontDefault = 0 WHERE headingFontDefault IS NULL AND headingFont IS NOT NULL AND headingFont <> ''",
				"UPDATE themes SET headingFontDefault = 1 WHERE headingFontDefault IS NULL OR (headingFontDefault = 0 AND (headingFont IS NULL OR headingFont = ''))",
				"UPDATE themes SET bodyFontDefault = 2 WHERE customBodyFont IS NOT NULL AND customBodyFont <> ''",
				"UPDATE themes SET bodyFontDefault = 0 WHERE bodyFontDefault IS NULL AND bodyFont IS NOT NULL AND bodyFont <> ''",
				"UPDATE themes SET bodyFontDefault = 1 WHERE bodyFontDefault IS NULL OR (bodyFontDefault = 0 AND (bodyFont IS NULL OR bodyFont = ''))",
				'keepAncestorUploadedThemeFonts',
			],
		], //theme_font_sources
		'repair_theme_font_files' => [
			'title' => 'Repair Theme Font Files',
			'description' => 'Restore uploaded theme fonts that were saved to the data directory instead of being served, and list fonts whose file is missing.',
			'sql' => [
				'repairThemeFontFiles',
			],
		], //repair_theme_font_files

		//tomas

		// stephen

		//jacob - OpenFifth


	];
}

/**
 * Google Fonts choices overridden by an uploaded font up the hierarchy become Use default, so the
 * uploaded font is still shown now that the closest theme setting a font decides it.
 */
function keepAncestorUploadedThemeFonts(&$update): void {
	global $aspen_db;
	$themes = [];
	$result = $aspen_db->query('SELECT id, themeName, extendsTheme, headingFontDefault, customHeadingFont, bodyFontDefault, customBodyFont FROM themes');
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$themes[$row['themeName']] = $row;
	}

	$switched = [];
	foreach (['heading' => ['headingFontDefault', 'customHeadingFont'], 'body' => ['bodyFontDefault', 'customBodyFont']] as $role => [$sourceColumn, $uploadColumn]) {
		$useDefault = $aspen_db->prepare("UPDATE themes SET $sourceColumn = 1 WHERE id = ?");
		foreach ($themes as $theme) {
			if ((string)$theme[$sourceColumn] !== '0') {
				continue;
			}
			// Stops on extends loops.
			$visited = [$theme['themeName'] => true];
			$parentName = $theme['extendsTheme'];
			while (!empty($parentName) && isset($themes[$parentName]) && !isset($visited[$parentName])) {
				$visited[$parentName] = true;
				$parentUpload = $themes[$parentName][$uploadColumn];
				if ($parentUpload !== null && $parentUpload !== '') {
					$useDefault->execute([$theme['id']]);
					$switched[] = "{$theme['themeName']} ($role)";
					break;
				}
				$parentName = $themes[$parentName]['extendsTheme'];
			}
		}
	}

	$update['success'] = true;
	$update['status'] = translate([
		'text' => 'Stored the font source of every theme. %1% Google Fonts choices were set to Use default to keep showing an uploaded font from a parent theme.',
		1 => count($switched),
		'isAdminFacing' => true,
	]);
	if (!empty($switched)) {
		$update['status'] .= '<br/>' . htmlspecialchars(implode('; ', $switched));
	}
}

/**
 * Copies uploaded fonts left in the data directory, where they are not served, to the served fonts
 * directory. Themes whose file cannot be found are listed in the status.
 */
function repairThemeFontFiles(&$update): void {
	global $aspen_db;
	require_once ROOT_DIR . '/sys/Storage/StorageDriverFactory.php';
	$storage = StorageDriverFactory::get();
	$legacyFontsDir = StorageDriverFactory::resolveDataRoot() . '/fonts/';

	$themesByFont = [];
	$themes = $aspen_db->query("SELECT themeName, customHeadingFont, customBodyFont FROM themes");
	while ($theme = $themes->fetch(PDO::FETCH_ASSOC)) {
		foreach (['customHeadingFont' => 'heading', 'customBodyFont' => 'body'] as $column => $role) {
			if (!empty($theme[$column])) {
				$themesByFont[$theme[$column]][] = "{$theme['themeName']} ($role)";
			}
		}
	}

	$restored = [];
	$missing = [];
	$failed = [];
	foreach ($themesByFont as $fontFile => $usedBy) {
		$description = $fontFile . ': ' . implode(', ', $usedBy);
		if (basename($fontFile) !== $fontFile || $fontFile === '.' || $fontFile === '..') {
			$missing[] = $description;
		} elseif ($storage->exists('fonts/' . $fontFile)) {
			continue;
		} elseif (is_file($legacyFontsDir . $fontFile)) {
			if ($storage->write('fonts/' . $fontFile, $legacyFontsDir . $fontFile)) {
				$restored[] = $description;
			} else {
				$failed[] = $description;
			}
		} else {
			$missing[] = $description;
		}
	}

	$status = translate([
		'text' => 'Checked %1% uploaded theme fonts: %2% restored from the data directory, %3% missing, %4% could not be copied.',
		1 => count($themesByFont),
		2 => count($restored),
		3 => count($missing),
		4 => count($failed),
		'isAdminFacing' => true,
	]);
	$copyManually = translate([
		'text' => 'Could not be copied, copy them from %1% to %2%',
		1 => $legacyFontsDir,
		2 => StorageDriverFactory::resolvePublicRoot() . '/fonts/',
		'isAdminFacing' => true,
	]);
	foreach (['Restored' => $restored, 'Missing, upload again' => $missing, $copyManually => $failed] as $label => $fonts) {
		if (!empty($fonts)) {
			$labelText = $label == $copyManually ? $label : translate(['text' => $label, 'isAdminFacing' => true]);
			$status .= '<br/>' . htmlspecialchars($labelText) . ': ' . htmlspecialchars(implode('; ', $fonts));
		}
	}
	if (!empty($missing) || !empty($failed)) {
		// Not translated: DB Maintenance highlights statuses containing "Warning".
		$status = 'Warning: ' . $status;
	}
	$update['success'] = empty($failed);
	$update['status'] = $status;
}
