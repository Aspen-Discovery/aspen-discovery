package com.turning_leaf_technologies.hoopla;

import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.json.JSONArray;
import org.json.JSONObject;

public class HooplaUtils {
	public static String getPrimaryAuthor(JSONObject dataFromHoopla, String hooplaKind) {
		String primaryAuthor = "";
		if (dataFromHoopla.has("artists")){
			JSONArray artists = dataFromHoopla.optJSONArray("artists");
			if (artists != null){
				for (int i = 0; i < artists.length(); i++) {
					JSONObject artist = artists.optJSONObject(i);
					if (artist != null){
						if (artist.has("artistFormal")){
							primaryAuthor = artist.getString("artistFormal");
							break;
						}
					}
				}
			}
		}
		if (primaryAuthor.isEmpty()) {
			if (dataFromHoopla.has("artist")) {
				primaryAuthor = dataFromHoopla.getString("artist");
				//Don't swap artist names for music since these are typically group names.
				if (!hooplaKind.equals("MUSIC")) {
					primaryAuthor = AspenStringUtils.swapFirstLastNames(primaryAuthor);
				}
			} else if (dataFromHoopla.has("publisher")) {
				primaryAuthor = dataFromHoopla.getString("publisher");
			}
		}
		return primaryAuthor;
	}
}
