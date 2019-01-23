package org.vufind;

class CopyNoOverwriteResult {
	enum CopyResult {
		FILE_ALREADY_EXISTS, FILE_COPIED
	}

	private CopyResult	copyResult;
	private String			newFilename;

	public CopyResult getCopyResult() {
		return copyResult;
	}

	void setCopyResult(CopyResult copyResult) {
		this.copyResult = copyResult;
	}

	public String getNewFilename() {
		return newFilename;
	}

	void setNewFilename(String newFilename) {
		this.newFilename = newFilename;
	}

}
