package com.turning_leaf_technologies.reindexer;

import com.jcraft.jsch.*;
import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.file.UnzipUtility;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import org.apache.solr.client.solrj.impl.BinaryRequestWriter;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.impl.HttpSolrClient;
import org.apache.solr.common.SolrInputDocument;
import org.ini4j.Ini;

import java.io.*;
import java.sql.*;
import java.util.*;
import java.util.Date;

import org.apache.logging.log4j.Logger;

import javax.xml.parsers.SAXParser;
import javax.xml.parsers.SAXParserFactory;

public class GroupedWorkIndexer {
	private Ini configIni;
	private String serverName;
	private String solrPort;
	private Logger logger;
	private Long indexStartTime;
	private int totalRecordsHandled = 0;
	private ConcurrentUpdateSolrClient updateServer;
	private HashMap<String, MarcRecordProcessor> ilsRecordProcessors = new HashMap<>();
	private OverDriveProcessor overDriveProcessor;
	private RbdigitalProcessor rbdigitalProcessor;
	private HashMap<String, HashMap<String, String>> translationMaps = new HashMap<>();
	private HashMap<String, LexileTitle> lexileInformation = new HashMap<>();
	private Long maxWorksToProcess = -1L;

	private PreparedStatement getRatingStmt;
	private PreparedStatement getNovelistStmt;
	private Connection dbConn;

	static int availableAtBoostValue = 50;
	static int ownedByBoostValue = 10;

	private boolean fullReindex;
	private long lastReindexTime;
	private Long lastReindexTimeVariableId;
	private boolean okToIndex = true;


	private TreeSet<Scope> scopes ;

	private PreparedStatement getGroupedWorkPrimaryIdentifiers;
	private PreparedStatement getDateFirstDetectedStmt;
	private PreparedStatement getGroupedWorkInfoStmt;
	private PreparedStatement getArBookIdForIsbnStmt;
	private PreparedStatement getArBookInfoStmt;

	private static PreparedStatement deleteGroupedWorkStmt;

	public GroupedWorkIndexer(String serverName, Connection dbConn, Ini configIni, boolean fullReindex, boolean clearIndex, boolean singleWorkIndex, Logger logger) {
		indexStartTime = new Date().getTime() / 1000;
		this.serverName = serverName;
		this.logger = logger;
		this.dbConn = dbConn;
		this.fullReindex = fullReindex;
		this.configIni = configIni;

		solrPort = configIni.get("Reindex", "solrPort");

		String maxWorksToProcessStr = ConfigUtil.cleanIniValue(configIni.get("Reindex", "maxWorksToProcess"));
		if (maxWorksToProcessStr != null && maxWorksToProcessStr.length() > 0){
			try{
				maxWorksToProcess = Long.parseLong(maxWorksToProcessStr);
				logger.warn("Processing a maximum of " + maxWorksToProcess + " works");
			}catch (NumberFormatException e){
				logger.warn("Unable to parse max works to process " + maxWorksToProcessStr);
			}
		}

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
			logger.error("Could not load last index time from variables table ", e);
		}

