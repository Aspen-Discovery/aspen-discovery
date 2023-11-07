package com.turning_leaf_technologies.reindexer;

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;
import org.xml.sax.helpers.DefaultHandler;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.SQLException;

import org.apache.logging.log4j.Logger;

class ArTitlesHandler extends DefaultHandler {
	private final PreparedStatement addArTitleStmt;
	private final Logger logger;

	private ARTitleData arTitleData;
	private String nodeContents = "";

	ArTitlesHandler(Connection dbConn, Logger logger) throws SQLException {
		addArTitleStmt = dbConn.prepareStatement("INSERT INTO accelerated_reading_titles " +
				"(arBookId, language, title, authorCombined, bookLevel, arPoints, interestLevel, isFiction) " +
				"VALUES (?, ?, ?, ?, ?, ?, ?, ?) " +
				"ON DUPLICATE KEY UPDATE language = VALUES(language), title = VALUES(title), authorCombined = VALUES(authorCombined), " +
				"bookLevel = VALUES(bookLevel), arPoints = VALUES(arPoints), interestLevel = VALUES(interestLevel), isFiction = VALUES(isFiction)");

		this.logger = logger;

	}

	public void startElement(String uri, String localName, String qName, Attributes attributes) {
		if (qName.equals("z:row")) {
			//Older update has information as attributes
			String bookId = attributes.getValue("iBookID");
			String language = attributes.getValue("vchLanguageCode");
			String title = attributes.getValue("vchBookTitle");
			String authorCombined = attributes.getValue("vchAuthorLastName") + ", " + attributes.getValue("vchAuthorFirstName") + " " + attributes.getValue("vchAuthorMiddleName");
			authorCombined = authorCombined.trim();
			String bookLevel = attributes.getValue("iReadingLevel");
			String arPoints = attributes.getValue("iARPoints");
			String interestLevel = attributes.getValue("vchInterestLevel");
			String isFiction = attributes.getValue("iFiction");

			try {
				addArTitleStmt.setString(1, bookId);
				addArTitleStmt.setString(2, language);
				addArTitleStmt.setString(3, title);
				addArTitleStmt.setString(4, authorCombined);
				addArTitleStmt.setString(5, bookLevel);
				addArTitleStmt.setString(6, arPoints);
				addArTitleStmt.setString(7, interestLevel);
				addArTitleStmt.setString(8, isFiction);

				addArTitleStmt.executeUpdate();
			} catch (SQLException e) {
				logger.error("Error saving AR Title", e);
			}

		}else if (qName.equals("Table")) {
			// New way has objects as sub tags
			arTitleData = new ARTitleData();
		}
	}

	public void characters(char[] ch, int start, int length) {
		nodeContents += new String(ch, start, length);
	}

	@Override
	public void endElement(String uri, String localName, String qName) throws SAXException {
		super.endElement(uri, localName, qName);
		switch (qName) {
			case "Table":
				// New way has objects as sub tags
				if (arTitleData != null) {
					try {
						addArTitleStmt.setString(1, arTitleData.getBookId());
						addArTitleStmt.setString(2, arTitleData.getLanguage());
						addArTitleStmt.setString(3, arTitleData.getTitle());
						addArTitleStmt.setString(4, arTitleData.getAuthorCombined());
						addArTitleStmt.setString(5, arTitleData.getBookLevel());
						addArTitleStmt.setString(6, arTitleData.getArPoints());
						addArTitleStmt.setString(7, arTitleData.getInterestLevel());
						addArTitleStmt.setString(8, arTitleData.getIsFiction());

						addArTitleStmt.executeUpdate();
					} catch (SQLException e) {
						logger.error("Error saving AR Title", e);
					}
					arTitleData = null;
				}
				break;
			case "iBookID":
				arTitleData.setBookId(nodeContents.trim());
				break;
			case "vchLanguageCode":
				arTitleData.setLanguage(nodeContents.trim());
				break;
			case "vchBookTitle":
				arTitleData.setTitle(nodeContents.trim());
				break;
			case "vchAuthorLastName":
				arTitleData.setAuthorLastName(nodeContents.trim());
				break;
			case "vchAuthorFirstName":
				arTitleData.setAuthorFirstName(nodeContents.trim());
				break;
			case "vchAuthorMiddleName":
				arTitleData.setAuthorMiddleName(nodeContents.trim());
				break;
			case "iReadingLevel":
				arTitleData.setBookLevel(nodeContents.trim());
				break;
			case "iARPoints":
				arTitleData.setArPoints(nodeContents.trim());
				break;
			case "vchInterestLevel":
				arTitleData.setInterestLevel(nodeContents.trim());
				break;
			case "iFiction":
				arTitleData.setIsFiction(nodeContents.trim());
				break;
		}
		nodeContents = "";
	}
}
