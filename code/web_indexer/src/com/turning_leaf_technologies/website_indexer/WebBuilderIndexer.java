package com.turning_leaf_technologies.website_indexer;

import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateHttp2SolrClient;
import org.apache.solr.client.solrj.impl.BaseHttpSolrClient;
import org.apache.solr.common.SolrInputDocument;
import org.ini4j.Ini;
import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;

import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.List;

class WebBuilderIndexer {
	private final WebsiteIndexLogEntry logEntry;
	private final Connection aspenConn;
	private final Ini configIni;

	private final ConcurrentUpdateHttp2SolrClient solrUpdateServer;
	private final HashMap<Long, String> audiences = new HashMap<>();
	private final HashMap<Long, String> categories = new HashMap<>();
	private final HashMap<Long, String> librarySubdomains = new HashMap<>();
	private final HashMap<Long, String> libraryBaseUrls = new HashMap<>();

	WebBuilderIndexer(Ini configIni, WebsiteIndexLogEntry logEntry, Connection aspenConn, ConcurrentUpdateHttp2SolrClient solrUpdateServer){
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
			solrUpdateServer.deleteByQuery("recordtype:\"GrapesPage\"");
			//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
		} catch (BaseHttpSolrClient.RemoteSolrException rse) {
			logEntry.addNote("Solr is not running properly, try restarting " + rse);
			System.exit(-1);
		} catch (Exception e) {
			logEntry.incErrors("Error deleting from index ", e);
		}

		indexBasicPages();
		indexCustomPages();
		indexResources();
		indexGrapesPages();
		indexWebResourcePages();

		try {
			solrUpdateServer.commit(false, false, true);
		} catch (Exception e) {
			logEntry.incErrors("Error in final commit while finishing extract, shutting down", e);
			logEntry.setFinished();
			logEntry.saveResults();
			System.exit(-3);
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
				if (baseUrl == null || baseUrl.trim().isEmpty() || baseUrl.equals("null")){
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
						long tmpFirstLibraryId = getLibrariesForResourceRS.getLong("libraryId");
						if (libraryBaseUrls.containsKey(tmpFirstLibraryId)) {
							firstLibraryId = tmpFirstLibraryId;
						}
					}
					solrDocument.addField("scope_has_related_records", librarySubdomains.get(getLibrariesForResourceRS.getLong("libraryId")));
				}

				if (firstLibraryId == -1) {
					//This is not actually connected to any libraries, just skip it.
					continue;
				}

