import { create } from 'apisauce';
import i18n from 'i18n-js';
import _ from 'lodash';
import { popAlert } from '../../components/loadError';
import { createAuthTokens, ENDPOINT, getHeaders, postData } from '../apiAuth';
import { GLOBALS } from '../globals';
import { PATRON } from '../loadPatron';

const endpoint = ENDPOINT.user;

/** *******************************************************************
 * General
 ******************************************************************* **/
/**
 * Returns profile information for a given user
 * @param {string} url
 **/
export async function refreshProfile(url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
          params: {
               linkedUsers: true,
               checkIfValid: false,
          },
     });
     const response = await discovery.post(`${endpoint.url}getPatronProfile`, postBody);
     console.log(response);
     if (response.ok) {
          if (response.data?.result) {
               //console.log(response.data.result.profile);
               if (response.data?.result?.profile) {
                    return response.data.result.profile;
               } else {
                    return response.data.result;
               }
          }
     }
     return {
          success: false,
     };
}

/**
 * Returns profile information for a given user (force refresh)
 * @param {string} url
 **/
export async function reloadProfile(url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
          params: {
               linkedUsers: true,
               reload: true,
               checkIfValid: false,
          },
     });
     const response = await discovery.post(`${endpoint.url}getPatronProfile`, postBody);
     if (response.ok) {
          if (response.data.result) {
               //console.log(response.data.result.profile);
               if (response.data?.result?.profile) {
                    return response.data.result.profile;
               } else {
                    return response.data.result;
               }
          }
     }
     return {
          success: false,
     };
}

/**
 * Validates the given credentials to initiate logging into Aspen LiDA. For Discovery 23.02.00 and later.
 * @param {string} username
 * @param {password} password
 * @param {string} url
 **/
export async function loginToLiDA(username, password, url) {
     const postBody = new FormData();
     postBody.append('username', username);
     postBody.append('password', password);
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const results = await discovery.post('/UserAPI?method=loginToLiDA', postBody);
     if (results.ok) {
          return results.data.result;
     }
}

/**
 * Validates the given credentials to initiate logging into Aspen LiDA. For Discovery 23.01.00 and older.
 * @param {string} username
 * @param {string} password
 * @param {string} url
 **/
export async function validateUser(username, password, url) {
     const postBody = new FormData();
     postBody.append('username', username);
     postBody.append('password', password);
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const results = await discovery.post('/UserAPI?method=validateAccount', postBody);
     if (results.ok) {
          return results.data.result;
     }
}

/**
 * Validates the given session to see if still valid in Discovery.
 * @param {string} url
 **/
export async function validateSession(url) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=validateSession', postBody);
     if (response.ok) {
          if (response?.data?.result) {
               return response.data.result;
          }
     } else {
          console.log(response);
     }
     return [];
}

/**
 * Revalidates the stored user details.
 * @param {string} url
 **/
export async function revalidateUser(url) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/UserAPI?method=validateUserCredentials', postBody);
     if (response.ok) {
          if (response?.data?.result?.valid) {
               return response.data.result.valid;
          } else {
               console.log(response.data);
          }
     } else {
          console.log(response);
     }
     return false;
}

/**
 * Logout the user and end the Aspen Discovery session
 **/
export async function logoutUser(url) {
     const api = create({
          baseURL: url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
     });
     const response = await api.get(`${endpoint.url}logout`);
     if (response.ok) {
          return response.data;
     } else {
          console.log(response);
          return false;
     }
}

/**
 * Updates the users alternate library card
 * @param {string} cardNumber
 * @param {string} cardPassword
 * @param {boolean} deleteCard
 * @param {string} url
 * @param {string} language
 **/
export async function updateAlternateLibraryCard(cardNumber = '', cardPassword = '', deleteCard = false, url, language = 'en') {
     const postBody = await postData();
     postBody.append('alternateLibraryCard', cardNumber);
     postBody.append('alternateLibraryCardPassword', cardPassword);

     const api = create({
          baseURL: url + '/API',
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               deleteAlternateLibraryCard: deleteCard,
               language,
          },
     });

     const response = await api.post('/UserAPI?method=updateAlternateLibraryCard', postBody);
     let data = [];
     if (response.ok) {
          data = response.data;
     }

     return {
          success: data?.success ?? false,
          title: data?.title ?? null,
          message: data?.message ?? null,
     };
}

/** *******************************************************************
 * Checkouts and Holds
 ******************************************************************* **/
