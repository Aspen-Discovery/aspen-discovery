import { create } from 'apisauce';
import { createAuthTokens, getHeaders, postData } from '../apiAuth';
import { GLOBALS } from '../globals';

/** *******************************************************************
 * General
 ******************************************************************* **/

/**
 * Return the user's saved events
 * @param {number} page
 * @param {number} pageSize
 * @param {string} filter
 * @param {string} url
 * @param {string} language
 **/
export async function fetchSavedEvents(page = 1, pageSize = 25, filter = 'upcoming', url, language = 'en') {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               page: page,
               pageSize: pageSize,
               filter,
               language,
          },
     });

     const response = await api.post('/EventAPI?method=getSavedEvents', postBody);
     let data = [];
     let morePages = false;
     if (response.ok) {
          data = response.data;
          if (data.page_current !== data.page_total) {
               morePages = true;
          }
     }

     return {
          events: data.events ?? [],
          totalResults: data.totalResults ?? 0,
          curPage: data.page_current ?? 0,
          totalPages: data.page_total ?? 0,
          hasMore: morePages,
          filter: data.filter ?? filter,
          message: data?.message ?? null,
     };
}

/**
 * Returns event data for a given id
 * @param {string} id
 * @param {string} source
 * @param {string} language
 * @param {string} url
 **/
export async function getEventDetails(id, source, language, url) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               id: id,
               source: source,
               language,
          },
     });
     const response = await api.post('/EventAPI?method=getEventDetails', postBody);

     return {
          results: response.data,
     };
}

/**
 * Adds the given event to the user's Saved Events
 * @param {string} id
 * @param {string} language
 * @param {string} url
 **/
export async function saveEvent(id, language, url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               id,
               language,
          },
     });

     const response = await discovery.post('/EventAPI?method=saveEvent', postBody);
     if (response.ok) {
          if (response?.data) {
               return response.data;
          }
     } else {
          console.log(response);
     }
     return [];
}

/**
 * Removes the given event from the user's Saved Events
 * @param {string} id
 * @param {string} language
 * @param {string} url
 **/
export async function removeSavedEvent(id, language, url) {
     const postBody = await postData();
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               id,
               language,
          },
     });

     const response = await discovery.post('/EventAPI?method=removeSavedEvent', postBody);
     if (response.ok) {
          if (response?.data) {
               return response.data;
          }
     } else {
          console.log(response);
     }
     return [];
}