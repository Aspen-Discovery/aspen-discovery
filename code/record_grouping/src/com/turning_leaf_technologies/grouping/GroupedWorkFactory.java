package com.turning_leaf_technologies.grouping;

class GroupedWorkFactory {
	static GroupedWorkBase getInstance(int version, RecordGroupingProcessor processor){
		if (version == -1){
			version = 5;
		}
		if (version == 1){
			return new GroupedWork1(processor);
		}else if (version == 2){
			return new GroupedWork2(processor);
		}else if (version == 3){
			return new GroupedWork3(processor);
		}else if (version == 4){
			return new GroupedWork4(processor);
		}else{
			//Get the default Grouped Work
			return new GroupedWork5(processor);
		}
	}
}
