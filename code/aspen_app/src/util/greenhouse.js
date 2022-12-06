import { create } from 'apisauce';
import Constants from 'expo-constants';
import * as SecureStore from 'expo-secure-store';

import { popToast } from '../components/loadError';
import { createAuthTokens, getHeaders, problemCodeMap } from './apiAuth';
import { GLOBALS } from './globals';

/**
 * Fetch libraries to log into
 **/
export async function makeGreenhouseRequest(method, fetchAll = false) {
     const slug = GLOBALS.slug;
     let greenhouseUrl;
     if (slug === 'aspen-lida') {
          greenhouseUrl = Constants.manifest2?.extra?.expoClient?.extra?.greenhouse ?? Constants.manifest.extra.greenhouse;
     } else {
          greenhouseUrl = GLOBALS.url;
     }
     let latitude = await SecureStore.getItemAsync('latitude');
     let longitude = await SecureStore.getItemAsync('longitude');

     if (fetchAll) {
          latitude = 0;
          longitude = 0;
     }

     const api = create({
          baseURL: greenhouseUrl + '/API',
          timeout: 10000,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               latitude,
               longitude,
               release_channel: await SecureStore.getItemAsync('releaseChannel'),
          },
     });
     const response = await api.post('/GreenhouseAPI?method=' + method);
     if (response.ok) {
          return response.data;
     } else {
          const problem = problemCodeMap(response.problem);
          popToast(problem.title, problem.message, 'warning');
     }
}