package com.turning_leaf_technologies.website_indexer;

import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.commons.text.StringEscapeUtils;
import org.apache.http.HttpEntity;
import org.apache.http.StatusLine;
import org.apache.http.client.methods.CloseableHttpResponse;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.entity.ContentType;
import org.apache.http.impl.client.CloseableHttpClient;
import org.apache.http.impl.client.HttpClients;
import org.apache.http.util.EntityUtils;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.impl.HttpSolrClient;
import org.apache.solr.common.SolrInputDocument;

import java.sql.*;
import java.util.HashMap;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.regex.PatternSyntaxException;
import java.util.zip.CRC32;

class WebsiteIndexer {
	private final Long websiteId;
	private final String websiteName;
	private final String searchCategory;
	private String initialUrl;
	private String siteUrl;
	private final String siteUrlShort;
	private final boolean fullReload;
	private final long maxPagesToIndex;
	private final WebsiteIndexLogEntry logEntry;
	private final Connection aspenConn;
	private final HashMap<String, WebPage> existingPages = new HashMap<>();
	private final HashMap<String, Boolean> allLinks = new HashMap<>();
	private Pattern pageTitleExpression = null;
	private Pattern descriptionExpression = null;
	private final Pattern titlePattern = Pattern.compile("<title>(.*?)</title>", Pattern.DOTALL);
	private final Pattern bodyPattern = Pattern.compile("<body.*?>(.*?)</body>", Pattern.DOTALL);
	private final Pattern linkPattern = Pattern.compile("<a\\s.*?href=['\"](.*?)['\"].*?>(.*?)</a>", Pattern.DOTALL);
	private final ArrayList<Pattern> pathsToExcludePatterns = new ArrayList<>();
	private static final CRC32 checksumCalculator = new CRC32();
	private PreparedStatement addPageToStmt;
	private PreparedStatement deletePageStmt;
	private final HashSet<String> scopesToInclude;

	private final ConcurrentUpdateSolrClient solrUpdateServer;

	WebsiteIndexer(Long websiteId, String websiteName, String searchCategory, String initialUrl, String pageTitleExpression, String descriptionExpression, String pathsToExclude, long maxPagesToIndex, HashSet<String> scopesToInclude, boolean fullReload, WebsiteIndexLogEntry logEntry, Connection aspenConn, ConcurrentUpdateSolrClient solrUpdateServer) {
		this.websiteId = websiteId;
		this.websiteName = websiteName;
		this.searchCategory = searchCategory;
		this.initialUrl = initialUrl;
		this.siteUrl = initialUrl;
		if (this.siteUrl.indexOf("/", 8) != -1){
			this.siteUrl = this.siteUrl.substring(0, this.siteUrl.indexOf("/", 8));
		}
		this.siteUrlShort = siteUrl.replaceAll("http[s]?://", "");
		this.scopesToInclude = scopesToInclude;
		this.maxPagesToIndex = maxPagesToIndex;

		this.logEntry = logEntry;
		this.aspenConn = aspenConn;
		this.fullReload = fullReload;
		this.solrUpdateServer = solrUpdateServer;

		if (pageTitleExpression.length() > 0){
			try{
				this.pageTitleExpression = Pattern.compile(pageTitleExpression, Pattern.CASE_INSENSITIVE | Pattern.DOTALL);
			}catch (Exception e){
				logEntry.incErrors("Page Title Expression was not a valid regular expression");
			}
		}
		if (descriptionExpression.length() > 0){
			try{
				this.descriptionExpression = Pattern.compile(descriptionExpression, Pattern.CASE_INSENSITIVE | Pattern.DOTALL);
			}catch (Exception e){
				logEntry.incErrors("Description Expression was not a valid regular expression");
			}
		}

		if (pathsToExclude != null && pathsToExclude.length() > 0){
			String[] paths = pathsToExclude.split("\r\n|\r|\n");
			for (String path : paths){
				if (path.contains(initialUrl)){
					pathsToExcludePatterns.add(Pattern.compile(path));
				}else if (path.startsWith("/")){
					pathsToExcludePatterns.add(Pattern.compile(initialUrl + path));
				}else{
					if (path.contains("*") || path.contains("?")) {
						pathsToExcludePatterns.add(Pattern.compile(path));
					}else{
						pathsToExcludePatterns.add(Pattern.compile(initialUrl + "/" + path));
					}
				}
			}
		}

		try {
			addPageToStmt = aspenConn.prepareStatement("INSERT INTO website_pages SET websiteId = ?, url = ?, checksum = ?, deleted = 0, firstDetected = ? ON DUPLICATE KEY UPDATE checksum = VALUES(checksum)", Statement.RETURN_GENERATED_KEYS);
			deletePageStmt = aspenConn.prepareStatement("UPDATE website_pages SET deleted = 1 where id = ?");
		} catch (Exception e) {
			logEntry.incErrors("Error setting up statements ", e);
		}

		loadExistingPages();
	}

