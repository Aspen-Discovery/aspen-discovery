import { API_KEY_1, API_KEY_2, API_KEY_3, API_KEY_4, API_KEY_5 } from '@env';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Device from 'expo-device';
import * as SecureStore from 'expo-secure-store';
import _ from 'lodash';
import React, { useEffect } from 'react';
import base64 from 'react-native-base64';

import { GLOBALS } from './globals';

// polyfill for base64 (required for authentication)
if (!global.btoa) {
     global.btoa = base64.encode;
}
if (!global.atob) {
     global.atob = base64.decode;
}

/**
 * Create authentication token to validate the API request to Aspen
 **/
export function createAuthTokens() {
     const tokens = {};
     tokens['username'] = makeNewSecret();
     tokens['password'] = makeNewSecret();
     return tokens;
}

/**
 * Create secure data body to send the patron login information to Aspen via POST
 **/
export async function postData() {
     const content = new FormData();
     try {
          const secretKey = await SecureStore.getItemAsync('secretKey');
          const userKey = await SecureStore.getItemAsync('userKey');
          content.append('username', userKey);
          content.append('password', secretKey);
     } catch (e) {
          console.log('Unable to fetch user keys to make POST request.');
          console.log(e);
          await AsyncStorage.removeItem('@userToken');
     }

     return content;
}

export const UsePostData = () => {
     const content = new FormData();
     let secretKey = null;
     let userKey = null;
     useEffect(() => {
          async function GetPostData() {
               secretKey = await SecureStore.getItemAsync('secretKey');
               userKey = await SecureStore.getItemAsync('userKey');
          }

          GetPostData();
     }, []);

     content.append('username', userKey);
     content.append('password', secretKey);

     return content;
};

/**
 * Collect header information to send to Aspen
 *
 * Parameters:
 * <ul>
 *     <li>isPost - if request is POST type, set to true. Required for Aspen to see POST parameters.</li>
 * </ul>
 **/
export function getHeaders(isPost = false) {
     const headers = {};

     headers['User-Agent'] = 'Aspen LiDA ' + Device.modelName + ' ' + Device.osName + '/' + Device.osVersion;
     headers['Version'] = 'v' + GLOBALS.appVersion + ' [b' + GLOBALS.appBuild + '] p' + GLOBALS.appPatch;
     headers['LiDA-SessionID'] = GLOBALS.appSessionId;
     headers['Cache-Control'] = 'no-cache';

     if (isPost) {
          headers['Content-Type'] = 'application/x-www-form-urlencoded';
     }

     return headers;
}

/**
 * Generate a random pairing of current keys
 **/
function makeNewSecret() {
     const tokens = [API_KEY_1, API_KEY_2, API_KEY_3, API_KEY_4, API_KEY_5];
     const thisKey = _.sample(_.shuffle(tokens));
     return base64.encode(thisKey);
}

/**
 * Check the problem code sent to display appropriate error message
 **/
export function problemCodeMap(code) {
     switch (code) {
          case 'CLIENT_ERROR':
               return {
                    title: "There's been a glitch",
                    message: "We're not quite sure what went wrong. Try reloading the page or come back later.",
               };
          case 'SERVER_ERROR':
               return {
                    title: 'Something went wrong',
                    message: 'Looks like our server encountered an internal error or misconfiguration and was unable to complete your request. Please try again in a while.',
               };
          case 'TIMEOUT_ERROR':
               return {
                    title: 'Connection timed out',
                    message: 'Looks like the server is taking to long to respond, this can be caused by either poor connectivity or an error with our servers. Please try again in a while.',
               };
          case 'CONNECTION_ERROR':
               return {
                    title: 'Problem connecting',
                    message: 'Check your internet connection and try again.',
               };
          case 'NETWORK_ERROR':
               return {
                    title: 'Problem connecting',
                    message: 'Looks like our servers are currently unavailable. Please try again in a while.',
               };
          case 'CANCEL_ERROR':
               return {
                    title: 'Something went wrong',
                    message: "We're not quite sure what went wrong so the request to our server was cancelled. Please try again in awhile.",
               };
          default:
               return null;
     }
}

/**
 * Check Aspen Discovery response for valid data
 * <ul>
 *     <li>payload - The object returned from api instance</li>
 * </ul>
 * @param {object} payload
 **/
export function getResponseCode(payload) {
     if (payload.ok) {
          return {
               success: true,
               config: payload.config,
               data: payload.data,
          };
     } else {
          //console.log(payload);
          const problem = problemCodeMap(payload.problem);
          //popToast(problem.title, problem.message, "warning");
          return {
               success: false,
               config: payload.config,
               error: {
                    title: problem.title,
                    code: payload.problem,
                    message: problem.message + ' (' + payload.problem + ')',
               },
          };
     }
}

/**
 * Remove HTML from a string
 **/
export function stripHTML(string) {
     return string.replace(/(<([^>]+)>)/gi, '');
}

/**
 * Array of available endpoints into Aspen Discovery
 *
 **/

export const ENDPOINT = {
     user: {
          url: '/API/UserAPI?method=',
          isPost: true,
     },
     search: {
          url: '/API/SearchAPI?method=',
          isPost: false,
     },
     list: {
          url: '/API/ListAPI?method=',
          isPost: true,
     },
     work: {
          url: '/API/WorkAPI?method=',
          isPost: false,
     },
     item: {
          url: '/API/ItemAPI?method=',
          isPost: false,
     },
     fine: {
          url: '/API/FineAPI?method=',
          isPost: true,
     },
     system: {
          url: '/API/SystemAPI?method=',
          isPost: false,
     },
     translation: {
          url: '/API/SystemAPI?method=',
          isPost: false,
     },
     greenhouse: {
          url: '/API/GreenhouseAPI?method=',
          isPost: false,
     },
};