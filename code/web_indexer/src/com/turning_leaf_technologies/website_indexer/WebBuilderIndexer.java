package com.turning_leaf_technologies.website_indexer;

import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.impl.HttpSolrClient;
import org.apache.solr.common.SolrInputDocument;
import org.ini4j.Ini;
import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;

import java.io.IOException;
import java.net.MalformedURLException;
import java.net.URL;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;

class WebBuilderIndexer {
	private final WebsiteIndexLogEntry logEntry;
	private final Connection aspenConn;
	private final Ini configIni;

	private final ConcurrentUpdateSolrClient solrUpdateServer;
	private final HashMap<Long, String> audiences = new HashMap<>();
	private final HashMap<Long, String> categories = new HashMap<>();
	private final HashMap<Long, String> librarySubdomains = new HashMap<>();
	private final HashMap<Long, String> libraryBaseUrls = new HashMap<>();

	WebBuilderIndexer(Ini configIni, WebsiteIndexLogEntry logEntry, Connection aspenConn, ConcurrentUpdateSolrClient solrUpdateServer){
		this.configIni = configIni;
		this.logEntry = logEntry;
		this.aspenConn = aspenConn;
		this.solrUpdateServer = solrUpdateServer;
	}

	void indexContent() {
		loadAudiences();
		loadCategories();
		loadLibrarySubdomains();

		try {
			solrUpdateServer.deleteByQuery("recordtype:\"WebResource\"");
			solrUpdateServer.deleteByQuery("recordtype:\"BasicPage\"");
			solrUpdateServer.deleteByQuery("recordtype:\"PortalPage\"");
			//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
		} catch (HttpSolrClient.RemoteSolrException rse) {
			logEntry.addNote("Solr is not running properly, try restarting " + rse.toString());
			System.exit(-1);
		} catch (Exception e) {
			logEntry.incErrors("Error deleting from index ", e);
		}

		indexBasicPages();
		indexCustomPages();
		indexResources();

		try {
			solrUpdateServer.commit(true, true, false);
		} catch (Exception e) {
			logEntry.incErrors("Error in final commit ", e);
		}
	}

