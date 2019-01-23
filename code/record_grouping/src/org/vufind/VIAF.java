package org.vufind;

import org.apache.log4j.Logger;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.xpath.XPath;
import javax.xml.xpath.XPathConstants;
import javax.xml.xpath.XPathExpression;
import javax.xml.xpath.XPathFactory;
import java.io.*;
import java.net.URL;
import java.net.URLEncoder;
import java.sql.*;
import java.util.HashMap;
import java.util.HashSet;

/**
 * Handles lookup of authorities via VIAF Webservice by OCLC
 * Pika
 * User: Mark Noble
 * Date: 1/23/2015
 * Time: 5:10 PM
 */
public class VIAF {
	public static HashMap<String, String> loadedAuthorities = new HashMap<String, String>();
	private static Logger logger = Logger.getLogger(VIAF.class);
	private static Connection authoritiesConn = null;

	private static boolean connectToDatabase(){
		String databaseConnectionInfo = "jdbc:mysql://localhost/authorities?user=authorities&password=authorities&useUnicode=yes&characterEncoding=UTF-8";
		if (authoritiesConn == null) {
			try {
				authoritiesConn = DriverManager.getConnection(databaseConnectionInfo);
				getPreferredAuthorByOriginalNameStmt = authoritiesConn.prepareStatement("SELECT viafId, normalizedName from preferred_authors where originalName = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				getPreferredAuthorByNormalizedNameStmt = authoritiesConn.prepareStatement("SELECT viafId, normalizedName from preferred_authors where originalName = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				getPreferredAuthorByAlternateNameStmt = authoritiesConn.prepareStatement("SELECT preferred_authors.viafId, normalizedName FROM `preferred_authors` inner join alternate_authors on preferred_authors.viafId = alternate_authors.viafId where alternateName = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

				return true;
			} catch (Exception e) {
				logger.error("Error connecting to authorities database");
				return false;
			}
		}
		return true;
	}

	public static void loadAuthoritiesFromVIAF(){
		if (!connectToDatabase()){
			return;
		}
		try {
			PreparedStatement addPreferredAuthorStmt = authoritiesConn.prepareStatement("INSERT INTO preferred_authors (viafId, originalName, normalizedName, wikipediaLink) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE originalName = VALUES(originalName), normalizedName = VALUES(normalizedName), wikipediaLink = VALUES(wikipediaLink)");
			PreparedStatement addAlternateAuthorStmt = authoritiesConn.prepareStatement("INSERT IGNORE alternate_authors (viafId, alternateName) VALUES (?, ?)");

			File viafFile = new File("d:/data/vufind-plus/viaf/viaf-20150115-clusters-rdf.xml");

			//Read data from the file one line at a time since the whole thing is HUGE
			try {
				BufferedReader reader = new BufferedReader(new FileReader(viafFile));

				//Setup the XML processor
				DocumentBuilderFactory dbFactory = DocumentBuilderFactory.newInstance();
				DocumentBuilder dBuilder = dbFactory.newDocumentBuilder();
				XPath xpath = XPathFactory.newInstance().newXPath();
				XPathExpression viafIdExpression = xpath.compile("RDF/Description");
				XPathExpression conceptsExpression = xpath.compile("RDF/Concept");

				String curLine = reader.readLine();
				while (curLine != null){
					if (!curLine.startsWith("<")){
						curLine = curLine.substring(curLine.indexOf('<'));
					}
					//Get XMl for the line
					Document doc = dBuilder.parse(new InputSource(new StringReader(curLine)));

					//Load VIAF ID, Preferred authority, and alternate labels, also wikipedia article?
					Element viafIdNode = (Element)viafIdExpression.evaluate(doc, XPathConstants.NODE);
					String viafIdStr = viafIdNode.getAttribute("rdf:about");
					viafIdStr = viafIdStr.replace("http://viaf.org/viaf/", "");
					Long viafId = Long.parseLong(viafIdStr);
					NodeList concepts = (NodeList)conceptsExpression.evaluate(doc, XPathConstants.NODESET);
					HashSet<String> altLabels = new HashSet<String>();
					String preferredLabel = null;
					String wikipediaLink = null;
					//Scan through the concepts to get the Library of Congress Concept
					for (int i = 0; i < concepts.getLength(); i++){
						Node curConcept = concepts.item(i);

						boolean isPreferredConcept = false;
						String preferredConceptLabel = null;
						HashSet<String> altLabelsForConcept = new HashSet<String>();
						//Get the scheme for the concept
						NodeList conceptInfoNodes = curConcept.getChildNodes();
						for (int j = 0; j < conceptInfoNodes.getLength(); j++){
							Element conceptInfoNode = (Element)conceptInfoNodes.item(j);
							if (conceptInfoNode.getTagName().equals("skos:inScheme")){
								String schemeName = conceptInfoNode.getAttribute("rdf:resource");
								if (schemeName.equals("http://viaf.org/authorityScheme/LC")){
									isPreferredConcept = true;
								}
							}else if (conceptInfoNode.getTagName().equals("skos:prefLabel")){
								preferredConceptLabel = conceptInfoNode.getTextContent();
							}else if (conceptInfoNode.getTagName().equals("skos:altLabel")){
								altLabelsForConcept.add(conceptInfoNode.getTextContent());
							}else if (conceptInfoNode.getTagName().equals("foaf:isPrimaryTopicOf")){
								if (conceptInfoNode.hasAttribute("rdf:resource") && conceptInfoNode.getAttribute("rdf:resource").startsWith("http://en.wikipedia.org")){
									wikipediaLink = conceptInfoNode.getAttribute("rdf:resource");
								}
							}
						}

						if (isPreferredConcept){
							preferredLabel = preferredConceptLabel;
							altLabels = altLabelsForConcept;
							break;
						}
					}

					if (preferredLabel != null) {
						String normalizedName = AuthorNormalizer.getNormalizedName(preferredLabel);
						if (normalizedName.length() > 50){
							logger.warn("Normalized author longer than 50 characters " + normalizedName);
							normalizedName = normalizedName.substring(0, 50);
						}
						if (preferredLabel.length() > 200){
							logger.warn("Author longer than 200 characters " + preferredLabel);
							preferredLabel = preferredLabel.substring(0, 200);
						}

						addPreferredAuthorStmt.setLong(1, viafId);
						addPreferredAuthorStmt.setString(2, preferredLabel);
						addPreferredAuthorStmt.setString(3, normalizedName);
						addPreferredAuthorStmt.setString(4, wikipediaLink);
						addPreferredAuthorStmt.executeUpdate();

						//To make lookups faster, we will want to put alternate labels as first name last name in addition to
						// last name, first name
						for (String curAltName : altLabels) {
							//Add the normalized author name for improved performance doing lookups
							String normalizedAltAuthor = AuthorNormalizer.getNormalizedName(curAltName);
							if (normalizedAltAuthor.length() > 200){
								logger.warn("Normalized alternate author longer than 200 characters " + preferredLabel);
								normalizedAltAuthor = normalizedAltAuthor.substring(0, 200);
							}
							addAlternateAuthorStmt.setLong(1, viafId);
							addAlternateAuthorStmt.setString(2, normalizedAltAuthor);
							addAlternateAuthorStmt.executeUpdate();

							//See if we need to reverse the author name to first name / last name
							String reversedName = AuthorNormalizer.getDisplayName(curAltName);
							if (reversedName != null) {
								String normalizedReversedName = AuthorNormalizer.getNormalizedName(reversedName);
								if (normalizedReversedName.length() > 200) {
									logger.warn("Normalized reversed alternate author longer than 200 characters " + preferredLabel);
									normalizedReversedName = normalizedReversedName.substring(0, 200);
								}
								addAlternateAuthorStmt.setLong(1, viafId);
								addAlternateAuthorStmt.setString(2, normalizedReversedName);
								addAlternateAuthorStmt.executeUpdate();
							}
						}

						//TODO: optionally load related works from see also?
					}else{
						//logger.warn("No preferred Label found for cluster " + viafIdStr);
					}

					//Get the next line
					curLine = reader.readLine();
				}
			}catch (Exception e){
				logger.error("Error loading authorities from VIAF", e);
			}
		} catch (SQLException e) {
			logger.error("Unable to connect to database", e);
		}
		if (authoritiesConn != null){
			return;
		}

	}

	private static PreparedStatement getPreferredAuthorByOriginalNameStmt;
	private static PreparedStatement getPreferredAuthorByNormalizedNameStmt;
	private static PreparedStatement getPreferredAuthorByAlternateNameStmt;

	/**
	 * Returns the normalized Authority value for an author based on the name passed in.
	 * If no authority exists, null will be returned.
	 *
	 * @param author the author to get the authority information for
	 * @return the normalized authority information or null if no authority exists.
	 */
	public static String getNormalizedAuthorAuthorityFromDatabase(String author){
		if (!connectToDatabase()){
			return null;
		}else{
			try {
				getPreferredAuthorByOriginalNameStmt.setString(1, author);
				//First check without normalization
				ResultSet originalNameResults = getPreferredAuthorByOriginalNameStmt.executeQuery();
				if (originalNameResults.next()){
					String authority = originalNameResults.getString("normalizedName");
					//Found a match
					originalNameResults.close();
					return authority;
				}else{
					//No match, check alternate names for the author
					String normalizedAuthor = AuthorNormalizer.getNormalizedName(author);
					getPreferredAuthorByAlternateNameStmt.setString(1, normalizedAuthor);
					ResultSet alternateNameResults = getPreferredAuthorByAlternateNameStmt.executeQuery();
					if (alternateNameResults.next()){
						String authority = alternateNameResults.getString("normalizedName");
						alternateNameResults.close();
						return authority;
					}
				}
			}catch(Exception e){
				logger.error("Error loading authority information from database", e);
			}
		}
		return null;
	}

	public static String getAuthorAuthorityWithWebservice(String author) throws IOException {
		if (loadedAuthorities.containsKey(author)){
			return loadedAuthorities.get(author);
		}
		String authority = null;

		URL urlToCall = new URL("http://viaf.org/viaf/search?query=local.names+%3D+%22" + URLEncoder.encode(author, "UTF-8") + "%22" +
				/*+AND+local.title%3D" + URLEncoder.encode(baseTitle, "UTF-8") +*/
				"+AND+local.sources%3D%22LC%22" +
				"&maximumRecords=25" +
				"&startRecord=1&sortKeys=holdingscount&httpAccept=text/xml");
		String responseXML = null;
		try {
			responseXML = VIAF.convertStreamToString((InputStream) urlToCall.getContent());
		}catch(Exception e){
			logger.error("Unable to connect to VIAF", e);
		}

		//If we are getting the name in the 100/110 we can be a bit more sloppy to deal with abbreviations, etc.
		String authorityMatching100 = null;
		double authorityMatch100Similarity = 0.45;
		//Need very good similarity to use related author
		String authorityMatching400 = null;
		double authorityMatch400Similarity = 0.7;
		if (responseXML != null) {
			//Parse the data as XML
			DocumentBuilderFactory dbFactory = DocumentBuilderFactory.newInstance();
			try {
				DocumentBuilder dBuilder = dbFactory.newDocumentBuilder();
				Document doc = dBuilder.parse(new InputSource(new StringReader(responseXML)));
				XPath xpath = XPathFactory.newInstance().newXPath();
				XPathExpression recordExpression = xpath.compile("searchRetrieveResponse/records/record/recordData/VIAFCluster");
				XPathExpression mainHeadingExpression = xpath.compile("mainHeadings/mainHeadingEl");
				XPathExpression marc21Expression = xpath.compile("datafield[@dtype='MARC21']");
				XPathExpression sourcesExpression = xpath.compile("sources/s");
				XPathExpression subfieldExpression = xpath.compile("subfield[@code='a']");
				XPathExpression marc400FieldsExpression = xpath.compile("x400s/x400/datafield[@tag='400']/subfield[@code='a']");

				//Get the returned records
				NodeList records = (NodeList)recordExpression.evaluate(doc, XPathConstants.NODESET);
				for (int h = 0; h < records.getLength(); h++){
					Node curRecord = records.item(h);
					//Get main headings for the response
					NodeList mainHeadings = (NodeList)mainHeadingExpression.evaluate(curRecord, XPathConstants.NODESET);
					for (int i = 0; i < mainHeadings.getLength(); i++) {
						//Look through all nodes to get 100 or 110 fields.
						Node curMainHeading = mainHeadings.item(i);
						NodeList marc21DataFields = (NodeList)marc21Expression.evaluate(curMainHeading, XPathConstants.NODESET);
						for (int j = 0; j < marc21DataFields.getLength(); j++){
							Element curField = (Element)marc21DataFields.item(j);
							if (curField.hasAttribute("tag")){
								String tag = curField.getAttribute("tag");
								if (tag.equals("100") || tag.equals("110")){
									//Keep processing
									boolean isValidSource = true;
									/*NodeList sources = (NodeList)sourcesExpression.evaluate(curMainHeading, XPathConstants.NODESET);
									for (int k = 0; k < sources.getLength(); k++){
										String curSource = ((Element)sources.item(k)).getTextContent();
										if (curSource.equals("LC")){
											//This is an authority we can use (at least so far)
											isValidSource = true;
											break;
										}
									}*/

									if (isValidSource){
										String tempAuthority = (String)subfieldExpression.evaluate(curField, XPathConstants.STRING);
										if (tempAuthority.endsWith(",")){
											tempAuthority = tempAuthority.substring(0, tempAuthority.length() - 1);
										}
										//We can use it if at least one word we passed in is in this authority
										double matchSimilarity = StringSimilarity.similarity(author, tempAuthority);
										if (matchSimilarity > authorityMatch100Similarity){
											authorityMatching100 = tempAuthority;
											authorityMatch100Similarity = matchSimilarity;
											if (authorityMatch100Similarity >= 0.99){
												break;
											}
										}

										//Or we can use it if the term we passed in is located in the 400 fields
										//Get a list of 400 fields
										NodeList marc400Fields = (NodeList)marc400FieldsExpression.evaluate(curRecord, XPathConstants.NODESET);
										for (int k = 0; k < marc400Fields.getLength(); k++){
											Node curMarc400 = marc400Fields.item(k);
											String relatedName = curMarc400.getTextContent();
											if (relatedName.endsWith(",")){
												relatedName = relatedName.substring(0, relatedName.length() - 1);
											}
											double matchSimilarity400 = StringSimilarity.similarity(author, relatedName);
											if (matchSimilarity400 > authorityMatch400Similarity){
												authorityMatching400 = tempAuthority;
												authorityMatch400Similarity = matchSimilarity400;
											}
										}
									}
								}
							}
						}
						//Yeah, we have a valid authority, be done
						if (authorityMatch100Similarity >= 0.99){
							break;
						}
					}
					//Yeah, we have a valid authority, be done
					if (authorityMatch100Similarity >= 0.99){
						break;
					}
				}
			} catch (Exception e) {
				logger.error("Error parsing XML for VIAF record", e);
			}
		}


		if (authorityMatching100 != null && authorityMatching400 != null){
			if (authorityMatch100Similarity >= authorityMatch400Similarity){
				authority = authorityMatching100;
			}else{
				authority = authorityMatching400;
			}
		}else if (authorityMatching100 != null){
			authority = authorityMatching100;
		}else if (authorityMatching400 != null){
			authority = authorityMatching400;
		}

		/*if (authority != null){
			if (authorityMatch100Similarity <= 0.9999999)
			logger.debug("For author " + author + " preferred name is " + authority + " authorityMatch100Similarity = " + authorityMatch100Similarity + " authorityMatch400Similarity = " + authorityMatch400Similarity);
		}else{
			logger.debug("No authority found for author " + author);
		}*/
		loadedAuthorities.put(author, authority);
		return authority;
	}

	private static boolean isAuthorRelevant(String author, String tempAuthority) {
		//The authority is only valid if it contains the first word from the original.
		//Match on the first word to try to get the last name to match.
		//We don't want to match on the first name because there are many more people
		//that share the same first name (or middle name) than share last names.
		//This is somewhat inexact because we could get author names in any order.
		//However, it is much more common to get last name, first name
		//May want to look at a percent match as well
		String[] authorParts = author.split("\\s");
		if (tempAuthority.contains(authorParts[0])){
			return true;
		}
		/*for (String curPart : authorParts){
			if (tempAuthority.contains(curPart)){
				return true;
			}
		}*/
		return false;
	}

	public static String convertStreamToString(InputStream is) throws IOException {
		/*
		 * To convert the InputStream to String we use the Reader.read(char[]
		 * buffer) method. We iterate until the Reader return -1 which means there's
		 * no more data to read. We use the StringWriter class to produce the
		 * string.
		 */
		if (is != null) {
			Writer writer = new StringWriter();

			char[] buffer = new char[1024];
			try {
				Reader reader = new BufferedReader(new InputStreamReader(is, "UTF-8"));
				int n;
				while ((n = reader.read(buffer)) != -1) {
					writer.write(buffer, 0, n);
				}
			} finally {
				is.close();
			}
			return writer.toString();
		} else {
			return "";
		}
	}
}
