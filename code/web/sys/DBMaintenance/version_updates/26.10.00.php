<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_10_00(): array {
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

		//imani

		//galen

		//chloe
	
		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//jacob - OpenFifth

		//kyle - ByWater
		'aggregate_aspen_usage' => [
			'title' => 'Aggregate aspen_usage & add unique key',
			'description' => 'Combine multiple rows per (instance, year, month, day) into one row before adding unique key so concurrent requests cannot create duplicate daily rows',
			'continueOnError' => false,
			'sql' => [
				// 1. If an earlier run of this update died part way through it will have left the scratch table
				//    behind, so drop it and let the admin just run the update again. aspen_usage_old is
				//    deliberately *not* dropped, if an earlier run died after step 3 it holds the only copy of
				//    the history.
				"DROP TABLE IF EXISTS aspen_usage_temp",

				// 2. Build the replacement table with the unique key already in place.
				"CREATE TABLE aspen_usage_temp LIKE aspen_usage",
				"ALTER TABLE aspen_usage_temp DROP INDEX IF EXISTS instance",
				"ALTER TABLE aspen_usage_temp ADD UNIQUE INDEX uniqueness (instance, year, month, day)",

				// 3. Swap the empty table in before aggregating. Renaming both tables in one statement means
				//    aspen_usage never disappears, and counters written from here on land in the new table
				//    rather than being thrown away with the old one.
				"RENAME TABLE aspen_usage TO aspen_usage_old, aspen_usage_temp TO aspen_usage",

				// 4. Fold the history in, adding to any counts that have accumulated since the swap.
				"INSERT INTO aspen_usage (instance, year, month, day, pageViews, pageViewsByBots, pageViewsByAuthenticatedUsers, pagesWithErrors, ajaxRequests, coverViews, genealogySearches, groupedWorkSearches, openArchivesSearches, userListSearches, websiteSearches, eventsSearches, blockedRequests, blockedApiRequests, ebscoEdsSearches, sessionsStarted, timedOutSearches, timedOutSearchesWithHighLoad, searchesWithErrors, ebscohostSearches, emailsSent, emailsFailed, summonSearches, galeSearches)
				 SELECT instance, year, month, day,
						SUM(pageViews),
						SUM(pageViewsByBots),
						SUM(pageViewsByAuthenticatedUsers),
						SUM(pagesWithErrors),
						SUM(ajaxRequests),
						SUM(coverViews),
						SUM(genealogySearches),
						SUM(groupedWorkSearches),
						SUM(openArchivesSearches),
						SUM(userListSearches),
						SUM(websiteSearches),
						SUM(eventsSearches),
						SUM(blockedRequests),
						SUM(blockedApiRequests),
						SUM(ebscoEdsSearches),
						SUM(sessionsStarted),
						SUM(timedOutSearches),
						SUM(timedOutSearchesWithHighLoad),
						SUM(searchesWithErrors),
						SUM(ebscohostSearches),
						SUM(emailsSent),
						SUM(emailsFailed),
						SUM(summonSearches),
						SUM(galeSearches)
				 FROM aspen_usage_old
				 GROUP BY instance, year, month, day
				 ON DUPLICATE KEY UPDATE
						pageViews = pageViews + VALUES(pageViews),
						pageViewsByBots = pageViewsByBots + VALUES(pageViewsByBots),
						pageViewsByAuthenticatedUsers = pageViewsByAuthenticatedUsers + VALUES(pageViewsByAuthenticatedUsers),
						pagesWithErrors = pagesWithErrors + VALUES(pagesWithErrors),
						ajaxRequests = ajaxRequests + VALUES(ajaxRequests),
						coverViews = coverViews + VALUES(coverViews),
						genealogySearches = genealogySearches + VALUES(genealogySearches),
						groupedWorkSearches = groupedWorkSearches + VALUES(groupedWorkSearches),
						openArchivesSearches = openArchivesSearches + VALUES(openArchivesSearches),
						userListSearches = userListSearches + VALUES(userListSearches),
						websiteSearches = websiteSearches + VALUES(websiteSearches),
						eventsSearches = eventsSearches + VALUES(eventsSearches),
						blockedRequests = blockedRequests + VALUES(blockedRequests),
						blockedApiRequests = blockedApiRequests + VALUES(blockedApiRequests),
						ebscoEdsSearches = ebscoEdsSearches + VALUES(ebscoEdsSearches),
						sessionsStarted = sessionsStarted + VALUES(sessionsStarted),
						timedOutSearches = timedOutSearches + VALUES(timedOutSearches),
						timedOutSearchesWithHighLoad = timedOutSearchesWithHighLoad + VALUES(timedOutSearchesWithHighLoad),
						searchesWithErrors = searchesWithErrors + VALUES(searchesWithErrors),
						ebscohostSearches = ebscohostSearches + VALUES(ebscohostSearches),
						emailsSent = emailsSent + VALUES(emailsSent),
						emailsFailed = emailsFailed + VALUES(emailsFailed),
						summonSearches = summonSearches + VALUES(summonSearches),
						galeSearches = galeSearches + VALUES(galeSearches)",

				// 5. The history is merged into aspen_usage now, the copy is no longer needed.
				"DROP TABLE aspen_usage_old",
			],
		], //aggregate_aspen_usage

	];
}
