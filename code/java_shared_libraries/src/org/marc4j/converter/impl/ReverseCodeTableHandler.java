/**
 * Copyright (C) 2002 Bas Peters
 *
 * This file is part of MARC4J
 *
 * MARC4J is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * MARC4J is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with MARC4J; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

package org.marc4j.converter.impl;

import java.util.Hashtable;
import java.util.Vector;

import org.xml.sax.Attributes;
import org.xml.sax.Locator;
import org.xml.sax.helpers.DefaultHandler;

/**
 * <p>
 * <code>ReverseCodeTableHandler</code> is a SAX2 <code>ContentHandler</code> that builds a data structure to facilitate
 * <code>UnicodeToAnsel</code> character conversion.
 *
 * @author Corey Keith
 * @see DefaultHandler
 */
public class ReverseCodeTableHandler extends DefaultHandler {

    private Hashtable<Character, Hashtable<Integer, char[]>> charsets;

    private Vector<Character> combiningChars;

    /** Data element identifier */
    private Integer isoCode;

    private char[] marc;

    private Character ucs;

    private Character altUcs;

    private boolean combining;

    /** StringBuffer to store data */
    private StringBuffer data;

    /** Locator object */
    protected Locator locator;

    /**
     * Gets character sets.
     *
     * @return The character sets
     */
    public Hashtable<Character, Hashtable<Integer, char[]>> getCharSets() {
        return charsets;
    }

    /**
     * Gets the combining characters.
     *
     * @return The combining characters
     */
    public Vector<Character> getCombiningChars() {
        return combiningChars;
    }

    /**
     * <p>
     * Registers the SAX2 <code>Locator</code> object.
     * </p>
     *
     * @param locator the {@link Locator}object
     */
    @Override
    public void setDocumentLocator(final Locator locator) {
        this.locator = locator;
    }

    @Override
    public void startElement(final String uri, final String name, final String qName, final Attributes attributes) {
	    switch (name) {
		    case "characterSet":
			    isoCode = Integer.valueOf(attributes.getValue("ISOcode"), 16);
			    break;
		    case "marc":
		    case "ucs":
		    case "alt":
		    case "isCombining":
			    data = new StringBuffer();
			    break;
		    case "codeTables":
			    charsets = new Hashtable<>();
			    combiningChars = new Vector<>();
			    break;
		    case "code":
			    ucs = null;
			    altUcs = null;
			    combining = false;
			    break;
	    }
    }

    @Override
    public void characters(final char[] ch, final int start, final int length) {
        if (data != null) {
            data.append(ch, start, length);
        }
    }

    @Override
    public void endElement(final String uri, final String name, final String qName) {
	    switch (name) {
		    case "marc":
			    final String marcString = data.toString();

			    if (marcString.length() == 6) {
				    marc = new char[3];
				    marc[0] = (char) Integer.parseInt(marcString.substring(0, 2), 16);
				    marc[1] = (char) Integer.parseInt(marcString.substring(2, 4), 16);
				    marc[2] = (char) Integer.parseInt(marcString.substring(4, 6), 16);
			    } else {
				    marc = new char[1];
				    marc[0] = (char) Integer.parseInt(marcString, 16);
			    }
			    break;
		    case "ucs":
			    if (data.length() > 0) {
				    ucs = (char) Integer.parseInt(data.toString(), 16);
			    }
			    break;
		    case "alt":
			    if (data.length() > 0) {
				    altUcs = (char) Integer.parseInt(data.toString(), 16);
			    }
			    break;
		    case "code":
			    if (combining) {
				    if (ucs != null) {
					    combiningChars.add(ucs);
				    }

				    if (altUcs != null) {
					    combiningChars.add(altUcs);
				    }
			    }

			    if (ucs != null) {
				    if (charsets.get(ucs) == null) {
					    final Hashtable<Integer, char[]> h = new Hashtable<>(1);
					    h.put(isoCode, marc);
					    charsets.put(ucs, h);
				    } else {
					    final Hashtable<Integer, char[]> h = charsets.get(ucs);
					    h.put(isoCode, marc);
				    }
			    }

			    if (altUcs != null) {
				    if (charsets.get(altUcs) == null) {
					    final Hashtable<Integer, char[]> h = new Hashtable<>(1);

					    h.put(isoCode, marc);
					    charsets.put(altUcs, h);
				    } else {
					    final Hashtable<Integer, char[]> h = charsets.get(altUcs);

					    if (!h.containsKey(isoCode)) {
						    h.put(isoCode, marc);
					    }
				    }
			    }
			    break;
		    case "isCombining":
			    if (data.toString().equals("true")) {
				    combining = true;
			    }
			    break;
	    }

        data = null;
    }

}
