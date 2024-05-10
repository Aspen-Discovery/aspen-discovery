package org.aspen_discovery.format_classification;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.indexing.IndexingProfile;

import java.util.HashMap;

public class FormatInfo {
	public String format;
	public String formatCategory;
	public int formatBoost;

	public static HashMap<String, String> categoryMap = new HashMap<>();
	static {
		categoryMap.put("other", "other");
		categoryMap.put("book", "book");
		categoryMap.put("books", "book");
		categoryMap.put("ebook", "book");
		categoryMap.put("audio", "book");
		categoryMap.put("audio books", "book");
		categoryMap.put("music", "music");
		categoryMap.put("movie", "movie");
		categoryMap.put("movies", "movie");
		categoryMap.put("comic", "comic");
	}

	public static HashMap<String, String> formatsToFormatCategory = new HashMap<>();

	static {
		formatsToFormatCategory.put("emagazine", "book");
		formatsToFormatCategory.put("emusic", "music");
		formatsToFormatCategory.put("music", "music");
		formatsToFormatCategory.put("video", "movie");
		formatsToFormatCategory.put("evideo", "movie");
		formatsToFormatCategory.put("eaudio", "book");
		formatsToFormatCategory.put("eaudiobook", "book");
		formatsToFormatCategory.put("ecomic", "comic");
		formatsToFormatCategory.put("audiobook", "book");
		formatsToFormatCategory.put("atlas", "other");
		formatsToFormatCategory.put("map", "other");
		formatsToFormatCategory.put("tapecartridge", "other");
		formatsToFormatCategory.put("chipcartridge", "other");
		formatsToFormatCategory.put("disccartridge", "other");
		formatsToFormatCategory.put("tapecassette", "other");
		formatsToFormatCategory.put("tapereel", "other");
		formatsToFormatCategory.put("floppydisk", "other");
		formatsToFormatCategory.put("cdrom", "other");
		formatsToFormatCategory.put("software", "other");
		formatsToFormatCategory.put("globe", "other");
		formatsToFormatCategory.put("braille", "book");
		formatsToFormatCategory.put("filmstrip", "movie");
		formatsToFormatCategory.put("transparency", "other");
		formatsToFormatCategory.put("slide", "other");
		formatsToFormatCategory.put("microfilm", "other");
		formatsToFormatCategory.put("collage", "other");
		formatsToFormatCategory.put("drawing", "other");
		formatsToFormatCategory.put("painting", "other");
		formatsToFormatCategory.put("print", "other");
		formatsToFormatCategory.put("photonegative", "other");
		formatsToFormatCategory.put("flashcard", "other");
		formatsToFormatCategory.put("chart", "other");
		formatsToFormatCategory.put("photo", "other");
		formatsToFormatCategory.put("motionpicture", "movie");
		formatsToFormatCategory.put("kit", "other");
		formatsToFormatCategory.put("musicalscore", "book");
		formatsToFormatCategory.put("sensorimage", "other");
		formatsToFormatCategory.put("sounddisc", "audio");
		formatsToFormatCategory.put("soundcassette", "audio");
		formatsToFormatCategory.put("soundrecording", "audio");
		formatsToFormatCategory.put("videocartridge", "movie");
		formatsToFormatCategory.put("videosisc", "movie");
		formatsToFormatCategory.put("videocassette", "movie");
		formatsToFormatCategory.put("videoreel", "movie");
		formatsToFormatCategory.put("musicrecording", "music");
		formatsToFormatCategory.put("electronic", "other");
		formatsToFormatCategory.put("physicalobject", "other");
		formatsToFormatCategory.put("manuscript", "book");
		formatsToFormatCategory.put("ebook", "ebook");
		formatsToFormatCategory.put("book", "book");
		formatsToFormatCategory.put("newspaper", "book");
		formatsToFormatCategory.put("journal", "book");
		formatsToFormatCategory.put("serial", "book");
		formatsToFormatCategory.put("unknown", "other");
		formatsToFormatCategory.put("playaway", "audio");
		formatsToFormatCategory.put("largeprint", "book");
		formatsToFormatCategory.put("blu-ray", "movie");
		formatsToFormatCategory.put("dvd", "movie");
		formatsToFormatCategory.put("verticalfile", "other");
		formatsToFormatCategory.put("compactdisc", "audio");
		formatsToFormatCategory.put("taperecording", "audio");
		formatsToFormatCategory.put("phonograph", "audio");
		formatsToFormatCategory.put("pdf", "ebook");
		formatsToFormatCategory.put("epub", "ebook");
		formatsToFormatCategory.put("jpg", "other");
		formatsToFormatCategory.put("gif", "other");
		formatsToFormatCategory.put("mp3", "audio");
		formatsToFormatCategory.put("plucker", "ebook");
		formatsToFormatCategory.put("kindle", "ebook");
		formatsToFormatCategory.put("externallink", "ebook");
		formatsToFormatCategory.put("externalmp3", "audio");
		formatsToFormatCategory.put("interactivebook", "ebook");
		formatsToFormatCategory.put("overdrive", "ebook");
		formatsToFormatCategory.put("external_web", "ebook");
		formatsToFormatCategory.put("external_ebook", "ebook");
		formatsToFormatCategory.put("external_eaudio", "audio");
		formatsToFormatCategory.put("external_emusic", "music");
		formatsToFormatCategory.put("external_evideo", "movie");
		formatsToFormatCategory.put("text", "ebook");
		formatsToFormatCategory.put("gifs", "other");
		formatsToFormatCategory.put("itunes", "audio");
		formatsToFormatCategory.put("adobe_epub_ebook", "ebook");
		formatsToFormatCategory.put("kindle_book", "ebook");
		formatsToFormatCategory.put("microsoft_ebook", "ebook");
		formatsToFormatCategory.put("overdrive_wma_audiobook", "audio");
		formatsToFormatCategory.put("overdrive_mp3_audiobook", "audio");
		formatsToFormatCategory.put("overdrive_music", "music");
		formatsToFormatCategory.put("overdrive_video", "movie");
		formatsToFormatCategory.put("overdrive_read", "ebook");
		formatsToFormatCategory.put("overdrive_listen", "audio");
		formatsToFormatCategory.put("adobe_pdf_ebook", "ebook");
		formatsToFormatCategory.put("palm", "ebook");
		formatsToFormatCategory.put("mobipocket_ebook", "ebook");
		formatsToFormatCategory.put("disney_online_book", "ebook");
		formatsToFormatCategory.put("open_pdf_ebook", "ebook");
		formatsToFormatCategory.put("open_epub_ebook", "ebook");
		formatsToFormatCategory.put("nook_periodicals", "ebook");
		formatsToFormatCategory.put("econtent", "ebook");
		formatsToFormatCategory.put("seedpacket", "other");
		formatsToFormatCategory.put("magazine-overdrive", "ebook");
		formatsToFormatCategory.put("magazine", "book");
		formatsToFormatCategory.put("xps", "ebook");
		formatsToFormatCategory.put("bingepass", "other");
		formatsToFormatCategory.put("graphicnovel", "comic");
		formatsToFormatCategory.put("graphic novel", "comic");
		formatsToFormatCategory.put("manga", "comic");
		formatsToFormatCategory.put("comic", "comic");
	}

