package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.encryption.EncryptionUtils;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.indexing.Scope;
import org.apache.logging.log4j.Logger;
import org.apache.solr.client.solrj.SolrQuery;
import org.apache.solr.client.solrj.SolrClient;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.BinaryRequestWriter;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.impl.HttpSolrClient;
import org.apache.solr.client.solrj.response.QueryResponse;
import org.apache.solr.common.SolrDocument;
import org.apache.solr.common.SolrDocumentList;
import org.apache.solr.common.SolrInputDocument;
import org.ini4j.Ini;

import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.HashSet;
import java.util.TreeSet;

class UserListIndexer {
	private Connection dbConn;
	private final Logger logger;
	private ConcurrentUpdateSolrClient updateServer;
	private SolrClient groupedWorkServer;
	private TreeSet<Scope> scopes;
	private HashMap<Long, Long> librariesByHomeLocation = new HashMap<>();
	private HashMap<Long, String> locationCodesByHomeLocation = new HashMap<>();
	private HashSet<Long> usersThatCanShareLists = new HashSet<>();
	private SolrClient openArchivesServer;
	private PreparedStatement getListDisplayNameAndAuthorStmt;
	private final String serverName;

	UserListIndexer(String serverName, Ini configIni, Connection dbConn, Logger logger){
		this.serverName = serverName;
		this.dbConn = dbConn;
		this.logger = logger;
		//Load a list of all list publishers
		try {
			PreparedStatement listPublishersStmt = dbConn.prepareStatement("SELECT userId FROM user_roles INNER JOIN roles on user_roles.roleId = roles.roleId inner join role_permissions on role_permissions.roleId = roles.roleId where permissionId = (select id from permissions where name = 'Include Lists In Search Results')");
			ResultSet listPublishersRS = listPublishersStmt.executeQuery();
			while (listPublishersRS.next()){
				usersThatCanShareLists.add(listPublishersRS.getLong(1));
			}
			getListDisplayNameAndAuthorStmt = dbConn.prepareStatement("SELECT title, displayName FROM user_list inner join user on user_id = user.id where user_list.id = ?");
		}catch (Exception e){
			logger.error("Error loading a list of users with the listPublisher role");
		}

		String solrPort = configIni.get("Reindex", "solrPort");
		if (solrPort == null || solrPort.length() == 0) {
			logger.error("You must provide the port where the solr index is loaded in the import configuration file");
			System.exit(1);
		}

		ConcurrentUpdateSolrClient.Builder solrBuilder = new ConcurrentUpdateSolrClient.Builder("http://localhost:" + solrPort + "/solr/lists");
		solrBuilder.withThreadCount(1);
		solrBuilder.withQueueSize(25);
		updateServer = solrBuilder.build();
		updateServer.setRequestWriter(new BinaryRequestWriter());
		HttpSolrClient.Builder groupedWorkHttpBuilder = new HttpSolrClient.Builder("http://localhost:" + solrPort + "/solr/grouped_works");
		groupedWorkServer = groupedWorkHttpBuilder.build();
		HttpSolrClient.Builder openArchivesHttpBuilder = new HttpSolrClient.Builder("http://localhost:" + solrPort + "/solr/open_archives");
		openArchivesServer = openArchivesHttpBuilder.build();

		scopes = IndexingUtils.loadScopes(dbConn, logger);
	}

	void close() {
		this.dbConn = null;
		try {
			groupedWorkServer.close();
			groupedWorkServer = null;
		} catch (IOException e) {
			logger.error("Could not close grouped work server", e);
		}
		try{
			openArchivesServer.close();
			openArchivesServer = null;
		} catch (IOException e) {
			logger.error("Could not close open archives server", e);
		}
		updateServer.close();
		updateServer = null;
		scopes = null;
		librariesByHomeLocation = null;
		locationCodesByHomeLocation = null;
		usersThatCanShareLists = null;
	}

