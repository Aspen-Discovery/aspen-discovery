package com.peakc.marmot;

public class CopyNoOverwriteResult {
	public enum CopyResult {
		FILE_ALREADY_EXISTS, FILE_COPIED
	}

	private CopyResult	copyResult;
	private String			newFilename;

	public CopyResult getCopyResult() {
		return copyResult;
	}

	public void setCopyResult(CopyResult copyResult) {
		this.copyResult = copyResult;
	}

	public String getNewFilename() {
		return newFilename;
	}

	public void setNewFilename(String newFilename) {
		this.newFilename = newFilename;
	}

}
