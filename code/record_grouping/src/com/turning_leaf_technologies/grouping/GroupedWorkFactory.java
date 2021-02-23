package com.turning_leaf_technologies.grouping;

class GroupedWorkFactory {
	static GroupedWorkBase getInstance(int version, RecordGroupingProcessor processor){
		return new GroupedWork5(processor);
	}
}
