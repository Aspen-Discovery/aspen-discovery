import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'apisauce';
import _ from 'lodash';
import React from 'react';

// custom components and helper files
import { popToast } from '../components/loadError';
import { getTermFromDictionary } from '../translations/TranslationService';
import { createAuthTokens, getHeaders, postData } from './apiAuth';
import { GLOBALS } from './globals';
import { PATRON } from './loadPatron';
import { RemoveData } from './logout';

export const LIBRARY = {
     url: '',
     name: '',
     favicon: '',
     languages: [],
     vdx: [],
};

export const BRANCH = {
     name: '',
     vdxFormId: null,
     vdxLocation: null,
     vdx: [],
};

export const ALL_LOCATIONS = {
     branches: [],
};

export const ALL_BRANCHES = {};

/**
 * Fetch branch/location information
 **/
export async function getLocationInfo() {
     let profile = [];

     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
     });
     const response = await api.get('/SystemAPI?method=getLocationInfo', {
          id: PATRON.location,
          library: PATRON.scope,
          version: GLOBALS.appVersion,
     });
     if (response.ok) {
          if (response.data.result.success) {
               if (typeof response.data.result.location !== 'undefined') {
                    profile = response.data.result.location;
                    if (typeof profile.vdxFormId !== 'undefined' && !_.isNull(profile.vdxFormId)) {
                         try {
                              if (_.isEmpty(LIBRARY.vdx)) {
                                   await getVdxForm(LIBRARY.url, profile.vdxFormId);
                              }
                         } catch (e) {
                              console.log(e);
                         }
                    }
               } else {
                    console.log('Location undefined.');
               }
               await AsyncStorage.setItem('@locationInfo', JSON.stringify(profile));
               return profile;
          }
          return profile;
     } else {
          console.log('Unable to fetch location.');
          console.log(response);
          return profile;
     }
}

/**
 * Fetch library information
 **/
export async function getLibraryInfo(libraryId, libraryUrl, timeout) {
     let profile = [];
     const api = create({
          baseURL: libraryUrl + '/API',
          timeout,
          headers: getHeaders(),
          auth: createAuthTokens(),
     });
     const response = await api.get('/SystemAPI?method=getLibraryInfo', {
          id: libraryId,
     });
     if (response.ok) {
          if (response.data.result.success) {
               if (typeof response.data.result.library !== 'undefined') {
                    profile = response.data.result.library;
               }
               await AsyncStorage.setItem('@libraryInfo', JSON.stringify(profile));
               return profile;
          }
          return profile;
     } else {
          console.log('Unable to fetch library.');
          console.log(response);
          return profile;
     }
}

/**
 * Fetch settings for app that are maintained by the library
 **/
export async function getAppSettings(url, timeout, slug) {
     const api = create({
          baseURL: url + '/API',
          timeout,
          headers: getHeaders(),
          auth: createAuthTokens(),
     });
     const response = await api.get('/SystemAPI?method=getAppSettings', {
          slug,
     });
     if (response.ok) {
          //await AsyncStorage.setItem('@appSettings', JSON.stringify(appSettings));
          LIBRARY.appSettings = response.data.result.settings;
          console.log('App settings saved');
          return response.data?.result?.settings ?? [];
     } else {
          console.log(response);
     }
}

export async function getLocationAppSettings(url, timeout, slug) {
     const api = create({
          baseURL: url + '/API',
          timeout,
          headers: getHeaders(),
          auth: createAuthTokens(),
     });
     const response = await api.get('/SystemAPI?method=getLocationAppSettings', {
          slug,
     });
     if (response.ok) {
          const locationAppSettings = response.data.result.settings;
          await AsyncStorage.setItem('@locationAppSettings', JSON.stringify(locationAppSettings));
          LIBRARY.locationAppSettings = locationAppSettings;
          console.log('Location app settings saved');
          return response.data.result;
     } else {
          console.log(response);
     }
}

/**
 * Fetch valid pickup locations for the patron
 **/