/**
 * Return a list of current holds for a user
 * @param {string} readySort
 * @param {string} pendingSort
 * @param {string} holdSource
 * @param {string} url
 * @param {boolean} refresh
 * @param {string} language
 **/
export async function getPatronHolds(readySort = 'expire', pendingSort = 'sortTitle', holdSource = 'all', url, refresh = true, language = 'en') {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               source: holdSource,
               linkedUsers: true,
               refreshHolds: refresh,
               unavailableSort: pendingSort,
               availableSort: readySort,
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=getPatronHolds', postBody);
     if (response.ok) {
          const allHolds = response.data.result.holds;
          let holds;
          let holdsReady = [];
          let holdsNotReady = [];

          let pendingSortMethod = pendingSort;
          if (pendingSort === 'sortTitle') {
               pendingSortMethod = 'title';
          } else if (pendingSort === 'libraryAccount') {
               pendingSortMethod = 'user';
          }

          let readySortMethod = readySort;
          if (readySort === 'sortTitle') {
               readySortMethod = 'title';
          } else if (readySort === 'libraryAccount') {
               readySortMethod = 'user';
          }

          if (typeof allHolds !== 'undefined') {
               if (typeof allHolds.unavailable !== 'undefined') {
                    holdsNotReady = Object.values(allHolds.unavailable);
                    if (pendingSortMethod === 'position') {
                         holdsNotReady = _.orderBy(holdsNotReady, [pendingSortMethod], ['desc']);
                    }
                    holdsNotReady = _.orderBy(holdsNotReady, [pendingSortMethod], ['asc']);
               }

               if (typeof allHolds.available !== 'undefined') {
                    holdsReady = Object.values(allHolds.available);
                    holdsReady = _.orderBy(holdsReady, [readySortMethod], ['asc']);
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
               },
          ];
     } else {
          console.log(response);
          return {
               holds: [],
               holdsReady: [],
               holdsNotReady: [],
          };
     }
}

/**
 * Return a list of current checkouts for a user
 * @param {string} source
 * @param {string} url
 * @param {boolean} refresh
 * @param {string} language
 **/
export async function getPatronCheckedOutItems(source = 'all', url, refresh = true, language = 'en') {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               source: source,
               linkedUsers: true,
               refreshCheckouts: refresh,
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=getPatronCheckedOutItems', postBody);
     if (response.ok) {
          let items = response.data?.result?.checkedOutItems ?? [];
          items = _.sortBy(items, ['daysUntilDue', 'title']);
          return items;
     } else {
          console.log(response);
          return [];
     }
}

/** *******************************************************************
 * Browse Category Management
 ******************************************************************* **/
/**
 * Show a hidden browse category for a user
 * @param {string} categoryId
 * @param {string} patronId
 * @param {string} url
 * @param {string} language
 **/
export async function showBrowseCategory(categoryId, patronId, url, language = 'en') {
     const postBody = await postData();
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
          params: {
               browseCategoryId: categoryId,
               patronId: patronId,
               language,
          },
     });
     const response = await discovery.post(`${endpoint.url}showBrowseCategory`, postBody);
     if (response.ok) {
          return response.data;
     } else {
          console.log(response);
          return false;
     }
}

/**
 * Dismiss a browse category for a user
 * @param {string} categoryId
 * @param {string} patronId
 * @param {string} url
 * @param {string} language
 **/
export async function hideBrowseCategory(categoryId, patronId, url, language = 'en') {
     const postBody = await postData();
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
          params: {
               browseCategoryId: categoryId,
               patronId: patronId,
               language,
          },
     });
     const response = await discovery.post(`${endpoint.url}dismissBrowseCategory`, postBody);
     if (response.ok) {
          return response.data;
     } else {
          console.log(response);
          return false;
     }
}

/** *******************************************************************
 * Linked Accounts
 ******************************************************************* **/
/**
 * Return a list of accounts that the user has initiated account linking with
 * @param {array} primaryUser
 * @param {array} cards
 * @param {string} barcodeStyle
 * @param {string} url
 * @param {string} language
 * @return array
 **/
