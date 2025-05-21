package org.aspen_discovery.reindexer;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;


public class ItemStatus {
	private String originalValue;
	private String status;
	private String groupedStatus;
	private int source;
	public static final int FROM_STATUS_FIELD = 1;
	public static final int FROM_STATUS_ALT_FIELD = 2;
	public static final int FROM_OTHER = 3;

	public ItemStatus(String status, int source, IlsRecordProcessor recordProcessor, String identifier) {
		this.originalValue = status;
		this.source = source;
		if (originalValue != null) {
			if (source == ItemStatus.FROM_STATUS_FIELD || source == ItemStatus.FROM_OTHER) {
				this.status = recordProcessor.translateValue("item_status", originalValue, identifier);
				this.groupedStatus = recordProcessor.translateValue("item_grouped_status", originalValue, identifier);
			}else{
				this.status = recordProcessor.translateValue("item_status_alt", originalValue, identifier);
				this.groupedStatus = recordProcessor.translateValue("item_grouped_status_alt", originalValue, identifier);
			}
			if (this.status == null) {
				this.status = originalValue;
			}
			if (this.groupedStatus == null) {
				this.groupedStatus = originalValue;
			}
		}
	}
	public String getOriginalValue() {
		return originalValue;
	}

	public void setOriginalValue(String originalValue) {
		this.originalValue = originalValue;
	}

	public String getStatus() {
		return status;
	}

	public void setStatus(String status) {
		this.status = status;
	}

	public String getGroupedStatus() {
		return groupedStatus;
	}

	public void setGroupedStatus(String groupedStatus) {
		this.groupedStatus = groupedStatus;
	}

	public int getSource() {
		return source;
	}

	public void setSource(int source) {
		this.source = source;
	}

}