export async function getPickupLocations(url = null) {
     let baseUrl = url ?? LIBRARY.url;
     const postBody = await postData();
     const api = create({
          baseURL: baseUrl + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=getValidPickupLocations', postBody);

     if (response.ok) {
          let locations = [];
          const data = response.data.result.pickupLocations;
          if (_.isObject(data) || _.isArray(data)) {
               locations = data.map(({ displayName, code, locationId }) => ({
                    key: locationId,
                    locationId,
                    code,
                    name: displayName,
               }));
          }

          try {
               await AsyncStorage.setItem('@pickupLocations', JSON.stringify(locations));
          } catch (e) {
               console.log(e);
          }

          PATRON.pickupLocations = locations;
          return locations;
     } else {
          console.log(response);
     }
}

/**
 * Fetch active browse categories for the branch/location
 **/
export async function getBrowseCategories(libraryUrl, discoveryVersion, limit = null) {
     if (libraryUrl) {
          const postBody = await postData();
          const api = create({
               baseURL: libraryUrl + '/API',
               timeout: GLOBALS.timeoutAverage,
               headers: getHeaders(true),
               auth: createAuthTokens(),
               params: {
                    maxCategories: limit,
                    LiDARequest: true,
               },
          });
          const hiddenCategories = [];
          if (discoveryVersion < '22.07.00') {
               const responseHiddenCategories = await api.post('/UserAPI?method=getHiddenBrowseCategories', postBody);
               if (responseHiddenCategories.ok) {
                    if (typeof responseHiddenCategories.data.result !== 'undefined') {
                         const categories = responseHiddenCategories.data.result.categories;
                         if (_.isArray(categories) === true) {
                              if (categories.length > 0) {
                                   categories.map(function (category, index, array) {
                                        hiddenCategories.push({
                                             key: category.id,
                                             title: category.name,
                                             isHidden: true,
                                        });
                                   });
                              }
                         }
                    }
               }
          }
          let response = '';
          response = await api.post('/SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true', postBody);
          //console.log(response);
          if (response.status === 403) {
               await RemoveData().then((res) => {
                    console.log('Session ended.');
               });
          }

          if (response.data.result) {
               return formatBrowseCategories(response.data.result);
          }
     }

     return [];
}

export async function getVdxForm(url, id) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: { formId: id },
     });
     const response = await api.post('/SystemAPI?method=getVdxForm', postBody);
     if (response.ok) {
          LIBRARY.vdx = response.data.result;
          return response.data.result;
     } else {
          popToast(getTermFromDictionary('en', 'error_no_server_connection'), getTermFromDictionary('en', 'error_no_library_connection'), 'error');
          console.log(response);
     }
}

export function formatDiscoveryVersion(payload) {
     try {
          const result = payload.split(' ');
          if (_.isObject(result)) {
               LIBRARY.version = result[0];
               return result[0];
          }
     } catch (e) {
          console.log(payload);
          console.log(e);
     }
     return payload;
}

export const UpdateBrowseCategoryContext = (maxCat = 6) => {
     const [categories, setCategories] = React.useState();
     React.useEffect(() => {
          async function getUpdatedBrowseCategories() {
               await updatePatronBrowseCategories(maxCat).then((result) => {
                    setCategories(result);
               });
          }

          getUpdatedBrowseCategories();
     }, []);

     return categories;
};

export async function updatePatronBrowseCategories(maxCat) {
     const postBody = await postData();
     const discovery = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               maxCategories: maxCat,
               LiDARequest: true,
          },
     });
     const response = await discovery.post('/SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true', postBody);
     if (response.ok) {
          if (response.data.result) {
               return formatBrowseCategories(response.data.result);
          }
     }
     return [];
}

