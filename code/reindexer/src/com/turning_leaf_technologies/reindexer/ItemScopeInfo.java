package com.turning_leaf_technologies.reindexer;

import java.sql.ResultSet;
import java.sql.SQLException;

public class ItemScopeInfo {
	public long scopeId;
	public long scopeDetailsId;

	public ItemScopeInfo(ResultSet getExistingScopesForItemRS) throws SQLException {
		this.scopeId = getExistingScopesForItemRS.getLong("scopeId");
		this.scopeDetailsId = getExistingScopesForItemRS.getLong("scopeDetailsId");
	}
}
