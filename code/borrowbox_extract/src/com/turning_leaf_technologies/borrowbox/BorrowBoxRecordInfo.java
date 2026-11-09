package com.turning_leaf_technologies.borrowbox;

import java.util.ArrayList;
import java.util.List;

/**
 * Represents a BorrowBox product record from the API.
 */
class BorrowBoxRecordInfo {
	boolean hasChanges = false;
	boolean isNew = false;
	boolean removedFromCollection = false;

	private String borrowboxId;
	private long databaseId = -1;
	private String isbn13;
	private String format;
	private String title;
	private String subTitle;
	private String series;
	private int seriesNumber;
	private String primaryCreatorName;
	private String publisher;
	private String cover;
	private final List<PendingAvailability> pendingAvailabilities = new ArrayList<>();

	static class PendingAvailability {
		String siteId;
		String availabilityStatus;
		Long nextAvailableDate;

		PendingAvailability(String siteId, String availabilityStatus, Long nextAvailableDate) {
			this.siteId = siteId;
			this.availabilityStatus = availabilityStatus;
			this.nextAvailableDate = nextAvailableDate;
		}
	}

	String getBorrowboxId() {
		return borrowboxId;
	}

	void setBorrowboxId(String borrowboxId) {
		this.borrowboxId = borrowboxId;
	}

	long getDatabaseId() {
		return databaseId;
	}

	void setDatabaseId(long databaseId) {
		this.databaseId = databaseId;
	}

	String getIsbn13() {
		return isbn13;
	}

	void setIsbn13(String isbn13) {
		this.isbn13 = isbn13;
	}

	String getFormat() {
		return format;
	}

	void setFormat(String format) {
		this.format = format;
	}

	String getTitle() {
		return title;
	}

	void setTitle(String title) {
		this.title = title;
	}

	String getSubTitle() {
		return subTitle;
	}

	void setSubTitle(String subTitle) {
		this.subTitle = subTitle;
	}

	String getSeries() {
		return series;
	}

	void setSeries(String series) {
		this.series = series;
	}

	int getSeriesNumber() {
		return seriesNumber;
	}

	void setSeriesNumber(int seriesNumber) {
		this.seriesNumber = seriesNumber;
	}

	String getPrimaryCreatorName() {
		return primaryCreatorName;
	}

	void setPrimaryCreatorName(String primaryCreatorName) {
		this.primaryCreatorName = primaryCreatorName;
	}

	String getPublisher() {
		return publisher;
	}

	void setPublisher(String publisher) {
		this.publisher = publisher;
	}

	String getCover() {
		return cover;
	}

	void setCover(String cover) {
		this.cover = cover;
	}

	void addPendingAvailability(String siteId, String availabilityStatus, Long nextAvailableDate) {
		pendingAvailabilities.add(new PendingAvailability(siteId, availabilityStatus, nextAvailableDate));
	}

	List<PendingAvailability> getPendingAvailabilities() {
		return pendingAvailabilities;
	}

}
