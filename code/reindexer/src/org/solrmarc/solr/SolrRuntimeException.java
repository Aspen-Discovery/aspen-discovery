package org.solrmarc.solr;

public class SolrRuntimeException extends RuntimeException
{
     private static final long serialVersionUID = 4089699202677606384L;
     public SolrRuntimeException(String message, Exception containedException)
     {
         super(message, containedException);
     }
     public SolrRuntimeException(String message)
     {
         super(message);
     }
}
