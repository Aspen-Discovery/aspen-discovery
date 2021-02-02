package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.solr.client.solrj.impl.BinaryRequestWriter;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.impl.HttpSolrClient;
import org.apache.solr.client.solrj.response.UpdateResponse;
import org.apache.solr.common.SolrInputDocument;
import org.ini4j.Ini;

import java.io.*;
import java.sql.*;
import java.util.*;
import java.util.Date;

import org.apache.logging.log4j.Logger;

public class GroupedWorkIndexer {
	private final String serverName;
	private final BaseLogEntry logEntry;
	private final Logger logger;
	private final Long indexStartTime;
	private int totalRecordsHandled = 0;
	private ConcurrentUpdateSolrClient updateServer;
	private final HashMap<String, MarcRecordProcessor> ilsRecordProcessors = new HashMap<>();
	private final HashMap<String, SideLoadedEContentProcessor> sideLoadProcessors = new HashMap<>();
	private OverDriveProcessor overDriveProcessor;
	private RbdigitalProcessor rbdigitalProcessor;
	private RbdigitalMagazineProcessor rbdigitalMagazineProcessor;
	private CloudLibraryProcessor cloudLibraryProcessor;
	private Axis360Processor axis360Processor;
	private HooplaProcessor hooplaProcessor;
	private final HashMap<String, HashMap<String, String>> translationMaps = new HashMap<>();
	private final HashMap<String, LexileTitle> lexileInformation = new HashMap<>();

	private PreparedStatement getRatingStmt;
	private PreparedStatement getNovelistStmt;
	private PreparedStatement getDisplayInfoStmt;

	private PreparedStatement getUserReadingHistoryLinkStmt;
	private PreparedStatement getUserRatingLinkStmt;
	private PreparedStatement getUserNotInterestedLinkStmt;

	private final Connection dbConn;

	static int availableAtBoostValue = 50;
	static int ownedByBoostValue = 10;

	private final boolean fullReindex;
	private final boolean clearIndex;
	private long lastReindexTime;
	private Long lastReindexTimeVariableId;
	private boolean okToIndex = true;


	private TreeSet<Scope> scopes ;

	private PreparedStatement getGroupedWorkPrimaryIdentifiers;
	private PreparedStatement getGroupedWorkInfoStmt;
	private PreparedStatement getArBookIdForIsbnStmt;
	private PreparedStatement getArBookInfoStmt;
	private PreparedStatement getScheduledWorksStmt;
	private PreparedStatement getScheduledWorkStmt;
	private PreparedStatement markScheduledWorkProcessedStmt;
	private PreparedStatement addScheduledWorkStmt;


	//private static PreparedStatement deleteGroupedWorkStmt;

	private boolean removeRedundantHooplaRecords = false;

