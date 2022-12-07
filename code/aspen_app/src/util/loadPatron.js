import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'apisauce';
import _ from 'lodash';

// custom components and helper files
import { popAlert } from '../components/loadError';
import { createAuthTokens, ENDPOINT, getHeaders, postData } from './apiAuth';
import { GLOBALS } from './globals';
import { LIBRARY } from './loadLibrary';

export const PATRON = {
     userToken: null,
     scope: null,
     library: null,
     location: null,
     listLastUsed: null,
     fines: 0,
     messages: [],
     num: {
          checkedOut: 0,
          holds: 0,
          lists: 0,
          overdue: 0,
          ready: 0,
          savedSearches: 0,
          updatedSearches: 0,
     },
     promptForOverdriveEmail: 1,
     rememberHoldPickupLocation: 0,
     pickupLocations: [],
     language: 'en',
     coords: {
          lat: null,
          long: null,
     },
     linkedAccounts: [],
     holds: [],
     lists: [],
     browseCategories: [],
};

const endpoint = ENDPOINT.user;

export async function getProfile(reload = false) {
     const postBody = await postData();
     let libraryUrl = LIBRARY.url;
     if (_.isNull(libraryUrl)) {
          try {
               libraryUrl = await AsyncStorage.getItem('@pathUrl');
          } catch (e) {
               console.log(e);
          }
     }

     if (libraryUrl) {
          const api = create({
               baseURL: libraryUrl + '/API',
               timeout: GLOBALS.timeoutAverage,
               headers: getHeaders(true),
               auth: createAuthTokens(),
               params: { reload },
          });
          const response = await api.post('/UserAPI?method=getPatronProfile&linkedUsers=true', postBody);
          await getILSMessages(libraryUrl);
          if (response.ok) {
               if (!_.isUndefined(response.data.result)) {
                    if (!_.isUndefined(response.data.result.profile)) {
                         return response.data.result.profile;
                    }
               }
          } else {
               console.log(response);
          }
     }
}

export async function reloadProfile() {
     const postBody = await postData();

     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=getPatronProfile&reload&linkedUsers=true', postBody);
     if (response.ok) {
          if (!_.isUndefined(response.data.result)) {
               if (!_.isUndefined(response.data.result.profile)) {
                    const profile = response.data.result.profile;
                    console.log('User profile forcefully updated');
                    await reloadCheckedOutItems();
                    await reloadHolds();
                    return profile;
               }
          }
     } else {
          //console.log(response);
     }
}

export async function getILSMessages(url) {
     let baseUrl = url ?? LIBRARY.url;
     const postBody = await postData();
     const api = create({
          baseURL: baseUrl + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=getILSMessages', postBody);
     if (response.ok) {
          let messages = [];
          if (response.data.result.messages) {
               messages = response.data.result.messages;
               PATRON.messages = messages;
               try {
                    await AsyncStorage.setItem('@ILSMessages', JSON.stringify(messages));
               } catch (e) {
                    console.log(e);
               }
          } else {
               try {
                    await AsyncStorage.setItem('@ILSMessages', JSON.stringify(messages));
               } catch (e) {
                    console.log(e);
               }
          }
          return messages;
     }
}

export async function getCheckedOutItems(url) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(true),
          params: {
               source: 'all',
               linkedUsers: 'true',
          },
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=getPatronCheckedOutItems', postBody);
     if (response.ok) {
          let items = response.data?.result?.checkedOutItems ?? [];
          items = _.sortBy(items, ['daysUntilDue', 'title']);
          return items;
     } else {
          console.log(response);
     }
}

export async function reloadCheckedOutItems(url) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(true),
          params: {
               source: 'all',
               linkedUsers: 'true',
               refreshCheckouts: 'true',
          },
          auth: createAuthTokens(),
     });

     const response = await api.post('/UserAPI?method=getPatronCheckedOutItems', postBody);
     if (response.ok) {
          let items = response.data?.result?.checkedOutItems ?? [];
          items = _.sortBy(items, ['daysUntilDue', 'title']);
          return items;
     } else {
          console.log(response);
     }
}

export async function getHolds(url) {
     let response;
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(true),
          params: {
               source: 'all',
               linkedUsers: true,
          },
          auth: createAuthTokens(),
     });
     response = await api.post('/UserAPI?method=getPatronHolds', postBody);
     if (response.ok) {
          //console.log(response.data);
          let holds;
          let holdsReady = [];
          let holdsNotReady = [];
          if (!_.isUndefined(response.data.result)) {
               if (!_.isUndefined(response.data.result.holds)) {
                    const items = response.data.result.holds;

                    if (typeof items !== 'undefined') {
                         if (typeof items.unavailable !== 'undefined') {
                              holdsNotReady = Object.values(items.unavailable);
                         }

                         if (typeof items.available !== 'undefined') {
                              holdsReady = Object.values(items.available);
                         }
                    }

                    holds = holdsReady.concat(holdsNotReady);
               }
          }

          PATRON.holds = holds;
          return {
               holds,
               holdsReady,
               holdsNotReady,
          };
     } else {
          console.log(response);
     }
}

export async function reloadHolds(url) {
     let response;
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(true),
          params: {
               source: 'all',
               linkedUsers: true,
               refreshHolds: true,
          },
          auth: createAuthTokens(),
     });
     response = await api.post('/UserAPI?method=getPatronHolds', postBody);
     if (response.ok) {
          //console.log(response.data);
          const items = response.data.result.holds;
          let holds;
          let holdsReady = [];
          let holdsNotReady = [];

          if (typeof items !== 'undefined') {
               if (typeof items.unavailable !== 'undefined') {
                    holdsNotReady = Object.values(items.unavailable);
               }

               if (typeof items.available !== 'undefined') {
                    holdsReady = Object.values(items.available);
               }
          }

          holds = holdsReady.concat(holdsNotReady);
          PATRON.holds = holds;
          return {
               holds,
               holdsReady,
               holdsNotReady,
          };
     } else {
          console.log(response);
          return {
               holds: [],
               holdsReady: [],
               holdsNotReady: [],
          };
     }
}

