package com.turning_leaf_technologies.net;

import org.apache.logging.log4j.Logger;

import javax.net.ssl.HttpsURLConnection;
import java.io.*;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.SocketTimeoutException;
import java.net.URL;
import java.nio.charset.Charset;
import java.nio.charset.StandardCharsets;
import java.util.Base64;
import java.util.HashMap;


public class NetworkUtils {
	public static WebServiceResponse getURL(String url, Logger logger) {
		return NetworkUtils.getURL(url, logger, null);
	}

	public static WebServiceResponse getURL(String url, Logger logger, HashMap<String, String> headers) {
		return NetworkUtils.getURL(url, logger, headers, 300000);
	}
	public static WebServiceResponse getURL(String url, Logger logger, HashMap<String, String> headers, int readTimeout) {
		return NetworkUtils.getURL(url, logger, headers, readTimeout, true);
	}
	public static WebServiceResponse getURL(String url, Logger logger, HashMap<String, String> headers, int readTimeout, boolean logFailures) {
		WebServiceResponse retVal;
		try {
			URL urlToCall = new URL(url);
			HttpURLConnection conn = (HttpURLConnection) urlToCall.openConnection();
			conn.setConnectTimeout(10000);
			conn.setReadTimeout(readTimeout);
			if (headers != null) {
				for (String header : headers.keySet()) {
					conn.setRequestProperty(header, headers.get(header));
				}
			}

			logger.debug("Getting From URL " + url);
			if (conn instanceof HttpsURLConnection) {
				HttpsURLConnection sslConn = (HttpsURLConnection) conn;
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
					response.append(line).append("\r\n");
				}

				rd.close();
				retVal = new WebServiceResponse(true, 200, response.toString());
			} else {
				if (logFailures) {
					logger.error("Received error " + conn.getResponseCode() + " getting " + url);
				}
				// Get any errors
				InputStream errorStream = conn.getErrorStream();
				if (errorStream != null) {
					BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
					String line;
					while ((line = rd.readLine()) != null) {
						response.append(line);
					}

					rd.close();
				}
				retVal = new WebServiceResponse(false, conn.getResponseCode(), response.toString());
			}

		} catch (MalformedURLException e) {
			logger.error("URL to post (" + url + ") is malformed", e);
			retVal = new WebServiceResponse(false, -1, "URL to post (" + url + ") is malformed");
		} catch (SocketTimeoutException toe) {
			retVal = new WebServiceResponse(false, -1, "Call timed out");
			retVal.setCallTimedOut(true);
		} catch (IOException e) {
			logger.error("Error posting to url \r\n" + url, e);
			retVal = new WebServiceResponse(false, -1, "Error posting to url \r\n" + url + "\r\n" + e.toString());
		}
		return retVal;
	}

	public static WebServiceResponse postToURL(String url, String postData, String contentType, String referer, Logger logger) {
		return NetworkUtils.postToURL(url, postData, contentType, referer, logger, null,  10000, 300000, StandardCharsets.UTF_8);
	}

	public static WebServiceResponse postToURL(String url, String postData, String contentType, String referer, Logger logger, String authentication) {
		return NetworkUtils.postToURL(url, postData, contentType, referer, logger, authentication, 10000, 300000, StandardCharsets.UTF_8);
	}

	public static WebServiceResponse postToURL(String url, String postData, String contentType, String referer, Logger logger, String authentication, int connectTimeout, int readTimeout) {
		return NetworkUtils.postToURL(url, postData, contentType, referer, logger, authentication, connectTimeout, readTimeout, StandardCharsets.UTF_8);
	}

	public static WebServiceResponse postToURL(String url, String postData, String contentType, String referer, Logger logger, String authentication, int connectTimeout, int readTimeout, Charset authenticationCharSet) {
		WebServiceResponse retVal;
		HttpURLConnection conn = null;
		try {
			URL emptyIndexURL = new URL(url);
			conn = (HttpURLConnection) emptyIndexURL.openConnection();
			conn.setConnectTimeout(connectTimeout);
			conn.setReadTimeout(readTimeout);
			if (authentication != null) {
				conn.setRequestProperty("Authorization", "Basic " + Base64.getEncoder().encodeToString(authentication.getBytes(authenticationCharSet)));
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
				conn.setRequestProperty("Content-Type", contentType + "; charset=" + authenticationCharSet.toString());
				conn.setRequestProperty("Content-Language", "en-US");
				conn.setRequestProperty("Connection", "keep-alive");

				conn.setDoOutput(true);
				OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream(), StandardCharsets.UTF_8);
				wr.write(postData);
				wr.flush();
				wr.close();
			}else if (postData != null){
				conn.setRequestProperty("Content-Language", "en-US");
				conn.setRequestProperty("Content-Length", "0");
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
				retVal = new WebServiceResponse(true, 200, response.toString());
			} else {
				logger.info("Received error " + conn.getResponseCode() + " posting to " + url + " data " + postData);
				logger.info(postData);
				// Get any errors
				InputStream errorStream = conn.getErrorStream();
				if (errorStream != null) {
					BufferedReader rd = new BufferedReader(new InputStreamReader(errorStream));
					String line;
					while ((line = rd.readLine()) != null) {
						response.append(line);
					}

					rd.close();
				}

				if (response.length() == 0) {
					//Try to load the regular body as well
					// Get the response
					InputStream inputStream = conn.getInputStream();
					if (inputStream != null) {
						String line;
						BufferedReader rd2 = new BufferedReader(new InputStreamReader(inputStream));
						while ((line = rd2.readLine()) != null) {
							response.append(line);
						}
						rd2.close();
					}
				}
				retVal = new WebServiceResponse(false, conn.getResponseCode(), response.toString());
			}

		} catch (SocketTimeoutException e) {
			logger.error("Timeout connecting to URL (" + url + ") data " + postData, e);
			retVal = new WebServiceResponse(false, -1, "Timeout connecting to URL (" + url + ")");
		} catch (MalformedURLException e) {
			logger.error("URL to post (" + url + ") is malformed", e);
			retVal = new WebServiceResponse(false, -1, "URL to post (" + url + ") is malformed");
		} catch (IOException e) {
			logger.error("Error posting to url \r\n" + url, e);
			retVal = new WebServiceResponse(false, -1, "Error posting to url \r\n" + url + "\r\n" + e.toString());
		} finally {
			if (conn != null) conn.disconnect();
		}
		return retVal;
	}
}
