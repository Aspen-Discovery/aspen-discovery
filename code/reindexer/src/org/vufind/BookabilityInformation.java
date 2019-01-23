package org.vufind;

import java.util.HashSet;

/**
 * Information about bookability for a title, includes related pTypes when applicable
 * Pika
 * User: Mark Noble
 * Date: 8/26/2015
 * Time: 3:10 PM
 */
public class BookabilityInformation {
	private boolean isBookable;
	private HashSet<Long> bookablePTypes;

	public BookabilityInformation(boolean bookable, HashSet<Long> bookablePTypes) {
		this.isBookable = bookable;
		this.bookablePTypes = bookablePTypes;
	}

	public boolean isBookable() {
		return isBookable;
	}

	public String getBookablePTypes() {
		if (bookablePTypes.contains(999L)){
			return "999";
		}else{
			return Util.getCsvSeparatedStringFromLongs(bookablePTypes);
		}
	}
}
