import _ from 'lodash';
import { GLOBALS } from '../globals';
import { createAuthTokens, getHeaders } from '../apiAuth';
import axios from 'axios';
import { getManifestation } from './item';

/** *******************************************************************
 * General
 ******************************************************************* **/
/**
 * Returns grouped work data for a given id
 * @param {string} itemId
 * @param {string} language
 * @param {string} url
 **/
export async function getGroupedWork(itemId, language, url) {
     const { data } = await axios.get('/WorkAPI?method=getGroupedWork', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: itemId,
               language
          },
     });

     const keys = _.keys(data.formats);
     const firstFormat = _.first(keys);
     const manifestation = await getManifestation(itemId, firstFormat, language, url);

     return {
          results: data,
          format: firstFormat ?? '',
          manifestation: manifestation ?? [],
     };
}