export function formatBrowseCategories(payload) {
     const categories = [];
     if (!_.isUndefined(payload)) {
          payload.map(function (category, index, array) {
               const subCategories = category['subCategories'] ?? [];
               const manyLists = category['lists'] ?? [];
               const records = category['records'] ?? [];
               const allEvents = category['events'] ?? [];
               const lists = [];
               const events = [];
               if (!_.isEmpty(subCategories) && subCategories.length > 0) {
                    subCategories.forEach((item) =>
                         categories.push({
                              key: item.key,
                              title: item.title,
                              source: item.source,
                              records: item.records,
                              isHidden: item.isHidden ?? false,
                         })
                    );
               } else {
                    if (!_.isEmpty(subCategories) || !_.isEmpty(manyLists) || !_.isEmpty(records) || !_.isEmpty(allEvents)) {
                         if (!_.isEmpty(subCategories) && subCategories.length > 0) {
                              subCategories.forEach((item) =>
                                   categories.push({
                                        key: item.key,
                                        title: item.title,
                                        source: item.source,
                                        records: item.records,
                                        isHidden: item.isHidden ?? false,
                                   })
                              );
                         } else {
                              if (!_.isEmpty(manyLists)) {
                                   manyLists.forEach((item) =>
                                        lists.push({
                                             id: item.sourceId,
                                             categoryId: category.key,
                                             source: 'List',
                                             title_display: item.title,
                                             isHidden: category.isHidden ?? false,
                                        })
                                   );
                              }

                              if (!_.isEmpty(allEvents)) {
                                   allEvents.forEach((item) =>
                                        events.push({
                                             id: item.sourceId ?? item.id,
                                             categoryId: category.key,
                                             source: 'Event',
                                             title_display: item.title ?? item.title_display,
                                             isHidden: category.isHidden ?? false,
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
                                   categories.push({
                                        key: id,
                                        title: category.title,
                                        source: category.source,
                                        numNewTitles,
                                        records: lists,
                                        id: categoryId,
                                        isHidden: category.isHidden ?? false,
                                   });
                              }

                              if (events.length !== 0) {
                                   if (!_.isUndefined(category.listId)) {
                                        id = category.listId;
                                   }

                                   let numNewTitles = 0;
                                   if (!_.isUndefined(category.numNewTitles)) {
                                        numNewTitles = category.numNewTitles;
                                   }

                                   categories.push({
                                        key: id,
                                        title: category.title,
                                        source: category.source,
                                        numNewTitles: numNewTitles,
                                        records: events,
                                        isHidden: category.isHidden ?? false,
                                        id: categoryId,
                                   });
                              }

                              if (records.length !== 0) {
                                   if (!_.isUndefined(category.listId) && !_.isNull(category.listId)) {
                                        id = category.listId;
                                   }

                                   if (!_.isUndefined(category.sourceId) && !_.isNull(category.sourceId) && category.sourceId !== '' && category.sourceId !== -1 && category.sourceId !== '-1') {
                                        id = category.sourceId;
                                   }

                                   let numNewTitles = 0;
                                   if (!_.isUndefined(category.numNewTitles)) {
                                        numNewTitles = category.numNewTitles;
                                   }

                                   if (_.find(categories, ['id', categoryId])) {
                                        let thisCategory = _.find(categories, ['id', categoryId]);
                                        let allRecords = category.records;
                                        let allFormattedRecords = [];
                                        allRecords.forEach((item) =>
                                             allFormattedRecords.push({
                                                  id: item.id,
                                                  categoryId: category.key,
                                                  source: 'grouped_work',
                                                  title_display: item.title,
                                             })
                                        );
                                        thisCategory.records = _.concat(thisCategory.records, allFormattedRecords);
                                        _.merge(categories, thisCategory);
                                   } else {
                                        categories.push({
                                             key: id,
                                             title: category.title,
                                             source: category.source,
                                             numNewTitles,
                                             records: category.records,
                                             isHidden: category.isHidden ?? false,
                                             id: categoryId,
                                        });
                                   }
                              }
                         }
                    }
               }
          });
     }
     return categories;
}

export async function reloadBrowseCategories(maxCat, url = null) {
     let maxCategories = maxCat ?? 5;
     const postBody = await postData();
     let discovery;
     let baseUrl = url ?? LIBRARY.url;
     if (maxCategories !== 9999) {
          discovery = create({
               baseURL: baseUrl + '/API',
               timeout: GLOBALS.timeoutAverage,
               headers: getHeaders(true),
               auth: createAuthTokens(),
               params: {
                    maxCategories: maxCategories,
                    LiDARequest: true,
               },
          });
     } else {
          discovery = create({
               baseURL: baseUrl + '/API',
               timeout: GLOBALS.timeoutAverage,
               headers: getHeaders(true),
               auth: createAuthTokens(),
               params: {
                    LiDARequest: true,
               },
          });
     }
     const response = await discovery.post('/SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true', postBody);

     if (response.ok) {
          if (response.data.result) {
               return formatBrowseCategories(response.data.result);
          }
     } else {
          console.log(response.config);
     }
     return [];
}