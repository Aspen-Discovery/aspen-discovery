import { createAuthTokens, ENDPOINT, getHeaders, postData } from '../apiAuth';
import { LIBRARY } from '../loadLibrary';
import { GLOBALS } from '../globals';
import { popAlert } from '../../components/loadError';
import { PATRON } from '../loadPatron';
import _ from 'lodash';
import React from 'react';
import { create } from 'apisauce';

const endpoint = ENDPOINT.list;

/**
 * Returns array of basic details about a given user list
 * @param {array} id
 * @param {string} url
 **/
export async function getListDetails(id, url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          params: { id: id },
          auth: createAuthTokens(),
     });
     const response = await discovery.post(`${endpoint.url}getListDetails`, postBody);
     if (response.ok) {
          return response.data?.result ?? [];
     } else {
          console.log(response);
          return false;
     }
}

/**
 * Returns all lists for a given user
 * @param {string} url
 **/
export async function getLists(url) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
     });
     const response = await api.post('/ListAPI?method=getUserLists&checkIfValid=false', postBody);
     if (response.ok) {
          let lists = [];
          if (response.data.result.success) {
               if (!_.isUndefined(response.data.result.lists)) {
                    lists = _.sortBy(response.data.result.lists, ['title']);
               }
          }
          PATRON.lists = lists;
          return lists;
     } else {
          //console.log(response);
     }
}

/**
 * Create a new list for a user
 * @param {string} title
 * @param {string} description
 * @param {boolean} isPublic
 * @param {string} url
 **/
export async function createList(title, description, isPublic = false, url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               title,
               description,
               isPublic,
          },
     });
     const response = await discovery.post(`${endpoint.url}createList`, postBody);
     if (response.ok) {
          console.log(response.config);
          if (response.data.result.listId) {
               PATRON.listLastUsed = response.data.result.listId;
          }
          return response.data.result;
     } else {
          console.log(response);
     }
}

export async function createListFromTitle(title, description, access, items) {
     const postBody = await postData();
     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               title,
               description,
               access,
               recordIds: items,
          },
     });
     const response = await api.post('/ListAPI?method=createList', postBody);
     if (response.ok) {
          if (response.data.result.listId) {
               PATRON.listLastUsed = response.data.result.listId;
          }

          let status = 'success';
          let alertTitle = 'Success';
          if (!response.data.result.success) {
               status = 'danger';
               alertTitle = 'Error';
          }

          if (response.data.result.numAdded) {
               popAlert(alertTitle, response.data.result.numAdded + ' added to ' + title, status);
          } else {
               popAlert(alertTitle, 'Title added to ' + title, status);
          }
          return response.data.result;
     } else {
          console.log(response);
     }
}

export async function editList(listId, title, description, access, url) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               id: listId,
               title,
               description,
               public: access,
          },
     });
     const response = await api.post('/ListAPI?method=editList', postBody);
     if (response.ok) {
          PATRON.listLastUsed = listId;
          return response.data;
     } else {
          console.log(response);
     }
}

export async function clearListTitles(listId, url) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: { listId },
     });
     const response = await api.post('/ListAPI?method=clearListTitles', postBody);
     if (response.ok) {
          PATRON.listLastUsed = listId;
          return response.data;
     } else {
          console.log(response);
     }
}

export async function addTitlesToList(id, itemId, url) {
     const postBody = await postData();
     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               listId: id,
               recordIds: itemId,
          },
     });
     const response = await api.post('/ListAPI?method=addTitlesToList', postBody);
     if (response.ok) {
          PATRON.listLastUsed = id;
          if (response.data.result.success) {
               popAlert('Success', response.data.result.numAdded + ' added to list', 'success');
          } else {
               popAlert('Error', 'Unable to add item to list', 'error');
          }
          return response.data.result;
     } else {
          console.log(response);
     }
}

export async function getListTitles(id, url, page, pageSize = 25, numTitles = 25, sort = 'dateAdded') {
     let morePages = false;
     let totalResults = 0;
     let curPage = page;
     let totalPages = 0;
     let titles = [];
     let message = null;

     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               id: id,
               page: page,
               pageSize: pageSize,
               numTitles: numTitles,
               sort_by: sort,
          },
     });
     const response = await api.post('/ListAPI?method=getListTitles', postBody);
     if (response.ok) {
          const data = response.data;
          morePages = true;
          if (data.result?.page_current === data.result?.page_total) {
               morePages = false;
          }
          titles = data.result?.titles ?? [];
          totalResults = data.result?.totalResults ?? 0;
          curPage = data.result?.page_current ?? 0;
          totalPages = data.result?.page_total ?? 0;
          message = data.result?.message ?? null;
     } else {
          console.log(response);
     }

     return {
          listTitles: titles,
          totalResults: totalResults,
          curPage: curPage,
          totalPages: totalPages,
          hasMore: morePages,
          sort: sort,
          message: message,
     };
}

export async function removeTitlesFromList(listId, title, url) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               listId,
               recordIds: title,
          },
     });
     const response = await api.post('/ListAPI?method=removeTitlesFromList', postBody);
     if (response.ok) {
          PATRON.listLastUsed = listId;
          return response.data.result;
     } else {
          console.log(response);
     }
}

export async function deleteList(listId, libraryUrl) {
     const postBody = await postData();
     const api = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: { id: listId },
     });
     const response = await api.post('/ListAPI?method=deleteList', postBody);
     //console.log(response);
     if (response.ok) {
          return response.data.result;
     } else {
          console.log(response);
     }
}