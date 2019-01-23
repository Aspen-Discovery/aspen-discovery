package org.vufind;

/**
 * Description goes here
 * Rampart Marc Conversion
 * User: Mark Noble
 * Date: 10/18/13
 * Time: 10:27 AM
 */
class RecordIdentifier {
	private String type;
	private String identifier;
	private boolean suppressed;

	@Override
	public int hashCode() {
		return toString().hashCode();
	}

	private String myString = null;
	public String toString(){
		if (myString == null && type != null && identifier != null){
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

	String getType() {
		return type;
	}

	boolean isValid() {
		return identifier.length() > 0;
	}

	String getIdentifier() {
		return identifier;
	}

	void setValue(String type, String identifier) {
		this.type = type.toLowerCase();
		identifier = identifier.trim();
		this.identifier = identifier;
	}

	boolean isSuppressed() {
		return suppressed;
	}

	void setSuppressed(boolean suppressed) {
		this.suppressed = suppressed;
	}

}
