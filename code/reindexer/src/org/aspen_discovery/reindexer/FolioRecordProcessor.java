package org.aspen_discovery.reindexer;

import org.apache.logging.log4j.Logger;

import java.sql.Connection;
import java.sql.ResultSet;

public class FolioRecordProcessor extends IlsRecordProcessor {
	FolioRecordProcessor(String serverName, GroupedWorkIndexer indexer, String curType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(serverName, indexer, curType, dbConn, indexingProfileRS, logger, fullReindex);
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo, String displayStatus, String groupedStatus) {
		return true;
	}
}
