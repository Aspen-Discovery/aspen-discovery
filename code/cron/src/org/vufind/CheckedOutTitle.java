package org.vufind;

/**
 * A title that is checked out to a user for reading history
 * VuFind-Plus
 * User: Mark Noble
 * Date: 12/11/2014
 * Time: 1:34 PM
 */
class CheckedOutTitle {
	private Long id;
	private String groupedWorkPermanentId;
	private String source;
	private String sourceId;
	private String title;

	public Long getId() {
		return id;
	}

	public void setId(Long id) {
		this.id = id;
	}

	public String getGroupedWorkPermanentId() {
		return groupedWorkPermanentId;
	}

	void setGroupedWorkPermanentId(String groupedWorkPermanentId) {
		this.groupedWorkPermanentId = groupedWorkPermanentId;
	}

	public String getSource() {
		return source;
	}

	public void setSource(String source) {
		this.source = source;
	}

	String getSourceId() {
		return sourceId;
	}

	void setSourceId(String sourceId) {
		this.sourceId = sourceId;
	}

	public String getTitle() {
		return title;
	}

	public void setTitle(String title){
		this.title = title;
	}
}
