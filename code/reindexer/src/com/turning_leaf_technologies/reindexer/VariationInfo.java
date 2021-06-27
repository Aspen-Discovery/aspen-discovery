package com.turning_leaf_technologies.reindexer;

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

	public String toString(){
		return String.valueOf(primaryLanguageId) + eContentSourceId + formatId + formatCategoryId;
	}

	public int hashCode(){
		 return this.toString().hashCode();
	}
}
