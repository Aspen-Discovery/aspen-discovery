import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'apisauce';
import _ from 'lodash';

// custom components and helper files
import { createAuthTokens, getHeaders, postData } from './apiAuth';
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
          const allHolds = response.data.result.holds;
          let holds;
          let holdsReady = [];
          let holdsNotReady = [];

          if (typeof allHolds !== 'undefined') {
               if (typeof allHolds.unavailable !== 'undefined') {
                    holdsNotReady = Object.values(allHolds.unavailable);
               }

               if (typeof allHolds.available !== 'undefined') {
                    holdsReady = Object.values(allHolds.available);
               }
          }

          holds = holdsReady.concat(holdsNotReady);
          PATRON.holds = holds;

          return [
               {
                    title: 'Ready',
                    data: holdsReady,
               },
               {
                    title: 'Pending',
                    data: holdsNotReady,
               }
          ]
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

export async function getBrowseCategoryListForUser(url = null) {
     const postBody = await postData();
     let baseUrl = url ?? LIBRARY.url;
     const discovery = create({
          baseURL: baseUrl,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               checkIfValid: false,
          },
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
          headers: getHeaders(true),
          params: { browseCategoryId: id },
          auth: createAuthTokens(),
     });
     const response = await discovery.post('/API/UserAPI?method=updateBrowseCategoryStatus', postBody);
     if (response.ok) {
          return response.data.result;
     } else {
          console.log(response);
          return false;
     }
}