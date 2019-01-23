package org.vufind;

/**
 * Accelerated Reader information for a title
 * Pika
 * User: Mark Noble
 * Date: 10/21/2015
 * Time: 5:11 PM
 */
class ARTitle {
	private String title;
	private String author;
	private String bookLevel;
	private String arPoints;
	private String interestLevel;

	public String getTitle() {
		return title;
	}

	public void setTitle(String title) {
		this.title = title;
	}

	public String getAuthor() {
		return author;
	}

	public void setAuthor(String author) {
		this.author = author;
	}

	String getBookLevel() {
		return bookLevel;
	}

	void setBookLevel(String bookLevel) {
		this.bookLevel = bookLevel;
	}

	String getArPoints() {
		return arPoints;
	}

	void setArPoints(String arPoints) {
		this.arPoints = arPoints;
	}

	String getInterestLevel() {
		return interestLevel;
	}

	void setInterestLevel(String interestLevel) {
		this.interestLevel = interestLevel;
	}
}
