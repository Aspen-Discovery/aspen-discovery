package com.turning_leaf_technologies.indexing;

public class RecordIdentifier {
	private String type;
	private String identifier;
	private boolean suppressed;
	private String suppressionReason;

	@Override
	public int hashCode() {
		return toString().hashCode();
	}

	private String myString = null;
	public String toString(){
		if (myString == null){
			myString = type + ":" + identifier.toUpperCase();
		}
		return myString;
	}

	@Override
	public boolean equals(Object obj) {
		if (obj instanceof  RecordIdentifier){
			RecordIdentifier tmpObj = (RecordIdentifier)obj;
			return (tmpObj.type.equals(type) && tmpObj.identifier.equals(identifier));
		}else{
			return false;
		}
	}

	public boolean isValid() {
		switch (type) {
			case "publishercatalognumber":
			case "doi":
			case "asin":
				return false;
			case "upc":
				return identifier.matches("^(\\d{7,14})|(\\d{9}X|\\d{10}|\\d{12}X|\\d{13})?$");
			case "isbn":
				return identifier.matches("^\\d{9}X|\\d{10}|\\d{12}X|\\d{13}$");
			default:
				return identifier.length() > 0;
		}
	}

	public String getIdentifier() {
		return identifier;
	}

	public String getType() {
		return type;
	}

	public void setValue(String type, String identifier) {
		this.type = type.toLowerCase();
		switch (this.type) {
			case "isbn":
				identifier = identifier.replaceAll("[\\DXx]", "").toUpperCase().trim();
				//Convert any ISBN-10 to ISBN-13 for consistency and to minimize the total number of stored ISBNs
				if (identifier.length() == 10) {
					identifier = convertISBN10to13(identifier);
				}
				break;
			case "upc":
				identifier = identifier.replaceAll("[\\D]", "");
				break;
			case "oclc":
				identifier = identifier.toUpperCase();
				break;
		}
		if (identifier != null) {
			identifier = identifier.trim();
		}
		this.identifier = identifier;
	}

	private static String convertISBN10to13(String isbn10){
		if (isbn10.length() != 10){
			return null;
		}
		String isbn = "978" + isbn10.substring(0, 9);
		//Calculate the 13 digit checksum
		int sumOfDigits = 0;
		for (int i = 0; i < 12; i++){
			int multiplier = 1;
			if (i % 2 == 1){
				multiplier = 3;
			}
			sumOfDigits += multiplier * (int)(isbn.charAt(i));
		}
		int modValue = sumOfDigits % 10;
		int checksumDigit;
		if (modValue == 0){
			checksumDigit = 0;
		}else{
			checksumDigit = 10 - modValue;
		}
		return  isbn + checksumDigit;
	}

	public boolean isSuppressed() {
		return suppressed;
	}

	public void setSuppressed() {
		this.suppressed = true;
	}

	public void setSuppressionReason(String suppressionReason) {
		this.suppressionReason = suppressionReason;
	}

	public String getSuppressionReason() {
		return suppressionReason;
	}
}