	Long processPublicUserLists(boolean fullReindex, long lastReindexTime, ListIndexingLogEntry logEntry) {
		long numListsProcessed = 0L;
		long numListsIndexed = 0;
		try{
			PreparedStatement listsStmt;
			PreparedStatement numListsStmt;
			if (fullReindex){
				//Delete all lists from the index
				updateServer.deleteByQuery("recordtype:list");
				//Get a list of all public lists
				numListsStmt = dbConn.prepareStatement("select count(id) as numLists from user_list WHERE deleted = 0 AND public = 1 and searchable = 1");
				listsStmt = dbConn.prepareStatement("SELECT user_list.id as id, deleted, public, searchable, title, description, user_list.created, dateUpdated, username, firstname, lastname, displayName, homeLocationId, user_id from user_list INNER JOIN user on user_id = user.id WHERE public = 1 AND searchable = 1 AND deleted = 0");
			}else{
				//Get a list of all lists that are were changed since the last update
				//Have to process all lists because one could have been deleted, made private, or made non searchable.
				numListsStmt = dbConn.prepareStatement("select count(id) as numLists from user_list");
				listsStmt = dbConn.prepareStatement("SELECT user_list.id as id, deleted, public, searchable, title, description, user_list.created, dateUpdated, username, firstname, lastname, displayName, homeLocationId, user_id from user_list INNER JOIN user on user_id = user.id WHERE dateUpdated > ?");
				listsStmt.setLong(1, lastReindexTime);
			}

			PreparedStatement getTitlesForListStmt = dbConn.prepareStatement("SELECT source, sourceId, notes from user_list_entry WHERE listId = ?");
			PreparedStatement getLibraryForHomeLocation = dbConn.prepareStatement("SELECT libraryId, locationId from location");
			PreparedStatement getCodeForHomeLocation = dbConn.prepareStatement("SELECT code, locationId from location");

			ResultSet librariesByHomeLocationRS = getLibraryForHomeLocation.executeQuery();
			while (librariesByHomeLocationRS.next()){
				librariesByHomeLocation.put(librariesByHomeLocationRS.getLong("locationId"), librariesByHomeLocationRS.getLong("libraryId"));
			}
			librariesByHomeLocationRS.close();

			ResultSet codesByHomeLocationRS = getCodeForHomeLocation.executeQuery();
			while (codesByHomeLocationRS.next()){
				locationCodesByHomeLocation.put(codesByHomeLocationRS.getLong("locationId"), codesByHomeLocationRS.getString("code"));
			}
			codesByHomeLocationRS.close();

			ResultSet allPublicListsRS = listsStmt.executeQuery();
			ResultSet numListsRS = numListsStmt.executeQuery();
			if (numListsRS.next()){
				logEntry.setNumLists(numListsRS.getInt("numLists"));
			}

			while (allPublicListsRS.next()){
				if (updateSolrForList(fullReindex, updateServer, getTitlesForListStmt, allPublicListsRS, lastReindexTime, logEntry)){
					numListsIndexed++;
				}
				numListsProcessed++;
			}
			if (numListsProcessed > 0){
				updateServer.commit(true, true);
			}

		}catch (Exception e){
			logger.error("Error processing public lists", e);
		}
		logger.debug("Indexed lists: processed " + numListsProcessed + " indexed " + numListsIndexed);
		return numListsProcessed;
	}

