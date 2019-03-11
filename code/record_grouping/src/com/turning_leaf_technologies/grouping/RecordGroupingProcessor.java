package com.turning_leaf_technologies.grouping;

import com.turning_leaf_technologies.indexing.RecordIdentifier;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.*;

import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.sql.*;
import java.util.*;
import java.util.Date;

public class RecordGroupingProcessor {
	protected Logger logger;

	private PreparedStatement insertGroupedWorkStmt;
	private PreparedStatement groupedWorkForIdentifierStmt;
	private PreparedStatement updateDateUpdatedForGroupedWorkStmt;
	private PreparedStatement addPrimaryIdentifierForWorkStmt;
	private PreparedStatement removePrimaryIdentifiersForWorkStmt;

	private int numRecordsProcessed = 0;
	private int numGroupedWorksAdded = 0;

	private boolean fullRegrouping;
	private long startTime = new Date().getTime();

	HashMap<String, HashMap<String, String>> translationMaps = new HashMap<>();

	//TODO: Determine if we can avoid this by simply using the ON DUPLICATE KEY UPDATE FUNCTIONALITY
	//Would also want to mark merged works as changed (at least once) to make sure they get reindexed.
	private HashMap<String, Long> existingGroupedWorks = new HashMap<>();

	//A list of grouped works that have been manually merged.
	private HashMap<String, String> mergedGroupedWorks = new HashMap<>();
	private HashSet<String> recordsToNotGroup = new HashSet<>();
	private Long updateTime = new Date().getTime() / 1000;

	/**
	 * Default constructor for use by subclasses.  Should only be used within Record Grouping module
	 */
	RecordGroupingProcessor(Logger logger, boolean fullRegrouping){
		this.logger = logger;
		this.fullRegrouping = fullRegrouping;
	}

	/**
	 * Creates a record grouping processor that saves results to the database.  For use from external extractors
	 *
	 * @param dbConnection   - The Connection to the Pika database
	 * @param serverName     - The server we are grouping data for
	 * @param logger         - A logger to store debug and error messages to.
	 * @param fullRegrouping - Whether or not we are doing full regrouping or if we are only grouping changes.
	 *                         Determines if old works are loaded at the beginning.
	 */
	public RecordGroupingProcessor(Connection dbConnection, String serverName, Logger logger, boolean fullRegrouping) {
		this.logger = logger;
		this.fullRegrouping = fullRegrouping;

		setupDatabaseStatements(dbConnection);

		loadTranslationMaps(serverName);

	}

