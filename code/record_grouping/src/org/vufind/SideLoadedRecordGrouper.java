package org.vufind;

import org.apache.log4j.Logger;

import java.sql.Connection;

/**
 * Groups records that are not loaded into the ILS.  These are additional records that are processed directly in Pika
 *
 * Pika
 * User: Mark Noble
 * Date: 12/15/2015
 * Time: 5:29 PM
 */
class SideLoadedRecordGrouper extends MarcRecordGrouper {

	/**
	 * Creates a record grouping processor that saves results to the database.
	 *
	 * @param dbConnection   - The Connection to the Pika database
	 * @param profile        - The profile that we are grouping records for
	 * @param logger         - A logger to store debug and error messages to.
	 * @param fullRegrouping - Whether or not we are doing full regrouping or if we are only grouping changes.
	 */
	SideLoadedRecordGrouper(Connection dbConnection, IndexingProfile profile, Logger logger, boolean fullRegrouping) {
		super(dbConnection, profile, logger, fullRegrouping);
	}


}
