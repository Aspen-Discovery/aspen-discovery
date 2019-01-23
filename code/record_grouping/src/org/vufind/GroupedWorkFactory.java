package org.vufind;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 1/26/2015
 * Time: 8:57 AM
 */
class GroupedWorkFactory {
	private static int defaultVersion = 4;
	static GroupedWorkBase getInstance(int version){
		if (version == -1){
			version = defaultVersion;
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
