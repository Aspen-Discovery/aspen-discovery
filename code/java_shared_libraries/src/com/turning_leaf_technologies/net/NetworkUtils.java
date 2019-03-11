package com.turning_leaf_technologies.net;

import org.apache.commons.codec.binary.Base64;
import org.apache.logging.log4j.Logger;


import javax.net.ssl.HttpsURLConnection;
import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.SocketTimeoutException;
import java.net.URL;
import java.nio.charset.StandardCharsets;


public class NetworkUtils {
    public static URLPostResponse getURL(String url, Logger logger) {
        return NetworkUtils.getURL(url, logger, null);
    }
    public static URLPostResponse getURL(String url, Logger logger, String authentication) {
        URLPostResponse retVal;
        try {
            URL emptyIndexURL = new URL(url);
            HttpURLConnection conn = (HttpURLConnection) emptyIndexURL.openConnection();
            conn.setConnectTimeout(10000);
            conn.setReadTimeout(300000);
            if (authentication != null){
                conn.setRequestProperty("Authorization", "Basic " + Base64.encodeBase64String(authentication.getBytes()));
            }
            logger.debug("Getting From URL " + url);
            if (conn instanceof HttpsURLConnection){
                HttpsURLConnection sslConn = (HttpsURLConnection)conn;
                sslConn.setHostnameVerifier((hostname, session) -> {
                    //Do not verify host names
                    return true;
                });
            }

            StringBuilder response = new StringBuilder();
            if (conn.getResponseCode() == 200) {
                // Get the response
                BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                String line;
                while ((line = rd.readLine()) != null) {
                    response.append(line + "\r\n");
                }

                rd.close();
                retVal = new URLPostResponse(true, 200, response.toString());
            } else {
                logger.error("Received error " + conn.getResponseCode() + " getting " + url);
                // Get any errors
                BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
                String line;
                while ((line = rd.readLine()) != null) {
                    response.append(line);
                }

                rd.close();
                retVal = new URLPostResponse(false, conn.getResponseCode(), response.toString());
            }

        } catch (MalformedURLException e) {
            logger.error("URL to post (" + url + ") is malformed", e);
            retVal = new URLPostResponse(false, -1, "URL to post (" + url + ") is malformed");
        } catch (IOException e) {
            logger.error("Error posting to url \r\n" + url, e);
            retVal = new URLPostResponse(false, -1, "Error posting to url \r\n" + url + "\r\n" + e.toString());
        }
        return retVal;
    }

    public static URLPostResponse postToURL(String url, String postData, String contentType, String referer, Logger logger) {
        return NetworkUtils.postToURL(url, postData, contentType, referer, logger, null);
    }

    public static URLPostResponse postToURL(String url, String postData, String contentType, String referer, Logger logger, String authentication) {
        URLPostResponse retVal;
        HttpURLConnection conn = null;
        try {
            URL emptyIndexURL = new URL(url);
            conn = (HttpURLConnection) emptyIndexURL.openConnection();
            conn.setConnectTimeout(10000);
            conn.setReadTimeout(300000);
            if (authentication != null){
                conn.setRequestProperty("Authorization", "Basic " + Base64.encodeBase64String(authentication.getBytes()));
            }
            //logger.debug("Posting To URL " + url + (postData != null && postData.length() > 0 ? "?" + postData : ""));

            if (conn instanceof HttpsURLConnection) {
                HttpsURLConnection sslConn = (HttpsURLConnection) conn;
                sslConn.setHostnameVerifier((hostname, session) -> {
                    //Do not verify host names
                    return true;
                });
            }
            conn.setDoInput(true);
            if (referer != null) {
                conn.setRequestProperty("Referer", referer);
            }
            conn.setRequestMethod("POST");
            if (postData != null && postData.length() > 0) {
                conn.setRequestProperty("Content-Type", contentType + "; charset=utf-8");
                conn.setRequestProperty("Content-Language", "en-US");
                conn.setRequestProperty("Connection", "keep-alive");

                conn.setDoOutput(true);
                OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream(), StandardCharsets.UTF_8);
                wr.write(postData);
                wr.flush();
                wr.close();
            }

            StringBuilder response = new StringBuilder();
            if (conn.getResponseCode() == 200) {
                // Get the response
                BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                String line;
                while ((line = rd.readLine()) != null) {
                    response.append(line);
                }

                rd.close();
                retVal = new URLPostResponse(true, 200, response.toString());
            } else {
                logger.info("Received error " + conn.getResponseCode() + " posting to " + url + " data " + postData);
                logger.info(postData);
                // Get any errors
                BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
                String line;
                while ((line = rd.readLine()) != null) {
                    response.append(line);
                }

                rd.close();

                if (response.length() == 0) {
                    //Try to load the regular body as well
                    // Get the response
                    BufferedReader rd2 = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                    while ((line = rd2.readLine()) != null) {
                        response.append(line);
                    }

                    rd.close();
                }
                retVal = new URLPostResponse(false, conn.getResponseCode(), response.toString());
            }

        } catch (SocketTimeoutException e){
            logger.error("Timeout connecting to URL (" + url + ") data " + postData, e);
            retVal = new URLPostResponse(false, -1, "Timeout connecting to URL (" + url + ")");
        } catch (MalformedURLException e) {
            logger.error("URL to post (" + url + ") is malformed", e);
            retVal = new URLPostResponse(false, -1, "URL to post (" + url + ") is malformed");
        } catch (IOException e) {
            logger.error("Error posting to url \r\n" + url, e);
            retVal = new URLPostResponse(false, -1, "Error posting to url \r\n" + url + "\r\n" + e.toString());
        }finally{
            if (conn != null) conn.disconnect();
        }
        return retVal;
    }
}
