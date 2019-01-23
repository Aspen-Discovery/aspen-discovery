package org.solrmarc.tools;

import java.io.PrintStream;
import java.io.PrintWriter;

/**
 * Exception handler for Solrmarc
 * @author Wayne Graham
 * @version $Id: SolrMarcException.java 77 2008-07-18 15:55:47Z wayne.graham $
 *
 */
public class SolrMarcException extends Exception {
	
	private static final long serialVersionUID = 1L;
	private transient Throwable cause;

	/**
	 * Default constructor
	 */
	public SolrMarcException() {
		super();
	}

	/**
	 * Constructs with message.
	 * @param message Message to pass
	 */
	public SolrMarcException(final String message) {
		super(message);
	}

	/**
	 * Constructs with chained exception
	 * @param cause Chained exception
	 */
	public SolrMarcException(final Throwable cause) {
		super(cause.toString());
		this.cause = cause;
	}

	/**
	 * Constructs with message and exception
	 * @param message Message
	 * @param cause Exception
	 */
	public SolrMarcException(final String message, final Throwable cause) {
		super(message, cause);
		this.cause = cause;
	}
	
	/**
	 * Get the current exception
	 * @return Throwable cause of the exception
	 */
	public Throwable getException(){
		return cause;
	}
	
	/**
	 * Print a message 
	 * @param message Message to print
	 */
	public void printMessage(final String message){
		System.err.println(message);
	}
	
	/**
	 * Print stack trace for the current exception
	 */
	public void printStackTrace(){
		printStackTrace(System.err);
	}
	
	/**
	 * Print the stack trace for a nested exception
	 * @param printStream PrintStream to print stack trace for
	 */
	public void printStackTrace(final PrintStream printStream){
		synchronized(printStream){
			super.printStackTrace();
			if(cause != null){
				printStream.println("--- Nested Exception ---");
				cause.printStackTrace(printStream);
			}
		}
	}
	
	/**
	 * Print the nested stack trace for nested PrintWriter exceptions
	 * @param printWriter PrintWriter to print stack trace for
	 */
	public void printStrackTrace(final PrintWriter printWriter){
		synchronized(printWriter){
			super.printStackTrace(printWriter);
			if(cause != null){
				printWriter.println("--- Nested Exception ---");
				cause.printStackTrace(printWriter);
			}
		}
	}

}
