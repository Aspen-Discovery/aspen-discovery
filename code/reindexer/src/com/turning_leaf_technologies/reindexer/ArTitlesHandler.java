package com.turning_leaf_technologies.reindexer;

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;
import org.xml.sax.helpers.DefaultHandler;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.SQLException;

import org.apache.logging.log4j.Logger;

class ArTitlesHandler extends DefaultHandler {
    private PreparedStatement addArTitleStmt;
    private Logger logger;

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
            String bookId = attributes.getValue("iBookID");
            String language = attributes.getValue("vchLanguageCode");
            String title = attributes.getValue("vchBookTitle");
            String authorCombined = attributes.getValue("vchAuthorLastName") + ", " + attributes.getValue("vchAuthorFirstName") + " " + attributes.getValue("vchAuthorMiddleName") ;
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

        }
    }
}
