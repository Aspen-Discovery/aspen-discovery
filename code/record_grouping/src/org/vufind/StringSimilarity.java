package org.vufind;

public class StringSimilarity {

	/**
	 * Calculates the similarity (a number within 0 and 1) between two strings.
	 */
	public static double similarity(String s1, String s2) {
		String longer = s1, shorter = s2;
		if (s1.length() < s2.length()) { // longer should always have greater length
			longer = s2;
			shorter = s1;
		}
		int longerLength = longer.length();
		if (longerLength == 0) {
			return 1.0; /* both strings are zero length */
		}
    /* // If you have StringUtils, you can use it to calculate the edit distance:
    return (longerLength - StringUtils.getLevenshteinDistance(longer, shorter)) /
                               (double) longerLength; */
		double editDistance = (longerLength - editDistance(longer, shorter)) / (double) longerLength;
		double distanceByWord = getSimilarityByWord(s1, s2);
		return Math.max(editDistance, distanceByWord);

	}

	/**
	 * Get similarity of the 2 strings based on the words within the strings.
	 * Words can appear in any order.
	 *
	 * @param s1
	 * @param s2
	 * @return
	 */
	private static double getSimilarityByWord(String s1, String s2) {
		String[] words1 = s1.split("\\W+");
		String[] words2 = s2.split("\\W+");

		int maxWords = Math.max(words1.length, words2.length);
		int matchingWords = 0;
		boolean[] wordsMatched2 = new boolean[words2.length];
		for (int i = 0; i < words1.length; i++){
			for (int j = 0; j < words2.length; j++){
				if (!wordsMatched2[j] && words1[i].equalsIgnoreCase(words2[j])){
					wordsMatched2[j] = true;
					matchingWords++;
				}
			}
		}
		return (double)matchingWords/(double)maxWords;
	}


	// Example implementation of the Levenshtein Edit Distance
	// See http://rosettacode.org/wiki/Levenshtein_distance#Java
	public static int editDistance(String s1, String s2) {
		s1 = s1.toLowerCase();
		s2 = s2.toLowerCase();

		int[] costs = new int[s2.length() + 1];
		for (int i = 0; i <= s1.length(); i++) {
			int lastValue = i;
			for (int j = 0; j <= s2.length(); j++) {
				if (i == 0)
					costs[j] = j;
				else {
					if (j > 0) {
						int newValue = costs[j - 1];
						if (s1.charAt(i - 1) != s2.charAt(j - 1))
							newValue = Math.min(Math.min(newValue, lastValue),
									costs[j]) + 1;
						costs[j - 1] = lastValue;
						lastValue = newValue;
					}
				}
			}
			if (i > 0)
				costs[s2.length()] = lastValue;
		}
		return costs[s2.length()];
	}
}