	void setupDatabaseStatements(Connection dbConnection) {
		try{
			insertGroupedWorkStmt = dbConnection.prepareStatement("INSERT INTO " + RecordGrouperMain.groupedWorkTableName + " (full_title, author, grouping_category, permanent_id, date_updated) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE date_updated = VALUES(date_updated), id=LAST_INSERT_ID(id) ", Statement.RETURN_GENERATED_KEYS) ;
			updateDateUpdatedForGroupedWorkStmt = dbConnection.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = ?");
			addPrimaryIdentifierForWorkStmt = dbConnection.prepareStatement("INSERT INTO grouped_work_primary_identifiers (grouped_work_id, type, identifier) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), grouped_work_id = VALUES(grouped_work_id)", Statement.RETURN_GENERATED_KEYS);
			removePrimaryIdentifiersForWorkStmt = dbConnection.prepareStatement("DELETE FROM grouped_work_primary_identifiers where grouped_work_id = ?");
			groupedWorkForIdentifierStmt = dbConnection.prepareStatement("SELECT grouped_work.id, grouped_work.permanent_id FROM grouped_work inner join grouped_work_primary_identifiers on grouped_work_primary_identifiers.grouped_work_id = grouped_work.id where type = ? and identifier = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

			if (!fullRegrouping){
				PreparedStatement loadExistingGroupedWorksStmt = dbConnection.prepareStatement("SELECT id, permanent_id from grouped_work");
				ResultSet loadExistingGroupedWorksRS = loadExistingGroupedWorksStmt.executeQuery();
				while (loadExistingGroupedWorksRS.next()){
					existingGroupedWorks.put(loadExistingGroupedWorksRS.getString("permanent_id"), loadExistingGroupedWorksRS.getLong("id"));
				}
				loadExistingGroupedWorksRS.close();
				loadExistingGroupedWorksStmt.close();
			}
			PreparedStatement loadMergedWorksStmt = dbConnection.prepareStatement("SELECT * from merged_grouped_works");
			ResultSet mergedWorksRS = loadMergedWorksStmt.executeQuery();
			while (mergedWorksRS.next()){
				mergedGroupedWorks.put(mergedWorksRS.getString("sourceGroupedWorkId"), mergedWorksRS.getString("destinationGroupedWorkId"));
			}
			mergedWorksRS.close();
			PreparedStatement recordsToNotGroupStmt = dbConnection.prepareStatement("SELECT * from nongrouped_records");
			ResultSet nonGroupedRecordsRS = recordsToNotGroupStmt.executeQuery();
			while (nonGroupedRecordsRS.next()){
				String identifier = nonGroupedRecordsRS.getString("source") + ":" + nonGroupedRecordsRS.getString("recordId");
				recordsToNotGroup.add(identifier.toLowerCase());
			}
			nonGroupedRecordsRS.close();

		}catch (Exception e){
			logger.error("Error setting up prepared statements", e);
		}
	}


	List<DataField> getDataFields(Record marcRecord, String tag) {
		return marcRecord.getDataFields(tag);
	}

	/**
	 * Add a work to the database
	 *
	 * @param primaryIdentifier The primary identifier we are updating the work for
	 * @param groupedWork       Information about the work itself
	 */
	void addGroupedWorkToDatabase(RecordIdentifier primaryIdentifier, GroupedWorkBase groupedWork, boolean primaryDataChanged) {
		//Check to see if we need to ungroup this
		if (recordsToNotGroup.contains(primaryIdentifier.toString().toLowerCase())){
			groupedWork.makeUnique(primaryIdentifier.toString());
		}

		String groupedWorkPermanentId = groupedWork.getPermanentId();

		//Check to see if we are doing a manual merge of the work
		if (mergedGroupedWorks.containsKey(groupedWorkPermanentId)){
			groupedWorkPermanentId = handleMergedWork(groupedWork, groupedWorkPermanentId);
		}

		//Check to see if the record is already on an existing work.  If so, remove from the old work.
		try {
			groupedWorkForIdentifierStmt.setString(1, primaryIdentifier.getType());
			groupedWorkForIdentifierStmt.setString(2, primaryIdentifier.getIdentifier());

			ResultSet groupedWorkForIdentifierRS = groupedWorkForIdentifierStmt.executeQuery();
			if (groupedWorkForIdentifierRS.next()){
				//We have an existing grouped work
				String existingGroupedWorkPermanentId = groupedWorkForIdentifierRS.getString("permanent_id");
				long existingGroupedWorkId = groupedWorkForIdentifierRS.getLong("id");
				if (!existingGroupedWorkPermanentId.equals(groupedWorkPermanentId)){
					markWorkUpdated(existingGroupedWorkId);
				}
			}
			groupedWorkForIdentifierRS.close();
		}catch(SQLException e){
			logger.error("Error determining existing grouped work for identifier", e);
		}

		//Add the work to the database
		numRecordsProcessed++;
		long groupedWorkId = -1;
		try{
			if (existingGroupedWorks.containsKey(groupedWorkPermanentId)){
				//There is an existing grouped record
				groupedWorkId = existingGroupedWorks.get(groupedWorkPermanentId);

				//Mark that the work has been updated
				//Only mark it as updated if the data for the primary identifier has changed
				if (primaryDataChanged) {
					markWorkUpdated(groupedWorkId);
				}

			} else {
				//Need to insert a new grouped record
				insertGroupedWorkStmt.setString(1, groupedWork.getTitle());
				insertGroupedWorkStmt.setString(2, groupedWork.getAuthor());
				insertGroupedWorkStmt.setString(3, groupedWork.getGroupingCategory());
				insertGroupedWorkStmt.setString(4, groupedWorkPermanentId);
				insertGroupedWorkStmt.setLong(5, updateTime);

				insertGroupedWorkStmt.executeUpdate();
				ResultSet generatedKeysRS = insertGroupedWorkStmt.getGeneratedKeys();
				if (generatedKeysRS.next()){
					groupedWorkId = generatedKeysRS.getLong(1);
				}
				generatedKeysRS.close();
				numGroupedWorksAdded++;

				//Add to the existing works so we can optimize performance later
				existingGroupedWorks.put(groupedWorkPermanentId, groupedWorkId);
				updatedAndInsertedWorksThisRun.add(groupedWorkId);
			}

			//Update identifiers
			addPrimaryIdentifierForWorkToDB(groupedWorkId, primaryIdentifier);
		}catch (Exception e){
			logger.error("Error adding grouped record to grouped work ", e);
		}

	}

	private String handleMergedWork(GroupedWorkBase groupedWork, String groupedWorkPermanentId) {
		//Handle the merge
		String originalGroupedWorkPermanentId = groupedWorkPermanentId;
		//Override the work id
		groupedWorkPermanentId = mergedGroupedWorks.get(groupedWorkPermanentId);
		groupedWork.overridePermanentId(groupedWorkPermanentId);

		logger.debug("Overriding grouped work " + originalGroupedWorkPermanentId + " with " + groupedWorkPermanentId);

		//Mark that the original was updated
		if (existingGroupedWorks.containsKey(originalGroupedWorkPermanentId)) {
			//There is an existing grouped record
			long originalGroupedWorkId = existingGroupedWorks.get(originalGroupedWorkPermanentId);

			//Make sure we mark the original work as updated so it can be removed from the index next time around
			markWorkUpdated(originalGroupedWorkId);

			//Remove the identifiers for the work.
			//TODO: If we have multiple identifiers for this work, we'll call the delete once for each work.
			//Should we optimize to just call it once and remember that we removed it already?
			try {
				removePrimaryIdentifiersForWorkStmt.setLong(1, originalGroupedWorkId);
				removePrimaryIdentifiersForWorkStmt.executeUpdate();
			} catch (SQLException e) {
				logger.error("Error removing primary identifiers for merged work " + originalGroupedWorkPermanentId + "(" + originalGroupedWorkId + ")");
			}
		}
		return groupedWorkPermanentId;
	}

	private HashSet<Long> updatedAndInsertedWorksThisRun = new HashSet<>();
	private void markWorkUpdated(long groupedWorkId) {
		//Optimize to not continually mark the same works as updateed
		if (!updatedAndInsertedWorksThisRun.contains(groupedWorkId)) {
			try {
				updateDateUpdatedForGroupedWorkStmt.setLong(1, updateTime);
				updateDateUpdatedForGroupedWorkStmt.setLong(2, groupedWorkId);
				updateDateUpdatedForGroupedWorkStmt.executeUpdate();
				updatedAndInsertedWorksThisRun.add(groupedWorkId);
			} catch (Exception e) {
				logger.error("Error updating date updated for grouped work ", e);
			}
		}
	}

	private void addPrimaryIdentifierForWorkToDB(long groupedWorkId, RecordIdentifier primaryIdentifier) {
		//Optimized to not delete and remove the primary identifier if it hasn't changed.  Just updates the grouped_work_id.
		try {
			//This statement will either add the primary key or update the work id if it already exists
			//Note, we can not lower case this because we depend on the actual identifier later
			addPrimaryIdentifierForWorkStmt.setLong(1, groupedWorkId);
			addPrimaryIdentifierForWorkStmt.setString(2, primaryIdentifier.getType());
			addPrimaryIdentifierForWorkStmt.setString(3, primaryIdentifier.getIdentifier());
			addPrimaryIdentifierForWorkStmt.executeUpdate();
			/*ResultSet primaryIdentifierRS = addPrimaryIdentifierForWorkStmt.getGeneratedKeys();
			primaryIdentifierRS.next();
			primaryIdentifier.setIdentifierId(primaryIdentifierRS.getLong(1));
			primaryIdentifierRS.close();*/
		} catch (SQLException e) {
			logger.error("Error adding primary identifier to grouped work " + groupedWorkId + " " + primaryIdentifier.toString(), e);
		}
	}

	/**
	 * Processes the record and returns the permanent id of the grouped work
	 *
	 * @param primaryIdentifier  	The primary identifier of the record
	 * @param title					The title of the record
	 * @param subtitle				The subtitle of the record
	 * @param author				The author of the record
	 * @param format				The format of the record
	 * @param primaryDataChanged	Whether or not the primary data has been changed
	 * @return						The permanent id of the gerouped work
	 */
	public String processRecord(RecordIdentifier primaryIdentifier, String title, String subtitle, String author, String format, boolean primaryDataChanged){
		GroupedWorkBase groupedWork = GroupedWorkFactory.getInstance(-1);

		//Replace & with and for better matching
		groupedWork.setTitle(title, 0, subtitle);

		if (author != null){
			groupedWork.setAuthor(author);
		}

		if (formatsToGroupingCategory.containsKey(format)){
			groupedWork.setGroupingCategory(formatsToGroupingCategory.get(format));
		} else {
			if (!formatsWarned.contains(format)) {
				logger.warn("Could not find format category for format " + format + " setting to other");
				groupedWork.setGroupingCategory("other");
				formatsWarned.add(format);
			}
		}


		addGroupedWorkToDatabase(primaryIdentifier, groupedWork, primaryDataChanged);
		return groupedWork.getPermanentId();
	}


	static HashSet<String> formatsWarned = new HashSet<>();
	static HashMap<String, String> formatsToGroupingCategory = new HashMap<>();
	static {
		formatsToGroupingCategory.put("eMagazine", "book");
		formatsToGroupingCategory.put("eMusic", "music");
		formatsToGroupingCategory.put("music", "music");
		formatsToGroupingCategory.put("video", "movie");
		formatsToGroupingCategory.put("eAudio", "book");
		formatsToGroupingCategory.put("Atlas", "other");
		formatsToGroupingCategory.put("Map", "other");
		formatsToGroupingCategory.put("TapeCartridge", "other");
		formatsToGroupingCategory.put("ChipCartridge", "other");
		formatsToGroupingCategory.put("DiscCartridge", "other");
		formatsToGroupingCategory.put("TapeCassette", "other");
		formatsToGroupingCategory.put("TapeReel", "other");
		formatsToGroupingCategory.put("FloppyDisk", "other");
		formatsToGroupingCategory.put("CDROM", "other");
		formatsToGroupingCategory.put("Software", "other");
		formatsToGroupingCategory.put("Globe", "other");
		formatsToGroupingCategory.put("Braille", "book");
		formatsToGroupingCategory.put("Filmstrip", "movie");
		formatsToGroupingCategory.put("Transparency", "other");
		formatsToGroupingCategory.put("Slide", "other");
		formatsToGroupingCategory.put("Microfilm", "other");
		formatsToGroupingCategory.put("Collage", "other");
		formatsToGroupingCategory.put("Drawing", "other");
		formatsToGroupingCategory.put("Painting", "other");
		formatsToGroupingCategory.put("Print", "other");
		formatsToGroupingCategory.put("Photonegative", "other");
		formatsToGroupingCategory.put("FlashCard", "other");
		formatsToGroupingCategory.put("Chart", "other");
		formatsToGroupingCategory.put("Photo", "other");
		formatsToGroupingCategory.put("MotionPicture", "movie");
		formatsToGroupingCategory.put("Kit", "other");
		formatsToGroupingCategory.put("MusicalScore", "book");
		formatsToGroupingCategory.put("SensorImage", "other");
		formatsToGroupingCategory.put("SoundDisc", "audio");
		formatsToGroupingCategory.put("SoundCassette", "audio");
		formatsToGroupingCategory.put("SoundRecording", "audio");
		formatsToGroupingCategory.put("VideoCartridge", "movie");
		formatsToGroupingCategory.put("VideoDisc", "movie");
		formatsToGroupingCategory.put("VideoCassette", "movie");
		formatsToGroupingCategory.put("VideoReel", "movie");
		formatsToGroupingCategory.put("Video", "movie");
		formatsToGroupingCategory.put("MusicRecording", "music");
		formatsToGroupingCategory.put("Electronic", "other");
		formatsToGroupingCategory.put("PhysicalObject", "other");
		formatsToGroupingCategory.put("Manuscript", "book");
		formatsToGroupingCategory.put("eBook", "ebook");
		formatsToGroupingCategory.put("Book", "book");
		formatsToGroupingCategory.put("Newspaper", "book");
		formatsToGroupingCategory.put("Journal", "book");
		formatsToGroupingCategory.put("Serial", "book");
		formatsToGroupingCategory.put("Unknown", "other");
		formatsToGroupingCategory.put("Playaway", "audio");
		formatsToGroupingCategory.put("LargePrint", "book");
		formatsToGroupingCategory.put("Blu-ray", "movie");
		formatsToGroupingCategory.put("DVD", "movie");
		formatsToGroupingCategory.put("VerticalFile", "other");
		formatsToGroupingCategory.put("CompactDisc", "audio");
		formatsToGroupingCategory.put("TapeRecording", "audio");
		formatsToGroupingCategory.put("Phonograph", "audio");
		formatsToGroupingCategory.put("pdf", "ebook");
		formatsToGroupingCategory.put("epub", "ebook");
		formatsToGroupingCategory.put("jpg", "other");
		formatsToGroupingCategory.put("gif", "other");
		formatsToGroupingCategory.put("mp3", "audio");
		formatsToGroupingCategory.put("plucker", "ebook");
		formatsToGroupingCategory.put("kindle", "ebook");
		formatsToGroupingCategory.put("externalLink", "ebook");
		formatsToGroupingCategory.put("externalMP3", "audio");
		formatsToGroupingCategory.put("interactiveBook", "ebook");
		formatsToGroupingCategory.put("overdrive", "ebook");
		formatsToGroupingCategory.put("external_web", "ebook");
		formatsToGroupingCategory.put("external_ebook", "ebook");
		formatsToGroupingCategory.put("external_eaudio", "audio");
		formatsToGroupingCategory.put("external_emusic", "music");
		formatsToGroupingCategory.put("external_evideo", "movie");
		formatsToGroupingCategory.put("text", "ebook");
		formatsToGroupingCategory.put("gifs", "other");
		formatsToGroupingCategory.put("itunes", "audio");
		formatsToGroupingCategory.put("Adobe_EPUB_eBook", "ebook");
		formatsToGroupingCategory.put("Kindle_Book", "ebook");
		formatsToGroupingCategory.put("Microsoft_eBook", "ebook");
		formatsToGroupingCategory.put("OverDrive_WMA_Audiobook", "audio");
		formatsToGroupingCategory.put("OverDrive_MP3_Audiobook", "audio");
		formatsToGroupingCategory.put("OverDrive_Music", "music");
		formatsToGroupingCategory.put("OverDrive_Video", "movie");
		formatsToGroupingCategory.put("OverDrive_Read", "ebook");
		formatsToGroupingCategory.put("OverDrive_Listen", "audio");
		formatsToGroupingCategory.put("Adobe_PDF_eBook", "ebook");
		formatsToGroupingCategory.put("Palm", "ebook");
		formatsToGroupingCategory.put("Mobipocket_eBook", "ebook");
		formatsToGroupingCategory.put("Disney_Online_Book", "ebook");
		formatsToGroupingCategory.put("Open_PDF_eBook", "ebook");
		formatsToGroupingCategory.put("Open_EPUB_eBook", "ebook");
		formatsToGroupingCategory.put("Nook_Periodicals", "ebook");
		formatsToGroupingCategory.put("eContent", "ebook");
		formatsToGroupingCategory.put("SeedPacket", "other");
	}

	static HashMap<String, String> categoryMap = new HashMap<>();
	static {
		categoryMap.put("other", "book");
		categoryMap.put("book", "book");
		categoryMap.put("ebook", "book");
		categoryMap.put("audio", "book");
		categoryMap.put("music", "music");
		categoryMap.put("movie", "movie");
		categoryMap.put("movies", "movie");
	}


	void dumpStats() {
		long totalElapsedTime = new Date().getTime() - startTime;
		long totalElapsedMinutes = totalElapsedTime / (60 * 1000);
		logger.debug("-----------------------------------------------------------");
		logger.debug("Processed " + numRecordsProcessed + " records in " + totalElapsedMinutes + " minutes");
		logger.debug("Created a total of " + numGroupedWorksAdded + " grouped works");
	}

	private void loadTranslationMaps(String serverName){
		//Load all translationMaps, first from default, then from the site specific configuration
		File defaultTranslationMapDirectory = new File("../../sites/default/translation_maps");
		File[] defaultTranslationMapFiles = defaultTranslationMapDirectory.listFiles((dir, name) -> name.endsWith("properties"));

		File serverTranslationMapDirectory = new File("../../sites/" + serverName + "/translation_maps");
		File[] serverTranslationMapFiles = serverTranslationMapDirectory.listFiles((dir, name) -> name.endsWith("properties"));

		if (defaultTranslationMapFiles != null){
			for (File curFile : defaultTranslationMapFiles){
				String mapName = curFile.getName().replace(".properties", "");
				mapName = mapName.replace("_map", "");
				translationMaps.put(mapName, loadTranslationMap(curFile));
			}
			if (serverTranslationMapFiles != null) {
				for (File curFile : serverTranslationMapFiles) {
					String mapName = curFile.getName().replace(".properties", "");
					mapName = mapName.replace("_map", "");
					translationMaps.put(mapName, loadTranslationMap(curFile));
				}
			}
		}
	}

	private HashMap<String, String> loadTranslationMap(File translationMapFile) {
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

	private HashSet<String> unableToTranslateWarnings = new HashSet<>();
	String translateValue(@SuppressWarnings("SameParameterValue") String mapName, String value){
		value = value.toLowerCase();
		HashMap<String, String> translationMap = translationMaps.get(mapName);
		String translatedValue;
		if (translationMap == null){
			if (!unableToTranslateWarnings.contains("unable_to_find_" + mapName)){
				logger.error("Unable to find translation map for " + mapName);
				unableToTranslateWarnings.add("unable_to_find_" + mapName);
			}

			translatedValue = value;
		}else{
			if (translationMap.containsKey(value)){
				translatedValue = translationMap.get(value);
			}else{
				if (translationMap.containsKey("*")){
					translatedValue = translationMap.get("*");
				}else{
					String concatenatedValue = mapName + ":" + value;
					if (!unableToTranslateWarnings.contains(concatenatedValue)){
						logger.warn("Could not translate '" + concatenatedValue + "'");
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
}
