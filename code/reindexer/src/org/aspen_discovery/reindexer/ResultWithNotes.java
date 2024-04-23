package org.aspen_discovery.reindexer;

public class ResultWithNotes {
	public boolean result;
	public StringBuilder notes;

	public ResultWithNotes(boolean result, StringBuilder notes){
		this.result = result;
		this.notes = notes;
	}
}
