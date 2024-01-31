/**
 * Copyright (C) 2004 Bas Peters
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

package org.marc4j;

import java.io.BufferedInputStream;
import java.io.ByteArrayInputStream;
import java.io.DataInputStream;
import java.io.EOFException;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.UnsupportedEncodingException;
import java.nio.charset.StandardCharsets;
import java.util.Arrays;
import java.util.HashMap;

import org.marc4j.converter.CharConverter;
import org.marc4j.converter.impl.AnselToUnicode;
import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Leader;
import org.marc4j.marc.MarcFactory;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.impl.Verifier;

/**
 * An iterator over a collection of MARC records in ISO 2709 format.
 * <p>
 * Example usage:
 * 
 * <pre>
 * InputStream input = new FileInputStream(&quot;file.mrc&quot;);
 * MarcReader reader = new MarcStreamReader(input);
 * while (reader.hasNext()) {
 *     Record record = reader.next();
 *     // Process record
 * }
 * </pre>
 * 
 * <p>
 * Check the {@link org.marc4j.marc}&nbsp;package for examples about the use of
 * the {@link Record}&nbsp;object model.
 * </p>
 * 
 * <p>
 * When no encoding is given as an constructor argument the parser tries to
 * resolve the encoding by looking at the character coding scheme (leader
 * position 9) in MARC21 records. For UNIMARC records this position is not
 * defined.
 * </p>
 * 
 * @author Bas Peters
 * 
 */
public class MarcStreamReader implements MarcReader {

    private final DataInputStream input;

	private final MarcFactory factory;

    private String encoding = "ISO8859_1";

    private boolean override = false;

    private CharConverter converterAnsel = null;

    /**
     * Constructs an instance with the specified input stream.
     *
     * @param input - the InputStream to read the record from
     */
    public MarcStreamReader(final InputStream input) {
        this(input, null);
    }

    /**
     * Constructs an instance with the specified input stream.
     *
     * @param input - the InputStream to read the record from
     * @param encoding - the expected encoding of the supplied byte stream
     */
    public MarcStreamReader(final InputStream input, final String encoding) {
        this.input = new DataInputStream(input.markSupported() ? input : new BufferedInputStream(input));
        factory = MarcFactory.newInstance();
        if (encoding != null) {
            this.encoding = encoding;
            override = true;
        }
    }

    /**
     * Returns true if the iteration has more records, false otherwise.
     */
    @Override
    public boolean hasNext() {
        try {
            input.mark(10);
            if (input.read() == -1) {
                return false;
            }
            input.reset();
        } catch (final IOException e) {
            throw new MarcException(e.getMessage(), e);
        }
        return true;
    }

    /**
     * Returns the next record in the iteration.
     *
     * @return Record - the record object
     */
    @Override
    public Record next() {
	    Record record = factory.newRecord();

        try {

            final byte[] byteArray = new byte[24];
            input.readFully(byteArray);

            final int recordLength = parseRecordLength(byteArray);
            final byte[] recordBuf = new byte[recordLength - 24];
            input.readFully(recordBuf);
            parseRecord(record, byteArray, recordBuf, recordLength);
            return record;
        } catch (final EOFException e) {
            throw new MarcException("Premature end of file encountered", e);
        } catch (final IOException e) {
            throw new MarcException("an error occurred reading input", e);
        }
    }

