<?php

require_once __DIR__ . '/../bootstrap.php';

// Iterate all libraries and refresh their SSO XMl metadata
// as appropriate
//
// Usage: php refreshSsoMetadata.php <sitename>
//
$library = new Library();
$library->find();
while ($library->fetch()) {
	if (strlen($library->ssoXmlUrl) > 0) {
		print "Refreshing metadata for $library->displayName from $library->ssoXmlUrl\n";
		$result = $library->fetchAndStoreSsoMetadata();
		if ($result instanceof AspenError) {
			print "FAILED: " . $result->message . "\n";
		} else {
			print "-- Success\n";
		}
	} else {
		print "No metadata URL found for $library->displayName\n";
	}
}