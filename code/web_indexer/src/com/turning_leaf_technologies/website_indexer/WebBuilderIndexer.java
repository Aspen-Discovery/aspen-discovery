package com.turning_leaf_technologies.website_indexer;

import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.impl.HttpSolrClient;
import org.apache.solr.common.SolrInputDocument;

import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;

class WebBuilderIndexer {
	private boolean fullReload;
	private WebsiteIndexLogEntry logEntry;
	private Connection aspenConn;

	private ConcurrentUpdateSolrClient solrUpdateServer;
	private HashMap<Long, String> audiences = new HashMap<>();
	private HashMap<Long, String> categories = new HashMap<>();
	private HashMap<Long, String> librarySubdomains = new HashMap<>();

	WebBuilderIndexer (boolean fullReload, WebsiteIndexLogEntry logEntry, Connection aspenConn, ConcurrentUpdateSolrClient solrUpdateServer){
		this.logEntry = logEntry;
		this.aspenConn = aspenConn;
		this.fullReload = fullReload;
		this.solrUpdateServer = solrUpdateServer;
	}

	void indexContent() {
		loadAudiences();
		loadCategories();
		loadLibrarySubdomains();

		try {
			solrUpdateServer.deleteByQuery("website_name:\"Local Content\"");
			//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
		} catch (HttpSolrClient.RemoteSolrException rse) {
			logEntry.addNote("Solr is not running properly, try restarting " + rse.toString());
			System.exit(-1);
		} catch (Exception e) {
			logEntry.incErrors("Error deleting from index ", e);
		}

		indexBasicPages();
		indexResources();

		try {
			solrUpdateServer.commit(true, true, false);
		} catch (Exception e) {
			logEntry.incErrors("Error in final commit ", e);
		}
	}

	private void loadLibrarySubdomains() {
		try{
			PreparedStatement getLibrarySubdomainsStmt = aspenConn.prepareStatement("SELECT libraryId, subdomain from library");
			ResultSet getLibrarySubdomainsRS = getLibrarySubdomainsStmt.executeQuery();
			while (getLibrarySubdomainsRS.next()){
				String scopeName = getLibrarySubdomainsRS.getString("subdomain");
				scopeName = scopeName.replaceAll("[^a-zA-Z0-9_]", "").toLowerCase();
				librarySubdomains.put(getLibrarySubdomainsRS.getLong("libraryId"), scopeName);
			}
			getLibrarySubdomainsRS.close();
			getLibrarySubdomainsStmt.close();
		}catch (SQLException e){
			logEntry.incErrors("Error loading library subdomains", e);
		}
	}

	private void loadCategories() {
		try{
			PreparedStatement getCategoriesStmt = aspenConn.prepareStatement("SELECT * from web_builder_category");
			ResultSet getCategoriesRS = getCategoriesStmt.executeQuery();
			while (getCategoriesRS.next()){
				categories.put(getCategoriesRS.getLong("id"), getCategoriesRS.getString("name"));
			}
			getCategoriesRS.close();
			getCategoriesStmt.close();
		}catch (SQLException e){
			logEntry.incErrors("Error loading categories", e);
		}
	}

	private void loadAudiences() {
		try{
			PreparedStatement getAudiencesStmt = aspenConn.prepareStatement("SELECT * from web_builder_audience");
			ResultSet getAudiencesRS = getAudiencesStmt.executeQuery();
			while (getAudiencesRS.next()){
				audiences.put(getAudiencesRS.getLong("id"), getAudiencesRS.getString("name"));
			}
			getAudiencesRS.close();
			getAudiencesStmt.close();
		}catch (SQLException e){
			logEntry.incErrors("Error loading audiences", e);
		}
	}

