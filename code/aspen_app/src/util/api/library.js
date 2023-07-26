import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'apisauce';
import _ from 'lodash';
import React from 'react';
import { createAuthTokens, getHeaders } from '../apiAuth';
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
               return _.sortBy(response.data.result.languages, 'id');
          }
          return languages;
     } else {
          console.log(response);
     }
     return [];
}