				String teaser = getResourcesRS.getString("teaser");
				String description = getResourcesRS.getString("description");
				if (teaser == null || teaser.isEmpty()){
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
				if (url.isEmpty()){
					url = "/WebBuilder/BasicPage?id=" + id;
				}
				solrDocument.addField("source_url", url);
				String title = getBasicPagesRS.getString("title");
				solrDocument.addField("title", title);
				solrDocument.addField("title_display", title);
				solrDocument.addField("title_sort", AspenStringUtils.makeValueSortable(title));
				String teaser = getBasicPagesRS.getString("teaser");
				String contents = getBasicPagesRS.getString("contents");
				if (teaser == null || teaser.isEmpty()){
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
				if (url.isEmpty()){
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
						//We have some cases where a library was deleted, but the connections to the pages were not cleaned up.
						//Make sure that the library id is valid.
						long tmpFirstLibraryId = getLibrariesForPortalPageRS.getLong("libraryId");
						if (libraryBaseUrls.containsKey(tmpFirstLibraryId)) {
							firstLibraryId = tmpFirstLibraryId;
						}
					}
					solrDocument.addField("scope_has_related_records", librarySubdomains.get(getLibrariesForPortalPageRS.getLong("libraryId")));
				}

				if (firstLibraryId == -1){
					//The page is not attached to any library
					continue;
				}

				//Generate the contents based on the rows and cells within the page, to do this we will use an Aspen API to
				//ensure that the content is rendered in the same way.
				if (libraryBaseUrls.get(firstLibraryId) == null) {
					logEntry.incErrors("Could not get base url for library id " + firstLibraryId + " for portal page " + id);
					continue;
				}
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

	private void indexGrapesPages() {
		try {
			PreparedStatement getLibrariesForGrapesPageStmt = aspenConn.prepareStatement("SELECT libraryId from library_web_builder_grapes_page WHERE grapesPageId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getGrapesPagesStmt = aspenConn.prepareStatement("SELECT * FROM grapes_web_builder", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

			ResultSet getGrapesPagesRS = getGrapesPagesStmt.executeQuery();

			while (getGrapesPagesRS.next()) {
				SolrInputDocument solrDocument = new SolrInputDocument();

				//Load basic information
				String id = getGrapesPagesRS.getString("id");
				solrDocument.addField("id", "GrapesPage:" + id);
				solrDocument.addField("recordtype", "GrapesPage");
				solrDocument.addField("settingId", -1);
				solrDocument.addField("website_name", "Library Website");
				solrDocument.addField("search_category", "Website");

				String url = getGrapesPagesRS.getString("urlAlias");
				if (url.isEmpty()) {
					url = "/WebBuilder/GrapesPage?id=" + id;
				}
				solrDocument.addField("source_url", url);

				String title = getGrapesPagesRS.getString("title");
				solrDocument.addField("title", title);
				solrDocument.addField("title_display", title);
				solrDocument.addField("title_sort", AspenStringUtils.makeValueSortable(title));

				//Load libraries
				getLibrariesForGrapesPageStmt.setString(1, id);
				ResultSet getLibrariesForGrapesPageRS = getLibrariesForGrapesPageStmt.executeQuery();
				long firstLibraryId = -1;
				while (getLibrariesForGrapesPageRS.next()) {
					if (firstLibraryId == -1) {
						long tmpFirstLibraryId = getLibrariesForGrapesPageRS.getLong("libraryId");
						if (libraryBaseUrls.containsKey(tmpFirstLibraryId)) {
							firstLibraryId = tmpFirstLibraryId;
						}
					}
					solrDocument.addField("scope_has_related_records", librarySubdomains.get(getLibrariesForGrapesPageRS.getLong("libraryId")));
				}
				if (firstLibraryId == -1) {
					continue;
				}
				if (libraryBaseUrls.get(firstLibraryId) == null) {
					logEntry.incErrors("Could not get base URL for library ID " + firstLibraryId + " for GrapesPage " + id);
					continue;
				}

				String aspenRawUrl = libraryBaseUrls.get(firstLibraryId) + "/WebBuilder/GrapesPage?id=" + id + "&raw=true";

				try {
					//Load content from URL
					Document pageDoc = Jsoup.connect(aspenRawUrl).followRedirects(true).get();
					String contents = pageDoc.title();
					String body = pageDoc.body().text();

					String teaser = AspenStringUtils.trimTo(250, body);

					solrDocument.addField("description", teaser);
					solrDocument.addField("keywords", contents + body);

					logEntry.incNumPages();
					try {
						solrUpdateServer.add(solrDocument);
						logEntry.incUpdated();
					} catch (SolrServerException | IOException e) {
						logEntry.incErrors("Error adding GrapesPage to index", e);
					}
				} catch (IOException ioe) {
					logEntry.incErrors("Error loading content from " + aspenRawUrl, ioe);
				}
			}
			getGrapesPagesRS.close();
			getGrapesPagesStmt.close();
		} catch (SQLException e) {
			logEntry.incErrors("Error indexing GrapesPages", e);
		}
	}

	private void indexWebResourcePages() {
		try{
			PreparedStatement getAudiencesForCustomPagesStmt = aspenConn.prepareStatement("SELECT audienceId FROM web_builder_custom_web_resource_page_audience WHERE customResourcePageId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getCategoriesForCustomPagesStmt = aspenConn.prepareStatement("SELECT categoryId FROM web_builder_custom_web_resource_page_category WHERE customResourcePageId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getTitleOfCustomPageStmt = aspenConn.prepareStatement("SELECT title FROM web_builder_custom_web_resource_page WHERE id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getDescriptionForPageStmt = aspenConn.prepareStatement("SELECT translation FROM text_block_translation WHERE objectId = ? AND objectType = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getWebResourcePagesToIndexStmt = aspenConn.prepareStatement("SELECT * from web_builder_web_resources_to_index", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getLibrariesForPageStmt = aspenConn.prepareStatement("SELECT DISTINCT library.libraryId from library INNER JOIN web_builder_web_resources_to_index ON web_builder_web_resources_to_index.webResourcesSettingId = library.webResourcesSettingId WHERE library.webResourcesSettingId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);


			ResultSet getWebResourcePagesToIndexRS = getWebResourcePagesToIndexStmt.executeQuery();

			while (getWebResourcePagesToIndexRS.next()){
				SolrInputDocument solrDocument = new SolrInputDocument();
				String type = getWebResourcePagesToIndexRS.getString("webResourcePageType");
				String settingId = getWebResourcePagesToIndexRS.getString("webResourcesSettingId");

				//handle custom resource pages
				if (type.equals("custom")) {
					String id = getWebResourcePagesToIndexRS.getString("customWebResourcePageId");
					solrDocument.addField("id", "CustomResourcePage:" + id);
					solrDocument.addField("recordtype", "CustomResourcePage");

					getTitleOfCustomPageStmt.setString(1, id);
					ResultSet getTitleOfCustomPageRS = getTitleOfCustomPageStmt.executeQuery();
					while (getTitleOfCustomPageRS.next()) {
						String title = getTitleOfCustomPageRS.getString("title");
						solrDocument.addField("title", title);
						solrDocument.addField("title_display", title);
						solrDocument.addField("title_sort", AspenStringUtils.makeValueSortable(title));
					}

					//Load audiences
					getAudiencesForCustomPagesStmt.setString(1, id);
					ResultSet getAudiencesForPortalPageRS = getAudiencesForCustomPagesStmt.executeQuery();
					while (getAudiencesForPortalPageRS.next()) {
						solrDocument.addField("audience", audiences.get(getAudiencesForPortalPageRS.getLong("audienceId")));
					}

					//Load categories
					getCategoriesForCustomPagesStmt.setString(1, id);
					ResultSet getCategoriesForPortalPageRS = getCategoriesForCustomPagesStmt.executeQuery();
					while (getCategoriesForPortalPageRS.next()) {
						solrDocument.addField("category", categories.get(getCategoriesForPortalPageRS.getLong("categoryId")));
					}

					//Load Description
					getDescriptionForPageStmt.setString(1, id);
					getDescriptionForPageStmt.setString(2, "CustomWebResourcePage");
					ResultSet getDescriptionForPageRS = getDescriptionForPageStmt.executeQuery();
					while (getDescriptionForPageRS.next()){
						solrDocument.addField("description", getDescriptionForPageRS.getString("translation"));
					}
				}
				//handle audience pages
				if (type.equals("audience")) {
					String id = getWebResourcePagesToIndexRS.getString("webResourceAudienceId");
					solrDocument.addField("id", "ResourceAudiencePage:" + id);
					solrDocument.addField("recordtype", "ResourceAudiencePage");
					String title = "Resources For " + audiences.get(getWebResourcePagesToIndexRS.getLong("webResourceAudienceId")); //there is only one audience for an audience resource page
					solrDocument.addField("title", title);
					solrDocument.addField("title_display", title);
					solrDocument.addField("title_sort", AspenStringUtils.makeValueSortable(title));
					solrDocument.addField("audience", audiences.get(getWebResourcePagesToIndexRS.getLong("webResourceAudienceId")));
					//Load Description
					getDescriptionForPageStmt.setString(1, id);
					getDescriptionForPageStmt.setString(2, "WebBuilderAudience");
					ResultSet getDescriptionForPageRS = getDescriptionForPageStmt.executeQuery();
					while (getDescriptionForPageRS.next()){
						solrDocument.addField("description", getDescriptionForPageRS.getString("translation"));
					}
				}
				//handle category pages
				if (type.equals("category")) {
					String id = getWebResourcePagesToIndexRS.getString("webResourceCategoryId");
					solrDocument.addField("id", "ResourceCategoryPage:" + id);
					solrDocument.addField("recordtype", "ResourceCategoryPage");
					String title = categories.get(getWebResourcePagesToIndexRS.getLong("webResourceCategoryId")); //there is only one category for a category resource page
					solrDocument.addField("title", title);
					solrDocument.addField("title_display", title);
					solrDocument.addField("title_sort", AspenStringUtils.makeValueSortable(title));
					solrDocument.addField("category", title);
					//Load Description
					getDescriptionForPageStmt.setString(1, id);
					getDescriptionForPageStmt.setString(2, "WebBuilderCategory");
					ResultSet getDescriptionForPageRS = getDescriptionForPageStmt.executeQuery();
					while (getDescriptionForPageRS.next()){
						solrDocument.addField("description", getDescriptionForPageRS.getString("translation"));
					}
				}
				//handle A to Z page
				if (type.equals("AtoZ")) {
					solrDocument.addField("id", "WebResourcesAtoZ");
					solrDocument.addField("recordtype", "WebResourcesAtoZ");
					solrDocument.addField("title", "Resources A to Z");
					solrDocument.addField("title_display", "Resources A to Z");
					solrDocument.addField("title_sort", AspenStringUtils.makeValueSortable("Resources A to Z"));
					getDescriptionForPageStmt.setString(1, settingId);
					getDescriptionForPageStmt.setString(2, "WebResourcesSetting");
					ResultSet getDescriptionForPageRS = getDescriptionForPageStmt.executeQuery();
					while (getDescriptionForPageRS.next()){
						solrDocument.addField("description", getDescriptionForPageRS.getString("translation"));
					}
				}

				solrDocument.addField("settingId", -1);
				solrDocument.addField("website_name", "Library Website");
				solrDocument.addField("search_category", "Website");
				String url = getWebResourcePagesToIndexRS.getString("webResourcePageURL");
				solrDocument.addField("source_url", url);

				//Load libraries to scope to
				getLibrariesForPageStmt.setString(1, settingId);
				ResultSet getLibrariesForPageRS = getLibrariesForPageStmt.executeQuery();
				long firstLibraryId = -1;
				while (getLibrariesForPageRS.next()){
					if (firstLibraryId == -1){
						//We have some cases where a library was deleted, but the connections to the pages were not cleaned up.
						//Make sure that the library id is valid.
						long tmpFirstLibraryId = getLibrariesForPageRS.getLong("libraryId");
						if (libraryBaseUrls.containsKey(tmpFirstLibraryId)) {
							firstLibraryId = tmpFirstLibraryId;
						}
					}
					solrDocument.addField("scope_has_related_records", librarySubdomains.get(getLibrariesForPageRS.getLong("libraryId")));
				}

				if (firstLibraryId == -1){
					//The page is not attached to any library
					continue;
				}

				logEntry.incNumPages();
				try {
					solrUpdateServer.add(solrDocument);
					logEntry.incUpdated();
				} catch (SolrServerException | IOException e) {
					logEntry.incErrors("Error adding page to index", e);
				}
			}
			getWebResourcePagesToIndexRS.close();
			getWebResourcePagesToIndexStmt.close();
		}catch (SQLException e){
			logEntry.incErrors("Error indexing custom web resource pages", e);
		}
	}
}
