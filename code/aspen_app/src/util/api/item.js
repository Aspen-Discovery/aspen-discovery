import {GLOBALS} from '../globals';
import {createAuthTokens, getHeaders} from '../apiAuth';
import axios from 'axios';
import _ from 'lodash';

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
     const {data} = await axios.get('/ItemAPI?method=getManifestation', {
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
export async function getVariations(itemId, format, url) {
     const {data} = await axios.get('/ItemAPI?method=getVariations', {
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
          variations: data.variations ?? [],
          volumeInfo: {
               numItemsWithVolumes: data.numItemsWithVolumes,
               numItemsWithoutVolumes: data.numItemsWithoutVolumes,
               hasItemsWithoutVolumes: data.hasItemsWithoutVolumes,
               majorityOfItemsHaveVolumes: data.majorityOfItemsHaveVolumes,
          },
     };
}

/**
 * Returns record data for the given grouped work id and format
 * @param {string} itemId
 * @param {string} format
 * @param {string} source
 * @param {string} url
 **/
export async function getRecords(itemId, format, source, url) {
     const {data} = await axios.get('/ItemAPI?method=getRecords', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: itemId,
               format: format,
               source: source,
          },
     });

     return {
          id: data.id ?? itemId,
          format: data.format ?? format,
          records: data.records ?? [],
     };
}

/**
 * Returns item availability for the given record id
 * @param {string} recordId
 * @param {string} url
 **/
export async function getItemAvailability(recordId, url) {
     const {data} = await axios.get('/ItemAPI?method=getItemAvailability', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: recordId,
          },
     });

     return {
          id: data.id ?? recordId,
          holdings: data.holdings ?? [],
     };
}

export async function getFirstRecord(itemId, format, url) {
     const {data} = await axios.get('/ItemAPI?method=getRecords', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: itemId,
               format: format,
          },
     });

     let id = null;
     let source = 'ils';
     let record = null;
     if (data.records) {
          const records = data.records;
          const keys = Object.keys(records);
          let firstKey = _.toString(_.take(keys));
          id = records[firstKey].id;
          record = id;
          const recordId = _.split(id, ':');
          id = _.toString(recordId[1]);
          source = _.toString(recordId[0]);
     }
     return {
          id: id,
          source: source,
          record: record,
     };
}

export async function getVolumes(id, url) {
     const {data} = await axios.get('/ItemAPI?method=getVolumes', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: id,
          },
     });

     return data.volumes;
}