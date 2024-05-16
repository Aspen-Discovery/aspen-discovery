import { API_KEY_1, API_KEY_2, API_KEY_3, API_KEY_4, API_KEY_5 } from '@env';
import { create } from 'apisauce';
import * as Device from 'expo-device';
import * as SecureStore from 'expo-secure-store';
import * as WebBrowser from 'expo-web-browser';
import { decode } from 'html-entities';
import _ from 'lodash';
import { useEffect } from 'react';
import base64 from 'react-native-base64';
import { popToast } from '../components/loadError';
import { getTermFromDictionary } from '../translations/TranslationService';

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
export function getHeaders(isPost = false, language = 'en') {
     const headers = {};

     headers['User-Agent'] = 'Aspen LiDA ' + Device.modelName + ' ' + Device.osName + '/' + Device.osVersion;
     headers['Version'] = 'v' + GLOBALS.appVersion + ' [b' + GLOBALS.appBuild + '] p' + GLOBALS.appPatch;
     headers['LiDA-SessionID'] = GLOBALS.appSessionId;
     headers['Cache-Control'] = 'no-cache';
     headers['Preferred-Language'] = language;

     if (isPost) {
          headers['Content-Type'] = 'application/x-www-form-urlencoded';
     }

     return headers;
}

/**
 * Passes the logged-in user to a Discovery page
 * @param {string} url
 * @param {string} redirectTo
 * @param {string} userId
 * @param {string} backgroundColor
 * @param {string} textColor
 **/
export async function passUserToDiscovery(url, redirectTo, userId, backgroundColor, textColor) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await discovery.post('/UserAPI?method=prepareSharedSession', postBody);
     if (response.ok) {
          const sessionId = response?.data?.result?.session ?? null;

          const browserParams = {
               enableDefaultShareMenuItem: false,
               presentationStyle: 'automatic',
               showTitle: false,
               toolbarColor: backgroundColor,
               controlsColor: textColor,
               secondaryToolbarColor: backgroundColor,
          };

          if (sessionId && userId) {
               const accessUrl = url + '/Authentication/LiDA?init&session=' + sessionId + '&user=' + userId + '&goTo=' + redirectTo + '&minimalInterface=true';
               await WebBrowser.openBrowserAsync(accessUrl, browserParams)
                    .then((res) => {
                         console.log(res);
                         if (res.type === 'cancel' || res.type === 'dismiss') {
                              console.log('User closed or dismissed window.');
                              WebBrowser.dismissBrowser();
                              WebBrowser.coolDownAsync();
                         }
                    })
                    .catch(async (err) => {
                         if (err.message === 'Another WebBrowser is already being presented.') {
                              try {
                                   WebBrowser.dismissBrowser();
                                   WebBrowser.coolDownAsync();
                                   await WebBrowser.openBrowserAsync(accessUrl, browserParams)
                                        .then((response) => {
                                             console.log(response);
                                             if (response.type === 'cancel') {
                                                  console.log('User closed window.');
                                             }
                                        })
                                        .catch(async (error) => {
                                             console.log('Unable to close previous browser session.');
                                        });
                              } catch (error) {
                                   console.log('Really borked.');
                              }
                         } else {
                              popToast(getTermFromDictionary('en', 'error_no_open_resource'), getTermFromDictionary('en', 'error_device_block_browser'), 'error');
                              console.log(err);
                         }
                    });
          } else {
               // unable to validate the user
               popToast(getTermFromDictionary('en', 'error_no_open_resource'), getTermFromDictionary('en', 'error_device_block_browser'), 'error');
               console.log('unable to validate user');
          }
     } else {
          popToast(getTermFromDictionary('en', 'error_no_server_connection'), getTermFromDictionary('en', 'error_no_library_connection'), 'error');
          console.log(response);
     }
}

/**
 * Generate a random pairing of current keys
 **/
function makeNewSecret() {
     let tokens = [API_KEY_1, API_KEY_2, API_KEY_3, API_KEY_4, API_KEY_5];
     if (!__DEV__) {
          tokens = [process.env.API_KEY_1, process.env.API_KEY_2, process.env.API_KEY_3, process.env.API_KEY_4, process.env.API_KEY_5];
     }
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
 * Decode HTML entities in a string
 **/
export function decodeHTML(string) {
     return decode(string);
}

export function urldecode(str) {
     return decodeURIComponent(str.replace(/\+/g, ' '));
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

export const delay = (ms) => new Promise((res) => setTimeout(res, ms));