export async function getLinkedAccounts(primaryUser, cards, barcodeStyle, url, language = 'en') {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=getLinkedAccounts', postBody);
     if (response.ok) {
          let count = 1;
          let cardStack = [];
          let accounts = [];
          const primaryCard = {
               key: 0,
               displayName: primaryUser.displayName,
               userId: primaryUser.id,
               ils_barcode: primaryUser.ils_barcode ?? primaryUser.cat_username,
               expired: primaryUser.expired,
               expires: primaryUser.expires,
               barcodeStyle: barcodeStyle,
          };
          cardStack.push(primaryCard);
          if (!_.isUndefined(response.data.result.linkedAccounts)) {
               accounts = _.values(response.data.result.linkedAccounts);
               PATRON.linkedAccounts = accounts;
               if (_.size(accounts) >= 1) {
                    accounts.forEach((account) => {
                         if (_.includes(cards, account.ils_barcode) === false) {
                              count = count + 1;
                              const card = {
                                   key: count,
                                   displayName: account.displayName,
                                   userId: account.id,
                                   ils_barcode: account.ils_barcode ?? account.barcode,
                                   expired: account.expired,
                                   expires: account.expires,
                                   barcodeStyle: account.barcodeStyle ?? barcodeStyle,
                              };
                              cardStack.push(card);
                         } else if (_.includes(cards, account.cat_username) === false) {
                              count = count + 1;
                              const card = {
                                   key: count,
                                   displayName: account.displayName,
                                   userId: account.id,
                                   cat_username: account.cat_username ?? account.barcode,
                                   expired: account.expired,
                                   expires: account.expires,
                                   barcodeStyle: account.barcodeStyle ?? barcodeStyle,
                              };
                              cardStack.push(card);
                         }
                    });
               }
          }
          return {
               accounts: accounts ?? [],
               cards: cardStack ?? [],
          };
     } else {
          console.log(response);
          return false;
     }
}

/**
 * Return a list of accounts that the user has been linked to by another user
 * @param {string} url
 * @param {string} language
 **/
export async function getViewerAccounts(url, language = 'en') {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=getViewers', postBody);
     if (response.ok) {
          let viewers = [];
          if (!_.isUndefined(response.data.result.viewers)) {
               viewers = response.data.result.viewers;
               PATRON.viewerAcccounts = viewers;
          }
          return _.values(viewers);
     } else {
          console.log(response);
          return false;
     }
}

/**
 * Add an account that the user wants to create a link to
 * @param {string} username
 * @param {string} password
 * @param {string} url
 * @param {string} language
 **/
export async function addLinkedAccount(username = '', password = '', url, language = 'en') {
     const postBody = await postData();
     postBody.append('accountToLinkUsername', username);
     postBody.append('accountToLinkPassword', password);
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=addAccountLink', postBody);
     if (response.ok) {
          let status = false;
          if (!_.isUndefined(response.data.result.success)) {
               status = response.data.result.success;
               if (status !== true) {
                    popAlert(response.data.result.title, response.data.result.message, 'error');
               } else {
                    try {
                         popAlert(response.data.result.title, response.data.result.message, 'success');
                    } catch (e) {
                         console.log(e);
                    }
               }
          }
          return status;
     } else {
          console.log(response);
          return false;
     }
}

/**
 * Remove an account that the user has created a link to
 * @param {string} patronToRemove
 * @param {string} url
 * @param {string} language
 **/
export async function removeLinkedAccount(patronToRemove, url, language) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               idToRemove: patronToRemove,
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=removeAccountLink', postBody);
     if (response.ok) {
          let status = false;
          if (!_.isUndefined(response.data.result.success)) {
               status = response.data.result.success;
               if (status !== true) {
                    popAlert(response.data.result.title, response.data.result.message, 'error');
               } else {
                    try {
                         popAlert(response.data.result.title, response.data.result.message, 'success');
                    } catch (e) {
                         console.log(e);
                    }
               }
          }
          return status;
     } else {
          console.log(response);
          return false;
     }
}

/**
 * Remove an account that another user has created a link to
 * @param {string} patronToRemove
 * @param {string} url
 * @param {string} language
 **/
export async function removeViewerAccount(patronToRemove, url, language = 'en') {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               idToRemove: patronToRemove,
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=removeViewerLink', postBody);
     if (response.ok) {
          let status = false;
          if (!_.isUndefined(response.data.result.success)) {
               status = response.data.result.success;
               if (status !== true) {
                    popAlert(response.data.result.title, response.data.result.message, 'error');
               } else {
                    popAlert(response.data.result.title, response.data.result.message, 'success');
               }
          }
          return status;
     } else {
          console.log(response);
          return false;
     }
}

