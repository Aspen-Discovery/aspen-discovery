import { GLOBALS } from '../globals';
import { createAuthTokens, getHeaders } from '../apiAuth';
import axios from 'axios';

/** *******************************************************************
 * General
 ******************************************************************* **/
/**
 * Returns manifestation data for the given grouped work id and format
 * @param {string} itemId
 * @param {string} format
 * @param {string} url
 **/
export async function getManifestation(itemId, format, url) {
     const { data } = await axios.get('/ItemAPI?method=getManifestation', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: itemId,
               format: format,
          },
     });

     return {
          id: data.id ?? itemId,
          format: data.format ?? format,
          manifestation: data.manifestation ?? [],
     };
}

/**
 * Returns variation data for the given grouped work id and format
 * @param {string} itemId
 * @param {string} format
 * @param {string} url
 **/
export async function getVariation(itemId, format, url) {
     const { data } = await axios.get('/ItemAPI?method=getVariation', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: itemId,
               format: format,
          },
     });

     return {
          id: data.result.id ?? itemId,
          format: data.result.format ?? format,
          variation: data.result.variation ?? [],
     };
}

/**
 * Returns record data for the given grouped work id and format
 * @param {string} itemId
 * @param {string} format
 * @param {string} url
 **/
export async function getRecords(itemId, format, url) {
     const { data } = await axios.get('/ItemAPI?method=getRecords', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: itemId,
               format: format,
          },
     });

     return {
          id: data.result.id ?? itemId,
          format: data.result.format ?? format,
          records: data.result.records ?? [],
     };
}