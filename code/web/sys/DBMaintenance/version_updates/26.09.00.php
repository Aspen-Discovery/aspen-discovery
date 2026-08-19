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
			'continueOnError' => true,
			'sql' => [
				// 1. Create new empty table with original structure.
				"CREATE TABLE aspen_usage_temp LIKE aspen_usage",

				// 2. Remove old non-unique index from temp table.
				"ALTER TABLE aspen_usage_temp DROP INDEX instance",

				// 3. Insert aggregated data directly into temp table.
				"INSERT INTO aspen_usage_temp (instance, year, month, day, pageViews, pageViewsByBots, pageViewsByAuthenticatedUsers, pagesWithErrors, ajaxRequests, coverViews, genealogySearches, groupedWorkSearches, openArchivesSearches, userListSearches, websiteSearches, eventsSearches, blockedRequests, blockedApiRequests, ebscoEdsSearches, sessionsStarted, timedOutSearches, timedOutSearchesWithHighLoad, searchesWithErrors, ebscohostSearches, emailsSent, emailsFailed, summonSearches, galeSearches)
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
				 FROM aspen_usage
				 GROUP BY instance, year, month, day",

				// 4. Add unique index to temp table before swap.
				"ALTER TABLE aspen_usage_temp ADD UNIQUE INDEX uniqueness (instance, year, month, day)",

				// 5. Atomic table swap.
				"DROP TABLE aspen_usage",
				"RENAME TABLE aspen_usage_temp TO aspen_usage",
			],
		], //aggregate_aspen_usage

	];
}
