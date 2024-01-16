package com.turning_leaf_technologies.website_indexer;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;
import java.util.HashSet;

class WebPage {
	private long id;
	private final String url;
	private long checksum;
	private boolean deleted;
	private final long firstDetected;
	private String title;
	private final HashSet<String> links = new HashSet<>();

	WebPage(ResultSet websitePagesRS) throws SQLException {
		this.id = websitePagesRS.getLong("id");
		this.url = websitePagesRS.getString("url");
		this.checksum = websitePagesRS.getLong("checksum");
		this.deleted = websitePagesRS.getBoolean("deleted");
		this.firstDetected = websitePagesRS.getLong("firstDetected");
	}

	WebPage(String url) {
		this.url = url;
		this.firstDetected = new Date().getTime();
	}

	long getId() {
		return id;
	}

	void setId(long id) {
		this.id = id;
	}

	String getUrl() {
		return url;
	}

	long getChecksum() {
		return checksum;
	}

	boolean isDeleted() {
		return deleted;
	}

	long getFirstDetected() {
		return firstDetected;
	}

	String getTitle() {
		return title;
	}

	HashSet<String> getLinks() {
		return links;
	}

	void setTitle(String title) {
		this.title = title;
	}

}