	public String getGroupingFormat(BaseIndexingSettings settings) {
		//Special processing for graphic novels that go into the Books Category, but group as comic
		String groupingFormat = format;
		String groupingFormatCategory = formatCategory;
		if (format == null || format.isEmpty()){
			//We didn't get a format, make it a book
			groupingFormat = "book";
		}
		String formatLower = groupingFormat.toLowerCase();
		if (formatLower.contains("graphicnovel") || formatLower.contains("graphic novel") || (formatLower.contains("comic") && !formatLower.contains("ecomic")) || formatLower.contains("manga")) {
			formatLower = "graphic novel";
			groupingFormatCategory = "comic";
		}

		if (groupingFormatCategory != null && !groupingFormatCategory.isEmpty()) {
			formatLower = groupingFormatCategory.toLowerCase();
			return categoryMap.getOrDefault(formatLower, "other");
		}else{
			if (settings instanceof IndexingProfile) {
				IndexingProfile profile = (IndexingProfile) settings;
				if (profile.hasTranslation("format_category", format)){
					String formatCategory = profile.translateValue("format_category", format);
					groupingFormat = FormatInfo.categoryMap.getOrDefault(FormatInfo.formatsToFormatCategory.get(formatLower), "other");
				}else{
					groupingFormat = "other";
				}
			}else{
				if (FormatInfo.formatsToFormatCategory.containsKey(formatLower)) {
					groupingFormat = FormatInfo.categoryMap.getOrDefault(FormatInfo.formatsToFormatCategory.get(formatLower), "other");
				}else{
					groupingFormat = "other";
				}
			}
			return groupingFormat;
		}
	}
}
