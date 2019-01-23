package org.solrmarc.solr;

import java.io.IOException;
import java.lang.reflect.InvocationTargetException;
import java.lang.reflect.Method;
import java.util.Collection;
import java.util.Iterator;
import java.util.Map;


public class SolrRemoteProxy implements SolrProxy
{
    Object server = null;
    Object solrInputDoc = null;
    Class<?> solrServerExceptionClass = null;
    
    public SolrRemoteProxy(String remoteURL)
    {
        initializeSolrInputDoc();
        String URL = remoteURL.replaceAll("[/\\\\]update$", "");
        initializeSolrServer(URL);
    }
    
    /**
     * initialize SolrServer object 
     */
    private void initializeSolrServer(String remoteURL)
    {
        try
        {
            if (server == null)
            {
                Class<?> solrServerClass = Class.forName("org.apache.solr.client.solrj.impl.CommonsHttpSolrServer");
                server = solrServerClass.getConstructor(String.class).newInstance(remoteURL);
            }
        }
        catch (Exception e)
        {
            String errmsg = "Error: Problem creating SolrInputDocument in SolrRemoteProxy";
            System.err.println(errmsg); 
            throw new SolrRuntimeException(errmsg, e);
        }
    }
   
    /**
     * initialize SolrInputDoc object if it doesn't yet exist
     */
    private void initializeSolrInputDoc()
    {
        try
        {
            if (solrInputDoc == null)
            {
                Class<?> solrInputDocClass = Class.forName("org.apache.solr.common.SolrInputDocument");
                solrInputDoc = solrInputDocClass.getConstructor().newInstance();
            }
        }
        catch (Exception e)
        {
            String errmsg = "Error: Problem creating SolrInputDocument in SolrRemoteProxy";
            System.err.println(errmsg); 
            throw new SolrRuntimeException(errmsg, e);
        }
    }
    
    /**
     * invoke a method with no arguments on the documentBuilder object
     * @param methodName
     */
    private Object invokeSolrInputDocMethodNoArgs(String methodName) 
    {
        Object result;
        try 
        { 
            result = solrInputDoc.getClass().getMethod(methodName).invoke(solrInputDoc);
        }
        catch (Exception e)
        {
            String errmsg = "Error: Problem invoking " + methodName + " in SolrCoreProxy";
            System.err.println(errmsg);               
            throw new SolrRuntimeException(errmsg, e);
        }
        return result;
    }

    /**
     * add a field to the Document being created with documentBuilder
     * @param fieldName the name of the field to add
     * @param fieldVal the value of the field to add, as a String
     */
    private void invokeSolrInputDocAddField(String fieldName, Object fieldVal) 
    {
        try
        {
            solrInputDoc.getClass().getMethod("addField", String.class, Object.class).invoke(solrInputDoc, fieldName, fieldVal);
        }
        catch (Exception e)
        {
            String errmsg = "Error: Problem invoking addField in SolrCoreProxy";
            System.err.println(errmsg);  
//            e.printStackTrace();
            throw new SolrRuntimeException(errmsg, e);
        }
    }
    /**
     * invoke a method with One argument on the SolrServer object
     */
    private void invokeSolrServerMethodWithArg(String methodName, Object commandObject) throws IOException
    {
        String errmsg = "Error: Problem invoking " + methodName + " on updateHandler via SolrCoreProxy";
        
        try
        {
            Method method = server.getClass().getMethod(methodName, commandObject.getClass());
            method.invoke(server, commandObject);
        }
        catch (InvocationTargetException e)
        {
            Throwable cause = e.getCause();
            if (cause instanceof IOException) 
                throw (IOException) cause;
            System.err.println(errmsg);               
            throw new SolrRuntimeException(errmsg, e);
        }
        catch (Exception e)
        {
            System.err.println(errmsg);               
            throw new SolrRuntimeException(errmsg, e);
        } 
    }
    /**
     * invoke a method with no argument on the SolrServer object
     */
    private void invokeSolrServerMethodNoArg(String methodName) throws IOException
    {
        String errmsg = "Error: Problem invoking " + methodName + " on updateHandler via SolrCoreProxy";
        
        try
        {
            Method method = server.getClass().getMethod(methodName);
            method.invoke(server);
        }
        catch (InvocationTargetException e)
        {
            Throwable cause = e.getCause();
            if (cause instanceof IOException) 
                throw (IOException) cause;
            System.err.println(errmsg);               
            throw new SolrRuntimeException(errmsg, e);
        }
        catch (Exception e)
        {
            System.err.println(errmsg);               
            throw new SolrRuntimeException(errmsg, e);
        } 
    }
    
    public String addDoc(Map<String, Object> fieldsMap, boolean verbose, boolean addDocToIndex) throws IOException
    {
            
        invokeSolrInputDocMethodNoArgs("clear");

        Iterator<String> keys = fieldsMap.keySet().iterator();
        while (keys.hasNext())
        {
            String fldName = keys.next();
            Object fldValObject = fieldsMap.get(fldName);
            
            // add field to doc, whether it be a single value or a Collection
            if (fldValObject instanceof String)
            {
                invokeSolrInputDocAddField(fldName, (String) fldValObject);
            }
            else if (fldValObject instanceof Collection)
            {
                Iterator<?> valIter = ((Collection) fldValObject).iterator();
                while (valIter.hasNext())
                {
                    Object nextItem = valIter.next();
                    if (nextItem != null)
                    {
                        invokeSolrInputDocAddField(fldName, nextItem.toString());
                    }
                }
            }
        }
        
        if (addDocToIndex)
        {
            invokeSolrServerMethodWithArg("add", solrInputDoc);
        }

        if (verbose || !addDocToIndex)
            return solrInputDoc.toString().replaceAll("> ", "> \n");
        else
            return(null);
    }

    public void close()
    {
        // do nothing, be happy
    }

    public void commit(boolean optimize) throws IOException
    {
        if (optimize)
        {
            invokeSolrServerMethodNoArg("optimize");
        }
        else
        {
            invokeSolrServerMethodNoArg("commit");
        }
    }

    public void delete(String id, boolean fromCommitted, boolean fromPending) throws IOException
    {
        invokeSolrServerMethodWithArg("deleteById", id);
    }

    public void deleteAllDocs() throws IOException
    {
        invokeSolrServerMethodWithArg("deleteByQuery", "*:*");
    }

    public boolean isSolrException(Exception e)
    {
        if (solrServerExceptionClass == null)
        {
            try
            {
                solrServerExceptionClass = Class.forName("org.apache.solr.client.solrj.SolrServerException");
            }
            catch (ClassNotFoundException e1)
            {
                e1.printStackTrace();
            }
        }
        return ( (solrServerExceptionClass != null) ? solrServerExceptionClass.isInstance(e): false);
    }

}