	private void loadExistingPages() {
		try {
			PreparedStatement websitePagesStmt = aspenConn.prepareStatement("SELECT * from website_pages WHERE websiteId = ?");
			websitePagesStmt.setLong(1, websiteId);
			ResultSet websitePagesRS = websitePagesStmt.executeQuery();
			while (websitePagesRS.next()) {
				WebPage page = new WebPage(websitePagesRS);
				existingPages.put(page.getUrl(), page);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing pages for website " + websiteName + " ", e);
		}
	}

	void spiderWebsite() {
		if (fullReload) {
			try {
				solrUpdateServer.deleteByQuery("website_name:\"" + websiteName + "\"");
				//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
			} catch (HttpSolrClient.RemoteSolrException rse) {
				logEntry.addNote("Solr is not running properly, try restarting " + rse.toString());
				System.exit(-1);
			} catch (Exception e) {
				logEntry.incErrors("Error deleting from index ", e);
			}
		}
		if (initialUrl.endsWith("/")) {
			initialUrl = initialUrl.substring(0, initialUrl.length() - 1);
		}
		allLinks.put(initialUrl, false);
		logEntry.incNumPages();
		boolean moreToProcess = true;
		while (moreToProcess) {
			String urlToProcess = null;
			for (String link : allLinks.keySet()) {
				if (!allLinks.get(link)) {
					urlToProcess = link;
					break;
				}
			}
			if (urlToProcess != null) {
				processPage(urlToProcess);
				allLinks.put(urlToProcess, true);
			} else {
				moreToProcess = false;
			}
			if (allLinks.size() > this.maxPagesToIndex){
				logEntry.incErrors("Error processing website, found more than " + this.maxPagesToIndex + " links in the site");
			}
		}

		//If we are not doing a full reload, remove any pages that we didn't find on this go round.
		if (!fullReload) {
			for (WebPage curPage : existingPages.values()) {
				try {
					if (!curPage.isDeleted()) {
						deletePageStmt.setLong(1, curPage.getId());
						deletePageStmt.executeUpdate();
						logEntry.incDeleted();
						solrUpdateServer.deleteByQuery("id:" + curPage.getId() + "AND website_name:\"" + websiteName + "\"");
					}
				} catch (Exception e) {
					logEntry.incErrors("Error deleting page");
				}
			}
		}

		try {
			solrUpdateServer.commit(false, false, true);
		} catch (Exception e) {
			logEntry.incErrors("Error in final commit ", e);
		}
	}

	private void processPage(String pageToProcess) {
		try {
			CloseableHttpClient httpclient = HttpClients.createDefault();
			pageToProcess = pageToProcess.replaceAll("\\s", "%20");
			HttpGet httpGet = new HttpGet(pageToProcess);
			try (CloseableHttpResponse response1 = httpclient.execute(httpGet)) {
				StatusLine status = response1.getStatusLine();
				if (status.getStatusCode() == 200) {
					WebPage page;
					if (existingPages.containsKey(pageToProcess)) {
						page = existingPages.get(pageToProcess);
					} else {
						page = new WebPage(pageToProcess);
					}

					HttpEntity entity1 = response1.getEntity();
					ContentType contentType = ContentType.getOrDefault(entity1);
					String mimeType = contentType.getMimeType();
					if (!mimeType.equals("text/html")) {
						//TODO: Index PDFs
						if (!mimeType.equals("application/pdf")) {
							logEntry.addNote("Non HTML page " + pageToProcess + " " + mimeType);
						}
					} else {
						// do something useful with the response body
						// and ensure it is fully consumed
						String response = EntityUtils.toString(entity1);
						//Strip out javascript
						response = response.replaceAll("(?is)<script(.*?)>(.*?)</script>", "");
						//Strip out styles
						response = response.replaceAll("(?is)<style(.*?)>(.*?)</style>", "");
						page.setPageContents(response);

						//Extract the title
						try {
							boolean titleFound = false;
							if (pageTitleExpression != null){
								Matcher titleMatcher = pageTitleExpression.matcher(response);
								if (titleMatcher.find()) {
									page.setTitle(titleMatcher.group(1));
									titleFound = true;
								}
							}
							if (!titleFound) {
								Matcher titleMatcher = titlePattern.matcher(response);
								if (titleMatcher.find()) {
									page.setTitle(titleMatcher.group(1));
								} else {
									page.setTitle("Title not provided");
								}
							}
						} catch (PatternSyntaxException ex) {
							logEntry.incErrors("Error in pattern ", ex);
						}

						//Extract the related links
						try {
							Matcher regexMatcher = linkPattern.matcher(response);
							while (regexMatcher.find()) {
								for (int i = 1; i <= regexMatcher.groupCount(); i++) {
									String linkUrl = regexMatcher.group(1).trim();
									if (linkUrl.contains("&#x")){
										linkUrl = StringEscapeUtils.unescapeHtml4(linkUrl);
									}
									if (linkUrl.contains("#")) {
										linkUrl = linkUrl.substring(0, linkUrl.lastIndexOf("#"));
									}
									//TODO: DO we want to trim off parameters always, this could be a setting?
									if (linkUrl.contains("?")) {
										linkUrl = linkUrl.substring(0, linkUrl.lastIndexOf("?"));
									}
									if (linkUrl.endsWith("/")) {
										linkUrl = linkUrl.substring(0, linkUrl.length() - 1);
									}
									if (linkUrl.length() == 0 || linkUrl.startsWith(".")) {
										continue;
									}
									if (linkUrl.startsWith("http://")) {
										if (!linkUrl.startsWith(initialUrl)) {
											continue;
										}
									} else if (linkUrl.startsWith("https://")) {
										if (!linkUrl.startsWith(initialUrl)) {
											continue;
										}
									} else if (linkUrl.startsWith("mailto:") || linkUrl.startsWith("tel:") || linkUrl.startsWith("javascript:")) {
										continue;
									} else if (linkUrl.contains("/..") || linkUrl.contains("../")) {
										//Ignore relative paths for now
										continue;
									} else if (linkUrl.startsWith(siteUrlShort)) {
										linkUrl = "https://" + linkUrl;
									} else {
										if (!linkUrl.startsWith("/")) {
											linkUrl = "/" + linkUrl;
										}
										linkUrl = siteUrl + linkUrl;
									}
									if (!linkUrl.startsWith(initialUrl)) {
										continue;
									}
									//Make sure that we shouldn't be ignoring the path.
									boolean includePath = true;
									for (Pattern curPattern : pathsToExcludePatterns){
										if (curPattern.matcher(linkUrl).matches()){
											includePath = false;
										}
									}
									if (includePath && !allLinks.containsKey(linkUrl)) {
										page.getLinks().add(linkUrl);
										allLinks.put(linkUrl, false);
										//There are too many pages to process, quit
										if (allLinks.size() > 2500){
											return;
										}
										logEntry.incNumPages();
									}
								}
							}
						} catch (PatternSyntaxException ex) {
							logEntry.incErrors("Error in pattern ", ex);
						}

						//Get the description
						String description = response;
						try{
							boolean descriptionFound = false;
							if (descriptionExpression != null){
								Matcher descriptionMatcher = descriptionExpression.matcher(response);
								if (descriptionMatcher.find()) {
									description = descriptionMatcher.group(1);
									descriptionFound = true;
								}
							}
							if (!descriptionFound){
								Matcher bodyMatcher = bodyPattern.matcher(response);
								if (bodyMatcher.find()) {
									description = bodyMatcher.group(1);
								}
							}
							//Strip comments from the description
							description = description.replaceAll("(?is)<!--(.*?)-->", "");
						} catch (PatternSyntaxException ex) {
							logEntry.incErrors("Error in pattern ", ex);
						}

						checksumCalculator.reset();
						checksumCalculator.update(response.getBytes());
						long checksum = checksumCalculator.getValue();

						existingPages.remove(pageToProcess);

						if (checksum != page.getChecksum() || fullReload) {
							//Save the page to the database
							addPageToStmt.setLong(1, websiteId);
							addPageToStmt.setString(2, pageToProcess);
							addPageToStmt.setLong(3, checksum);
							addPageToStmt.setLong(4, page.getFirstDetected() / 1000);
							addPageToStmt.executeUpdate();
							ResultSet generatedIds = addPageToStmt.getGeneratedKeys();
							if (generatedIds.next()) {
								page.setId(generatedIds.getLong(1));
								logEntry.incAdded();
							} else {
								logEntry.incUpdated();
							}

							//Add to the solr index
							SolrInputDocument solrDocument = new SolrInputDocument();
							solrDocument.addField("id", page.getId());
							solrDocument.addField("recordtype", "WebPage");
							solrDocument.addField("website_name", websiteName);
							solrDocument.addField("search_category", searchCategory);
							solrDocument.addField("source_url", pageToProcess);
							solrDocument.addField("title", page.getTitle());
							solrDocument.addField("title_display", page.getTitle());
							solrDocument.addField("title_sort", StringUtils.makeValueSortable(page.getTitle()));
							//TODO: Make table of contents from header tags
							//Strip tags from body to get the text of the page, this is done using Solr to remove tags.
							solrDocument.addField("keywords", response);
							solrDocument.addField("description", description.trim());
							solrDocument.addField("scope_has_related_records", scopesToInclude);

							//TODO: Add popularity
							solrUpdateServer.add(solrDocument);
						}
					}
				} else if (status.getStatusCode() != 404 && status.getStatusCode() != 403){
					logEntry.addNote("Received error " + status.getStatusCode() + " for url " + pageToProcess );
				}
			}
		} catch (IllegalArgumentException e) {
			logEntry.addNote("Invalid path provided " + pageToProcess + " " + e.toString());
		} catch (Exception e) {
			logEntry.incErrors("Error parsing page " + pageToProcess, e);
		}
	}
}
