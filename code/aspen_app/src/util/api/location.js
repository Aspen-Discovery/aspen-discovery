import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'apisauce';
import { createAuthTokens, getHeaders } from '../apiAuth';
import { GLOBALS } from '../globals';
import { LIBRARY } from '../loadLibrary';

export async function getLocationInfo(url = null, locationId = null) {
     const apiUrl = url ?? LIBRARY.url;

     if (!locationId) {
          try {
               locationId = await AsyncStorage.getItem('@locationId');
          } catch (e) {
               console.log(e);
          }
     }

     const discovery = create({
          baseURL: apiUrl + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: locationId,
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
               locationId: locationId,
          },
     });
     const response = await discovery.get('/SystemAPI?method=getSelfCheckSettings');
     if (response.ok) {
          if (response.data.result) {
               return response.data.result;
          } else {
               return {
                    success: false,
                    settings: [],
               };
          }
     }
     return {
          success: false,
          settings: [],
     };
}

export async function getLocations(url, language = 'en', latitude, longitude) {
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               latitude,
               longitude,
               language,
          },
     });
     const response = await discovery.get('/SystemAPI?method=getLocations');
     if (response.ok) {
          if (response?.data?.result?.locations) {
               return response.data.result.locations;
          }
     }
     return [];
}