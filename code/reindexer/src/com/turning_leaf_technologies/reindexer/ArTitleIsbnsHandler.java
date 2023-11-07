package com.turning_leaf_technologies.reindexer;

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;
import org.xml.sax.helpers.DefaultHandler;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.SQLException;

import org.apache.logging.log4j.Logger;

class ArTitleIsbnsHandler extends DefaultHandler {
	private PreparedStatement addArTitleIsbnStmt;
	private Logger logger;
	private ARIsbnData arIsbnData;
	private String nodeContents = "";

	ArTitleIsbnsHandler(Connection dbConn, Logger logger) throws SQLException {
		addArTitleIsbnStmt = dbConn.prepareStatement("INSERT IGNORE INTO accelerated_reading_isbn " +
				"(arBookId, isbn) " +
				"VALUES (?, ?)");

		this.logger = logger;
	}

	@SuppressWarnings("RedundantThrows")
	public void startElement(String uri, String localName, String qName, Attributes attributes) throws SAXException {
		if (qName.equals("z:row")/*|| qName.equals("Table")*/) {
			String bookId = attributes.getValue("iBookID");
			String isbn = attributes.getValue("vchISBN");

			try {
				addArTitleIsbnStmt.setString(1, bookId);
				addArTitleIsbnStmt.setString(2, isbn.replaceAll("-", ""));

				addArTitleIsbnStmt.executeUpdate();
			} catch (SQLException e) {
				logger.error("Error saving AR Title ISBN", e);
			}
		}else if (qName.equals("Table")) {
			// New way has objects as sub tags
			arIsbnData = new ARIsbnData();
		}
	}

	public void characters(char[] ch, int start, int length) {
		nodeContents += new String(ch, start, length);
	}

	public void endElement(String uri, String localName, String qName) throws SAXException {
		super.endElement(uri, localName, qName);
		switch (qName) {
			case "Table":
				if (arIsbnData != null) {
					try {
						addArTitleIsbnStmt.setString(1, arIsbnData.getBookId());
						addArTitleIsbnStmt.setString(2, arIsbnData.getIsbn().replaceAll("-", ""));

						addArTitleIsbnStmt.executeUpdate();
					} catch (SQLException e) {
						logger.error("Error saving AR Title ISBN", e);
					}
					arIsbnData = null;
				}
				break;
			case "iBookID":
				arIsbnData.setBookId(nodeContents.trim());
				break;
			case "vchISBN":
				arIsbnData.setIsbn(nodeContents.trim());
				break;
		}
		nodeContents = "";
	}
}
