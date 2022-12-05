import { create } from 'apisauce';
import { GLOBALS } from '../globals';
import { createAuthTokens, ENDPOINT, getHeaders } from '../apiAuth';

const endpoint = ENDPOINT.search;
//const endpoint = ENDPOINT.work;

/** *******************************************************************
 * General
 ******************************************************************* **/
/**
 * Returns grouped work data for a given id
 * @param {string} itemId
 * @param {string} url
 **/
export async function getGroupedWork(itemId, url) {
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
          params: {
               id: itemId,
          },
     });
     const response = await discovery.get(`${endpoint.url}getAppGroupedWork`);
     if (response.ok) {
          return response.data;
     } else {
          console.log(response);
          return false;
     }
}