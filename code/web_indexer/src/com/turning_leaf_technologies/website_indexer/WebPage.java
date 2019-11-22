package com.turning_leaf_technologies.website_indexer;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;
import java.util.HashSet;

class WebPage {
	private long id;
	private String url;
	private long checksum;
	private boolean deleted;
	private long firstDetected;

	private String title;
	private String pageContents;

	private HashSet<String> links = new HashSet<>();

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

	String getPageContents() {
		return pageContents;
	}

	HashSet<String> getLinks() {
		return links;
	}

	public void setDeleted(boolean deleted) {
		this.deleted = deleted;
	}

	void setTitle(String title) {
		this.title = title;
	}

	void setPageContents(String pageContents) {
		this.pageContents = pageContents;
	}
}
