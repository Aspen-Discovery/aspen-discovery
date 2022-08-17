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
		if (myString == null && type != null){
			myString = type + ":" + identifier.toLowerCase();
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
		return identifier.length() > 0;
	}

	public String getIdentifier() {
		return identifier;
	}

	public String getType() {
		return type;
	}

	public RecordIdentifier(String type, String identifier) {
		this.setValue(type, identifier);
	}

	public void setValue(String type, String identifier) {
		this.type = type.toLowerCase();
		if (identifier != null) {
			identifier = identifier.trim();
		}
		this.identifier = identifier;
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