	private void loadLibrarySubdomains() {
		try{
			PreparedStatement getLibrarySubdomainsStmt = aspenConn.prepareStatement("SELECT libraryId, subdomain, baseUrl from library", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet getLibrarySubdomainsRS = getLibrarySubdomainsStmt.executeQuery();
			while (getLibrarySubdomainsRS.next()){
				String scopeName = getLibrarySubdomainsRS.getString("subdomain");
				scopeName = scopeName.replaceAll("[^a-zA-Z0-9_]", "").toLowerCase();
				librarySubdomains.put(getLibrarySubdomainsRS.getLong("libraryId"), scopeName);
				String baseUrl = getLibrarySubdomainsRS.getString("baseUrl");
				if (baseUrl == null || baseUrl.length() == 0){
					baseUrl = configIni.get("Site", "url");
				}
				libraryBaseUrls.put(getLibrarySubdomainsRS.getLong("libraryId"), baseUrl);
			}
			getLibrarySubdomainsRS.close();
			getLibrarySubdomainsStmt.close();
		}catch (SQLException e){
			logEntry.incErrors("Error loading library subdomains", e);
		}
	}

	private void loadCategories() {
		try{
			PreparedStatement getCategoriesStmt = aspenConn.prepareStatement("SELECT * from web_builder_category", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
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
			PreparedStatement getAudiencesStmt = aspenConn.prepareStatement("SELECT * from web_builder_audience", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
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
			PreparedStatement getAudiencesForResourceStmt = aspenConn.prepareStatement("SELECT audienceId FROM web_builder_resource_audience where webResourceId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getCategoriesForResourceStmt = aspenConn.prepareStatement("SELECT categoryId FROM web_builder_resource_category where webResourceId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getLibrariesForResourceStmt = aspenConn.prepareStatement("SELECT libraryId FROM library_web_builder_resource where webResourceId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getResourcesStmt = aspenConn.prepareStatement("SELECT * from web_builder_resource", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet getResourcesRS = getResourcesStmt.executeQuery();
			while (getResourcesRS.next()){
				SolrInputDocument solrDocument = new SolrInputDocument();
				//Load basic information
				String id = getResourcesRS.getString("id");
				solrDocument.addField("id", "WebResource:" + id);
				solrDocument.addField("recordtype", "WebResource");
				solrDocument.addField("settingId", -1);
				solrDocument.addField("website_name", "Library Website");
				solrDocument.addField("search_category", "Website");
				String url = "/WebBuilder/WebResource?id=" + id;
				solrDocument.addField("source_url", url);
				String title = getResourcesRS.getString("name");
				solrDocument.addField("title", title);
				solrDocument.addField("title_display", title);
				solrDocument.addField("title_sort", AspenStringUtils.makeValueSortable(title));

				//Load libraries to scope to
				getLibrariesForResourceStmt.setString(1, id);
				ResultSet getLibrariesForResourceRS = getLibrariesForResourceStmt.executeQuery();
				long firstLibraryId = -1;
				while (getLibrariesForResourceRS.next()){
					if (firstLibraryId == -1){
						firstLibraryId = getLibrariesForResourceRS.getLong("libraryId");
					}
					solrDocument.addField("scope_has_related_records", librarySubdomains.get(getLibrariesForResourceRS.getLong("libraryId")));
				}

				String teaser = getResourcesRS.getString("teaser");
				String description = getResourcesRS.getString("description");
				if (teaser == null || teaser.length() == 0){
					teaser = AspenStringUtils.trimTo(250, description);
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
			PreparedStatement getAudiencesForBasicPageStmt = aspenConn.prepareStatement("SELECT audienceId FROM web_builder_basic_page_audience where basicPageId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getCategoriesForBasicPageStmt = aspenConn.prepareStatement("SELECT categoryId FROM web_builder_basic_page_category where basicPageId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getLibrariesForBasicPageStmt = aspenConn.prepareStatement("SELECT libraryId FROM library_web_builder_basic_page where basicPageId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getBasicPagesStmt = aspenConn.prepareStatement("SELECT * from web_builder_basic_page", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet getBasicPagesRS = getBasicPagesStmt.executeQuery();
			while (getBasicPagesRS.next()){
				SolrInputDocument solrDocument = new SolrInputDocument();
				//Load basic information
				String id = getBasicPagesRS.getString("id");
				solrDocument.addField("id", "BasicPage:" + id);
				solrDocument.addField("recordtype", "BasicPage");
				solrDocument.addField("settingId", -1);
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
				solrDocument.addField("title_sort", AspenStringUtils.makeValueSortable(title));
				String teaser = getBasicPagesRS.getString("teaser");
				String contents = getBasicPagesRS.getString("contents");
				if (teaser == null || teaser.length() == 0){
					teaser = AspenStringUtils.trimTo(250, contents);
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

	private void indexCustomPages() {
		try{
			PreparedStatement getAudiencesForPortalPageStmt = aspenConn.prepareStatement("SELECT audienceId FROM web_builder_portal_page_audience where portalPageId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getCategoriesForPortalPageStmt = aspenConn.prepareStatement("SELECT categoryId FROM web_builder_portal_page_category where portalPageId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getLibrariesForPortalPageStmt = aspenConn.prepareStatement("SELECT libraryId FROM library_web_builder_portal_page where portalPageId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getPortalPagesStmt = aspenConn.prepareStatement("SELECT * from web_builder_portal_page", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet getPortalPagesRS = getPortalPagesStmt.executeQuery();
			while (getPortalPagesRS.next()){
				SolrInputDocument solrDocument = new SolrInputDocument();
				//Load portal information
				String id = getPortalPagesRS.getString("id");
				solrDocument.addField("id", "PortalPage:" + id);
				solrDocument.addField("settingId", -1);
				solrDocument.addField("recordtype", "PortalPage");
				solrDocument.addField("website_name", "Library Website");
				solrDocument.addField("search_category", "Website");
				String url = getPortalPagesRS.getString("urlAlias");
				if (url.length() == 0){
					url = "/WebBuilder/PortalPage?id=" + id;
				}
				solrDocument.addField("source_url", url);
				String title = getPortalPagesRS.getString("title");
				solrDocument.addField("title", title);
				solrDocument.addField("title_display", title);
				solrDocument.addField("title_sort", AspenStringUtils.makeValueSortable(title));

				//Load libraries to scope to
				getLibrariesForPortalPageStmt.setString(1, id);
				ResultSet getLibrariesForPortalPageRS = getLibrariesForPortalPageStmt.executeQuery();
				long firstLibraryId = -1;
				while (getLibrariesForPortalPageRS.next()){
					if (firstLibraryId == -1){
						firstLibraryId = getLibrariesForPortalPageRS.getLong("libraryId");
					}
					solrDocument.addField("scope_has_related_records", librarySubdomains.get(getLibrariesForPortalPageRS.getLong("libraryId")));
				}

				if (firstLibraryId == -1){
					//The page is not attached to any library
					continue;
				}

				//Generate the contents based on the rows and cells within the page, to do this we will use an Aspen API to
				//ensure that the content is rendered in the same way.
				String aspenRawUrl = libraryBaseUrls.get(firstLibraryId) + "/WebBuilder/PortalPage?id=" + id + "&raw=true";

				try {
					Document pageDoc = Jsoup.connect(aspenRawUrl).followRedirects(true).get();
					String contents = pageDoc.title();
					String body = pageDoc.body().text();

					String teaser = AspenStringUtils.trimTo(250, body);

					solrDocument.addField("description", teaser);
					solrDocument.addField("keywords", contents + body);
					//Load audiences
					getAudiencesForPortalPageStmt.setString(1, id);
					ResultSet getAudiencesForPortalPageRS = getAudiencesForPortalPageStmt.executeQuery();
					while (getAudiencesForPortalPageRS.next()) {
						solrDocument.addField("audience", audiences.get(getAudiencesForPortalPageRS.getLong("audienceId")));
					}

					//Load categories
					getCategoriesForPortalPageStmt.setString(1, id);
					ResultSet getCategoriesForPortalPageRS = getCategoriesForPortalPageStmt.executeQuery();
					while (getCategoriesForPortalPageRS.next()) {
						solrDocument.addField("category", categories.get(getCategoriesForPortalPageRS.getLong("categoryId")));
					}

					logEntry.incNumPages();
					try {
						solrUpdateServer.add(solrDocument);
						logEntry.incUpdated();
					} catch (SolrServerException | IOException e) {
						logEntry.incErrors("Error adding page to index", e);
					}
				}catch (IOException ioe){
					logEntry.incErrors("Error loading content from " + aspenRawUrl, ioe);
				}
			}
			getPortalPagesRS.close();
			getPortalPagesStmt.close();
		}catch (SQLException e){
			logEntry.incErrors("Error indexing portal pages", e);
		}
	}
}