export async function getPatronBrowseCategories(libraryUrl, patronId = null) {
     if (!patronId) {
          try {
               patronId = await AsyncStorage.getItem('@patronProfile');
          } catch (e) {
               console.log(e);
               patronId = null;
          }
     }

     if (patronId) {
          let browseCategories = [];
          const postBody = await postData();
          const api = create({
               baseURL: libraryUrl + '/API',
               timeout: GLOBALS.timeoutAverage,
               headers: getHeaders(true),
               auth: createAuthTokens(),
          });
          const responseHiddenCategories = await api.post('/UserAPI?method=getHiddenBrowseCategories', postBody);
          if (responseHiddenCategories.ok) {
               const hiddenCategories = [];

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
                    //console.log(hiddenCategories);
               }
               browseCategories = browseCategories.concat(hiddenCategories);
          } else {
               console.log(responseHiddenCategories);
          }

          const responseActiveCategories = await api.post('/SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true', postBody);
          if (responseActiveCategories.ok) {
               const categories = responseActiveCategories.data.result;
               const activeCategories = [];
               categories.map(function (category, index, array) {
                    const subCategories = category['subCategories'];
                    if (typeof subCategories !== 'undefined' && subCategories.length !== 0) {
                         subCategories.forEach((item) =>
                              activeCategories.push({
                                   key: item.key,
                                   title: item.title,
                              })
                         );
                    } else {
                         activeCategories.push({
                              key: category.key,
                              title: category.title,
                         });
                         if (typeof subCategories != 'undefined') {
                              if (subCategories.length !== 0) {
                                   subCategories.forEach((item) =>
                                        activeCategories.push({
                                             key: item.key,
                                             title: item.title,
                                        })
                                   );
                              } else {
                                   activeCategories.push({
                                        key: category.key,
                                        title: category.title,
                                   });
                              }
                         }
                    }
               });
               browseCategories = browseCategories.concat(activeCategories);
          } else {
               console.log(responseActiveCategories);
          }
          browseCategories = _.uniqBy(browseCategories, 'key');
          browseCategories = _.sortBy(browseCategories, 'title');
          await AsyncStorage.setItem('@patronBrowseCategories', JSON.stringify(browseCategories));
          return browseCategories;
     }
}

export async function getHiddenBrowseCategories(libraryUrl, patronId) {
     const postBody = await postData();
     const api = create({
          baseURL: libraryUrl + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=getHiddenBrowseCategories', postBody);
     if (response.ok) {
          const categories = response.data.result.categories;
          const hiddenCategories = [];
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

          await AsyncStorage.setItem('@hiddenBrowseCategories', JSON.stringify(hiddenCategories));
          return hiddenCategories;
     } else {
          console.log(response);
     }
}

export async function getLinkedAccounts(url = null) {
     let baseUrl = url ?? LIBRARY.url;
     const postBody = await postData();
     const api = create({
          baseURL: baseUrl + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=getLinkedAccounts', postBody);
     console.log(response.config);
     if (response.ok) {
          let accounts = [];
          if (!_.isUndefined(response.data.result.linkedAccounts)) {
               accounts = response.data.result.linkedAccounts;
               PATRON.linkedAccounts = accounts;
          }
          return accounts;
     } else {
          console.log(response);
     }
}

export async function getViewers() {
     const postBody = await postData();
     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=getViewers', postBody);
     if (response.ok) {
          const viewers = response.data.result.viewers;
          try {
               await AsyncStorage.setItem('@viewerAccounts', JSON.stringify(viewers));
          } catch (e) {
               console.log(e);
          }
          //console.log("Viewer accounts saved")
     } else {
          console.log(response);
     }
}

export async function getSavedSearches(libraryUrl) {
     const postBody = await postData();
     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/ListAPI?method=getSavedSearchesForLiDA&checkIfValid=false', postBody);
     if (response.ok) {
          //console.log(response);
          let savedSearches = [];
          if (response.data.result.success) {
               savedSearches = response.data.result.searches;
          }
          return savedSearches;
     } else {
          console.log(response);
     }
}

export async function getSavedSearchTitles(searchId, libraryUrl) {
     const postBody = await postData();
     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               searchId,
               numTitles: 30,
          },
     });
     const response = await api.post('/ListAPI?method=getSavedSearchTitles', postBody);
     if (response.ok) {
          let savedSearches = [];
          //console.log(response);
          if (response.data.result) {
               savedSearches = response.data.result;
          }
          return savedSearches;
     } else {
          console.log(response);
     }
}

export async function getBrowseCategoryListForUser(url = null) {
     const postBody = await postData();
     let baseUrl = url ?? LIBRARY.url;
     const discovery = create({
          baseURL: baseUrl,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
     });
     const response = await discovery.post('/API/SearchAPI?method=getBrowseCategoryListForUser', postBody);

     if (response.ok) {
          return _.sortBy(response.data.result, ['title']);
     } else {
          console.log(response);
          return false;
     }
}

export async function updateBrowseCategoryStatus(id, url = null) {
     const postBody = await postData();
     let baseUrl = url ?? LIBRARY.url;
     const discovery = create({
          baseURL: baseUrl,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          params: { browseCategoryId: id },
          auth: createAuthTokens(),
     });
     const response = await discovery.post(endpoint.url + 'updateBrowseCategoryStatus', postBody);
     if (response.ok) {
          return response.data.result;
     } else {
          console.log(response);
          return false;
     }
}