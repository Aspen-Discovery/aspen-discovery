import React from 'react';
import { createAuthTokens, ENDPOINT, getHeaders, postData } from '../apiAuth';
import { GLOBALS } from '../globals';
import _ from 'lodash';
import i18n from 'i18n-js';

import { create } from 'apisauce';
import { PATRON } from '../loadPatron';
import { popAlert } from '../../components/loadError';
import { LIBRARY } from '../loadLibrary';
import { SEARCH } from '../search';
import axios from 'axios';

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
               reload: false,
               checkIfValid: false,
          },
     });
     const response = await discovery.post(`${endpoint.url}getPatronProfile`, postBody);
     if (response.ok) {
          if (response.data.result) {
               //console.log(response.data.result.profile);
               return response.data.result.profile;
          }
     }
     return [];
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
               return response.data.result.profile;
          }
     }
     return [];
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
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const results = await discovery.post('/UserAPI?method=validateAccount', postBody);
     if (results.ok) {
          return results.data.result;
     }
}

/**
 * Checks if the user has an active Aspen Discovery session
 * @param {string} url
 **/
export async function isLoggedIn(url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
     });
     const response = await discovery.post(`${endpoint.url}isLoggedIn`, postBody);
     if (response.ok) {
          return response.data.result;
     } else {
          console.log(response);
          return false;
     }
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
 **/
export async function getPatronHolds(readySort='expire', pendingSort='sortTitle', holdSource = 'all', url, refresh = true) {
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
          },
     });
     const response = await discovery.post('/UserAPI?method=getPatronHolds', postBody);
     if (response.ok) {
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

/**
 * Return a list of current checkouts for a user
 * @param {string} source
 * @param {string} url
 * @param {boolean} refresh
 **/
export async function getPatronCheckedOutItems(source = 'all', url, refresh = true) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               source: source,
               linkedUsers: true,
               refreshCheckouts: refresh
          },
     });
     const response = await discovery.post('/UserAPI?method=getPatronCheckedOutItems', postBody);
     if (response.ok) {
          let items = response.data?.result?.checkedOutItems ?? [];
          items = _.sortBy(items, ['daysUntilDue', 'title']);
          return items;
     } else {
          console.log(response);
          return []
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
 **/
export async function showBrowseCategory(categoryId, patronId, url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
          params: {
               browseCategoryId: categoryId,
               patronId: patronId,
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
 **/
export async function hideBrowseCategory(categoryId, patronId, url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
          params: {
               browseCategoryId: categoryId,
               patronId: patronId,
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
 * @param {string} url
 **/
export async function getLinkedAccounts(url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await discovery.post('/UserAPI?method=getLinkedAccounts', postBody);
     if (response.ok) {
          let accounts = [];
          if (!_.isUndefined(response.data.result.linkedAccounts)) {
               accounts = response.data.result.linkedAccounts;
               PATRON.linkedAccounts = accounts;
          }
          return _.values(accounts);
     } else {
          console.log(response);
          return false;
     }
}

/**
 * Return a list of accounts that the user has been linked to by another user
 * @param {string} url
 **/
export async function getViewerAccounts(url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await discovery.post('/UserAPI?method=getViewers', postBody);
     if (response.ok) {
          console.log(_.values(response.data.result.viewers));
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
 **/
export async function addLinkedAccount(username='', password='', url) {
     const postBody = await postData();
     postBody.append('accountToLinkUsername', username);
     postBody.append('accountToLinkPassword', password);
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await discovery.post('/UserAPI?method=addAccountLink', postBody);
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
 * Remove an account that the user has created a link to
 * @param {string} patronToRemove
 * @param {string} url
 **/
export async function removeLinkedAccount(patronToRemove, url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               idToRemove: patronToRemove,
          },
     });
     const response = await discovery.post('/UserAPI?method=removeAccountLink', postBody);
     if (response.ok) {
          let status = false;
          if (!_.isUndefined(response.data.result.success)) {
               status = response.data.result.success;
               if (status !== true) {
                    popAlert(response.data.result.title, response.data.result.message, 'success');
               } else {
                    popAlert(response.data.result.title, response.data.result.message, 'error');
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
 **/
export async function removeViewerAccount(patronToRemove, url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               idToRemove: patronToRemove,
          },
     });
     const response = await discovery.post('/UserAPI?method=removeViewerLink', postBody);
     console.log(response);
     if (response.ok) {
          let status = false;
          if (!_.isUndefined(response.data.result.success)) {
               status = response.data.result.success;
               if (status !== true) {
                    popAlert(response.data.result.title, response.data.result.message, 'success');
               } else {
                    popAlert(response.data.result.title, response.data.result.message, 'error');
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
 **/
export async function saveLanguage(code, url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               languageCode: code,
          },
     });
     const response = await discovery.post('/UserAPI?method=saveLanguage', postBody);
     if (response.ok) {
          i18n.locale = code;
          PATRON.language = code;
          return code;
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
 **/
export async function fetchReadingHistory(page = 1, pageSize = 25, sort = 'checkedOut', url) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               page: page,
               pageSize: pageSize,
               sort_by: sort,
          },
     });

     const response = await api.post('/UserAPI?method=getPatronReadingHistory', postBody);

     let data = [];
     let morePages = false;
     if(response.ok) {
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
 **/
export async function optIntoReadingHistory(url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
     });
     const response = await discovery.post("/UserAPI?method=optIntoReadingHistory", postBody);
     if (response.ok) {
          return true;
     }
     return false;
}

/**
 * Disable reading history for the user
 * @param {string} url
 **/
export async function optOutOfReadingHistory(url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
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
 **/
export async function deleteAllReadingHistory(url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
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
 **/
export async function deleteSelectedReadingHistory(item, url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               selected: item,
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