    private void parseRecord(final Record record, final byte[] aByteArray, final byte[] recordBuf,
            final int recordLength) {
        final Leader ldr;

        byte[] byteArray = aByteArray;

        ldr = factory.newLeader();
        ldr.setRecordLength(recordLength);
        int directoryLength;

        try {
            parseLeader(ldr, byteArray);
            directoryLength = ldr.getBaseAddressOfData() - (24 + 1);
        } catch (final IOException | MarcException e) {
            throw new MarcException("error parsing leader with data: " + new String(byteArray), e);
        }

	    // if MARC 21 then check encoding
        switch (ldr.getCharCodingScheme()) {
            case ' ':
                if (!override) {
                    encoding = "ISO-8859-1";
                }
                break;
            case 'a':
                if (!override) {
                    encoding = "UTF8";
                }
        }

        record.setLeader(ldr);

        if (directoryLength % 12 != 0) {
            throw new MarcException("invalid directory");
        }

        final DataInputStream inputRecord = new DataInputStream(new ByteArrayInputStream(recordBuf));
        final int size = directoryLength / 12;

        final String[] tags = new String[size];
        final int[] lengths = new int[size];
        final int[] starts = new int[size];
        final HashMap<Integer, Integer> unsortedStartIndex = new HashMap<>();

        final byte[] tag = new byte[3];
        final byte[] length = new byte[4];
        final byte[] start = new byte[5];

        String tmp;

        try {
            for (int i = 0; i < size; i++) {
                inputRecord.readFully(tag);
                tmp = new String(tag);
                tags[i] = tmp;

                inputRecord.readFully(length);
                tmp = new String(length);
                lengths[i] = Integer.parseInt(tmp);

                inputRecord.readFully(start);

                tmp = new String(start);
                starts[i] = Integer.parseInt(tmp);
                unsortedStartIndex.put(starts[i], i);
            }

            // Sort starting character positions
            Arrays.sort(starts);

            if (inputRecord.read() != Constants.FT) {
                throw new MarcException("expected field terminator at end of directory");
            }

            int i;
            for (int s = 0; s < size; s++) {
                i = unsortedStartIndex.get(starts[s]);

                getFieldLength(inputRecord);

                if (Verifier.isControlField(tags[i])) {
                    byteArray = new byte[lengths[i] - 1];
                    inputRecord.readFully(byteArray);

                    if (inputRecord.read() != Constants.FT) {
                        throw new MarcException("expected field terminator at end of field");
                    }

                    final ControlField field = factory.newControlField();
                    field.setTag(tags[i]);
                    field.setData(getDataAsString(byteArray));
                    record.addVariableField(field);
                } else {
                    byteArray = new byte[lengths[i]];
                    inputRecord.readFully(byteArray);

                    try {
                        record.addVariableField(parseDataField(tags[i], byteArray));
                    } catch (final IOException e) {
                        throw new MarcException("error parsing data field for tag: " + tags[i] + " with data: " +
                                new String(byteArray), e);
                    }
                }
            }

            if (inputRecord.read() != Constants.RT) {
                throw new MarcException("expected record terminator");
            }
        } catch (final IOException e) {
            throw new MarcException("an error occurred reading input", e);
        }
    }

    private DataField parseDataField(final String tag, final byte[] field) throws IOException {
        final ByteArrayInputStream byteInputStream = new ByteArrayInputStream(field);
        final char ind1 = (char) byteInputStream.read();
        final char ind2 = (char) byteInputStream.read();

        final DataField dataField = factory.newDataField();
        dataField.setTag(tag);
        dataField.setIndicator1(ind1);
        dataField.setIndicator2(ind2);

        int code;
        int size;
        int readByte;
        byte[] data;
        Subfield subfield;
        while (true) {
            readByte = byteInputStream.read();
            if (readByte < 0) {
                break;
            }
            switch (readByte) {
                case Constants.US:
                    code = byteInputStream.read();
                    if (code < 0) {
                        throw new IOException("unexpected end of data field");
                    }
                    if (code == Constants.FT) {
                        break;
                    }
                    size = getSubfieldLength(byteInputStream);
                    data = new byte[size];
                    //noinspection ResultOfMethodCallIgnored
                    byteInputStream.read(data);
                    subfield = factory.newSubfield();
                    subfield.setCode((char) code);
                    subfield.setData(getDataAsString(data));
                    dataField.addSubfield(subfield);
                    break;
                case Constants.FT:
                    break;
            }
        }
        return dataField;
    }

    @SuppressWarnings("UnusedReturnValue")
    private int getFieldLength(final DataInputStream byteInputStream) throws IOException {
        byteInputStream.mark(9999);
        int bytesRead = 0;
        while (true) {
            switch (byteInputStream.read()) {
                case Constants.FT:
                    byteInputStream.reset();
                    return bytesRead;
                case -1:
                    byteInputStream.reset();
                    throw new IOException("Field not terminated");
                case Constants.US:
                default:
                    bytesRead++;
            }
        }
    }