/**
 * Disables a users ability to use linked accounts
 * @param {string} language
 * @param {string} url
 **/
export async function disableAccountLinking(language, url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=disableAccountLinking', postBody);
     if (response.ok) {
          let status = false;
          if (!_.isUndefined(response.data.result.success)) {
               status = response.data.result.success;
               if (status !== true) {
                    popAlert(response.data.result.title, response.data.result.message, 'error');
               } else {
                    popAlert(response.data.result.title, response.data.result.message, 'success');
               }
          }
          return status;
     } else {
          console.log(response);
          return false;
     }
}

/**
 * Re-enables a users ability to use linked accounts
 * @param {string} language
 * @param {string} url
 **/
export async function enableAccountLinking(language, url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=enableAccountLinking', postBody);
     if (response.ok) {
          let status = false;
          if (!_.isUndefined(response.data.result.success)) {
               status = response.data.result.success;
               if (status !== true) {
                    popAlert(response.data.result.title, response.data.result.message, 'error');
               } else {
                    popAlert(response.data.result.title, response.data.result.message, 'success');
               }
          }
          return status;
     } else {
          console.log(response);
          return false;
     }
}

/** *******************************************************************
 * Translations / Languages
 ******************************************************************* **/
/**
 * Update the user's language preference
 * @param {string} code
 * @param {string} url
 * @param {string} language
 **/
export async function saveLanguage(code, url, language = 'en') {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               languageCode: code,
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=saveLanguage', postBody);
     if (response.ok) {
          //i18n.locale = code;
          PATRON.language = code;
          return true;
     } else {
          console.log(response);
          return false;
     }
}

/** *******************************************************************
 * Reading History
 ******************************************************************* **/
/**
 * Return the user's reading history
 * @param {number} page
 * @param {number} pageSize
 * @param {string} sort
 * @param {string} url
 * @param {string} language
 **/
export async function fetchReadingHistory(page = 1, pageSize = 25, sort = 'checkedOut', url, language = 'en') {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               page: page,
               pageSize: pageSize,
               sort_by: sort,
               language,
          },
     });

     const response = await api.post('/UserAPI?method=getPatronReadingHistory', postBody);

     let data = [];
     let morePages = false;
     if (response.ok) {
          data = response.data;
          if (data.result?.page_current !== data.result?.page_total) {
               morePages = true;
          }
     }

     return {
          history: data.result?.readingHistory ?? [],
          totalResults: data.result?.totalResults ?? 0,
          curPage: data.result?.page_current ?? 0,
          totalPages: data.result?.page_total ?? 0,
          hasMore: morePages,
          sort: data.result?.sort ?? 'checkedOut',
          message: data.data?.message ?? null,
     };
}

/**
 * Enable reading history for the user
 * @param {string} url
 * @param {string} language
 **/
export async function optIntoReadingHistory(url, language = 'en') {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
          params: {
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=optIntoReadingHistory', postBody);
     if (response.ok) {
          return true;
     }
     return false;
}

/**
 * Disable reading history for the user
 * @param {string} url
 * @param {string} language
 **/
export async function optOutOfReadingHistory(url, language = 'en') {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=optOutOfReadingHistory', postBody);
     if (response.ok) {
          console.log(response.data);
          return true;
     }
     return false;
}

/**
 * Delete all reading history for the user
 * @param {string} url
 * @param {string} language
 **/
export async function deleteAllReadingHistory(url, language = 'en') {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=deleteAllFromReadingHistory', postBody);
     if (response.ok) {
          console.log(response.data);
          if (response.data.result?.success) {
               return true;
          }
     }
     return false;
}

/**
 * Delete selected reading history for the user
 * @param {string} item
 * @param {string} url
 * @param {string} language
 **/
export async function deleteSelectedReadingHistory(item, url, language = 'en') {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               selected: item,
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=deleteSelectedFromReadingHistory', postBody);
     if (response.ok) {
          if (response.data.result?.success) {
               return true;
          }
     }
     return false;
}

/** *******************************************************************
 * Saved Searches
 ******************************************************************* **/
/**
 * Return a list of the user's saved searches
 * @param {string} url
 * @param {string} language
 **/
export async function fetchSavedSearches(url, language = 'en') {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               checkIfValid: false,
               language,
          },
     });

     const response = await api.post('/ListAPI?method=getSavedSearchesForLiDA', postBody);

     if (response.ok) {
          return response.data.result.searches;
     }

     return {
          success: false,
          count: 0,
          countNewResults: 0,
          searches: [],
     };
}

