package org.aspen_discovery.reindexer;

import java.util.Objects;

class VariationInfo {
	public Long id;
	public long primaryLanguageId;
	public long eContentSourceId;
	public long formatId;
	public long formatCategoryId;

	public boolean equals(VariationInfo o) {
		return primaryLanguageId == o.primaryLanguageId &&
				eContentSourceId == o.eContentSourceId &&
				formatId == o.formatId &&
				formatCategoryId == o.formatCategoryId;
	}

	public boolean equals(Object o) {
		if (o instanceof VariationInfo) {
			VariationInfo variationInfo = (VariationInfo)o;
			return primaryLanguageId == variationInfo.primaryLanguageId &&
					eContentSourceId == variationInfo.eContentSourceId &&
					formatId == variationInfo.formatId &&
					formatCategoryId == variationInfo.formatCategoryId;
		}else{
			return false;
		}
	}

	private String stringValue = null;
	public String toString(){
		if (stringValue == null){
			stringValue = String.valueOf(primaryLanguageId) + eContentSourceId + formatId + formatCategoryId;
		}
		return stringValue;
	}

	public int hashCode(){
		return Objects.hash(primaryLanguageId, eContentSourceId, formatId, formatCategoryId);
	}
}
