package com.turning_leaf_technologies.logging;

public interface BaseLogEntry {
	void addNote(String note);

	@SuppressWarnings("UnusedReturnValue")
	boolean saveResults();

	void setFinished();

	public void incErrors(String note);

	public void incErrors(String note, Exception e);
}
