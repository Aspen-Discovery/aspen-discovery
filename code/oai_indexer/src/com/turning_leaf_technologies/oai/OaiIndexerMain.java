package com.turning_leaf_technologies.oai;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.logging.LoggingUtil;
import org.apache.logging.log4j.Logger;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.BinaryRequestWriter;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.impl.HttpSolrClient;
import org.ini4j.Ini;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;
import java.util.Date;

public class OaiIndexerMain {
    private static Logger logger;

    private static Ini configIni;
    private static ConcurrentUpdateSolrClient updateServer;

    public static void main(String[] args){
        if (args.length == 0) {
            System.out.println("You must provide the servername as the first argument.");
            System.exit(1);
        }
        String serverName = args[0];

        Date startTime = new Date();
        String processName = "oai_indexer";
        logger = LoggingUtil.setupLogging(serverName, processName);
        logger.info("Starting " + processName + ": " + startTime.toString());

        // Read the base INI file to get information about the server (current directory/cron/config.ini)
        configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);
        extractAndIndexOaiData();

        logger.info("Finished " + new Date().toString());
        long endTime = new Date().getTime();
        long elapsedTime = endTime - startTime.getTime();
        logger.info("Elapsed Minutes " + (elapsedTime / 60000));
    }

    private static void extractAndIndexOaiData() {
        String solrPort = configIni.get("Reindex", "solrPort");
        setupSolrClient(solrPort);

        String oaiBaseUrl = ConfigUtil.cleanIniValue(configIni.get("OAI", "baseUrl"));
        String oaiSetsFromConfig = ConfigUtil.cleanIniValue(configIni.get("OAI", "oaiSet"));

        try {
            updateServer.deleteByQuery("oai_source:\"" + oaiBaseUrl + "\"");
            //3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
        } catch (HttpSolrClient.RemoteSolrException rse) {
            logger.error("Solr is not running properly, try restarting", rse);
            System.exit(-1);
        } catch (Exception e) {
            logger.error("Error deleting from index", e);
        }

        int numRecordsLoaded = 0;
        int numRecordsSkipped = 0;

        String[] oaiSets = oaiSetsFromConfig.split(",");
        for (String oaiSet : oaiSets) {
            logger.info("Loading set " + oaiSet);
            boolean continueLoading = true;
            String resumptionToken = null;
            while (continueLoading) {
                continueLoading = false;

                String oaiUrl;
                if (resumptionToken != null) {
                    try {
                        oaiUrl = oaiBaseUrl + "?verb=ListRecords&resumptionToken=" + URLEncoder.encode(resumptionToken, "UTF-8");
                    } catch (UnsupportedEncodingException e) {
                        logger.error("Error encoding resumption token", e);
                        return;
                    }
                } else {
                    oaiUrl = oaiBaseUrl + "?verb=ListRecords&metadataPrefix=oai_dc&set=" + oaiSet;
                }
                try {
                    logger.info("Loading from " + oaiUrl);
                    DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
                    factory.setValidating(false);
                    factory.setIgnoringElementContentWhitespace(true);
                    DocumentBuilder builder = factory.newDocumentBuilder();

                    Document doc = builder.parse(oaiUrl);
                    Element docElement = doc.getDocumentElement();
                    //Normally we get list records, but if we are at the end of the list OAI may return an
                    //error rather than ListRecords (even though it gave us a resumption token)
                    NodeList listRecords = docElement.getElementsByTagName("ListRecords");
                    if (listRecords.getLength() > 0) {
                        Element listRecordsElement = (Element) docElement.getElementsByTagName("ListRecords").item(0);
                        NodeList allRecords = listRecordsElement.getElementsByTagName("record");
                        for (int i = 0; i < allRecords.getLength(); i++) {
                            Node curRecordNode = allRecords.item(i);
                            if (curRecordNode instanceof Element) {
                                Element curRecordElement = (Element) curRecordNode;

                                if (indexElement(curRecordElement, oaiBaseUrl)) {
                                    numRecordsLoaded++;
                                }else{
                                    numRecordsSkipped++;
                                }
                            }
                        }

                        //Check to see if there are more records to load and if so continue
                        NodeList resumptionTokens = listRecordsElement.getElementsByTagName("resumptionToken");
                        if (resumptionTokens.getLength() > 0) {
                            Node resumptionTokenNode = resumptionTokens.item(0);
                            if (resumptionTokenNode instanceof Element) {
                                Element resumptionTokenElement = (Element) resumptionTokenNode;
                                resumptionToken = resumptionTokenElement.getTextContent();
                                if (resumptionToken.length() > 0) {
                                    continueLoading = true;
                                }
                            }
                        }
                    }
                } catch (Exception e) {
                    logger.error("Error parsing OAI data ", e);
                }
            }
        }

        try {
            updateServer.commit();
        } catch (Exception e) {
            logger.error("Error in final commit", e);
        }

        logger.info("Loaded " + numRecordsLoaded + " records.");
        if (numRecordsSkipped > 0) {
            logger.info("Skipped " + numRecordsLoaded + " records.");
        }
    }

    private static void setupSolrClient(String solrPort) {
        ConcurrentUpdateSolrClient.Builder solrBuilder = new ConcurrentUpdateSolrClient.Builder("http://localhost:" + solrPort + "/solr/open_archives");
        solrBuilder.withThreadCount(1);
        solrBuilder.withQueueSize(25);
        updateServer = solrBuilder.build();
        updateServer.setRequestWriter(new BinaryRequestWriter());
    }

    private static boolean indexElement(Element curRecordElement, String oaiBaseUrl) {
        OAISolrRecord solrRecord = new OAISolrRecord();
        solrRecord.setOai_source(oaiBaseUrl);
        logger.debug("Indexing element");
        NodeList children = curRecordElement.getChildNodes();
        for (int i = 0; i < children.getLength(); i++) {
            Node curChild = children.item(i);
            if (curChild instanceof Element && ((Element)curChild).getTagName().equals("metadata")) {
                Element metadataElement = (Element)curChild;
                //There is an oai_dc element and then we get into the meat of things
                NodeList metadataFields = metadataElement.getChildNodes().item(1).getChildNodes();
                for (int j = 0; j < metadataFields.getLength(); j++){
                    Node curNode = metadataFields.item(j);
                    if (curNode instanceof Element){
                        Element metadataFieldElement = (Element)curNode;
                        String metadataTag = metadataFieldElement.getTagName();
                        String textContent = metadataFieldElement.getTextContent();
                        switch (metadataTag){
                            case "dc:title":
                                solrRecord.setTitle(textContent);
                                break;
                            case "dc:identifier":
                                if (textContent.startsWith("http")){
                                    solrRecord.setIdentifier(textContent);
                                } else if (solrRecord.getIdentifier() == null){
                                    solrRecord.setIdentifier(textContent);
                                }
                                break;
                            case "dc:creator":
                                solrRecord.setCreator(textContent);
                                break;
                            case "dc:contributor":
                                solrRecord.setContributor(textContent);
                                break;
                            case "dc:description":
                                solrRecord.setDescription(textContent);
                                break;
                            case "dc:type":
                                solrRecord.setType(textContent);
                                break;
                            case "dc:subject":
                                String[] subjects = textContent.split(";");
                                solrRecord.addSubjects(subjects);
                                break;
                            case "dc:coverage":
                                solrRecord.addCoverage(textContent);
                                break;
                            case "dc:publisher":
                                solrRecord.addPublisher(textContent);
                                break;
                            case "dc:format":
                                solrRecord.addFormat(textContent);
                                break;
                            case "dc:source":
                                solrRecord.addSource(textContent);
                                break;
                            case "dc:language":
                                solrRecord.setLanguage(textContent);
                                break;
                            case "dc:relation":
                                solrRecord.addRelation(textContent);
                                break;
                            case "dc:rights":
                                solrRecord.setRights(textContent);
                                break;
                            case "dc:date":
                                String[] dateRange = textContent.split(";");
                                for (int tmpIndex = 0; tmpIndex < dateRange.length; tmpIndex++){
                                    dateRange[tmpIndex] = dateRange[tmpIndex].trim();
                                }
                                solrRecord.addDates(dateRange);

                                break;
                            default:
                                logger.warn("Unhandled tag " + metadataTag + " value = " + textContent) ;
                        }
                    }
                }
            }
        }
        boolean addedToIndex = false;
        try {
            if (solrRecord.getIdentifier() == null || solrRecord.getTitle() == null) {
                logger.debug("Skipping record becuase no identifier was provided.");
            } else {
                updateServer.add(solrRecord.getSolrDocument());
                addedToIndex = true;
            }
        } catch (SolrServerException e) {
            logger.error("Error adding document to solr server", e);
        } catch (IOException e) {
            logger.error("I/O Error adding document to solr server", e);
        }
        return addedToIndex;
    }
}
