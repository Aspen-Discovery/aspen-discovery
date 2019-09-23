package com.turning_leaf_technologies.website_indexer;

import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.http.Header;
import org.apache.http.HttpEntity;
import org.apache.http.StatusLine;
import org.apache.http.client.methods.CloseableHttpResponse;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.entity.ContentType;
import org.apache.http.impl.client.CloseableHttpClient;
import org.apache.http.impl.client.HttpClients;
import org.apache.http.util.EntityUtils;
import org.apache.solr.client.solrj.impl.BinaryRequestWriter;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.impl.HttpSolrClient;
import org.apache.solr.common.SolrDocument;
import org.apache.solr.common.SolrInputDocument;
import org.ini4j.Ini;

import java.sql.*;
import java.util.HashMap;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.regex.PatternSyntaxException;
import java.util.zip.CRC32;

class WebsiteIndexer {
    private Long websiteId;
    private String websiteName;
    private String searchCategory;
    private String siteUrl;
    private boolean fullReload;
    private WebsiteIndexLogEntry logEntry;
    private Connection aspenConn;
    private HashMap<String, WebPage> existingPages = new HashMap<>();
    private HashMap<String, Boolean> allLinks = new HashMap<>();
    private Pattern titlePattern = Pattern.compile("<title>(.*?)</title>", Pattern.DOTALL);
    private Pattern linkPattern = Pattern.compile("<a\\s.*?href=['\"](.*?)['\"].*?>(.*?)</a>", Pattern.DOTALL);
    private static CRC32 checksumCalculator = new CRC32();
    private PreparedStatement addPageToStmt;
    private PreparedStatement deletePageStmt;

    private ConcurrentUpdateSolrClient solrUpdateServer;

    WebsiteIndexer(Long websiteId, String websiteName, String searchCategory, String siteUrl, boolean fullReload, WebsiteIndexLogEntry logEntry, Connection aspenConn, ConcurrentUpdateSolrClient solrUpdateServer) {
        this.websiteId = websiteId;
        this.websiteName = websiteName;
        this.siteUrl = siteUrl;
        this.logEntry = logEntry;
        this.aspenConn = aspenConn;
        this.fullReload = fullReload;
        this.solrUpdateServer = solrUpdateServer;

        try{
            addPageToStmt = aspenConn.prepareStatement("INSERT INTO website_pages SET websiteId = ?, url = ?, checksum = ?, deleted = 0, firstDetected = ? ON DUPLICATE KEY UPDATE checksum = VALUES(checksum)", Statement.RETURN_GENERATED_KEYS);
            deletePageStmt = aspenConn.prepareStatement("UPDATE website_pages SET deleted = 1 where id = ?");
        }catch (Exception e){
            logEntry.incErrors();
            logEntry.addNote("Error setting up statements " + e.toString());
        }

        loadExistingPages();
    }

    private void loadExistingPages() {
        try{
            PreparedStatement websitePagesStmt = aspenConn.prepareStatement("SELECT * from website_pages WHERE websiteId = ?");
            websitePagesStmt.setLong(1, websiteId);
            ResultSet websitePagesRS = websitePagesStmt.executeQuery();
            while (websitePagesRS.next()){
                WebPage page = new WebPage(websitePagesRS);
                existingPages.put(page.getUrl(), page);
            }
        }catch (SQLException e){
            logEntry.addNote("Error loading existing pages for website " + websiteName + " " + e.toString());
            logEntry.incErrors();
        }
    }

    void spiderWebsite() {
        if (fullReload) {
            try {
                solrUpdateServer.deleteByQuery("website_name:" + websiteName);
                //3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
            } catch (HttpSolrClient.RemoteSolrException rse) {
                logEntry.addNote("Solr is not running properly, try restarting " + rse.toString());
                System.exit(-1);
            } catch (Exception e) {
                logEntry.addNote("Error deleting from index " + e.toString());
                logEntry.incErrors();
            }
        }
        if (siteUrl.endsWith("/")) {
            siteUrl = siteUrl.substring(0, siteUrl.length() - 1);
        }
        allLinks.put(siteUrl, false);
        logEntry.incNumPages();
        boolean moreToProcess = true;
        while (moreToProcess){
            String urlToProcess = null;
            for (String link : allLinks.keySet()){
                if (!allLinks.get(link)){
                    urlToProcess = link;
                    break;
                }
            }
            if (urlToProcess != null){
                processPage(urlToProcess);
                allLinks.put(urlToProcess, true);
            }else{
                moreToProcess = false;
            }
        }

        for (WebPage curPage : existingPages.values()){
            try {
                if (!curPage.isDeleted()) {
                    deletePageStmt.setLong(1, curPage.getId());
                    deletePageStmt.executeUpdate();
                    logEntry.incDeleted();
                    solrUpdateServer.deleteById(Long.toString(curPage.getId()));
                }
            }catch (Exception e){
                logEntry.incErrors();
                logEntry.addNote("Error deleting page");
            }
        }

        try {
            solrUpdateServer.commit(false, false, true);
        } catch (Exception e) {
            logEntry.addNote("Error in final commit " + e.toString());
            logEntry.incErrors();
        }
    }

