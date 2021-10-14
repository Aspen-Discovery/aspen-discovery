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

import java.io.File;
import java.io.FileInputStream;
import java.io.InputStream;
import java.util.HashMap;
import java.util.Vector;

import javax.xml.parsers.SAXParser;
import javax.xml.parsers.SAXParserFactory;

import org.marc4j.MarcException;
import org.xml.sax.InputSource;
import org.xml.sax.XMLReader;

/**
 * <p>
 * <code>CodeTable</code> defines a data structure to facilitate
 * <code>AnselToUnicode</code> character conversion.
 * </p>
 *
 * @author Corey Keith
 */
class CodeTable implements CodeTableInterface {

    static HashMap<Integer, HashMap<Integer, Character>> charsets = null;

    protected static HashMap<Integer, Vector<Integer>> combining = null;

    /**
     * Returns <code>true</code> if combining; else, <code>false</code>.
     *
     * @param i - the character code to check
     * @param g0 - the current g0 character set in use
     * @param g1 - the current g1 character code in use
     * @return Returns <code>true</code> if combining
     */
    @Override
    public boolean isCombining(final int i, final int g0, final int g1) {
        if (i <= 0x7E) {
            final Vector<Integer> v = combining.get(g0);
            return (v != null && v.contains(i));
        } else {
            final Vector<Integer> v = combining.get(g1);
            return (v != null && v.contains(i));
        }
    }

    /**
     * Returns the <code>char</code> for the supplied <code>int</code> and mode.
     *
     * @param c - the character being looked up
     * @param mode - the current mode of the converter
     * @return Returns the <code>char</code> for the supplied <code>int</code> and mode
     */
    @Override
    public char getChar(final int c, final int mode) {
        if (c == 0x20) {
            return (char) c;
        } else {
            final HashMap<Integer, Character> charset = charsets.get(mode);

            if (charset == null) {
                // System.err.println("Hashtable not found: "
                // + Integer.toHexString(mode));
                return (char) c;
            } else {
                Character ch = charset.get(c);
                if (ch == null) {
                    final int newc = (c < 0x80) ? c + 0x80 : c - 0x80;
                    ch = charset.get(newc);
                    if (ch == null) {
                        // System.err.println("Character not found: "
                        // + Integer.toHexString(c) + " in Code Table: "
                        // + Integer.toHexString(mode));
                        return (char) 0;
                    } else {
                        return ch;
                    }
                } else {
                    return ch;
                }
            }
        }
    }

    /**
     * Creates a CodeTable from the supplied {@link InputStream}.
     *
     * @param byteStream - an InputStream to read to create the code table
     */
    CodeTable(final InputStream byteStream) {
        try {
            final SAXParserFactory factory = SAXParserFactory.newInstance();
            factory.setNamespaceAware(true);
            factory.setValidating(false);
            final SAXParser saxParser = factory.newSAXParser();
            final XMLReader rdr = saxParser.getXMLReader();
            final InputSource src = new InputSource(byteStream);
            final CodeTableHandler saxUms = new CodeTableHandler();

            rdr.setContentHandler(saxUms);
            rdr.parse(src);

            charsets = saxUms.getCharSets();
            combining = saxUms.getCombiningChars();
            boolean changeMade = true;
            while (changeMade) {
                changeMade = false;
                for (Integer mode : combining.keySet()){
                    if (combining.get(mode).size() == 0){
                        combining.remove(mode);
                        changeMade = true;
                        break;
                    }
                }
            }

        } catch (final Exception e) {
            throw new MarcException(e.getMessage(), e);
        }
    }

    /**
     * Creates a CodeTable from the supplied file name.
     *
     * @param filename - the name of a file to read to create the code table
     */
    CodeTable(final String filename) {
        try {
            final SAXParserFactory factory = SAXParserFactory.newInstance();
            factory.setNamespaceAware(true);
            factory.setValidating(false);
            final SAXParser saxParser = factory.newSAXParser();
            final XMLReader rdr = saxParser.getXMLReader();
            final File file = new File(filename);
            final InputSource src = new InputSource(new FileInputStream(file));
            final CodeTableHandler saxUms = new CodeTableHandler();

            rdr.setContentHandler(saxUms);
            rdr.parse(src);

            charsets = saxUms.getCharSets();
            combining = saxUms.getCombiningChars();
        } catch (final Exception e) {
            throw new MarcException(e.getMessage(), e);
        }
    }
}
