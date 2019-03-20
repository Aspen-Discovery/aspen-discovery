package com.turning_leaf_technologies.reindexer;

import org.apache.logging.log4j.Logger;
import org.apache.solr.client.solrj.SolrQuery;
import org.apache.solr.client.solrj.SolrClient;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.response.QueryResponse;
import org.apache.solr.common.SolrDocument;
import org.apache.solr.common.SolrDocumentList;

import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.HashSet;

/**
 * Handles setting up solr documents for User Lists
 *
 * Pika
 * User: Mark Noble
 * Date: 7/10/2015
 * Time: 5:14 PM
 */
public class UserListProcessor {
	private GroupedWorkIndexer indexer;
	private Connection dbConn;
	private Logger logger;
	private boolean fullReindex;
	private int availableAtLocationBoostValue;
	private int ownedByLocationBoostValue;
	private HashMap<Long, Long> librariesByHomeLocation = new HashMap<>();
	private HashMap<Long, String> locationCodesByHomeLocation = new HashMap<>();
	private HashSet<Long> listPublisherUsers = new HashSet<>();

	public UserListProcessor(GroupedWorkIndexer indexer, Connection dbConn, Logger logger, boolean fullReindex, int availableAtLocationBoostValue, int ownedByLocationBoostValue){
		this.indexer = indexer;
		this.dbConn = dbConn;
		this.logger = logger;
		this.fullReindex = fullReindex;
		this.availableAtLocationBoostValue = availableAtLocationBoostValue;
		this.ownedByLocationBoostValue = ownedByLocationBoostValue;
		//Load a list of all list publishers
		try {
			PreparedStatement listPublishersStmt = dbConn.prepareStatement("SELECT userId FROM `user_roles` INNER JOIN roles on user_roles.roleId = roles.roleId where name = 'listPublisher'");
			ResultSet listPublishersRS = listPublishersStmt.executeQuery();
			while (listPublishersRS.next()){
				listPublisherUsers.add(listPublishersRS.getLong(1));
			}
		}catch (Exception e){
			logger.error("Error loading a list of users with the listPublisher role");
		}
	}

	public Long processPublicUserLists(long lastReindexTime, ConcurrentUpdateSolrClient updateServer, SolrClient solrServer) {
		GroupedReindexMain.addNoteToReindexLog("Starting to process public lists");
		Long numListsProcessed = 0L;
		try{
			PreparedStatement listsStmt;
			if (fullReindex){
				//Delete all lists from the index
				updateServer.deleteByQuery("recordtype:list");
				//Get a list of all public lists
				listsStmt = dbConn.prepareStatement("SELECT user_list.id as id, deleted, public, title, description, user_list.created, dateUpdated, firstname, lastname, displayName, homeLocationId, user_id from user_list INNER JOIN user on user_id = user.id WHERE public = 1 AND deleted = 0");
			}else{
				//Get a list of all lists that are were changed since the last update
				listsStmt = dbConn.prepareStatement("SELECT user_list.id as id, deleted, public, title, description, user_list.created, dateUpdated, firstname, lastname, displayName, homeLocationId, user_id from user_list INNER JOIN user on user_id = user.id WHERE dateUpdated > ?");
				listsStmt.setLong(1, lastReindexTime);
			}

			PreparedStatement getTitlesForListStmt = dbConn.prepareStatement("SELECT groupedWorkPermanentId, notes from user_list_entry WHERE listId = ?");
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
			while (allPublicListsRS.next()){
				updateSolrForList(updateServer, solrServer, getTitlesForListStmt, allPublicListsRS);
				numListsProcessed++;
			}
			if (numListsProcessed > 0 && fullReindex){
				GroupedReindexMain.addNoteToReindexLog("Committing changes for public lists, processed " + numListsProcessed);
				updateServer.commit(true, true);
			}

		}catch (Exception e){
			logger.error("Error processing public lists", e);
		}
		GroupedReindexMain.addNoteToReindexLog("Finished processing public lists");
		return numListsProcessed;
	}

	private void updateSolrForList(ConcurrentUpdateSolrClient updateServer, SolrClient solrServer, PreparedStatement getTitlesForListStmt, ResultSet allPublicListsRS) throws SQLException, SolrServerException, IOException {
		UserListSolr userListSolr = new UserListSolr(indexer);
		Long listId = allPublicListsRS.getLong("id");

		int deleted = allPublicListsRS.getInt("deleted");
		int isPublic = allPublicListsRS.getInt("public");
		long userId = allPublicListsRS.getLong("user_id");
		if (deleted == 1 || isPublic == 0){
			updateServer.deleteByQuery("id:list");
		}else{
			logger.debug("Processing list " + listId + " " + allPublicListsRS.getString("title"));
			userListSolr.setId(listId);
			userListSolr.setTitle(allPublicListsRS.getString("title"));
			userListSolr.setDescription(allPublicListsRS.getString("description"));
			userListSolr.setCreated(allPublicListsRS.getLong("created"));
			userListSolr.setOwnerHasListPublisherRole(listPublisherUsers.contains(userId));

			String displayName = allPublicListsRS.getString("displayName");
			String firstName = allPublicListsRS.getString("firstname");
			String lastName = allPublicListsRS.getString("lastname");
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
				//Don't know the owning library for some reason
				userListSolr.setOwningLibrary(-1);
			}
			if (locationCodesByHomeLocation.containsKey(patronHomeLibrary)){
				userListSolr.setOwningLocation(locationCodesByHomeLocation.get(patronHomeLibrary));
			} else {
				//Don't know the owning location
				userListSolr.setOwningLocation("");
			}

			//Get information about all of the list titles.
			getTitlesForListStmt.setLong(1, listId);
			ResultSet allTitlesRS = getTitlesForListStmt.executeQuery();
			while (allTitlesRS.next()) {
				String groupedWorkId = allTitlesRS.getString("groupedWorkPermanentId");
				if (!allTitlesRS.wasNull() && groupedWorkId.length() > 0 && !groupedWorkId.contains(":")) {
					// Skip archive object Ids
					SolrQuery query = new SolrQuery();
					query.setQuery("id:" + groupedWorkId + " AND recordtype:grouped_work");
					query.setFields("title", "author");

					try {
						QueryResponse response = solrServer.query(query);
						SolrDocumentList results = response.getResults();
						//Should only ever get one response
						if (results.size() >= 1) {
							SolrDocument curWork = results.get(0);
							userListSolr.addListTitle(groupedWorkId, curWork.getFieldValue("title"), curWork.getFieldValue("author"));
						}
					}catch(Exception e){
						logger.error("Error loading information about title " + groupedWorkId);
					}
				}
				//TODO: Handle Archive Objects from a User List
			}
			// Index in the solr catalog
			updateServer.add(userListSolr.getSolrDocument(availableAtLocationBoostValue, ownedByLocationBoostValue));
		}
	}
}
