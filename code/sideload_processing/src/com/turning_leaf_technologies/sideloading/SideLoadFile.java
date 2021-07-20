package com.turning_leaf_technologies.sideloading;

import java.io.File;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;

public class SideLoadFile implements Comparable<SideLoadFile>{
	private long id = 0;
	private final long sideLoadId;
	private final String filename;
	private long lastChanged;
	private long deletedTime = 0;
	private long lastIndexed = 0;
	private File existingFile = null;
	private boolean needsReindex = false;

	public SideLoadFile(ResultSet filesForSideloadRS) throws SQLException {
		this.id = filesForSideloadRS.getLong("id");
		this.sideLoadId = filesForSideloadRS.getLong("sideLoadId");
		this.filename = filesForSideloadRS.getString("filename");
		this.lastChanged = filesForSideloadRS.getLong("lastChanged");
		this.deletedTime = filesForSideloadRS.getLong("deletedTime");
		this.lastIndexed = filesForSideloadRS.getLong("lastIndexed");
	}

	public SideLoadFile(long sideLoadId, File existingFile){
		this.sideLoadId = sideLoadId;
		this.filename = existingFile.getName();
		this.existingFile = existingFile;
		this.lastChanged = existingFile.lastModified() / 1000;
		this.needsReindex = true;
	}

	public String getFilename() {
		return filename;
	}

	public long getDeletedTime() {
		return deletedTime;
	}

	public long getLastIndexed() {
		return lastIndexed;
	}

	public void setExistingFile(File marcFile){
		this.existingFile = marcFile;
		if ((marcFile.lastModified() / 1000) > this.lastIndexed){
			this.needsReindex = true;
			this.lastChanged = marcFile.lastModified() / 1000;
		}
		if (this.deletedTime != 0){
			this.deletedTime = 0;
			this.needsReindex = true;
			this.lastChanged = marcFile.lastModified() / 1000;
		}

	}

	public File getExistingFile() {
		return existingFile;
	}

	@Override
	public int compareTo(SideLoadFile o) {
		return Long.compare(lastChanged, o.lastChanged);
	}

	public boolean isNeedsReindex() {
		return needsReindex;
	}

	public void setDeletedTime(long deletedTime) {
		this.deletedTime = deletedTime;
	}

	public void updateDatabase(PreparedStatement insertSideloadFileStmt, PreparedStatement updateSideloadFileStmt) throws SQLException {
		this.lastIndexed = new Date().getTime() / 1000;
		if (this.id == 0){
			insertSideloadFileStmt.setLong(1, this.sideLoadId);
			insertSideloadFileStmt.setString(2, this.filename);
			insertSideloadFileStmt.setLong(3, this.lastChanged);
			insertSideloadFileStmt.setLong(4, this.lastIndexed);
			insertSideloadFileStmt.executeUpdate();
		}else{
			updateSideloadFileStmt.setLong(1, this.lastChanged);
			updateSideloadFileStmt.setLong(2, this.deletedTime);
			updateSideloadFileStmt.setLong(3, this.lastIndexed);
			updateSideloadFileStmt.setLong(4, this.id);
			updateSideloadFileStmt.executeUpdate();
		}
	}

	public long getId() {
		return this.id;
	}
}