/**
 * Return a list of titles from a given saved search
 * @param {string} id
 * @param {string} language
 * @param {string} url
 **/
export async function getSavedSearch(id, language = 'en', url) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               searchId: id,
               numTitles: 30,
               language: language,
          },
     });
     const response = await api.post('/ListAPI?method=getSavedSearchTitles', postBody);
     if (response.ok) {
          return response.data?.result ?? [];
     } else {
          return [];
     }
}

/** *******************************************************************
 * Notifications
 ******************************************************************* **/
/**
 * Update the status on if the user should be prompted for notification onboarding
 * @param {boolean} status
 * @param {string} token
 * @param {string} url
 * @param {string} language
 **/
export async function updateNotificationOnboardingStatus(status, token, url, language = 'en') {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               status,
               token,
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=updateNotificationOnboardingStatus', postBody);
     if (response.ok) {
          let wasUpdated = false;
          if (!_.isUndefined(response.data.result.success)) {
               wasUpdated = response.data.result.success;
               if (wasUpdated === true || wasUpdated === 'true') {
                    return true;
               }
          }
          return false;
     } else {
          console.log(response);
          return false;
     }
}

export async function getAppPreferencesForUser(url, language) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=getAppPreferencesForUser', postBody);
     if (response.ok) {
          return response.data.result;
     }

     return {
          success: false,
          onboardAppNotifications: 0,
          shouldAskBrightness: 0,
          notification_preferences: [
               {
                    device: 'Unknown',
                    token: false,
                    notifySavedSearch: 0,
                    notifyCustom: 0,
                    notifyAccount: 0,
                    onboardStatus: 0,
               },
          ],
     };
}

/**
 * Return the user's notification history
 * @param {number} page
 * @param {number} pageSize
 * @param {boolean} forceUpdate
 * @param {string} url
 * @param {string} language
 **/
export async function fetchNotificationHistory(page = 1, pageSize = 20, forceUpdate = false, url, language = 'en') {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               page: page,
               pageSize: pageSize,
               forceUpdate,
               language,
          },
     });

     const response = await api.post('/UserAPI?method=getInbox', postBody);
     let data = [];
     let morePages = false;
     if (response.ok) {
          data = response.data.result;
          if (data.page_current !== data.page_total) {
               morePages = true;
          }
     }

     return {
          inbox: data.inbox ?? [],
          totalResults: data.totalResults ?? 0,
          curPage: data.page_current ?? 0,
          totalPages: data.page_total ?? 0,
          hasMore: morePages,
          message: data?.message ?? null,
     };
}

/**
 * Update the status of a message to being read
 * @param {string} id
 * @param {string} url
 * @param {string} language
 **/
export async function markMessageAsRead(id, url, language = 'en') {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               id,
               language,
          },
     });

     const response = await api.post('/UserAPI?method=markMessageAsRead', postBody);
     let data = [];
     if (response.ok) {
          data = response.data;
     }

     return {
          success: data?.success ?? false,
          title: data?.title ?? null,
          message: data?.message ?? null,
     };
}

/**
 * Update the status of a message to being unread
 * @param {string} id
 * @param {string} url
 * @param {string} language
 **/
export async function markMessageAsUnread(id, url, language = 'en') {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               id,
               language,
          },
     });

     const response = await api.post('/UserAPI?method=markMessageAsUnread', postBody);
     let data = [];
     if (response.ok) {
          data = response.data;
     }

     return {
          success: data?.success ?? false,
          title: data?.title ?? null,
          message: data?.message ?? null,
     };
}

/** *******************************************************************
 * Screen Brightness
 ******************************************************************* **/
/**
 * Update the status on if the user should be prompted for providing screen brightness permissions
 * @param {boolean} status
 * @param {string} url
 * @param {string} language
 **/
export async function updateScreenBrightnessStatus(status, url, language = 'en') {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               status,
               language,
          },
     });
     const response = await discovery.post('/UserAPI?method=updateScreenBrightnessStatus', postBody);
     if (response.ok) {
          let wasUpdated = false;
          if (!_.isUndefined(response.data.result.success)) {
               wasUpdated = response.data.result.success;
               if (wasUpdated === true || wasUpdated === 'true') {
                    return true;
               }
          }
          return false;
     } else {
          console.log(response);
          return false;
     }
}