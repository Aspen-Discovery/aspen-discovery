import { create } from 'apisauce';
import Constants from 'expo-constants';
import * as SecureStore from 'expo-secure-store';
import * as Updates from 'expo-updates';
import _ from 'lodash';

// custom components and helper files
import { popToast } from '../components/loadError';
import { createAuthTokens, getHeaders, postData, problemCodeMap } from './apiAuth';
import { GLOBALS, LOGIN_DATA } from './globals';
import { PATRON } from './loadPatron';
import { BrowseCategoryContext } from '../components/navigation';
import { useContext } from 'react';

export async function makeGreenhouseRequestNearby() {
     let method = 'getLibraries';
     let url = Constants.manifest2?.extra?.expoClient?.extra?.greenhouse ?? Constants.manifest.extra.greenhouse;
     let latitude,
          longitude = 0;
     if (GLOBALS.slug !== 'aspen-lida') {
          method = 'getLibrary';
          url = GLOBALS.url;
          LOGIN_DATA.runGreenhouse = false;
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
          release_channel: Updates.releaseChannel,
     });
     if (response.ok) {
          const data = response.data;
          let libraries;
          if (GLOBALS.slug === 'aspen-lida') {
               libraries = _.uniqBy(data.libraries, (v) => [v.locationId, v.libraryId].join());
               libraries = _.uniqBy(libraries, (v) => [v.librarySystem, v.name].join());
          } else {
               libraries = _.uniqBy(data.library, (v) => [v.locationId, v.name].join());
               libraries = _.values(libraries);
               libraries = _.uniqBy(libraries, (v) => [v.libraryId, v.name].join());
          }

          //console.log(libraries);

          if (data.count <= 1) {
               LOGIN_DATA.showSelectLibrary = false;
          }
          LOGIN_DATA.nearbyLocations = libraries;
          LOGIN_DATA.hasPendingChanges = true;
          console.log('Greenhouse request completed.');
          return true;
     } else {
          const problem = problemCodeMap(response.problem);
          popToast(problem.title, problem.message, 'warning');
     }
     return false;
}

export async function makeGreenhouseRequestAll() {
     const api = create({
          baseURL: Constants.manifest2?.extra?.expoClient?.extra?.greenhouse ?? Constants.manifest.extra.greenhouse,
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
     });
     const response = await api.get('/API/GreenhouseAPI?method=getLibraries', {
          release_channel: Updates.releaseChannel,
     });
     if (response.ok) {
          const data = response.data;
          LOGIN_DATA.allLocations = _.uniqBy(data.libraries, (v) => [v.librarySystem, v.name].join());
          LOGIN_DATA.hasPendingChanges = true;
          console.log('Full greenhouse request completed.');
          return true;
     } else {
          console.log(response);
     }
     return false;
}

export async function checkCachedUrl(url) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=getValidPickupLocations', postBody);
     return !!response.ok;
}

export async function getLibrarySystem(data) {
     const discovery = create({
          baseURL: data.patronsLibrary['baseUrl'] + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: data.patronsLibrary['libraryId'],
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

export async function getLibraryBranch(data) {
     const discovery = create({
          baseURL: data.patronsLibrary['baseUrl'] + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: data.patronsLibrary['locationId'],
               library: data.patronsLibrary['solrScope'],
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

export async function getUserProfile(data, user, pass) {
     const postBody = new FormData();
     postBody.append('username', user['valueUser']);
     postBody.append('password', pass['valueSecret']);

     const discovery = create({
          baseURL: data.patronsLibrary['baseUrl'] + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               linkedUsers: true,
               checkIfValid: false,
          },
     });
     const response = await discovery.post('/UserAPI?method=getPatronProfile', postBody);
     if (response.ok) {
          if (response.data.result) {
               return response.data.result.profile;
          }
     }
     return [];
}

export async function getBrowseCategories(data, user, pass) {
     const postBody = new FormData();
     postBody.append('username', user['valueUser']);
     postBody.append('password', pass['valueSecret']);

     const discovery = create({
          baseURL: data.patronsLibrary['baseUrl'] + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               maxCategories: 5,
               LiDARequest: true,
          },
     });
     const response = await discovery.post('/SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true', postBody);
     if (response.ok) {
          if (response.data.result) {
               const allItems = response.data.result;
               const allCategories = [];
               if (!_.isUndefined(allItems)) {
                    allItems.map(function (category, index, array) {
                         const subCategories = category['subCategories'] ?? [];
                         const manyLists = category['lists'] ?? [];
                         const records = category['records'] ?? [];
                         const lists = [];
                         if (!_.isUndefined(subCategories) && subCategories.length > 0) {
                              subCategories.forEach((item) =>
                                   allCategories.push({
                                        key: item.key,
                                        title: item.title,
                                        source: item.source,
                                        records: item.records,
                                   })
                              );
                         } else {
                              if (!_.isUndefined(subCategories) || !_.isUndefined(manyLists) || !_.isUndefined(records)) {
                                   if (!_.isUndefined(subCategories) && subCategories.length > 0) {
                                        subCategories.forEach((item) =>
                                             allCategories.push({
                                                  key: item.key,
                                                  title: item.title,
                                                  source: item.source,
                                                  records: item.records,
                                             })
                                        );
                                   } else {
                                        if (!_.isUndefined(manyLists) && manyLists.length > 0) {
                                             manyLists.forEach((item) =>
                                                  lists.push({
                                                       id: item.sourceId,
                                                       categoryId: item.id,
                                                       source: 'List',
                                                       title_display: item.title,
                                                  })
                                             );
                                        }

                                        let id = category.key;
                                        const categoryId = category.key;
                                        if (lists.length !== 0) {
                                             if (!_.isUndefined(category.listId)) {
                                                  id = category.listId;
                                             }

                                             let numNewTitles = 0;
                                             if (!_.isUndefined(category.numNewTitles)) {
                                                  numNewTitles = category.numNewTitles;
                                             }
                                             allCategories.push({
                                                  key: id,
                                                  title: category.title,
                                                  source: category.source,
                                                  numNewTitles,
                                                  records: lists,
                                                  id: categoryId,
                                             });
                                        } else {
                                             if (!_.isUndefined(category.listId)) {
                                                  id = category.listId;
                                             }

                                             let numNewTitles = 0;
                                             if (!_.isUndefined(category.numNewTitles)) {
                                                  numNewTitles = category.numNewTitles;
                                             }
                                             allCategories.push({
                                                  key: id,
                                                  title: category.title,
                                                  source: category.source,
                                                  numNewTitles,
                                                  records: category.records,
                                                  id: categoryId,
                                             });
                                        }
                                   }
                              }
                         }
                    });
               }
               return allCategories;
          }
     }
     return [];
}