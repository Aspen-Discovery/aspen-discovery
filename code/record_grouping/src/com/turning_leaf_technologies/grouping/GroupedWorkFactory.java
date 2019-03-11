package com.turning_leaf_technologies.grouping;

class GroupedWorkFactory {
	static GroupedWorkBase getInstance(int version){
		if (version == -1){
			version = 4;
		}
		if (version == 1){
			return new GroupedWork1();
		}else if (version == 2){
			return new GroupedWork2();
		}else if (version == 3){
			return new GroupedWork3();
		}else if (version == 4){
			return new GroupedWork4();
		}else{
			//Get the default Grouped Work
			return new GroupedWork4();
		}
	}
}
