package com.turning_leaf_technologies.reindexer;

import org.apache.logging.log4j.Logger;

import java.sql.Connection;
import java.sql.ResultSet;

public class FolioRecordProcessor extends IlsRecordProcessor {
	FolioRecordProcessor(GroupedWorkIndexer indexer, String curType, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, curType, dbConn, indexingProfileRS, logger, fullReindex);
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		return true;
	}
}
