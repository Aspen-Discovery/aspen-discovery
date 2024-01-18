import { create } from 'apisauce';
import axios from 'axios';
import { createAuthTokens, getHeaders, postData } from '../apiAuth';
import { GLOBALS } from '../globals';

/** *******************************************************************
 * General
 ******************************************************************* **/
/**
 * Returns event data for a given id
 * @param {string} id
 * @param {string} source
 * @param {string} language
 * @param {string} url
 **/
export async function getEventDetails(id, source, language, url) {
     const { data } = await axios.get('/EventAPI?method=getEventDetails', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: id,
               source: source,
               language,
          },
     });

     return {
          results: data,
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
          if (response?.data?.result) {
               return response.data.result;
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
          if (response?.data?.result) {
               return response.data.result;
          }
     } else {
          console.log(response);
     }
     return [];
}