	private boolean updateSolrForList(boolean fullReindex, ConcurrentUpdateSolrClient updateServer, PreparedStatement getTitlesForListStmt, ResultSet allPublicListsRS, long lastReindexTime, ListIndexingLogEntry logEntry) throws SQLException, SolrServerException, IOException {
		UserListSolr userListSolr = new UserListSolr(this);
		long listId = allPublicListsRS.getLong("id");

		int deleted = allPublicListsRS.getInt("deleted");
		int isPublic = allPublicListsRS.getInt("public");
		int isSearchable = allPublicListsRS.getInt("searchable");
		long userId = allPublicListsRS.getLong("user_id");
		boolean indexed = false;
		if (!fullReindex && (deleted == 1 || isPublic == 0 || isSearchable == 0)){
			updateServer.deleteByQuery("id:" + listId);
			logEntry.incDeleted();
		}else{
			logger.info("Processing list " + listId + " " + allPublicListsRS.getString("title"));
			userListSolr.setId(listId);
			userListSolr.setTitle(allPublicListsRS.getString("title"));
			userListSolr.setDescription(allPublicListsRS.getString("description"));
			long created = allPublicListsRS.getLong("created");
			userListSolr.setCreated(created);

			try {
				String displayName = EncryptionUtils.decryptString(allPublicListsRS.getString("displayName"), serverName, logEntry);
				String firstName = EncryptionUtils.decryptString(allPublicListsRS.getString("firstname"), serverName, logEntry);
				String lastName = EncryptionUtils.decryptString(allPublicListsRS.getString("lastname"), serverName, logEntry);
				String userName = allPublicListsRS.getString("username");

				if (userName.equalsIgnoreCase("nyt_user")) {
					userListSolr.setOwnerCanShareListsInSearchResults(true);
				}else{
					userListSolr.setOwnerCanShareListsInSearchResults(usersThatCanShareLists.contains(userId));
				}
				if (displayName != null && displayName.length() > 0){
					userListSolr.setAuthor(displayName);
				}else{
					if (firstName == null) firstName = "";
					if (lastName == null) lastName = "";
					String firstNameFirstChar = "";
					if (firstName.length() > 0){
						firstNameFirstChar = firstName.charAt(0) + ". ";
					}
					userListSolr.setAuthor(firstNameFirstChar + lastName);
				}

				long patronHomeLibrary = allPublicListsRS.getLong("homeLocationId");
				if (librariesByHomeLocation.containsKey(patronHomeLibrary)){
					userListSolr.setOwningLibrary(librariesByHomeLocation.get(patronHomeLibrary));
				} else {
					//Don't know the owning library for some reason, most likely this is an admin user.
					userListSolr.setOwningLibrary(-1);
				}

				//Don't know the owning location
				userListSolr.setOwningLocation(locationCodesByHomeLocation.getOrDefault(patronHomeLibrary, ""));

				//Get information about all of the list titles.
				getTitlesForListStmt.setLong(1, listId);
				ResultSet allTitlesRS = getTitlesForListStmt.executeQuery();
				while (allTitlesRS.next()) {
					String source = allTitlesRS.getString("source");
					String sourceId = allTitlesRS.getString("sourceId");
					if (!allTitlesRS.wasNull()){
						if (sourceId.length() > 0 && source.equals("GroupedWork")) {
							// Skip archive object Ids
							SolrQuery query = new SolrQuery();
							query.setQuery("id:" + sourceId);
							query.setFields("title_display", "author_display");

							try {
								QueryResponse response = groupedWorkServer.query(query);
								SolrDocumentList results = response.getResults();
								//Should only ever get one response
								if (results.size() >= 1) {
									SolrDocument curWork = results.get(0);
									userListSolr.addListTitle("grouped_work", sourceId, curWork.getFieldValue("title_display"), curWork.getFieldValue("author_display"));
								}
							} catch (Exception e) {
								logger.error("Error loading information about title " + sourceId);
							}
						}else if (source.equals("OpenArchives")){
							// Skip archive object Ids
							SolrQuery query = new SolrQuery();
							query.setQuery("id:" + sourceId);
							query.setFields("title", "creator");

							try {
								QueryResponse response = openArchivesServer.query(query);
								SolrDocumentList results = response.getResults();
								//Should only ever get one response
								if (results.size() >= 1) {
									SolrDocument curWork = results.get(0);
									userListSolr.addListTitle("open_archives", sourceId, curWork.getFieldValue("title"), curWork.getFieldValue("creator"));
								}
							} catch (Exception e) {
								logger.error("Error loading information about title " + sourceId);
							}
						}else if (source.equals("Lists")){
							getListDisplayNameAndAuthorStmt.setString(1, sourceId);
							ResultSet listDisplayNameAndAuthorRS = getListDisplayNameAndAuthorStmt.executeQuery();
							if (listDisplayNameAndAuthorRS.next()){
								String decryptedName = EncryptionUtils.decryptString(listDisplayNameAndAuthorRS.getString("displayName"), serverName, logEntry);
								userListSolr.addListTitle("lists", sourceId, listDisplayNameAndAuthorRS.getString("title"), decryptedName);
							}
							listDisplayNameAndAuthorRS.close();
						}else{
							logEntry.incErrors("Unhandled source " + source);
						}
						//TODO: Handle other types of objects within a User List
						//people, etc.
					}
				}
				if (userListSolr.getNumTitles() >= 3) {
					// Index in the solr catalog
					SolrInputDocument document = userListSolr.getSolrDocument();
					if (document != null){
						updateServer.add(document);
						if (created > lastReindexTime){
							logEntry.incAdded();
						}else{
							logEntry.incUpdated();
						}
						indexed = true;
					}else{
						updateServer.deleteByQuery("id:" + listId);
						logEntry.incDeleted();
					}
				} else {
					updateServer.deleteByQuery("id:" + listId);
					logEntry.incDeleted();
				}
			}catch (Exception e){
				logEntry.addNote("Could not decrypt user information for " + listId + " - " + e.toString());
				logEntry.incSkipped();
			}
		}

		return indexed;
	}

	TreeSet<Scope> getScopes() {
		return this.scopes;
	}
}