	private void indexResources() {
		try{
			PreparedStatement getAudiencesForResourceStmt = aspenConn.prepareStatement("SELECT audienceId FROM web_builder_resource_audience where webResourceId = ?");
			PreparedStatement getCategoriesForResourceStmt = aspenConn.prepareStatement("SELECT categoryId FROM web_builder_resource_category where webResourceId = ?");
			PreparedStatement getLibrariesForResourceStmt = aspenConn.prepareStatement("SELECT libraryId FROM library_web_builder_resource where webResourceId = ?");
			PreparedStatement getResourcesStmt = aspenConn.prepareStatement("SELECT * from web_builder_resource");
			ResultSet getResourcesRS = getResourcesStmt.executeQuery();
			while (getResourcesRS.next()){
				SolrInputDocument solrDocument = new SolrInputDocument();
				//Load basic information
				String id = getResourcesRS.getString("id");
				solrDocument.addField("id", "WebResource:" + id);
				solrDocument.addField("recordtype", "WebResource");
				solrDocument.addField("website_name", "Library Website");
				solrDocument.addField("search_category", "Website");
				String url = "/WebBuilder/WebResource?id=" + id;
				solrDocument.addField("source_url", url);
				String title = getResourcesRS.getString("name");
				solrDocument.addField("title", title);
				solrDocument.addField("title_display", title);
				solrDocument.addField("title_sort", StringUtils.makeValueSortable(title));
				String teaser = getResourcesRS.getString("teaser");
				String description = getResourcesRS.getString("description");
				if (teaser == null || teaser.length() == 0){
					teaser = StringUtils.trimTo(250, description);
				}
				solrDocument.addField("description", teaser);
				solrDocument.addField("keywords", description);
				//Load audiences
				getAudiencesForResourceStmt.setString(1, id);
				ResultSet getAudiencesForResourceRS = getAudiencesForResourceStmt.executeQuery();
				while (getAudiencesForResourceRS.next()){
					solrDocument.addField("audience", audiences.get(getAudiencesForResourceRS.getLong("audienceId")));
				}

				//Load categories
				getCategoriesForResourceStmt.setString(1, id);
				ResultSet getCategoriesForResourceRS = getCategoriesForResourceStmt.executeQuery();
				while (getCategoriesForResourceRS.next()){
					solrDocument.addField("category", categories.get(getCategoriesForResourceRS.getLong("categoryId")));
				}

				//Load libraries to scope to
				getLibrariesForResourceStmt.setString(1, id);
				ResultSet getLibrariesForResourceRS = getLibrariesForResourceStmt.executeQuery();
				while (getLibrariesForResourceRS.next()){
					solrDocument.addField("scope_has_related_records", librarySubdomains.get(getLibrariesForResourceRS.getLong("libraryId")));
				}

				logEntry.incNumPages();
				try {
					solrUpdateServer.add(solrDocument);
					logEntry.incUpdated();
				} catch (SolrServerException | IOException e) {
					logEntry.incErrors("Error adding page to index", e);
				}
			}
			getResourcesRS.close();
			getResourcesStmt.close();
		}catch (SQLException e){
			logEntry.incErrors("Error indexing web resources", e);
		}
	}

	private void indexBasicPages() {
		try{
			PreparedStatement getAudiencesForBasicPageStmt = aspenConn.prepareStatement("SELECT audienceId FROM web_builder_basic_page_audience where basicPageId = ?");
			PreparedStatement getCategoriesForBasicPageStmt = aspenConn.prepareStatement("SELECT categoryId FROM web_builder_basic_page_category where basicPageId = ?");
			PreparedStatement getLibrariesForBasicPageStmt = aspenConn.prepareStatement("SELECT libraryId FROM library_web_builder_basic_page where basicPageId = ?");
			PreparedStatement getBasicPagesStmt = aspenConn.prepareStatement("SELECT * from web_builder_basic_page");
			ResultSet getBasicPagesRS = getBasicPagesStmt.executeQuery();
			while (getBasicPagesRS.next()){
				SolrInputDocument solrDocument = new SolrInputDocument();
				//Load basic information
				String id = getBasicPagesRS.getString("id");
				solrDocument.addField("id", "BasicPage:" + id);
				solrDocument.addField("recordtype", "BasicPage");
				solrDocument.addField("website_name", "Library Website");
				solrDocument.addField("search_category", "Website");
				String url = getBasicPagesRS.getString("urlAlias");
				if (url.length() == 0){
					url = "/WebBuilder/BasicPage?id=" + id;
				}
				solrDocument.addField("source_url", url);
				String title = getBasicPagesRS.getString("title");
				solrDocument.addField("title", title);
				solrDocument.addField("title_display", title);
				solrDocument.addField("title_sort", StringUtils.makeValueSortable(title));
				String teaser = getBasicPagesRS.getString("teaser");
				String contents = getBasicPagesRS.getString("contents");
				if (teaser == null || teaser.length() == 0){
					teaser = StringUtils.trimTo(250, contents);
				}
				solrDocument.addField("description", teaser);
				solrDocument.addField("keywords", contents);
				//Load audiences
				getAudiencesForBasicPageStmt.setString(1, id);
				ResultSet getAudiencesForBasicPageRS = getAudiencesForBasicPageStmt.executeQuery();
				while (getAudiencesForBasicPageRS.next()){
					solrDocument.addField("audience", audiences.get(getAudiencesForBasicPageRS.getLong("audienceId")));
				}

				//Load categories
				getCategoriesForBasicPageStmt.setString(1, id);
				ResultSet getCategoriesForBasicPageRS = getCategoriesForBasicPageStmt.executeQuery();
				while (getCategoriesForBasicPageRS.next()){
					solrDocument.addField("category", categories.get(getCategoriesForBasicPageRS.getLong("categoryId")));
				}

				//Load libraries to scope to
				getLibrariesForBasicPageStmt.setString(1, id);
				ResultSet getLibrariesForBasicPageRS = getLibrariesForBasicPageStmt.executeQuery();
				while (getLibrariesForBasicPageRS.next()){
					solrDocument.addField("scope_has_related_records", librarySubdomains.get(getLibrariesForBasicPageRS.getLong("libraryId")));
				}

				logEntry.incNumPages();
				try {
					solrUpdateServer.add(solrDocument);
					logEntry.incUpdated();
				} catch (SolrServerException | IOException e) {
					logEntry.incErrors("Error adding page to index", e);
				}
			}
			getBasicPagesRS.close();
			getBasicPagesStmt.close();
		}catch (SQLException e){
			logEntry.incErrors("Error indexing basic pages", e);
		}
	}
}