    private int getSubfieldLength(final ByteArrayInputStream byteInputStream) throws IOException {
        byteInputStream.mark(9999);
        int bytesRead = 0;
        while (true) {
            switch (byteInputStream.read()) {
                case Constants.US:
                case Constants.FT:
                    byteInputStream.reset();
                    return bytesRead;
                case -1:
                    byteInputStream.reset();
                    throw new IOException("subfield not terminated");
                default:
                    bytesRead++;
            }
        }
    }

    private int parseRecordLength(final byte[] leaderData) throws IOException {
        InputStreamReader isr = new InputStreamReader(new ByteArrayInputStream(leaderData), StandardCharsets.ISO_8859_1);
        int length;
        char[] tmp = new char[5];
        //noinspection ResultOfMethodCallIgnored
        isr.read(tmp);
        try {
            length = Integer.parseInt(new String(tmp));
        } catch (final NumberFormatException e) {
            throw new MarcException("unable to parse record length", e);
        }
        isr.close();
        return length;
    }

    private void parseLeader(final Leader ldr, final byte[] leaderData) throws IOException {
        InputStreamReader isr = new InputStreamReader(new ByteArrayInputStream(leaderData), StandardCharsets.ISO_8859_1);
        char[] tmp = new char[5];
        //noinspection ResultOfMethodCallIgnored
        isr.read(tmp);
        // Skip over bytes for record length, If we get here, its already been
        // computed.
        ldr.setRecordStatus((char) isr.read());
        ldr.setTypeOfRecord((char) isr.read());
        tmp = new char[2];
        //noinspection ResultOfMethodCallIgnored
        isr.read(tmp);
        ldr.setImplDefined1(tmp);
        ldr.setCharCodingScheme((char) isr.read());
        final char indicatorCount = (char) isr.read();
        final char subfieldCodeLength = (char) isr.read();
        char[] baseAddress = new char[5];
        //noinspection ResultOfMethodCallIgnored
        isr.read(baseAddress);
        tmp = new char[3];
        //noinspection ResultOfMethodCallIgnored
        isr.read(tmp);
        ldr.setImplDefined2(tmp);
        tmp = new char[4];
        //noinspection ResultOfMethodCallIgnored
        isr.read(tmp);
        ldr.setEntryMap(tmp);
        isr.close();
        try {
            ldr.setIndicatorCount(Integer.parseInt(String.valueOf(indicatorCount)));
        } catch (final NumberFormatException e) {
            throw new MarcException("unable to parse indicator count", e);
        }
        try {
            ldr.setSubfieldCodeLength(Integer.parseInt(String.valueOf(subfieldCodeLength)));
        } catch (final NumberFormatException e) {
            throw new MarcException("unable to parse subfield code length", e);
        }
        try {
            ldr.setBaseAddressOfData(Integer.parseInt(new String(baseAddress)));
        } catch (final NumberFormatException e) {
            throw new MarcException("unable to parse base address of data", e);
        }
	    isr.close();
    }

    private String getDataAsString(final byte[] bytes) {
        String dataElement = null;
        if (encoding.equals("UTF-8") || encoding.equals("UTF8")) {
	        dataElement = new String(bytes, StandardCharsets.UTF_8);
        } else if (encoding.equals("MARC-8") || encoding.equals("MARC8")) {
            if (converterAnsel == null) {
                converterAnsel = new AnselToUnicode();
            }
            dataElement = converterAnsel.convert(bytes);
        } else if (encoding.equals("ISO-8859-1") || encoding.equals("ISO8859_1") || encoding.equals("ISO_8859_1")) {
	        dataElement = new String(bytes, StandardCharsets.ISO_8859_1);
        } else if (override) {
            try {
                dataElement = new String(bytes, encoding);
            } catch (final UnsupportedEncodingException e) {
                throw new MarcException("unsupported encoding", e);
            }
        }
        return dataElement;
    }

}
