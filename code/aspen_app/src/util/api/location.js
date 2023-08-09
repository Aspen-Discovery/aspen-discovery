import React from 'react';
import { LIBRARY } from '../loadLibrary';
import { GLOBALS } from '../globals';
import { createAuthTokens, getHeaders } from '../apiAuth';
import AsyncStorage from '@react-native-async-storage/async-storage';
import _ from 'lodash';
import { create } from 'apisauce';

export async function getLocationInfo(url = null) {
     const apiUrl = url ?? LIBRARY.url;
     let scope, locationId;
     try {
          scope = await AsyncStorage.getItem('@solrScope');
          locationId = await AsyncStorage.getItem('@locationId');
     } catch (e) {
          console.log(e);
     }

     const discovery = create({
          baseURL: apiUrl + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: locationId,
               library: scope,
               version: GLOBALS.appVersion,
          },
     });
     const response = await discovery.get('/SystemAPI?method=getLocationInfo');
     if (response.ok) {
          if (response.data.result) {
               return response.data.result.location;
          }
     }
     return [];
}

export async function getSelfCheckSettings(url = null) {
     const apiUrl = url ?? LIBRARY.url;
     let locationId;
     try {
          locationId = await AsyncStorage.getItem('@locationId');
     } catch (e) {
          console.log(e);
     }

     const discovery = create({
          baseURL: apiUrl + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: locationId,
          },
     });
     const response = await discovery.get('/SystemAPI?method=getSelfCheckSettings');
     if (response.ok) {
          if (response.data.result) {
               return response.data.result;
          }
     }
     return [];
}