	public GroupedWorkIndexer(String serverName, Connection dbConn, Ini configIni, boolean fullReindex, boolean clearIndex, BaseLogEntry logEntry, Logger logger) {
		indexStartTime = new Date().getTime() / 1000;
		this.serverName = serverName;
		this.logEntry = logEntry;
		this.logger = logger;
		this.dbConn = dbConn;
		this.fullReindex = fullReindex;
		this.clearIndex = clearIndex;

		String solrPort = configIni.get("Reindex", "solrPort");

		//Load the last Index time
		try{
			PreparedStatement loadLastGroupingTime = dbConn.prepareStatement("SELECT * from variables WHERE name = 'last_reindex_time'");
			ResultSet lastGroupingTimeRS = loadLastGroupingTime.executeQuery();
			if (lastGroupingTimeRS.next()){
				lastReindexTime = lastGroupingTimeRS.getLong("value");
				lastReindexTimeVariableId = lastGroupingTimeRS.getLong("id");
			}
			lastGroupingTimeRS.close();
			loadLastGroupingTime.close();
		} catch (Exception e){
			logEntry.incErrors("Could not load last index time from variables table ", e);
		}

		//Load a few statements we will need later
		try{
			getGroupedWorkPrimaryIdentifiers = dbConn.prepareStatement("SELECT * FROM grouped_work_primary_identifiers where grouped_work_id = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			//deleteGroupedWorkStmt = dbConn.prepareStatement("DELETE from grouped_work where id = ?");
			getGroupedWorkInfoStmt = dbConn.prepareStatement("SELECT id, grouping_category from grouped_work where permanent_id = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getArBookIdForIsbnStmt = dbConn.prepareStatement("SELECT arBookId from accelerated_reading_isbn where isbn = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getArBookInfoStmt = dbConn.prepareStatement("SELECT * from accelerated_reading_titles where arBookId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getScheduledWorksStmt = dbConn.prepareStatement("SELECT * FROM grouped_work_scheduled_index where processed = 0 and indexAfter <= ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getScheduledWorkStmt = dbConn.prepareStatement("SELECT * FROM grouped_work_scheduled_index where processed = 0 and permanent_id = ? and indexAfter = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			markScheduledWorkProcessedStmt = dbConn.prepareStatement("UPDATE grouped_work_scheduled_index set processed = 1 where id = ?");
			addScheduledWorkStmt = dbConn.prepareStatement("INSERT INTO grouped_work_scheduled_index (permanent_id, indexAfter) VALUES (?, ?)");
		} catch (Exception e){
			logEntry.incErrors("Could not load statements to get identifiers ", e);
			this.okToIndex = false;
			return;
		}

		//Check hoopla settings to see if we need to remove redundant records
		try{
			PreparedStatement getHooplaSettingsStmt = dbConn.prepareStatement("SELECT excludeTitlesWithCopiesFromOtherVendors from hoopla_settings");
			ResultSet getHooplaSettingsRS = getHooplaSettingsStmt.executeQuery();
			if (getHooplaSettingsRS.next()) {
				removeRedundantHooplaRecords = getHooplaSettingsRS.getBoolean("excludeTitlesWithCopiesFromOtherVendors");
			}
		}catch (Exception e){
			logEntry.incErrors("Error loading Hoopla Settings", e);
		}

		//Initialize the updateServer and solr server
		logEntry.addNote("Setting up update server and solr server");

		ConcurrentUpdateSolrClient.Builder solrBuilder = new ConcurrentUpdateSolrClient.Builder("http://localhost:" + solrPort + "/solr/grouped_works");
		solrBuilder.withThreadCount(1);
		solrBuilder.withQueueSize(25);
		updateServer = solrBuilder.build();
		updateServer.setRequestWriter(new BinaryRequestWriter());

		try {
			scopes = IndexingUtils.loadScopes(dbConn, logger);
			if (scopes == null){
				logEntry.incErrors("Error loading scopes, scopes were null");
				this.okToIndex = false;
				return;
			}else{
				logger.info("Loaded " + scopes.size() + " scopes");
			}
		}catch (Exception e) {
			logEntry.incErrors("Error loading scopes", e);
			this.okToIndex = false;
			return;
		}

		//Initialize processors based on our indexing profiles and the primary identifiers for the records.
		try {
			PreparedStatement uniqueIdentifiersStmt = dbConn.prepareStatement("SELECT DISTINCT type FROM grouped_work_primary_identifiers");
			PreparedStatement getIndexingProfile = dbConn.prepareStatement("SELECT * from indexing_profiles where name = ?");
			PreparedStatement getSideLoadSettings = dbConn.prepareStatement("SELECT * from sideloads where name = ?");

			ResultSet uniqueIdentifiersRS = uniqueIdentifiersStmt.executeQuery();

			while (uniqueIdentifiersRS.next()){
				String curType = uniqueIdentifiersRS.getString("type");
				getIndexingProfile.setString(1, curType);
				ResultSet indexingProfileRS = getIndexingProfile.executeQuery();
				if (indexingProfileRS.next()){
					String ilsIndexingClassString = indexingProfileRS.getString("indexingClass");
					switch (ilsIndexingClassString) {
						case "Marmot":
							ilsRecordProcessors.put(curType, new MarmotRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "WCPL":
							ilsRecordProcessors.put(curType, new WCPLRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Aspencat":
							ilsRecordProcessors.put(curType, new AspencatRecordProcessor(this, dbConn, configIni, indexingProfileRS, logger, fullReindex));
							break;
						case "Flatirons":
							ilsRecordProcessors.put(curType, new FlatironsRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Arlington":
							ilsRecordProcessors.put(curType, new ArlingtonRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "ArlingtonKoha":
							ilsRecordProcessors.put(curType, new ArlingtonKohaRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "CarlX":
							ilsRecordProcessors.put(curType, new CarlXRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "SantaFe":
							ilsRecordProcessors.put(curType, new SantaFeRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "AACPL":
							ilsRecordProcessors.put(curType, new AACPLRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Lion":
							ilsRecordProcessors.put(curType, new LionRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "SideLoadedEContent":
							ilsRecordProcessors.put(curType, new SideLoadedEContentProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Koha":
							ilsRecordProcessors.put(curType, new KohaRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Symphony":
							ilsRecordProcessors.put(curType, new SymphonyRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						default:
							logEntry.incErrors("Unknown indexing class " + ilsIndexingClassString);
							break;
					}
				}else if (!curType.equals("cloud_library") && !curType.equals("rbdigital") && !curType.equals("rbdigital_magazine") && !curType.equals("hoopla") && !curType.equals("overdrive") && !curType.equals("axis360")) {
					getSideLoadSettings.setString(1, curType);
					ResultSet getSideLoadSettingsRS = getSideLoadSettings.executeQuery();
					if (getSideLoadSettingsRS.next()){
						String sideLoadIndexingClassString = getSideLoadSettingsRS.getString("indexingClass");
						if ("SideLoadedEContent".equals(sideLoadIndexingClassString) || "SideLoadedEContentProcessor".equals(sideLoadIndexingClassString)) {
							sideLoadProcessors.put(curType, new SideLoadedEContentProcessor(this, dbConn, getSideLoadSettingsRS, logger, fullReindex));
						} else {
							logEntry.incErrors("Unknown side load processing class " + sideLoadIndexingClassString);
							okToIndex = false;
							return;
						}
					}else{
						logEntry.addNote("Could not find indexing profile or side load settings for type " + curType);
					}
				}
			}

			setupIndexingStats();

		}catch (Exception e){
			logEntry.incErrors("Error loading record processors for ILS records", e);
		}
		overDriveProcessor = new OverDriveProcessor(this, dbConn, logger);

		rbdigitalProcessor = new RbdigitalProcessor(this, dbConn, logger);

		rbdigitalMagazineProcessor = new RbdigitalMagazineProcessor(this, dbConn, logger);

		cloudLibraryProcessor = new CloudLibraryProcessor(this, dbConn, logger);

		hooplaProcessor = new HooplaProcessor(this, dbConn, logger);

		axis360Processor = new Axis360Processor(this, dbConn, logger);

		//Load translation maps
		loadSystemTranslationMaps();

		//Setup prepared statements to load local enrichment
		try {
			//No need to filter for ratings greater than 0 because the user has to rate from 1-5
			getRatingStmt = dbConn.prepareStatement("SELECT AVG(rating) as averageRating, groupedRecordPermanentId from user_work_review where groupedRecordPermanentId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getNovelistStmt = dbConn.prepareStatement("SELECT * from novelist_data where groupedRecordPermanentId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getDisplayInfoStmt = dbConn.prepareStatement("SELECT * from grouped_work_display_info where permanent_id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getUserReadingHistoryLinkStmt = dbConn.prepareStatement("SELECT DISTINCT userId from user_reading_history_work where groupedWorkPermanentId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getUserRatingLinkStmt = dbConn.prepareStatement("SELECT DISTINCT userId from user_work_review where groupedRecordPermanentId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getUserNotInterestedLinkStmt = dbConn.prepareStatement("SELECT DISTINCT userId from user_not_interested where groupedRecordPermanentId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		} catch (SQLException e) {
			logEntry.incErrors("Could not prepare statements to load local enrichment", e);
		}

		String lexileExportPath = configIni.get("Reindex", "lexileExportPath");
		loadLexileData(lexileExportPath);

		if (clearIndex){
			clearIndex();
		}
	}

	public void close(){
		updateServer = null;
		ilsRecordProcessors.clear();
		sideLoadProcessors.clear();
		overDriveProcessor = null;
		rbdigitalProcessor = null;
		rbdigitalMagazineProcessor = null;
		cloudLibraryProcessor = null;
		axis360Processor = null;
		hooplaProcessor = null;
		translationMaps.clear();
		lexileInformation.clear();
		scopes.clear();
		try {
			getRatingStmt.close();
			getNovelistStmt.close();
			getDisplayInfoStmt.close();
			getGroupedWorkPrimaryIdentifiers.close();
		} catch (Exception e) {
			logEntry.incErrors("Error closing prepared statements in grouped work indexer", e);
		}
	}

	private void setupIndexingStats() {
		ArrayList<String> recordProcessorNames = new ArrayList<>(ilsRecordProcessors.keySet());
		recordProcessorNames.add("overdrive");

		for (Scope curScope : scopes){
			ScopedIndexingStats scopedIndexingStats = new ScopedIndexingStats(curScope.getScopeName(), recordProcessorNames);
			indexingStats.put(curScope.getScopeName(), scopedIndexingStats);
		}
	}

	boolean isOkToIndex(){
		return okToIndex;
	}

	TreeSet<String> overDriveRecordsSkipped = new TreeSet<>();
	private final TreeMap<String, ScopedIndexingStats> indexingStats = new TreeMap<>();

	private void loadLexileData(String lexileExportPath) {
		String[] lexileFields = new String[0];
		int curLine = 0;
		try{
			File lexileData = new File(lexileExportPath);
			BufferedReader lexileReader = new BufferedReader(new FileReader(lexileData));
			//Skip over the header
			lexileReader.readLine();
			String lexileLine = lexileReader.readLine();
			curLine++;
			while (lexileLine != null){
				lexileFields = lexileLine.split("\\t");
				LexileTitle titleInfo = new LexileTitle();
				if (lexileFields.length >= 11){
					titleInfo.setTitle(lexileFields[0]);
					titleInfo.setAuthor(lexileFields[1]);
					String isbn = lexileFields[3];
					titleInfo.setLexileCode(lexileFields[4]);
					titleInfo.setLexileScore(lexileFields[5]);
					titleInfo.setSeries(lexileFields[10]);
					if (lexileFields.length >= 12) {
						titleInfo.setAwards(lexileFields[11]);
					}
					if (lexileFields.length >= 13) {
						titleInfo.setDescription(lexileFields[12]);
					}
					lexileInformation.put(isbn, titleInfo);
				}
				lexileLine = lexileReader.readLine();
				curLine++;
			}
			logger.info("Read " + lexileInformation.size() + " lines of lexile data");
		}catch (FileNotFoundException fne){
			//This is normal
			//logEntry.addNote("Error loading lexile data, the file was not found at " + lexileExportPath);
		}catch (Exception e){
			logEntry.incErrors("Error loading lexile data on " + curLine +  Arrays.toString(lexileFields), e);
		}
	}

	private void clearIndex() {
		//Check to see if we should clear the existing index
		logger.info("Clearing existing marc records from index");
		try {
			updateServer.deleteByQuery("recordtype:grouped_work");
			//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
		} catch (HttpSolrClient.RemoteSolrException rse) {
			logEntry.incErrors("Solr is not running properly, try restarting", rse);
			System.exit(-1);
		} catch (Exception e) {
			logEntry.incErrors("Error deleting from index", e);
		}
	}

	public void deleteRecord(String permanentId, @SuppressWarnings("unused") Long groupedWorkId) {
		logger.info("Clearing existing work " + permanentId + " from index");
		try {
			updateServer.deleteById(permanentId);
			//With this commit, we get errors in the log "Previous SolrRequestInfo was not closed!"
			//Allow auto commit functionality to handle this
			totalRecordsHandled++;
			if (totalRecordsHandled % 25 == 0) {
				updateServer.commit(false, false, true);
			}

			//Delete the work from the database?
			//TODO: Should we do this or leave a record if it was linked to lists, reading history, etc?
			//TODO: Add a deleted flag since overdrive will return titles that can no longer be accessed?
			//TODO: If we restore deleting the grouped work we should clean up enrichment, reading history, etc
			//We would avoid continually deleting and re-adding?
			//MDN: leave the grouped work to deal with OverDrive records.  The grouped work will still be active, but
			//it won't be in search results.
			//deleteGroupedWorkStmt.setLong(1, groupedWorkId);
			//deleteGroupedWorkStmt.executeUpdate();

		} catch (Exception e) {
			logEntry.incErrors("Error deleting work from index", e);
		}
	}

	public void finishIndexingFromExtract(BaseLogEntry logEntry){
		try {
			processScheduledWorks(logEntry);

			updateServer.commit(false, false, true);
			logEntry.addNote("Shutting down the update server");
			updateServer.blockUntilFinished();
			updateServer.close();
		}catch (Exception e) {
			logEntry.incErrors("Error finishing extract ", e);
		}
	}

	public void commitChanges(){
		try {
			updateServer.commit(false, false, true);
		}catch (Exception e) {
			logEntry.incErrors("Error finishing extract ", e);
		}
	}

	private void processScheduledWorks(BaseLogEntry logEntry) {
		//Check to see what records still need to be indexed based on a timed index
		logEntry.addNote("Checking for additional works that need to be indexed");

		try {
			int numWorksProcessed = 0;
			getScheduledWorksStmt.setLong(1, new Date().getTime() / 1000);
			ResultSet scheduledWorksRS = getScheduledWorksStmt.executeQuery();
			while (scheduledWorksRS.next()) {
				long scheduleId = scheduledWorksRS.getLong("id");
				String workToProcess = scheduledWorksRS.getString("permanent_id");

				//reindex the actual work
				this.processGroupedWork(workToProcess);

				markScheduledWorkProcessedStmt.setLong(1, scheduleId);
				markScheduledWorkProcessedStmt.executeUpdate();
				numWorksProcessed++;
			}
			scheduledWorksRS.close();
			if (numWorksProcessed > 0){
				logEntry.addNote("Processed " + numWorksProcessed + " works that were scheduled for indexing");
			}
		}catch (Exception e){
			logEntry.addNote("Error updating scheduled works " + e.toString());
		}
	}

	void finishIndexing(){
		logEntry.addNote("Finishing indexing");
		if (fullReindex) {
			try {
				logEntry.addNote("Calling final commit");
				updateServer.commit(true, true, false);
			} catch (Exception e) {
				logEntry.incErrors("Error calling final commit", e);
			}
		}else {
			try {
				logEntry.addNote("Doing a soft commit to make sure changes are saved");
				updateServer.commit(false, false, true);
				logEntry.addNote("Shutting down the update server");
				updateServer.blockUntilFinished();
				updateServer.close();
			} catch (Exception e) {
				logEntry.incErrors("Error shutting down update server", e);
			}
		}

		updateLastReindexTime();
	}

	private void updateLastReindexTime() {
		//Update the last grouping time in the variables table.  This needs to be the time the index started to catch anything that changes during the index
		try{
			if (lastReindexTimeVariableId != null){
				PreparedStatement updateVariableStmt  = dbConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
				updateVariableStmt.setLong(1, indexStartTime);
				updateVariableStmt.setLong(2, lastReindexTimeVariableId);
				updateVariableStmt.executeUpdate();
				updateVariableStmt.close();
			} else{
				PreparedStatement insertVariableStmt = dbConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('last_reindex_time', ?)");
				insertVariableStmt.setString(1, Long.toString(indexStartTime));
				insertVariableStmt.executeUpdate();
				insertVariableStmt.close();
			}
		}catch (Exception e){
			logEntry.incErrors("Error setting last grouping time", e);
		}
	}

	void processGroupedWorks() {
		long numWorksProcessed = 0L;
		try {
			PreparedStatement getAllGroupedWorks;
			PreparedStatement getNumWorksToIndex;
			PreparedStatement setLastUpdatedTime = dbConn.prepareStatement("UPDATE grouped_work set date_updated = ? where id = ?");
			if (fullReindex){
				getAllGroupedWorks = dbConn.prepareStatement("SELECT * FROM grouped_work", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				getNumWorksToIndex = dbConn.prepareStatement("SELECT count(id) FROM grouped_work", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			}else{
				//Load all grouped works that have changed since the last time the index ran
				getAllGroupedWorks = dbConn.prepareStatement("SELECT * FROM grouped_work WHERE date_updated IS NULL OR date_updated >= ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				getAllGroupedWorks.setLong(1, lastReindexTime);
				getNumWorksToIndex = dbConn.prepareStatement("SELECT count(id) FROM grouped_work WHERE date_updated IS NULL OR date_updated >= ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				getNumWorksToIndex.setLong(1, lastReindexTime);
			}

			//Get the number of works we will be processing
			ResultSet numWorksToIndexRS = getNumWorksToIndex.executeQuery();
			numWorksToIndexRS.next();
			long numWorksToIndex = numWorksToIndexRS.getLong(1);
			logEntry.addNote("Starting to process " + numWorksToIndex + " grouped works");

			ResultSet groupedWorks = getAllGroupedWorks.executeQuery();
			while (groupedWorks.next()){
				long id = groupedWorks.getLong("id");
				String permanentId = groupedWorks.getString("permanent_id");
				String grouping_category = groupedWorks.getString("grouping_category");
				Long lastUpdated = groupedWorks.getLong("date_updated");
				if (groupedWorks.wasNull()){
					lastUpdated = null;
				}
				processGroupedWork(id, permanentId, grouping_category);

				numWorksProcessed++;
				if (logEntry instanceof NightlyIndexLogEntry){
					((NightlyIndexLogEntry) logEntry).incNumWorksProcessed();
				}
				if (!this.clearIndex && (numWorksProcessed % 5000 == 0)){
					//Testing shows that regular commits do seem to improve performance.
					//However, we can't do it too often or we get errors with too many searchers warming.
					//This is happening now with the auto commit settings in solrconfig.xml
					if (numWorksProcessed % 10000 == 0) {
						try {
							logger.info("Doing a regular commit during full indexing");
							updateServer.commit(false, false, true);
						} catch (Exception e) {
							logger.warn("Error committing changes", e);
						}
					}
					//Change to a debug statement to avoid filling up the notes.
					logger.debug("Processed " + numWorksProcessed + " grouped works processed.");
				}
				if (lastUpdated == null){
					setLastUpdatedTime.setLong(1, indexStartTime - 1); //Set just before the index started so we don't index multiple times
					setLastUpdatedTime.setLong(2, id);
					setLastUpdatedTime.executeUpdate();
				}
			}
		} catch (SQLException e) {
			logEntry.incErrors("Unexpected SQL error", e);
		}
		logger.info("Finished processing grouped works.  Processed a total of " + numWorksProcessed + " grouped works");
	}

	public void processGroupedWork(String permanentId) {
		try{
			getGroupedWorkInfoStmt.setString(1, permanentId);
			ResultSet getGroupedWorkInfoRS = getGroupedWorkInfoStmt.executeQuery();
			if (getGroupedWorkInfoRS.next()) {
				long id = getGroupedWorkInfoRS.getLong("id");
				String grouping_category = getGroupedWorkInfoRS.getString("grouping_category");
				processGroupedWork(id, permanentId, grouping_category);
			}
			totalRecordsHandled++;
			if (totalRecordsHandled % 25 == 0) {
				updateServer.commit(false, false, true);
			}
		} catch (Exception e) {
			logEntry.incErrors("Error indexing grouped work by id", e);
		}

	}

	void processGroupedWork(Long id, String permanentId, String grouping_category) throws SQLException {
		//Create a solr record for the grouped work
		GroupedWorkSolr groupedWork = new GroupedWorkSolr(this, logger);
		groupedWork.setId(permanentId);
		groupedWork.setGroupingCategory(grouping_category);

		getGroupedWorkPrimaryIdentifiers.setLong(1, id);
		ResultSet groupedWorkPrimaryIdentifiers = getGroupedWorkPrimaryIdentifiers.executeQuery();
		int numPrimaryIdentifiers = 0;
		while (groupedWorkPrimaryIdentifiers.next()){
			String type = groupedWorkPrimaryIdentifiers.getString("type");
			String identifier = groupedWorkPrimaryIdentifiers.getString("identifier");

			//Make a copy of the grouped work so we can revert if we don't add any records
			GroupedWorkSolr originalWork;
			try {
				originalWork = groupedWork.clone();
			}catch (CloneNotSupportedException cne){
				logEntry.incErrors("Could not clone grouped work", cne);
				return;
			}
			//Figure out how many records we had originally
			int numRecords = groupedWork.getNumRecords();
			logger.debug("Processing " + type + ":" + identifier + " work currently has " + numRecords + " records");

			//This does the bulk of the work building fields for the solr document
			updateGroupedWorkForPrimaryIdentifier(groupedWork, type, identifier);

			//If we didn't add any records to the work (because they are all suppressed) revert to the original
			if (groupedWork.getNumRecords() == numRecords){
				//No change in the number of records, revert to the previous
				logger.debug("Record " + type + ":" + identifier + " did not contribute any records to work " + permanentId + ", reverting to previous state " + groupedWork.getNumRecords());
				groupedWork = originalWork;
			}else{
				logger.debug("Record " + identifier + " added to work " + permanentId);
				numPrimaryIdentifiers++;
			}
		}
		groupedWorkPrimaryIdentifiers.close();

		if (numPrimaryIdentifiers > 0) {
			//Strip out any hoopla records that have the same format as an rbdigital or overdrive record
			if (removeRedundantHooplaRecords) {
				groupedWork.removeRedundantHooplaRecords();
			}

			//Add a grouped work to any scopes that are relevant
			groupedWork.updateIndexingStats(indexingStats);

			//Load local enrichment for the work
			loadLocalEnrichment(groupedWork);
			//Load links for how users have interacted with the work
			loadUserLinkages(groupedWork);
			//Load lexile data for the work
			loadLexileDataForWork(groupedWork);
			//Load accelerated reader data for the work
			loadAcceleratedDataForWork(groupedWork);
			//Load Novelist data
			loadNovelistInfo(groupedWork);
			//Load Display Info
			loadDisplayInfo(groupedWork);

			//Write the record to Solr.
			try {
				SolrInputDocument inputDocument = groupedWork.getSolrDocument(logEntry);
				UpdateResponse response = updateServer.add(inputDocument);
				if (response.getException() != null){
					logEntry.incErrors("Error adding Solr record for " + groupedWork.getId() + " response: " + response);
				}
				//logger.debug("Updated solr \r\n" + inputDocument.toString());
				//Check to see if we need to automatically reindex this record in the future.
				HashSet<Long> autoReindexTimes = groupedWork.getAutoReindexTimes();
				if (autoReindexTimes.size() > 0){
					for (Long autoReindexTime : autoReindexTimes) {
						getScheduledWorkStmt.setString(1, groupedWork.getId());
						getScheduledWorkStmt.setLong(2, autoReindexTime);
						ResultSet getScheduledWorkRS = getScheduledWorkStmt.executeQuery();
						if (!getScheduledWorkRS.next()) {
							try {
								addScheduledWorkStmt.setString(1, groupedWork.getId());
								addScheduledWorkStmt.setLong(2, autoReindexTime);
								addScheduledWorkStmt.executeUpdate();
							} catch (SQLException sqe) {
								logEntry.incErrors("Error adding scheduled reindex time", sqe);
							}
						}
						getScheduledWorkRS.close();
					}
				}

			} catch (Exception e) {
				logEntry.incErrors("Error adding grouped work to solr " + groupedWork.getId(), e);
			}
		}else{
			//Log that this record did not have primary identifiers after
			logger.debug("Grouped work " + permanentId + " did not have any primary identifiers for it, suppressing");
			if (!this.clearIndex){
				this.deleteRecord(permanentId, id);
			}

		}

	}

	private void loadLexileDataForWork(GroupedWorkSolr groupedWork) {
		for(String isbn : groupedWork.getIsbns()){
			if (lexileInformation.containsKey(isbn)){
				LexileTitle lexileTitle = lexileInformation.get(isbn);
				String lexileCode = lexileTitle.getLexileCode();
				if (lexileCode.length() > 0){
					groupedWork.setLexileCode(this.translateSystemValue("lexile_code", lexileCode, groupedWork.getId()));
				}
				groupedWork.setLexileScore(lexileTitle.getLexileScore());
				groupedWork.addAwards(lexileTitle.getAwards());
				if (lexileTitle.getSeries().length() > 0){
					groupedWork.addSeries(lexileTitle.getSeries());
				}
				break;
			}
		}
	}

	private void loadAcceleratedDataForWork(GroupedWorkSolr groupedWork){
		try {
			for (String isbn : groupedWork.getIsbns()){
				getArBookIdForIsbnStmt.setString(1, isbn);
				ResultSet arBookIdRS = getArBookIdForIsbnStmt.executeQuery();
				if (arBookIdRS.next()){
					String arBookId = arBookIdRS.getString("arBookId");
					getArBookInfoStmt.setString(1, arBookId);
					ResultSet arBookInfoRS = getArBookInfoStmt.executeQuery();
					if (arBookInfoRS.next()){
						String bookLevel = arBookInfoRS.getString("bookLevel");
						if (bookLevel.length() > 0){
							groupedWork.setAcceleratedReaderReadingLevel(bookLevel);
						}
						groupedWork.setAcceleratedReaderPointValue(arBookInfoRS.getString("arPoints"));
						groupedWork.setAcceleratedReaderInterestLevel(arBookInfoRS.getString("interestLevel"));
						break;
					}
				}
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading accelerated reader information", e);
		}
	}

	private void loadLocalEnrichment(GroupedWorkSolr groupedWork) {
		//Load rating
		try{
			getRatingStmt.setString(1, groupedWork.getId());
			ResultSet ratingsRS = getRatingStmt.executeQuery();
			if (ratingsRS.next()){
				float averageRating = ratingsRS.getFloat("averageRating");
				if (!ratingsRS.wasNull()){
					groupedWork.setRating(averageRating);
				}
			}
			ratingsRS.close();
		}catch (Exception e){
			logEntry.incErrors("Unable to load local enrichment", e);
		}
	}

	private void loadUserLinkages(GroupedWorkSolr groupedWork) {
		try {
			//Add users with the work in their reading history
			getUserReadingHistoryLinkStmt.setString(1, groupedWork.getId());
			ResultSet userReadingHistoryRS = getUserReadingHistoryLinkStmt.executeQuery();
			while (userReadingHistoryRS.next()){
				groupedWork.addReadingHistoryLink(userReadingHistoryRS.getLong("userId"));
			}
			userReadingHistoryRS.close();
			//Add users who rated the title
			getUserRatingLinkStmt.setString(1, groupedWork.getId());
			ResultSet userRatingRS = getUserRatingLinkStmt.executeQuery();
			while (userRatingRS.next()){
				groupedWork.addRatingLink(userRatingRS.getLong("userId"));
			}
			userRatingRS.close();
			//Add users who are not interested in the title
			getUserNotInterestedLinkStmt.setString(1, groupedWork.getId());
			ResultSet userNotInterestedRS = getUserNotInterestedLinkStmt.executeQuery();
			while (userNotInterestedRS.next()) {
				groupedWork.addNotInterestedLink(userNotInterestedRS.getLong("userId"));
			}
			userNotInterestedRS.close();
			//Add users who have a hold on the title
			//Add users who have the title checked out
		}catch (Exception e){
			logEntry.incErrors("Unable to load user linkages", e);
		}
	}

	private void loadNovelistInfo(GroupedWorkSolr groupedWork){
		try{
			getNovelistStmt.setString(1, groupedWork.getId());
			ResultSet novelistRS = getNovelistStmt.executeQuery();
			if (novelistRS.next()){
				String series = novelistRS.getString("seriesTitle");
				if (!novelistRS.wasNull()){
					//Don't clear since there are valid cases when they are different
					//groupedWork.clearSeriesData();
					//groupedWork.addSeries(series);
					String volume = novelistRS.getString("volume");
					if (novelistRS.wasNull()){
						volume = "";
					}
					groupedWork.addSeriesWithVolume(series, volume);
				}
			}
			novelistRS.close();
		}catch (Exception e){
			logEntry.incErrors("Unable to load novelist data", e);
		}
	}

	private void loadDisplayInfo(GroupedWorkSolr groupedWork) {
		try {
			getDisplayInfoStmt.setString(1, groupedWork.getId());
			ResultSet displayInfoRS = getDisplayInfoStmt.executeQuery();
			if (displayInfoRS.next()) {
				String title = displayInfoRS.getString("title");
				if (title.length() > 0){
					groupedWork.setTitle(title, title, StringUtils.makeValueSortable(title), "", true);
					groupedWork.clearSubTitle();
				}
				String author = displayInfoRS.getString("author");
				if (author.length() > 0){
					groupedWork.setAuthorDisplay(author);
				}
				String seriesName = displayInfoRS.getString("seriesName");
				String seriesDisplayOrder = displayInfoRS.getString("seriesDisplayOrder");
				if (seriesName.length() > 0) {
					groupedWork.clearSeries();
					groupedWork.addSeries(seriesName);
					if (seriesDisplayOrder.length() > 0) {
						groupedWork.addSeriesWithVolume(seriesName, seriesDisplayOrder);
					}
				}
			}
		}catch (Exception e){
			logEntry.incErrors("Unable to load display info", e);
		}
	}

	private void updateGroupedWorkForPrimaryIdentifier(GroupedWorkSolr groupedWork, String type, String identifier)  {
		groupedWork.addAlternateId(identifier);
		type = type.toLowerCase();
		switch (type) {
			case "overdrive":
				overDriveProcessor.processRecord(groupedWork, identifier, logEntry);
				break;
			case "rbdigital":
				rbdigitalProcessor.processRecord(groupedWork, identifier, logEntry);
				break;
			case "rbdigital_magazine":
				rbdigitalMagazineProcessor.processRecord(groupedWork, identifier, logEntry);
				break;
			case "hoopla":
				hooplaProcessor.processRecord(groupedWork, identifier, logEntry);
				break;
			case "cloud_library":
				cloudLibraryProcessor.processRecord(groupedWork, identifier, logEntry);
				break;
			case "axis360":
				axis360Processor.processRecord(groupedWork, identifier, logEntry);
				break;
			default:
				if (ilsRecordProcessors.containsKey(type)) {
					ilsRecordProcessors.get(type).processRecord(groupedWork, identifier);
				}else if (sideLoadProcessors.containsKey(type)){
					sideLoadProcessors.get(type).processRecord(groupedWork, identifier);
				}else{
					//This happens if a side load processor is deleted and all the related record don't get cleaned up.
					logger.debug("Could not find a record processor for type " + type);
				}
				break;
		}
	}

	/**
	 * System translation maps are used for things that are not customizable (or that shouldn't be customized)
	 * by library.  For example, translations of language codes, or things where MARC standards define the values.
	 *
	 * We can also load translation maps that are specific to an indexing profile.  That is done within
	 * the record processor itself.
	 */
	private void loadSystemTranslationMaps(){
		//Load all translationMaps, first from default, then from the site specific configuration
		File defaultTranslationMapDirectory = new File("../../sites/default/translation_maps");
		File[] defaultTranslationMapFiles = defaultTranslationMapDirectory.listFiles((dir, name) -> name.endsWith("properties"));

		File serverTranslationMapDirectory = new File("../../sites/" + serverName + "/translation_maps");
		File[] serverTranslationMapFiles = serverTranslationMapDirectory.listFiles((dir, name) -> name.endsWith("properties"));

		if (defaultTranslationMapFiles != null) {
			for (File curFile : defaultTranslationMapFiles) {
				String mapName = curFile.getName().replace(".properties", "");
				mapName = mapName.replace("_map", "");
				translationMaps.put(mapName, loadSystemTranslationMap(curFile));
			}
			if (serverTranslationMapFiles != null) {
				for (File curFile : serverTranslationMapFiles) {
					String mapName = curFile.getName().replace(".properties", "");
					mapName = mapName.replace("_map", "");
					translationMaps.put(mapName, loadSystemTranslationMap(curFile));
				}
			}
		}
	}

	private HashMap<String, String> loadSystemTranslationMap(File translationMapFile) {
		Properties props = new Properties();
		try {
			props.load(new FileReader(translationMapFile));
		} catch (IOException e) {
			logEntry.incErrors("Could not read translation map, " + translationMapFile.getAbsolutePath(), e);
		}
		HashMap<String, String> translationMap = new HashMap<>();
		for (Object keyObj : props.keySet()){
			String key = (String)keyObj;
			translationMap.put(key.toLowerCase(), props.getProperty(key));
		}
		return translationMap;
	}

	boolean hasSystemTranslation(String mapName, String value) {
		return translationMaps.containsKey(mapName) && translationMaps.get(mapName).containsKey(value);
	}
	private final HashSet<String> unableToTranslateWarnings = new HashSet<>();
	private final HashSet<String> missingTranslationMaps = new HashSet<>();
	String translateSystemValue(String mapName, String value, String identifier){
		if (value == null){
			return null;
		}
		HashMap<String, String> translationMap = translationMaps.get(mapName);
		String translatedValue;
		if (translationMap == null){
			if (!missingTranslationMaps.contains(mapName)) {
				missingTranslationMaps.add(mapName);
				logEntry.incErrors("Unable to find system translation map for " + mapName);
			}
			translatedValue = value;
		}else{
			String lowerCaseValue = value.toLowerCase();
			if (translationMap.containsKey(lowerCaseValue)){
				translatedValue = translationMap.get(lowerCaseValue);
			}else{
				if (translationMap.containsKey("*")){
					translatedValue = translationMap.get("*");
				}else{
					String concatenatedValue = mapName + ":" + value;
					if (!unableToTranslateWarnings.contains(concatenatedValue)){
						if (fullReindex) {
							logger.warn("Could not translate '" + concatenatedValue + "' sample record " + identifier);
						}
						unableToTranslateWarnings.add(concatenatedValue);
					}
					translatedValue = value;
				}
			}
		}
		if (translatedValue != null){
			translatedValue = translatedValue.trim();
			if (translatedValue.length() == 0){
				translatedValue = null;
			}
		}
		return translatedValue;
	}

	LinkedHashSet<String> translateSystemCollection(String mapName, Set<String> values, String identifier) {
		LinkedHashSet<String> translatedCollection = new LinkedHashSet<>();
		for (String value : values){
				String translatedValue = translateSystemValue(mapName, value, identifier);
				if (translatedValue != null) {
						translatedCollection.add(translatedValue);
					}
			}
		return  translatedCollection;
	}

	TreeSet<Scope> getScopes() {
		return this.scopes;
	}
}
