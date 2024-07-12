import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'apisauce';
import _ from 'lodash';
import { createAuthTokens, getHeaders, postData, stripHTML } from '../apiAuth';
import { GLOBALS } from '../globals';
import { LIBRARY } from '../loadLibrary';

/**
 * Fetch library login labels
 **/
export async function getLibraryLoginLabels(id, url) {
     let usernameLabel = 'Your Name';
     let passwordLabel = 'Library Card Number';

     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
     });
     const response = await api.get('/SystemAPI?method=getLibraryInfo', {
          id,
     });
     if (response.ok) {
          if (response.data.result.success) {
               if (typeof response.data.result.library !== 'undefined') {
                    const profile = response.data.result.library;
                    usernameLabel = profile.usernameLabel;
                    passwordLabel = profile.passwordLabel;
               }
          }
     }

     return {
          username: usernameLabel,
          password: passwordLabel,
     };
}

export async function getLibraryInfo(url = null, id = null) {
     const apiUrl = url ?? LIBRARY.url;
     let libraryId;

     try {
          libraryId = await AsyncStorage.getItem('@libraryId');
     } catch (e) {
          console.log(e);
     }

     if (id) {
          libraryId = id;
     }

     const discovery = create({
          baseURL: apiUrl + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: libraryId,
          },
     });
     const response = await discovery.get('/SystemAPI?method=getLibraryInfo');
     if (response.ok) {
          if (response.data.result) {
               return response.data.result.library;
          }
     }

     return [];
}

/**
 * Return list of library menu links
 **/
export async function getLibraryLinks(url = null) {
     const postBody = await postData();
     const apiUrl = url ?? LIBRARY.url;
     const discovery = create({
          baseURL: apiUrl + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await discovery.post('/SystemAPI?method=getLibraryLinks', postBody);
     if (response.ok) {
          if (response?.data?.result?.items) {
               return response?.data?.result?.items;
          }
     }

     return [];
}

/**
 * Return list of available languages
 **/
export async function getLibraryLanguages(url = null) {
     const apiUrl = url ?? LIBRARY.url;
     const api = create({
          baseURL: apiUrl + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.get('/SystemAPI?method=getLanguages');
     if (response.ok) {
          let languages = [];
          if (response?.data?.result) {
               console.log('Library languages saved at Loading');
               return _.sortBy(response.data.result.languages, 'weight', 'displayName');
          }
          return languages;
     } else {
          console.log(response);
     }
     return [];
}

/**
 * Return array of pre-validated system messages
 * @param {int|null} libraryId
 * @param {int|null} locationId
 * @param {string} url
 **/
export async function getSystemMessages(libraryId = null, locationId = null, url) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               libraryId,
               locationId,
          },
     });
     const response = await api.post('/SystemAPI?method=getSystemMessages', postBody);
     if (response.ok) {
          let messages = [];
          if (response?.data?.result) {
               /*
			 0 => 'All Pages',
			 1 => 'All Account Pages',
			 2 => 'Checkouts Page',
			 3 => 'Holds Page',
			 4 => 'Fines Page',
			 5 => 'Contact Information Page'
			 */
               console.log('System messages fetched and stored');
               return _.castArray(response.data.result.systemMessages);
          }
          return messages;
     } else {
          console.log(response);
     }
     return [];
}

/**
 * Dismiss given system message from displaying again
 * @param {int} systemMessageId
 * @param {string} url
 **/
export async function dismissSystemMessage(systemMessageId, url) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               systemMessageId,
          },
     });
     const response = await api.post('/SystemAPI?method=dismissSystemMessage', postBody);
     if (response.ok) {
          if (response?.data?.result) {
               return response.data.result;
          }
     } else {
          console.log(response);
     }
     return [];
}

/**
 * Check if Aspen Discovery is in offline mode
 * @param {string} url
 **/
export async function getCatalogStatus(url = null) {
     const apiUrl = url ?? LIBRARY.url;
     const api = create({
          baseURL: apiUrl + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(),
          auth: createAuthTokens(),
     });
     const response = await api.get('/SystemAPI?method=getCatalogStatus');
     if (response.ok) {
          if (response?.data?.result) {
               let catalogMessage = null;
               if (response?.data?.result?.api?.message) {
                    catalogMessage = stripHTML(response?.data?.result?.api.message);
               }
               return {
                    status: response?.data?.result?.catalogStatus ?? 0,
                    message: catalogMessage,
               };
          }
     } else {
          console.log(response);
     }
     return {
          status: 0,
          message: null,
     };
}