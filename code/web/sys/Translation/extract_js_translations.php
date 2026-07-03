#!/usr/bin/env php
<?php
/**
 * Scans JS source files for __('phrase') calls and regenerates JsTranslations.php.
 *
 * Usage (from repo root):
 *   php code/web/sys/Translation/extract_js_translations.php
 *
 * Re-run this script every time a new __() call is added to a JS file.
 * The generated JsTranslations.php is committed alongside source changes.
 *
 * Admin-facing detection: files listed in $adminFiles produce isAdminFacing entries.
 * A phrase found in both admin and public files gets both flags set to true.
 */

$jsDir     = __DIR__ . '/../../interface/themes/responsive/js/aspen';
$outputFile = __DIR__ . '/JsTranslations.php';

$adminFiles = ['admin.js', 'web-builder.js', 'sideloads.js'];

// Collect all phrases: phrase => { isPublicFacing, isAdminFacing }
$terms = [];

foreach (glob($jsDir . '/*.js') as $file) {
	$basename = basename($file);
	if ($basename === 'aspen.js') {
		continue; // skip the merged output file
	}

	$isAdmin = in_array($basename, $adminFiles);
	$content = file_get_contents($file);

	// Match __('phrase') and __("phrase"), capturing everything up to the closing quote.
	// Uses a non-greedy match so it stops at the first unescaped quote.
	if (preg_match_all('/(?<![a-zA-Z0-9_])__\(([\'"])((?:[^\\\\\1]|\\\\.)*?)\1/', $content, $matches)) {
		foreach ($matches[2] as $phrase) {
			$phrase = stripslashes($phrase);

			if (!isset($terms[$phrase])) {
				$terms[$phrase] = [
					'isPublicFacing' => !$isAdmin,
					'isAdminFacing'  => $isAdmin,
				];
			} else {
				// Phrase appears in more than one file — merge facing flags.
				if ($isAdmin) {
					$terms[$phrase]['isAdminFacing'] = true;
				} else {
					$terms[$phrase]['isPublicFacing'] = true;
				}
			}
		}
	}
}

// Sort alphabetically for stable diffs.
ksort($terms);

// Generate JsTranslations.php
$lines   = [];
$lines[] = '<?php';
$lines[] = '';
$lines[] = 'class JsTranslations {';
$lines[] = "\tstatic function getTerms(): array {";
$lines[] = "\t\treturn [";

foreach ($terms as $phrase => $meta) {
	$escaped = str_replace("'", "\\'", $phrase);
	$parts   = [];
	if ($meta['isPublicFacing'] ?? false) {
		$parts[] = "'isPublicFacing' => true";
	}
	if ($meta['isAdminFacing'] ?? false) {
		$parts[] = "'isAdminFacing' => true";
	}
$lines[] = "\t\t\t'{$escaped}' => [" . implode(', ', $parts) . "],";
}

$lines[] = "\t\t];";
$lines[] = "\t}";
$lines[] = "}";
$lines[] = "";

file_put_contents($outputFile, implode("\n", $lines));

echo "Generated JsTranslations.php with " . count($terms) . " terms.\n";
