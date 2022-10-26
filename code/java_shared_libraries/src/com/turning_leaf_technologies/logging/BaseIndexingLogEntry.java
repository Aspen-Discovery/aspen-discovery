package com.turning_leaf_technologies.logging;

public interface BaseIndexingLogEntry {
	void addNote(String note);

	@SuppressWarnings("UnusedReturnValue")
	boolean saveResults();

	void setFinished();

	void incErrors(String note);

	void incErrors(String note, Exception e);

	void incInvalidRecords(String invalidRecordId);
}