    private void processPage(String pageToProcess) {
        try {
            //TODO: Add appropriate headers
            CloseableHttpClient httpclient = HttpClients.createDefault();
            HttpGet httpGet = new HttpGet(pageToProcess);
            try (CloseableHttpResponse response1 = httpclient.execute(httpGet)) {
                StatusLine status = response1.getStatusLine();
                if (status.getStatusCode() == 200) {
                    Header lastModifiedHeader = response1.getFirstHeader("Last-Modified");
                    //TODO: check last modified date
                    HttpEntity entity1 = response1.getEntity();
                    ContentType contentType = ContentType.getOrDefault(entity1);
                    String mimeType = contentType.getMimeType();
                    if (!mimeType.equals("text/html")) {
                        logEntry.addNote("Non HTML page " + pageToProcess + " " + mimeType);
                    }else{
                        // do something useful with the response body
                        // and ensure it is fully consumed
                        String response = EntityUtils.toString(entity1);
                        WebPage page;
                        if (existingPages.containsKey(pageToProcess)) {
                            page = existingPages.get(pageToProcess);
                        } else {
                            page = new WebPage(pageToProcess);
                        }
                        page.setPageContents(response);

                        //Extract the title
                        String ResultString = null;
                        try {
                            Matcher titleMatcher = titlePattern.matcher(response);
                            if (titleMatcher.find()) {
                                page.setTitle(titleMatcher.group(1));
                            } else {
                                page.setTitle("Title not provided");
                            }
                        } catch (PatternSyntaxException ex) {
                            logEntry.addNote("Error in pattern " + ex.toString());
                            logEntry.incErrors();
                        }

                        //Extract the related links
                        try {
                            Matcher regexMatcher = linkPattern.matcher(response);
                            while (regexMatcher.find()) {
                                for (int i = 1; i <= regexMatcher.groupCount(); i++) {
                                    String linkUrl = regexMatcher.group(1).trim();
                                    if (linkUrl.endsWith("/")) {
                                        linkUrl = linkUrl.substring(0, linkUrl.length() - 1);
                                    }
                                    if (linkUrl.contains("#")) {
                                        linkUrl = linkUrl.substring(0, linkUrl.lastIndexOf("#"));
                                    }
                                    if (linkUrl.startsWith("http://")) {
                                        if (!linkUrl.startsWith(siteUrl)) {
                                            continue;
                                        }
                                    } else if (linkUrl.startsWith("https://")) {
                                        if (!linkUrl.startsWith(siteUrl)) {
                                            continue;
                                        }
                                    } else if (linkUrl.startsWith("mailto:") || linkUrl.startsWith("tel:")){
                                        continue;
                                    } else {
                                        if (!linkUrl.startsWith("/")){
                                            linkUrl = "/" + siteUrl;
                                        }
                                        linkUrl = siteUrl + linkUrl;
                                    }
                                    if (!allLinks.containsKey(linkUrl)) {
                                        page.getLinks().add(linkUrl);
                                        allLinks.put(linkUrl, false);
                                        logEntry.incNumPages();
                                    }
                                }
                            }
                        } catch (PatternSyntaxException ex) {
                            logEntry.addNote("Error in pattern " + ex.toString());
                            logEntry.incErrors();
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
                            long addPageResult = addPageToStmt.executeUpdate();
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
                            //TODO: Strip tags from body to get the text of the page
                            solrDocument.addField("keywords", response);
                            //TODO: Add popularity
                            solrUpdateServer.add(solrDocument);
                        }
                    }
                }
            }
        }catch (Exception e){
            logEntry.addNote("Error parsing page " + pageToProcess + e.toString());
            logEntry.incErrors();
        }
    }
}
