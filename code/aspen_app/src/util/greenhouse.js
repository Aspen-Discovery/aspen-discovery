import { create } from 'apisauce';
import Constants from 'expo-constants';
import * as SecureStore from 'expo-secure-store';
import _ from 'lodash';
import { Platform } from 'react-native';

import { popToast } from '../components/loadError';
import { createAuthTokens, getHeaders, problemCodeMap } from './apiAuth';
import { GLOBALS } from './globals';
import { PATRON } from './loadPatron';

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

/**
 * Updates Aspen LiDA Build Tracker with patch information
 * @param {string} updateId
 * @param {string} updateChannel
 * @param {date} updateDate
 **/
export async function updateAspenLiDABuild(updateId, updateChannel, updateDate) {
     const greenhouseUrl = Constants.manifest2?.extra?.expoClient?.extra?.greenhouseUrl ?? Constants.manifest.extra.greenhouseUrl;
     const iOSDist = Constants.manifest2?.extra?.expoClient?.ios?.buildNumber ?? Constants.manifest.ios.buildNumber;
     const androidDist = Constants.manifest2?.extra?.expoClient?.android?.versionCode ?? Constants.manifest.android.versionCode;

     const api = create({
          baseURL: greenhouseUrl + 'API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               app: Constants.manifest2?.extra?.expoClient?.name ?? Constants.manifest.name,
               version: Constants.manifest2?.extra?.expoClient?.version ?? Constants.manifest.version,
               build: Platform.OS === 'android' ? androidDist : iOSDist,
               channel: __DEV__ ? 'development' : updateChannel,
               platform: Platform.OS,
               id: updateId,
               patch: GLOBALS.appPatch,
               timestamp: updateDate,
          },
     });

     const response = await api.post('/GreenhouseAPI?method=updateAspenLiDABuild');
     console.log(response);
     return response;
}

export async function fetchNearbyLibrariesFromGreenhouse() {
     let channel = GLOBALS.releaseChannel;
     if (channel === 'DEV' || 'alpha' || 'beta' || 'internal') {
          channel = 'any';
     }
     let method = 'getLibraries';
     let url = Constants.manifest2?.extra?.expoClient?.extra?.greenhouseUrl ?? Constants.manifest.extra.greenhouseUrl;
     let latitude,
          longitude = 0;
     if (!_.includes(GLOBALS.slug, 'aspen-lida')) {
          method = 'getLibrary';
          url = Constants.manifest2?.extra?.expoClient?.extra?.apiUrl ?? Constants.manifest.extra.apiUrl;
     }
     if (GLOBALS.slug === 'aspen-lida-bws') {
          method = 'getLibrary';
          url = Constants.manifest2?.extra?.expoClient?.extra?.apiUrl ?? Constants.manifest.extra.apiUrl;
     }
     if (GLOBALS.slug === 'aspen-lida-alpha') {
          channel = 'alpha';
     } else if (GLOBALS.slug === 'aspen-lida-beta') {
          channel = 'beta';
     } else if (GLOBALS.slug === 'aspen-lida-zeta') {
          channel = 'zeta';
     } else if (GLOBALS.slug === 'aspen-lida-bws') {
          channel = 'any';
     }
     if (_.isNull(PATRON.coords.lat) && _.isNull(PATRON.coords.long)) {
          try {
               latitude = await SecureStore.getItemAsync('latitude');
               longitude = await SecureStore.getItemAsync('longitude');
               PATRON.coords.lat = latitude;
               PATRON.coords.long = longitude;
          } catch (e) {
               console.log(e);
          }
     }
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
     });
     const response = await api.get('/GreenhouseAPI?method=' + method, {
          latitude: PATRON.coords.lat,
          longitude: PATRON.coords.long,
          release_channel: channel,
     });
     if (response.ok) {
          const data = response.data;
          let libraries;
          if (_.includes(GLOBALS.slug, 'aspen-lida') && GLOBALS.slug !== 'aspen-lida-bws') {
               libraries = data.libraries;
          } else {
               libraries = _.values(data.library);
          }

          libraries = _.sortBy(libraries, ['distance', 'name', 'librarySystem']);

          let showSelectLibrary = true;
          if (data.count <= 1) {
               showSelectLibrary = false;
          }

          return {
               success: true,
               libraries: libraries ?? [],
               shouldShowSelectLibrary: showSelectLibrary,
          };
     } else {
          const problem = problemCodeMap(response.problem);
          popToast(problem.title, problem.message, 'warning');
     }

     return {
          success: false,
          shouldShowSelectLibrary: false,
          libraries: [],
     };
}

export async function fetchAllLibrariesFromGreenhouse() {
     let channel = GLOBALS.releaseChannel;
     if (channel === 'DEV' || 'alpha' || 'beta' || 'internal') {
          channel = 'any';
     }
     let url = Constants.manifest2?.extra?.expoClient?.extra?.greenhouseUrl ?? Constants.manifest.extra.greenhouseUrl;
     if (!_.includes(GLOBALS.slug, 'aspen-lida') || GLOBALS.slug === 'aspen-lida-bws') {
          url = Constants.manifest2?.extra?.expoClient?.extra?.apiUrl ?? Constants.manifest.extra.apiUrl;
     }

     if (GLOBALS.slug === 'aspen-lida-alpha') {
          channel = 'alpha';
     } else if (GLOBALS.slug === 'aspen-lida-beta') {
          channel = 'beta';
     } else if (GLOBALS.slug === 'aspen-lida-zeta') {
          channel = 'zeta';
     } else if (GLOBALS.slug === 'aspen-lida-bws') {
          channel = 'any';
     }

     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
     });
     const response = await api.get('/GreenhouseAPI?method=getLibraries', {
          release_channel: channel,
     });
     if (response.ok) {
          const data = response.data;
          const libraries = _.sortBy(data.libraries, ['name', 'librarySystem']);
          return {
               success: true,
               libraries: libraries ?? [],
          };
     }
     return {
          success: false,
          libraries: [],
     };
}