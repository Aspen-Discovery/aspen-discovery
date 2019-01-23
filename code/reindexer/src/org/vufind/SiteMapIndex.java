package org.vufind;


import org.apache.log4j.Logger;
import org.w3c.dom.Document;
import org.w3c.dom.Element;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerException;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;
import java.io.File;

/**
 * Write an index of all possible sitemaps.
 *
 * Created by jabedo on 10/3/2016.
 */
class SiteMapIndex {
	//example from google
	  /* <?xml version="1.0" encoding="UTF-8"?>
    <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
       <loc>http://www.example.com/sitemap1.xml.gz</loc>
       <lastmod>2004-10-01T18:23:17+00:00</lastmod>
    </sitemap>
    <sitemap>
       <loc>http://www.example.com/sitemap2.xml.gz</loc>
       <lastmod>2005-01-01</lastmod>
    </sitemap>
    </sitemapindex>*/

	private Document doc;
	private Element rootElement;
	private Logger logger;

	SiteMapIndex(Logger log) {
		this.logger = log;
		try {
			DocumentBuilderFactory docFactory = DocumentBuilderFactory.newInstance();
			DocumentBuilder docBuilder = docFactory.newDocumentBuilder();
			doc = docBuilder.newDocument();
			rootElement = doc.createElement("sitemapindex");
			doc.appendChild(rootElement);
		} catch (ParserConfigurationException ex) {
			logger.error("Unable to create site map index");
		}
	}

	void addSiteMapLocation(String location, String dateModified) {
		Element sitemap = doc.createElement("sitemap");
		sitemap.setAttribute("xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");
		rootElement.appendChild(sitemap);
		Element loc = doc.createElement("loc");
		loc.appendChild(doc.createTextNode(location));

		Element lastmod = doc.createElement("lastmod");
		lastmod.appendChild(doc.createTextNode(dateModified));

		sitemap.appendChild(loc);
		sitemap.appendChild(lastmod);

	}

	void saveFile(File file) {
		try {
			TransformerFactory transformerFactory = TransformerFactory.newInstance();
			Transformer transformer = transformerFactory.newTransformer();
			DOMSource source = new DOMSource(doc);
			StreamResult result = new StreamResult(file/*new File("/Users/myXml/ScoreDetail.xml")*/);
			transformer.transform(source, result);
		} catch (TransformerException ex) {
			logger.error("Unable to save site map index");
		}
	}

}