		//Load a few statements we will need later
		try{
			getGroupedWorkPrimaryIdentifiers = dbConn.prepareStatement("SELECT * FROM grouped_work_primary_identifiers where grouped_work_id = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getDateFirstDetectedStmt = dbConn.prepareStatement("SELECT dateFirstDetected FROM ils_marc_checksums WHERE source = ? AND ilsId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			deleteGroupedWorkStmt = dbConn.prepareStatement("DELETE from grouped_work where id = ?");
			getGroupedWorkInfoStmt = dbConn.prepareStatement("SELECT id, grouping_category from grouped_work where permanent_id = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getArBookIdForIsbnStmt = dbConn.prepareStatement("SELECT arBookId from accelerated_reading_isbn where isbn = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getArBookInfoStmt = dbConn.prepareStatement("SELECT * from accelerated_reading_titles where arBookId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
		} catch (Exception e){
			logger.error("Could not load statements to get identifiers ", e);
		}

		//Initialize the updateServer and solr server
		GroupedReindexMain.addNoteToReindexLog("Setting up update server and solr server");
		//SolrClient solrServer;
		if (fullReindex){
			//MDN 10-21-2015 - use the grouped core since we are using replication.
			ConcurrentUpdateSolrClient.Builder solrBuilder = new ConcurrentUpdateSolrClient.Builder("http://localhost:" + solrPort + "/solr/grouped_works");
			solrBuilder.withThreadCount(1);
			solrBuilder.withQueueSize(25);
			updateServer = solrBuilder.build();
			updateServer.setRequestWriter(new BinaryRequestWriter());
			//HttpSolrClient.Builder httpBuilder = new HttpSolrClient.Builder("http://localhost:" + solrPort + "/solr/grouped_works");
			//solrServer = httpBuilder.build();

			//Stop replication from the master
			String url = "http://localhost:" + solrPort + "/solr/grouped_works/replication?command=disablereplication";
			WebServiceResponse stopReplicationResponse = NetworkUtils.getURL(url, logger);
			if (!stopReplicationResponse.isSuccess()){
				logger.error("Error restarting replication " + stopReplicationResponse.getMessage());
			}
		}else{
			//TODO: Bypass this if called from an export process?

			//Check to make sure that at least a couple of minutes have elapsed since the last index
			//Periodically in the middle of the night we get indexes every minute or multiple times a minute
			//which is annoying especially since it generally means nothing is changing.
			long elapsedTime = indexStartTime - lastReindexTime;
			long minIndexingInterval = 2 * 60;
			if (elapsedTime < minIndexingInterval && !singleWorkIndex) {
				try {
					logger.debug("Pausing between indexes, last index ran " + Math.ceil(elapsedTime / 60f) + " minutes ago");
					logger.debug("Pausing for " + (minIndexingInterval - elapsedTime) + " seconds");
					GroupedReindexMain.addNoteToReindexLog("Pausing between indexes, last index ran " + Math.ceil(elapsedTime / 60f) + " minutes ago");
					GroupedReindexMain.addNoteToReindexLog("Pausing for " + (minIndexingInterval - elapsedTime) + " seconds");
					Thread.sleep((minIndexingInterval - elapsedTime) * 1000);
				} catch (InterruptedException e) {
					logger.warn("Pause was interrupted while pausing between indexes");
				}
			}else{
				GroupedReindexMain.addNoteToReindexLog("Index last ran " + (elapsedTime) + " seconds ago");
			}

			ConcurrentUpdateSolrClient.Builder solrBuilder = new ConcurrentUpdateSolrClient.Builder("http://localhost:" + solrPort + "/solr/grouped_works");
			solrBuilder.withThreadCount(1);
			solrBuilder.withQueueSize(25);
			updateServer = solrBuilder.build();
			updateServer.setRequestWriter(new BinaryRequestWriter());
			//HttpSolrClient.Builder solrServerBuilder = new HttpSolrClient.Builder("http://localhost:" + solrPort + "/solr/grouped_works");
			//solrServer = solrServerBuilder.build();
		}

		scopes = IndexingUtils.loadScopes(dbConn, logger);
		logger.info("Loaded " + scopes.size() + " scopes");

		//Initialize processors based on our indexing profiles and the primary identifiers for the records.
		try {
			PreparedStatement uniqueIdentifiersStmt = dbConn.prepareStatement("SELECT DISTINCT type FROM grouped_work_primary_identifiers");
			PreparedStatement getIndexingProfile = dbConn.prepareStatement("SELECT * from indexing_profiles where name = ?");
			ResultSet uniqueIdentifiersRS = uniqueIdentifiersStmt.executeQuery();

			while (uniqueIdentifiersRS.next()){
				String curIdentifier = uniqueIdentifiersRS.getString("type");
				getIndexingProfile.setString(1, curIdentifier);
				ResultSet indexingProfileRS = getIndexingProfile.executeQuery();
				if (indexingProfileRS.next()){
					String ilsIndexingClassString =    indexingProfileRS.getString("indexingClass");
					switch (ilsIndexingClassString) {
						case "Marmot":
							ilsRecordProcessors.put(curIdentifier, new MarmotRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Nashville":
							ilsRecordProcessors.put(curIdentifier, new NashvilleRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "NashvilleSchools":
							ilsRecordProcessors.put(curIdentifier, new NashvilleSchoolsRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "WCPL":
							ilsRecordProcessors.put(curIdentifier, new WCPLRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Aspencat":
							ilsRecordProcessors.put(curIdentifier, new AspencatRecordProcessor(this, dbConn, configIni, indexingProfileRS, logger, fullReindex));
							break;
						case "Flatirons":
							ilsRecordProcessors.put(curIdentifier, new FlatironsRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Hoopla":
							ilsRecordProcessors.put(curIdentifier, new HooplaProcessor(this, indexingProfileRS, logger));
							break;
						case "Arlington":
							ilsRecordProcessors.put(curIdentifier, new ArlingtonRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "CarlX":
							ilsRecordProcessors.put(curIdentifier, new CarlXRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "SantaFe":
							ilsRecordProcessors.put(curIdentifier, new SantaFeRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "AACPL":
							ilsRecordProcessors.put(curIdentifier, new AACPLRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Lion":
							ilsRecordProcessors.put(curIdentifier, new LionRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "SideLoadedEContent":
							ilsRecordProcessors.put(curIdentifier, new SideLoadedEContentProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Koha":
							ilsRecordProcessors.put(curIdentifier, new KohaRecordProcessor(this, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						default:
							logger.error("Unknown indexing class " + ilsIndexingClassString);
							okToIndex = false;
							return;
					}
				}else{
					logger.debug("Could not find indexing profile for type " + curIdentifier);
				}
			}

			setupIndexingStats();

		}catch (Exception e){
			logger.error("Error loading record processors for ILS records", e);
		}
		overDriveProcessor = new OverDriveProcessor(this, dbConn, logger);

		rbdigitalProcessor = new RbdigitalProcessor(this, dbConn, logger);

		//Load translation maps
		loadSystemTranslationMaps();

		//Setup prepared statements to load local enrichment
		try {
			//No need to filter for ratings greater than 0 because the user has to rate from 1-5
			getRatingStmt = dbConn.prepareStatement("SELECT AVG(rating) as averageRating, groupedRecordPermanentId from user_work_review where groupedRecordPermanentId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getNovelistStmt = dbConn.prepareStatement("SELECT * from novelist_data where groupedRecordPermanentId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		} catch (SQLException e) {
			logger.error("Could not prepare statements to load local enrichment", e);
		}

		String lexileExportPath = configIni.get("Reindex", "lexileExportPath");
		loadLexileData(lexileExportPath);

		loadAcceleratedReaderData();

		if (clearIndex){
			clearIndex();
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
	private TreeMap<String, ScopedIndexingStats> indexingStats = new TreeMap<>();

	private void loadAcceleratedReaderData(){
		try{
			PreparedStatement arSettingsStmt = dbConn.prepareStatement("SELECT * FROM accelerated_reading_settings");
			ResultSet arSettingsRS = arSettingsStmt.executeQuery();
			if (arSettingsRS.next()){
				long lastFetched = arSettingsRS.getLong("lastFetched");
				//Update if we have never updated or if we last updated more than a week ago
				//If we are updating, update the settings table right away so multiple processors don't update at the same time
				if (lastFetched < ((new Date().getTime() / 1000) - (7 * 24 * 60 * 60 * 1000))){
					PreparedStatement updateSettingsStmt = dbConn.prepareStatement("UPDATE accelerated_reading_settings SET lastFetched = ?");
					updateSettingsStmt.setLong(1, (new Date().getTime() / 1000));

					updateSettingsStmt.executeUpdate();

					//Fetch the latest file from the SFTP server
					String ftpServer = arSettingsRS.getString("ftpServer");
					String ftpUser = arSettingsRS.getString("ftpUser");
					String ftpPassword = arSettingsRS.getString("ftpPassword");
					String arExportPath = arSettingsRS.getString("arExportPath");

					String remoteFile = "/RLI-ARDATA-XML.ZIP";
					File localFile = new File(arExportPath + "/RLI-ARDATA-XML.ZIP");

					JSch jsch = new JSch();
					Session session;
					try {
						session = jsch.getSession(ftpUser, ftpServer, 22);
						session.setConfig("StrictHostKeyChecking", "no");
						session.setPassword(ftpPassword);
						session.connect();

						Channel channel = session.openChannel("sftp");
						channel.connect();
						ChannelSftp sftpChannel = (ChannelSftp) channel;
						sftpChannel.get(remoteFile, new FileOutputStream(localFile));
						sftpChannel.exit();
						session.disconnect();
					} catch (JSchException e) {
						logger.error("JSch Error retrieving accelerated reader file from server", e);
					} catch (SftpException e) {
						logger.error("Sftp Error retrieving accelerated reader file from server", e);
					}

					if (localFile.exists()){
						UnzipUtility.unzip(localFile.getPath(), arExportPath);

						//Update the database
						//Load the ar_titles xml file
						File arTitles = new File(arExportPath + "/ar_titles.xml");
						loadAcceleratedReaderTitlesXMLFile(arTitles);

						//Load the ar_titles_isbn xml file
						File arTitlesIsbn = new File(arExportPath + "/ar_titles_isbn.xml");
						loadAcceleratedReaderTitlesIsbnXMLFile(arTitlesIsbn);
					}
				}
			}
		}catch (Exception e){
			logger.error("Error loading accelerated reader data", e);
		}
	}

	private void loadAcceleratedReaderTitlesIsbnXMLFile(File arTitlesIsbn) {
		try {
			logger.info("Loading ar isbns from " + arTitlesIsbn);

			SAXParserFactory saxParserFactory = SAXParserFactory.newInstance();
			SAXParser saxParser = saxParserFactory.newSAXParser();
			ArTitleIsbnsHandler handler = new ArTitleIsbnsHandler(dbConn, logger);
			saxParser.parse(arTitlesIsbn, handler);
		} catch (Exception e) {
			logger.error("Error parsing Accelerated Reader Title data ", e);
		}
	}

	private void loadAcceleratedReaderTitlesXMLFile(File arTitles) {
		try {
			logger.info("Loading ar titles from " + arTitles);

			SAXParserFactory saxParserFactory = SAXParserFactory.newInstance();
			SAXParser saxParser = saxParserFactory.newSAXParser();
			ArTitlesHandler handler = new ArTitlesHandler(dbConn, logger);
			saxParser.parse(arTitles, handler);
		} catch (Exception e) {
			logger.error("Error parsing Accelerated Reader Title data ", e);
		}
	}

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
			logger.warn("Error loading lexile data, the file was not found at " + lexileExportPath);
		}catch (Exception e){
			logger.warn("Error loading lexile data on " + curLine +  Arrays.toString(lexileFields), e);
		}
	}

	private void clearIndex() {
		//Check to see if we should clear the existing index
		logger.info("Clearing existing marc records from index");
		try {
			updateServer.deleteByQuery("recordtype:grouped_work");
			//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
			//With this commit, we get errors in the log "Previous SolrRequestInfo was not closed!"
			//Allow auto commit functionality to handle this
			//updateServer.commit(true, false, false);
		} catch (HttpSolrClient.RemoteSolrException rse) {
			logger.error("Solr is not running properly, try restarting", rse);
			System.exit(-1);
		} catch (Exception e) {
			logger.error("Error deleting from index", e);
		}
	}

	public void deleteRecord(String permanentId, Long groupedWorkId) {
		logger.info("Clearing existing work " + permanentId + " from index");
		try {
			updateServer.deleteById(permanentId);
			//With this commit, we get errors in the log "Previous SolrRequestInfo was not closed!"
			//Allow auto commit functionality to handle this
			//updateServer.commit(true, false, false);
			totalRecordsHandled++;
			if (totalRecordsHandled % 25 == 0) {
				updateServer.commit(false, false, true);
			}

			//Delete the work from the database?
			//TODO: Should we do this or leave a record if it was linked to lists, reading history, etc?
			//TODO: Add a deleted flag since overdrive will return titles that can no longer be accessed?
			//We would avoid continually deleting and re-adding?
			deleteGroupedWorkStmt.setLong(1, groupedWorkId);
			deleteGroupedWorkStmt.executeUpdate();

		} catch (Exception e) {
			logger.error("Error deleting work from index", e);
		}
	}

	void createSiteMaps(HashMap<Scope, ArrayList<SiteMapEntry>>siteMapsByScope, HashSet<Long> uniqueGroupedWorks ) {

		File dataDir = new File(configIni.get("SiteMap", "filePath"));
		String maxPopTitlesDefault = configIni.get("SiteMap", "num_titles_in_most_popular_sitemap");
		String maxUniqueTitlesDefault = configIni.get("SiteMap", "num_title_in_unique_sitemap");
		String url = configIni.get("Site", "url");
		try {
			SiteMap siteMap = new SiteMap(logger, dbConn, Integer.parseInt(maxUniqueTitlesDefault), Integer.parseInt(maxPopTitlesDefault));
			siteMap.createSiteMaps(url, dataDir, siteMapsByScope, uniqueGroupedWorks);

		} catch (IOException ex) {
			logger.error("Error creating site map");
		}
	}

	public void finishIndexingFromExtract(){
		try {
			updateServer.commit(false, false, true);
			GroupedReindexMain.addNoteToReindexLog("Shutting down the update server");
			updateServer.blockUntilFinished();
			updateServer.close();
		}catch (Exception e) {
			logger.error("Error finishing extract ", e);
		}
	}
	void finishIndexing(){
		GroupedReindexMain.addNoteToReindexLog("Finishing indexing");
		logger.info("Finishing indexing");
		if (fullReindex) {
			try {
				GroupedReindexMain.addNoteToReindexLog("Calling final commit");
				updateServer.commit(true, true, false);
			} catch (Exception e) {
				logger.error("Error calling final commit", e);
			}
			//Swap the indexes
			if (fullReindex)  {
				//Restart replication from the master
				String url = "http://localhost:" + solrPort + "/solr/grouped_works/replication?command=enablereplication";
				WebServiceResponse startReplicationResponse = NetworkUtils.getURL(url, logger);
				if (!startReplicationResponse.isSuccess()){
					logger.error("Error restarting replication " + startReplicationResponse.getMessage());
				}
			}
		}else {
			try {
				GroupedReindexMain.addNoteToReindexLog("Doing a soft commit to make sure changes are saved");
				updateServer.commit(false, false, true);
				GroupedReindexMain.addNoteToReindexLog("Shutting down the update server");
				updateServer.blockUntilFinished();
				updateServer.close();
			} catch (Exception e) {
				logger.error("Error shutting down update server", e);
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
			logger.error("Error setting last grouping time", e);
		}
	}

	Long processGroupedWorks(HashMap<Scope, ArrayList<SiteMapEntry>> siteMapsByScope, HashSet<Long> uniqueGroupedWorks) {
		Long numWorksProcessed = 0L;
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
			GroupedReindexMain.addNoteToReindexLog("Starting to process " + numWorksToIndex + " grouped works");

			ResultSet groupedWorks = getAllGroupedWorks.executeQuery();
			while (groupedWorks.next()){
				long id = groupedWorks.getLong("id");
				String permanentId = groupedWorks.getString("permanent_id");
				String grouping_category = groupedWorks.getString("grouping_category");
				Long lastUpdated = groupedWorks.getLong("date_updated");
				if (groupedWorks.wasNull()){
					lastUpdated = null;
				}
				processGroupedWork(id, permanentId, grouping_category, siteMapsByScope, uniqueGroupedWorks);

				numWorksProcessed++;
				if (fullReindex && (numWorksProcessed % 5000 == 0)){
					//Testing shows that regular commits do seem to improve performance.
					//However, we can't do it too often or we get errors with too many searchers warming.
					//This is happening now with the auto commit settings in solrconfig.xml
					/*try {
						logger.info("Doing a regular commit during full indexing");
						updateServer.commit(false, false, true);
					}catch (Exception e){
						logger.warn("Error committing changes", e);
					}*/
					GroupedReindexMain.addNoteToReindexLog("Processed " + numWorksProcessed + " grouped works processed.");
					GroupedReindexMain.updateNumWorksProcessed(numWorksProcessed);
				}
				if (maxWorksToProcess != -1 && numWorksProcessed >= maxWorksToProcess){
					logger.warn("Stopping processing now because we've reached the max works to process.");
					break;
				}
				if (lastUpdated == null){
					setLastUpdatedTime.setLong(1, indexStartTime - 1); //Set just before the index started so we don't index multiple times
					setLastUpdatedTime.setLong(2, id);
					setLastUpdatedTime.executeUpdate();
				}
			}
		} catch (SQLException e) {
			logger.error("Unexpected SQL error", e);
		}
		logger.info("Finished processing grouped works.  Processed a total of " + numWorksProcessed + " grouped works");
		return numWorksProcessed;
	}

	public void processGroupedWork(String permanentId) {
		try{
			getGroupedWorkInfoStmt.setString(1, permanentId);
			ResultSet getGroupedWorkInfoRS = getGroupedWorkInfoStmt.executeQuery();
			if (getGroupedWorkInfoRS.next()) {
				long id = getGroupedWorkInfoRS.getLong("id");
				String grouping_category = getGroupedWorkInfoRS.getString("grouping_category");
				processGroupedWork(id, permanentId, grouping_category, null, null);
			}
			totalRecordsHandled++;
			if (totalRecordsHandled % 25 == 0) {
				updateServer.commit(false, false, true);
			}
		} catch (Exception e) {
			logger.error("Error indexing grouped work by id", e);
		}

	}

	void processGroupedWork(Long id, String permanentId, String grouping_category, HashMap<Scope, ArrayList<SiteMapEntry>> siteMapsByScope, HashSet<Long> uniqueGroupedWorks) throws SQLException {
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
				logger.error("Could not clone grouped work", cne);
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
			//Add a grouped work to any scopes that are relevant
			groupedWork.updateIndexingStats(indexingStats);

			//Load local (VuFind) enrichment for the work
			loadLocalEnrichment(groupedWork);
			//Load lexile data for the work
			loadLexileDataForWork(groupedWork);
			//Load accelerated reader data for the work
			loadAcceleratedDataForWork(groupedWork);
			//Load Novelist data
			loadNovelistInfo(groupedWork);

			//Write the record to Solr.
			try {
				SolrInputDocument inputDocument = groupedWork.getSolrDocument();
				updateServer.add(inputDocument);
				//logger.debug("Updated solr \r\n" + inputDocument.toString());

			} catch (Exception e) {
				logger.error("Error adding grouped work to solr " + groupedWork.getId(), e);
			}
		}else{
			//Log that this record did not have primary identifiers after
			logger.debug("Grouped work " + permanentId + " did not have any primary identifiers for it, suppressing");
			if (!fullReindex){
				this.deleteRecord(permanentId, id);
			}

		}

		// loop through each of the scopes and if library owned add to appropriate sitemap
		if (fullReindex && siteMapsByScope != null) {
			int ownershipCount = 0;
			for (Scope scope : this.getScopes()) {
				if (scope.isLibraryScope() && groupedWork.getIsLibraryOwned(scope)) {
					if (!siteMapsByScope.containsKey(scope)) {
						siteMapsByScope.put(scope, new ArrayList<>());
					}
					siteMapsByScope.get(scope).add(new SiteMapEntry(id, permanentId, groupedWork.getPopularity()));
					ownershipCount++;
				}
			}
			if (ownershipCount == 1) //unique works
				uniqueGroupedWorks.add(id);
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
			logger.error("Error loading accelerated reader information", e);
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
			logger.error("Unable to load local enrichment", e);
		}
	}

	private void loadNovelistInfo(GroupedWorkSolr groupedWork){
		try{
			getNovelistStmt.setString(1, groupedWork.getId());
			ResultSet novelistRS = getNovelistStmt.executeQuery();
			if (novelistRS.next()){
				String series = novelistRS.getString("seriesTitle");
				if (!novelistRS.wasNull()){
					groupedWork.clearSeriesData();
					groupedWork.addSeries(series);
					String volume = novelistRS.getString("volume");
					if (novelistRS.wasNull()){
						volume = "";
					}
					groupedWork.addSeriesWithVolume(series, volume);
				}
			}
			novelistRS.close();
		}catch (Exception e){
			logger.error("Unable to load novelist data", e);
		}
	}

	private void updateGroupedWorkForPrimaryIdentifier(GroupedWorkSolr groupedWork, String type, String identifier)  {
		groupedWork.addAlternateId(identifier);
		type = type.toLowerCase();
		switch (type) {
			case "overdrive":
				overDriveProcessor.processRecord(groupedWork, identifier);
				break;
			case "rbdigital":
				rbdigitalProcessor.processRecord(groupedWork, identifier);
				break;
			default:
				if (ilsRecordProcessors.containsKey(type)) {
					ilsRecordProcessors.get(type).processRecord(groupedWork, identifier);
				}else{
					logger.debug("Could not find a record processor for type " + type);
				}
				break;
		}
	}

	/*private void updateGroupedWorkForSecondaryIdentifier(GroupedWorkSolr groupedWork, String type, String identifier) {
		type = type.toLowerCase();
		if (type.equals("isbn")){
			groupedWork.addIsbn(identifier);
		}else if (type.equals("upc")){
			groupedWork.addUpc(identifier);
		}else if (type.equals("order")){
			//Add as an alternate id
			groupedWork.addAlternateId(identifier);
		}else if (!type.equals("issn") && !type.equals("oclc")){
			logger.warn("Unknown identifier type " + type);
		}
	}*/

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
			logger.error("Could not read translation map, " + translationMapFile.getAbsolutePath(), e);
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
	private HashSet<String> unableToTranslateWarnings = new HashSet<>();
	private HashSet<String> missingTranslationMaps = new HashSet<>();
	String translateSystemValue(String mapName, String value, String identifier){
		if (value == null){
			return null;
		}
		HashMap<String, String> translationMap = translationMaps.get(mapName);
		String translatedValue;
		if (translationMap == null){
			if (!missingTranslationMaps.contains(mapName)) {
				missingTranslationMaps.add(mapName);
				logger.error("Unable to find system translation map for " + mapName);
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

	Date getDateFirstDetected(@SuppressWarnings("SameParameterValue") String source, String recordId){
		Long dateFirstDetected = null;
		try {
			getDateFirstDetectedStmt.setString(1, source);
			getDateFirstDetectedStmt.setString(2, recordId);
			ResultSet dateFirstDetectedRS = getDateFirstDetectedStmt.executeQuery();
			if (dateFirstDetectedRS.next()) {
				dateFirstDetected = dateFirstDetectedRS.getLong("dateFirstDetected");
			}
		}catch (Exception e){
			logger.error("Error loading date first detected for " + recordId);
		}
		if (dateFirstDetected != null){
			return new Date(dateFirstDetected * 1000);
		}else {
			return null;
		}
	}


}
