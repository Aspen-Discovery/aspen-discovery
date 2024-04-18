import { GLOBALS } from '../globals';
import { createAuthTokens, getHeaders } from '../apiAuth';
import axios from 'axios';
import { create } from 'apisauce';
import _ from 'lodash';
import { getVariableTermFromDictionary } from '../../translations/TranslationService';

/** *******************************************************************
 * General
 ******************************************************************* **/
/**
 * Returns manifestation data for the given grouped work id and format
 * @param {string} itemId
 * @param {string} format
 * @param {string} language
 * @param {string} url
 **/
export async function getManifestation(itemId, format, language, url) {
     const { data } = await axios.get('/ItemAPI?method=getManifestation', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: itemId,
               format: format,
               language,
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
 * @param {string} language
 * @param {string} url
 * @param {array} variation
 **/
export async function getVariations(itemId, format, language, url, variation) {
     console.log(variation);
     let recordId = null;
     if (variation.recordId) {
          recordId = variation.recordId;
     }

     const { data } = await axios.get('/ItemAPI?method=getVariations', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: itemId,
               format: format,
               language,
               recordId,
          },
     });

     return {
          id: data.id ?? itemId,
          format: data.format ?? format,
          variations: data.variations ?? [],
          volumeInfo: {
               numItemsWithVolumes: data.numItemsWithVolumes ?? 0,
               numItemsWithoutVolumes: data.numItemsWithoutVolumes ?? 0,
               hasItemsWithoutVolumes: data.hasItemsWithoutVolumes ?? 0,
               majorityOfItemsHaveVolumes: data.majorityOfItemsHaveVolumes ?? false,
               alwaysPlaceVolumeHoldWhenVolumesArePresent: data.alwaysPlaceVolumeHoldWhenVolumesArePresent ?? false,
          },
     };
}

/**
 * Returns record data for the given grouped work id and format
 * @param {string} itemId
 * @param {string} format
 * @param {string} source
 * @param {string} language
 * @param {string} url
 **/
export async function getRecords(itemId, format, source, language, url) {
     const { data } = await axios.get('/ItemAPI?method=getRecords', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: itemId,
               format: format,
               source: source,
               language,
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
     const { data } = await axios.get('/ItemAPI?method=getItemAvailability', {
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

export async function getFirstRecord(itemId, format, language, url) {
     const { data } = await axios.get('/ItemAPI?method=getRecords', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: itemId,
               format: format,
               language,
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
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: id,
          },
     });
     const response = await api.get('/ItemAPI?method=getVolumes', {
          id,
     });
     let volumes = [];
     if (response.ok) {
          if (response.data?.volumes) {
               volumes = _.sortBy(response.data.volumes, 'key');
          }
     }

     return volumes;
}

export async function getBasicItemInfo(id, url) {
     const { data } = await axios.get('/ItemAPI?method=getBasicItemInfo', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: id,
          },
     });

     return data;
}

export async function getRelatedRecord(id, recordId, format, url) {
     const { data } = await axios.get('/ItemAPI?method=getRelatedRecord', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: id,
               record: recordId,
               format: format,
          },
     });

     return {
          id: data.id ?? id,
          recordId: data.record ?? recordId,
          format: data.format ?? format,
          manifestation: data.record ?? [],
     };
}

/**
 * Returns copies data for given record id
 * @param {string} recordId
 * @param {string} language
 * @param {string} variationId
 * @param {string} url
 **/
export async function getCopies(recordId, language = 'en', variationId, url) {
     console.log(url);
     const { data } = await axios.get('/ItemAPI?method=getCopies', {
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               recordId,
               language,
               variationId,
          },
     });

     return {
          recordId: recordId,
          copies: data.copies ?? [],
     };
}