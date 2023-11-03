package com.turning_leaf_technologies.reindexer;

class ARTitleData {
	private String bookId;
	private String language;
	private String title;
	private String authorFirstName;
	private String authorMiddleName;
	private String authorLastName;
	private String bookLevel;
	private String arPoints;
	private String interestLevel;
	private String isFiction;

	public String getBookId() {
		return bookId;
	}

	public void setBookId(String bookId) {
		this.bookId = bookId;
	}

	public String getLanguage() {
		return language;
	}

	public void setLanguage(String language) {
		this.language = language;
	}

	public String getTitle() {
		return title;
	}

	public void setTitle(String title) {
		this.title = title;
	}

	public String getAuthorCombined() {
		return authorLastName + ", " + authorFirstName + " " + authorMiddleName;
	}

	public void setAuthorFirstName(String authorFirstName) {
		this.authorFirstName = authorFirstName;
	}

	public void setAuthorMiddleName(String authorMiddleName) {
		this.authorMiddleName = authorMiddleName;
	}

	public void setAuthorLastName(String authorLastName) {
		this.authorLastName = authorLastName;
	}

	public String getBookLevel() {
		return bookLevel;
	}

	public void setBookLevel(String bookLevel) {
		this.bookLevel = bookLevel;
	}

	public String getArPoints() {
		return arPoints;
	}

	public void setArPoints(String arPoints) {
		this.arPoints = arPoints;
	}

	public String getInterestLevel() {
		return interestLevel;
	}

	public void setInterestLevel(String interestLevel) {
		this.interestLevel = interestLevel;
	}

	public String getIsFiction() {
		return isFiction;
	}

	public void setIsFiction(String isFiction) {
		this.isFiction = isFiction;
	}
}
