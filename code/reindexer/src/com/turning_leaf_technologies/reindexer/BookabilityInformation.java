package com.turning_leaf_technologies.reindexer;

import java.util.HashSet;

class BookabilityInformation {
	private boolean isBookable;
	private HashSet<Long> bookablePTypes;

	BookabilityInformation(boolean bookable, HashSet<Long> bookablePTypes) {
		this.isBookable = bookable;
		this.bookablePTypes = bookablePTypes;
	}

	boolean isBookable() {
		return isBookable;
	}

	String getBookablePTypes() {
		if (bookablePTypes.contains(999L)){
			return "999";
		}else{
			return Util.getCsvSeparatedStringFromLongs(bookablePTypes);
		}
	}
}
