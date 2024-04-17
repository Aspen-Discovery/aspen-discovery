package com.turning_leaf_technologies.format_classification;

import org.apache.logging.log4j.Logger;

/**
 * Performs classification of a record from the ILS for use when grouping and indexing
 *
 * May take
 */
public class IlsRecordFormatClassifier extends MarcRecordFormatClassifier {
	public IlsRecordFormatClassifier(Logger logger) {
		super(logger);
	}
}
