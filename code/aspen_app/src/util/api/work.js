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
 * @param {string} url
 **/
export async function getGroupedWork(itemId, url) {
     const { data } = await axios.get('/WorkAPI?method=getGroupedWork', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: itemId,
          },
     });

     const keys = _.keys(data.formats);
     const firstFormat = _.first(keys);
     const manifestation = await getManifestation(itemId, firstFormat, url);

     return {
          results: data,
          format: firstFormat ?? '',
          manifestation: manifestation ?? [],
     };
}