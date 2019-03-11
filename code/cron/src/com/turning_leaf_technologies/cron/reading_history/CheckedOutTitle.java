package com.turning_leaf_technologies.cron.reading_history;

class CheckedOutTitle {
	private Long id;
	private String source;
	private String sourceId;
	private String title;

	public Long getId() {
		return id;
	}

	public void setId(Long id) {
		this.id = id;
	}

	String getSource() {
		return source;
	}

	void setSource(String source) {
		this.source = source;
	}

	String getSourceId() {
		return sourceId;
	}

	void setSourceId(String sourceId) {
		this.sourceId = sourceId;
	}

	String getTitle() {
		return title;
	}

	void setTitle(String title){
		this.title = title;
	}
}
