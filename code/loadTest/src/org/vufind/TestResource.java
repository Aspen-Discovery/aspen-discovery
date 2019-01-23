package org.vufind;

public class TestResource {
	private int record_id;
	private String source;
	private String title;
	private String author;
	private String isbn;
	private String upc;
	private String format;
	private String format_category;
	
	public TestResource(int record_id, String source, String title, String author, String isbn, String upc, String format, String format_category){
		this.record_id = record_id;
		this.source = source;
		this.title = title;
		this.author = author;
		this.isbn = isbn;
		this.upc = upc;
		this.format = format;
		this.format_category = format_category;
	}

	public int getRecord_id() {
		return record_id;
	}

	public String getSource() {
		return source;
	}

	public String getTitle() {
		return title;
	}

	public String getAuthor() {
		return author;
	}

	public String getIsbn() {
		return isbn;
	}

	public String getUpc() {
		return upc;
	}

	public String getFormat() {
		return format;
	}

	public String getFormat_category() {
		return format_category;
	}
}