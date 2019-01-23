package org.vufind;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

public class ReindexLists implements IProcessHandler {
	private Logger logger;
	private CronProcessLogEntry processLog;
	private String vufindUrl;
	private boolean reindexBiblio;
	private boolean reindexBiblio2;
	private String baseSolrUrl;
	private Connection vufindConn;
	
	@Override
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Reindex Lists");
		processLog.saveToDatabase(vufindConn, logger);
		this.vufindConn = vufindConn;
		try {
			this.logger = logger;
			vufindUrl = configIni.get("Site", "url");
			if (processSettings.get("reindexBiblio") != null){
				reindexBiblio = Boolean.parseBoolean(processSettings.get("reindexBiblio"));
			}else{
				reindexBiblio = true;
			}
			baseSolrUrl = processSettings.get("baseSolrUrl");
			if (baseSolrUrl == null){
				processLog.incErrors();
				processLog.addNote("baseSolrUrl not found in configuration options, please specify as part of process settings");
				return;
			}
			
			//Clear the existing lists from the solr index
			if (reindexBiblio){
				clearLists("biblio");
			}
			if (reindexBiblio2){
				clearLists("biblio2");
			}
			
			//Get a list of all public lists
			PreparedStatement getPublicListsStmt = vufindConn.prepareStatement("SELECT user_list.id, count(user_resource.id) as num_titles FROM user_list INNER JOIN user_resource on list_id = user_list.id WHERE public = 1 group by user_list.id");
			ResultSet publicListsRs = getPublicListsStmt.executeQuery();
			while (publicListsRs.next()){
				Long listId = publicListsRs.getLong("id");
				//Reindex each list
				if (reindexBiblio){
					reindexList("biblio", listId);
				}
				if (reindexBiblio2){
					reindexList("biblio2", listId);
				}
			}
			publicListsRs.close();
			getPublicListsStmt.close();
			
		} catch (Exception e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}finally{
			processLog.setFinished();
			processLog.saveToDatabase(vufindConn, logger);
		}
	}

	private void reindexList(String string, Long listId) {
		URLPostResponse response = Util.getURL(vufindUrl + "/MyResearch/MyList/" + listId + "?myListActionHead=reindex", logger);
		if (!response.isSuccess()){
			processLog.addNote("Error reindexing list " + response.getMessage());
			processLog.incErrors();
		}else{
			processLog.incUpdated();
		}
		processLog.saveToDatabase(vufindConn, logger);
	}

	private void clearLists(String coreName) {
		URLPostResponse response = Util.postToURL(baseSolrUrl + "/solr/" + coreName + "/update/?commit=true", "<delete><query>recordtype:list</query></delete>", "text/xml", null, logger);
		if (!response.isSuccess()){
			processLog.addNote("Error clearing existing marc records " + response.getMessage());
			processLog.incErrors();
		}
		processLog.saveToDatabase(vufindConn, logger);
	}

}
