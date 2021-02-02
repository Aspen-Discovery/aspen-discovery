package com.turning_leaf_technologies.cloud_library;

import org.apache.logging.log4j.Logger;
import org.xml.sax.helpers.DefaultHandler;

class CloudLibraryAvailabilityHandler extends DefaultHandler {
	private final CloudLibraryAvailability availability;
	private String nodeContents = "";

	CloudLibraryAvailabilityHandler(CloudLibraryAvailability availability) {
		this.availability = availability;
	}

	public void characters(char[] ch, int start, int length) {
		nodeContents += new String(ch, start, length);
	}

	public void endElement(String uri, String localName, String qName) {
		switch (qName) {
			case "totalCopies":
				availability.setTotalCopies(Integer.parseInt(nodeContents.trim()));
				break;
			case "sharedCopies":
				availability.setSharedCopies(Integer.parseInt(nodeContents.trim()));
				break;
			case "totalLoanCopies":
				availability.setTotalLoanCopies(Integer.parseInt(nodeContents.trim()));
				break;
			case "totalHoldCopies":
				availability.setTotalHoldCopies(Integer.parseInt(nodeContents.trim()));
				break;
			case "sharedLoanCopies":
				availability.setSharedLoanCopies(Integer.parseInt(nodeContents.trim()));
				break;
		}
		nodeContents = "";
	}
}
