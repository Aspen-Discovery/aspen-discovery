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

    ArTitleIsbnsHandler(Connection dbConn, Logger logger) throws SQLException {
        addArTitleIsbnStmt = dbConn.prepareStatement("INSERT IGNORE INTO accelerated_reading_isbn " +
                "(arBookId, isbn) " +
                "VALUES (?, ?)");

        this.logger = logger;
    }

    @SuppressWarnings("RedundantThrows")
    public void startElement(String uri, String localName, String qName, Attributes attributes) throws SAXException {
        if (qName.equals("z:row")) {
            String bookId = attributes.getValue("iBookID");
            String isbn = attributes.getValue("vchISBN");

            try {
                addArTitleIsbnStmt.setString(1, bookId);
                addArTitleIsbnStmt.setString(2, isbn.replaceAll("-", ""));

                addArTitleIsbnStmt.executeUpdate();
            } catch (SQLException e) {
                logger.error("Error saving AR Title ISBN", e);
            }

        }
    }
}
