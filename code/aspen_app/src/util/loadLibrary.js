import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'apisauce';
import _, { max } from 'lodash';
import React from 'react';

// custom components and helper files
import { popToast } from '../components/loadError';
import { translate } from '../translations/translations';
import { createAuthTokens, getHeaders, postData } from './apiAuth';
import { GLOBALS } from './globals';
import { PATRON } from './loadPatron';
import { BrowseCategoryContext } from '../context/initialContext';
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
          const appSettings = response.data.result.settings;
          await AsyncStorage.setItem('@appSettings', JSON.stringify(appSettings));
          LIBRARY.appSettings = appSettings;
          console.log('App settings saved');
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
          if (response.ok) {
               //console.log(response.data);
               const items = response.data.result;
               let allCategories = [];
               if (typeof items !== 'undefined') {
                    items.map(function (category, index, array) {
                         const subCategories = category['subCategories'];
                         const listOfLists = category['lists'];
                         const items = category['records'];
                         const lists = [];

                         //console.log(category);

                         if (discoveryVersion >= '22.07.00') {
                              if (typeof subCategories !== 'undefined' && subCategories.length !== 0) {
                                   subCategories.forEach((item) =>
                                        allCategories.push({
                                             key: item.key,
                                             title: item.title,
                                             source: item.source,
                                             records: item.records,
                                        })
                                   );
                              } else {
                                   if (typeof subCategories !== 'undefined' || typeof listOfLists !== 'undefined' || typeof items !== 'undefined') {
                                        if (typeof subCategories !== 'undefined' && subCategories.length !== 0) {
                                             subCategories.forEach((item) =>
                                                  allCategories.push({
                                                       key: item.key,
                                                       title: item.title,
                                                       source: item.source,
                                                       records: item.records,
                                                  })
                                             );
                                        } else {
                                             if (typeof listOfLists !== 'undefined' && listOfLists.length !== 0) {
                                                  listOfLists.forEach((item) =>
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
                                                  if (typeof category.listId !== 'undefined') {
                                                       id = category.listId;
                                                  }

                                                  let numNewTitles = 0;
                                                  if (typeof category.numNewTitles !== 'undefined') {
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
                                                  if (typeof category.listId !== 'undefined') {
                                                       id = category.listId;
                                                  }

                                                  let numNewTitles = 0;
                                                  if (typeof category.numNewTitles !== 'undefined') {
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
                         } else if (discoveryVersion >= '22.05.00' || discoveryVersion <= '22.06.10') {
                              if (typeof subCategories !== 'undefined' && subCategories.length !== 0) {
                                   subCategories.forEach((item) =>
                                        allCategories.push({
                                             key: item.key,
                                             title: item.title,
                                             records: item.records,
                                        })
                                   );
                              } else {
                                   //allCategories.push({'key': category.key, 'title': category.title});

                                   if (typeof subCategories != 'undefined') {
                                        if (subCategories.length !== 0) {
                                             subCategories.forEach((item) =>
                                                  allCategories.push({
                                                       key: item.key,
                                                       title: item.title,
                                                       records: item.records,
                                                  })
                                             );
                                        } else {
                                             allCategories.push({
                                                  key: category.key,
                                                  title: category.title,
                                                  records: category.records,
                                             });
                                        }
                                   }
                              }
                         } else {
                              if (typeof subCategories !== 'undefined' && subCategories.length !== 0) {
                                   subCategories.forEach((item) =>
                                        allCategories.push({
                                             key: item.key,
                                             title: item.title,
                                        })
                                   );
                              } else {
                                   allCategories.push({
                                        key: category.key,
                                        title: category.title,
                                   });

                                   if (typeof subCategories != 'undefined') {
                                        if (subCategories.length !== 0) {
                                             subCategories.forEach((item) =>
                                                  allCategories.push({
                                                       key: item.key,
                                                       title: item.title,
                                                  })
                                             );
                                        } else {
                                             allCategories.push({
                                                  key: category.key,
                                                  title: category.title,
                                             });
                                        }
                                   }
                              }
                         }
                    });
               }

               allCategories = _.pullAllBy(allCategories, hiddenCategories, 'key');
               return allCategories;
          } else {
               console.log(response);
          }
     } else {
          console.log('No library URL to fetch browse categories.');
     }
}

export async function getLanguages(libraryUrl) {
     const api = create({
          baseURL: libraryUrl + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.get('/SystemAPI?method=getLanguages');
     if (response.ok) {
          if (typeof response.data.result !== 'undefined') {
               LIBRARY.languages = _.sortBy(response.data.result.languages, 'id');
               console.log('Library languages saved');
          }
     } else {
          console.log(response);
     }
}

export async function getVdxForm(libraryUrl, id) {
     const postBody = await postData();
     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: { formId: id },
     });
     const response = await api.post('/SystemAPI?method=getVdxForm', postBody);
     if (response.ok) {
          const vdxFormFields = response.data.result;
          LIBRARY.vdx = response.data.result;
          await AsyncStorage.setItem('@vdxFormFields', JSON.stringify(vdxFormFields));
          return response.data;
     } else {
          popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
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
               const lists = [];
               if (!_.isEmpty(subCategories) && subCategories.length > 0) {
                    subCategories.forEach((item) =>
                         categories.push({
                              key: item.key,
                              title: item.title,
                              source: item.source,
                              records: item.records,
                         })
                    );
               } else {
                    if (!_.isEmpty(subCategories) || !_.isEmpty(manyLists) || !_.isEmpty(records)) {
                         if (!_.isEmpty(subCategories) && subCategories.length > 0) {
                              subCategories.forEach((item) =>
                                   categories.push({
                                        key: item.key,
                                        title: item.title,
                                        source: item.source,
                                        records: item.records,
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
                                   });
                              } else {
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
                                        records: category.records,
                                        id: categoryId,
                                   });
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
     console.log('maxCategories: ' + maxCategories);
     const postBody = await postData();
     let discovery;
     let baseUrl = url ?? LIBRARY.url;
     if (maxCategories !== '9999') {
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