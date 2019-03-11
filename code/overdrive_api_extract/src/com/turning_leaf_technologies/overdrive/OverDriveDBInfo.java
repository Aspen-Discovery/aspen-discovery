package com.turning_leaf_technologies.overdrive;

class OverDriveDBInfo {
	private long dbId;
	private long crossRefId;
	private String mediaType;
	private String title;
	private String subtitle;
	private String series;
	private String primaryCreatorRole;
	private String primaryCreatorName;
	private String cover;
	private boolean deleted;
	private boolean hasRawData;

	boolean hasRawData() {
		return hasRawData;
	}

	void setHasRawData(boolean hasRawData) {
		this.hasRawData = hasRawData;
	}

	boolean isDeleted() {
		return deleted;
	}
	void setDeleted(boolean deleted) {
		this.deleted = deleted;
	}
	String getMediaType() {
		return mediaType;
	}
	void setMediaType(String mediaType) {
		this.mediaType = mediaType;
	}
	String getTitle() {
		return title;
	}
	void setTitle(String title) {
		this.title = title;
	}
	String getSeries() {
		return series;
	}
	void setSeries(String series) {
		this.series = series;
	}
	String getPrimaryCreatorRole() {
		return primaryCreatorRole;
	}
	void setPrimaryCreatorRole(String primaryCreatorRole) {
		this.primaryCreatorRole = primaryCreatorRole;
	}
	String getPrimaryCreatorName() {
		return primaryCreatorName;
	}
	void setPrimaryCreatorName(String primaryCreatorName) {
		this.primaryCreatorName = primaryCreatorName;
	}
	
	
	long getDbId() {
		return dbId;
	}
	void setDbId(long dbId) {
		this.dbId = dbId;
	}
	String getCover() {
		return cover;
	}
	void setCover(String cover) {
		this.cover = cover;
	}

	String getSubtitle() {
		return subtitle;
	}
	void setSubtitle(String subtitle) {this.subtitle = subtitle;}

	long getCrossRefId() {
		return crossRefId;
	}

	void setCrossRefId(long crossRefId) {
		this.crossRefId = crossRefId;
	}
}
