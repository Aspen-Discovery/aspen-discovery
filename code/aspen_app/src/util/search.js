import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'apisauce';
import _ from 'lodash';

// custom components and helper files
import { popToast } from '../components/loadError';
import { getTermFromDictionary } from '../translations/TranslationService';
import { createAuthTokens, ENDPOINT, getHeaders, getResponseCode, postData } from './apiAuth';
import { GLOBALS } from './globals';
import { LIBRARY } from './loadLibrary';
import { PATRON } from './loadPatron';

export const SEARCH = {
     term: null,
     id: null,
     hasPendingChanges: false,
     sortMethod: 'relevance',
     appliedFilters: [],
     sortList: [],
     availableFacets: [],
     defaultFacets: [],
     pendingFilters: [],
     appendedParams: '',
     searchSource: 'local',
     searchIndex: 'Keyword',
};

const endpoint = ENDPOINT.search;

export async function searchResults(searchTerm, pageSize = 100, page, libraryUrl, filters = '', language) {
     let solrScope = '';
     if (GLOBALS.solrScope !== 'unknown') {
          solrScope = GLOBALS.solrScope;
     } else {
          try {
               solrScope = await AsyncStorage.getItem('@solrScope');
          } catch (e) {
               console.log(e);
          }
     }

     const api = create({
          baseURL: libraryUrl + '/API/',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(),
          params: {
               library: solrScope,
               lookfor: searchTerm,
               pageSize,
               page,
               language,
          },
          auth: createAuthTokens(),
     });

     const response = await api.get('/SearchAPI?method=getAppSearchResults' + filters);
     if (response.ok) {
          SEARCH.term = response.data.result.lookfor;
          return response;
     } else {
          popToast(getTermFromDictionary('en', 'error_no_server_connection'), getTermFromDictionary('en', 'error_no_library_connection'), 'error');
          console.log(response);
          return response;
     }
}

export async function getDefaultFacets(url, limit = 5, language) {
     const discovery = create({
          baseURL: url ?? LIBRARY.url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          params: { limit, language },
          auth: createAuthTokens(),
     });
     const data = await discovery.get(endpoint.url + 'getDefaultFacets');
     const response = getResponseCode(data);
     if (response.success) {
          SEARCH.defaultFacets = response.data.result.data;
          return response.data.result;
     } else {
          return response;
     }
}

export async function getSearchResults(searchTerm, pageSize = 25, page, url, language) {
     let baseUrl = url ?? LIBRARY.url;
     const postBody = await postData();
     const discovery = create({
          baseURL: baseUrl + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               library: PATRON.scope ?? null,
               lookfor: searchTerm ?? '',
               pageSize,
               page,
               type: 'catalog',
               language,
               searchIndex: SEARCH.searchIndex,
               source: SEARCH.searchSource,
          },
     });

     const results = await discovery.post('/SearchAPI?method=searchLite' + SEARCH.appendedParams, postBody);

     if (results.success) {
          // only set these if we get back a good result
          if (results.data.result.success) {
               SEARCH.id = results.data.result.id;
               SEARCH.sortMethod = results.data.result.sort;
               SEARCH.term = results.data.result.lookfor;
               await getSortList(baseUrl);
               await getAvailableFacets(baseUrl);
               await getAppliedFilters(baseUrl);
          }

          return {
               success: results.data.result.success,
               data: results.data.result,
          };
     } else {
          return {
               success: results.success,
               data: [],
               error: results.error ?? [],
          };
     }
}

export async function getAppliedFilters(url, language) {
     SEARCH.appliedFilters = [];
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
     });
     const data = await discovery.get(endpoint.url + 'getAppliedFilters', {
          id: SEARCH.id,
          language: language,
     });
     const response = getResponseCode(data);
     if (response.success) {
          const appliedFilters = response.data.result.data;
          SEARCH.appliedFilters = appliedFilters;
          _.map(appliedFilters, function (filter, index, collection) {
               _.forEach(filter, function (facet, key) {
                    addAppliedFilter(facet['field'], facet['value'], true);
               });
          });
          buildParamsForUrl();
          return response.data.result.data;
     } else {
          return response;
     }
}

export async function getSortList(url, language) {
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
          params: {
               id: SEARCH.id,
               language: language,
          },
     });
     const data = await discovery.get(endpoint.url + 'getSortList');
     const response = getResponseCode(data);
     if (response.success) {
          SEARCH.sortList = response.data.result;
          return response.data.result;
     } else {
          return response;
     }
}

export async function getAvailableFacets(url, language) {
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
          params: {
               includeSortList: true,
               id: SEARCH.id,
               language: language,
          },
     });
     const data = await discovery.get(endpoint.url + 'getAvailableFacets');
     const response = getResponseCode(data);
     if (response.success) {
          await getAvailableFacetsKeys(url, language);
          SEARCH.availableFacets = response.data.result;
          const defaultOptions = response.data.result.data;
          let i = 1;
          let defaults = [];
          _.map(defaultOptions, function (item, index, collection) {
               if (i <= 5 && item['field'] !== 'sort_by') {
                    defaults = _.concat(defaults, item);
                    i++;
               }
          });
          SEARCH.defaultFacets = defaults;
          return response.data.result;
     } else {
          return response;
     }
}

export function setDefaultFacets(facets) {
     const defaultOptions = facets;
     let i = 1;
     let defaults = [];
     _.map(defaultOptions, function (item, index, collection) {
          if (i <= 5 && item['field'] !== 'sort_by') {
               defaults = _.concat(defaults, item);
               i++;
          }
     });
     SEARCH.defaultFacets = defaults;
     return defaults;
}

export async function searchAvailableFacets(facet, label, term, url, language) {
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
          params: {
               includeSortList: false,
               id: SEARCH.id,
               facet,
               term,
               language: language,
          },
     });
     const response = await discovery.get(endpoint.url + 'searchAvailableFacets');
     if (response.ok) {
          const data = response.data;
          return (
               data.result?.data[label] ?? {
                    key: 1,
                    field: facet,
                    facets: [],
               }
          );
     } else {
          //console.log(response);
     }
     return {
          success: false,
          facets: [],
     };
}

export async function getAvailableFacetsKeys(url, language) {
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
          params: {
               includeSortList: true,
               id: SEARCH.id,
               language: language,
          },
     });
     const data = await discovery.get(endpoint.url + 'getAvailableFacetsKeys');
     const response = getResponseCode(data);
     if (response.success) {
          const keys = response.data.result.options;
          let map = [];
          let i = 0;
          _.mapKeys(keys, function (value, key) {
               const groupByKey = {
                    field: value,
                    key: i++,
                    facets: [],
               };
               map = _.concat(map, groupByKey);
          });

          SEARCH.pendingFilters = map;
          return map;
     } else {
          return response;
     }
}

export async function getFacetCluster() {
     return false;
}

export async function categorySearchResults(category, limit = 25, page, url, language) {
     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(true),
          params: {
               limit,
               id: category,
               page,
               language,
          },
          auth: createAuthTokens(),
     });
     const response = await api.post('/SearchAPI?method=getAppBrowseCategoryResults', postBody);
     if (response.ok) {
          return response;
     } else {
          console.log(response);
          return response;
     }
}

export async function listofListSearchResults(searchId, limit = 25, page, url, language) {
     const myArray = searchId.split('_');
     const id = myArray[myArray.length - 1];

     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(true),
          params: {
               limit,
               id,
               page,
               language,
          },
          auth: createAuthTokens(),
     });
     const response = await api.post('/SearchAPI?method=getListResults', postBody);
     if (response.ok) {
          return response.data.result;
     } else {
          console.log(response);
          return response;
     }
}

export async function savedSearchResults(searchId, limit = 25, page, url, language) {
     let id = searchId;
     if (_.isString(searchId)) {
          if (searchId.includes('system_saved_search')) {
               const myArray = searchId.split('_');
               id = myArray[3];
          }
     }

     const postBody = await postData();
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutSlow,
          headers: getHeaders(true),
          params: {
               limit,
               id,
               page,
               language,
          },
          auth: createAuthTokens(),
     });
     const response = await api.post('/SearchAPI?method=getSavedSearchResults', postBody);
     if (response.ok) {
          return response;
     } else {
          console.log(response);
          return response;
     }
}

export function getFormats(data) {
     if (_.isArray(data) || _.isObject(data)) {
          let formats = [];
          data.map((item) => {
               let thisFormat = item;
               if (item.includes('#')) {
                    thisFormat = item.split('#');
                    thisFormat = thisFormat[thisFormat.length - 1];
               }
               formats.push(thisFormat);
          });

          formats = _.uniq(formats);
          return formats;
     }
     return data;
}

/**
 * Functions for facets and filtering
 * Requires: Aspen Discovery 22.11.00 or greater
 **/

/**
 * Returns a string of encoded values to append to a search URL
 **/
export function buildParamsForUrl() {
     const filters = SEARCH.pendingFilters;
     let params = [];
     _.forEach(filters, function (filter) {
          const field = filter.field;
          const facets = filter.facets;
          if (field === 'sort_by') {
               //console.log(filter);
          }
          if (_.size(facets) > 0) {
               _.forEach(facets, function (facet) {
                    if (field === 'sort_by') {
                         //ignore adding sort here, we'll do it later
                         if (facet.includes(',')) {
                              params = params.concat('&sort=' + encodeURIComponent(facet));
                         } else {
                              params = params.concat('&sort=' + facet);
                         }
                    } else if (field === 'publishDateSort' || field === 'birthYear' || field === 'deathYear' || field === 'publishDate' || field === 'lexile_score' || field === 'accelerated_reader_point_value' || field === 'accelerated_reader_reading_level' || field === 'start_date') {
                         facet = facet.replaceAll(' ', '+');
                         params = params.concat('&filter[]=' + field + ':' + facet);
                    } else {
                         params = params.concat('&filter[]=' + field + ':' + facet);
                    }
               });
          }
     });

     params = _.join(params, '');
     SEARCH.appendedParams = params;
     console.log('buildParamsForUrl: ');
     console.log(params);
     return params;
}

export async function getSearchIndexes(url, language = 'en', source = 'local') {
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
          params: {
               searchSource: source,
               language: language,
          },
     });
     const response = await discovery.get(endpoint.url + 'getSearchIndexes');
     if (response.ok) {
          if (response?.data?.result?.indexes[source]) {
               SEARCH.validIndexes = response.data.result.indexes[source];
               return response.data.result.indexes[source];
          }
     }

     return {
          success: false,
          indexes: [],
     };
}

export async function getSearchSources(url, language = 'en') {
     const discovery = create({
          baseURL: url,
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
          params: {
               language: language,
          },
     });
     const response = await discovery.get(endpoint.url + 'getSearchSources');
     if (response.ok) {
          if (response?.data?.result?.sources) {
               SEARCH.validSources = response.data.result.sources;
               return response.data.result.sources;
          }
     }

     return {
          success: false,
          sources: [],
     };
}

/**
 * Iterates over objects of the collection of available facet clusters, returning objects of all
 * elements that [field, value] returns truthy for. If no matches are found, returns empty array.
 *
 * **Sample Call:**
 * > `const cluster = getFilterCluster('Available at?', 'field', 'available_at')`
 *
 * **Sample Response:**
 * > `{"count": 116, "display": "Main Library", "field": "available_at", "isApplied": false, "multiSelect": false, "value": "Main Library"}`
 *
 * @param {string} cluster The name of the cluster to search through
 * @param {string} key The key to find a value match to
 * @param {string} value The value to find a key match to
 **/
export function getFilterCluster(cluster, key, value) {
     if (cluster && key && value) {
          return _.filter(SEARCH.availableFacets[cluster], [key, value]);
     }
     return [];
}

export function formatOptions(cluster) {
     if (cluster) {
          return _.castArray(SEARCH.availableFacets[cluster]);
     }
     return [];
}

export function getAppliedFacets(cluster) {
     if (cluster) {
          return _.filter(SEARCH.appliedFilters, 'isApplied');
     }
     return [];
}

export function addAppliedFilter(group, values, multiSelect = false) {
     if (group) {
          if (_.isArray(values) || _.isObject(values)) {
               _.forEach(values, function (value) {
                    const i = _.findIndex(SEARCH.pendingFilters, ['field', group]);
                    if (i !== -1) {
                         if (multiSelect) {
                              SEARCH.pendingFilters[i]['facets'] = _.concat(SEARCH.pendingFilters[i]['facets'], value);
                         } else {
                              SEARCH.pendingFilters[i]['facets'] = _.castArray(value);
                         }
                         SEARCH.pendingFilters[i]['facets'] = _.uniqWith(SEARCH.pendingFilters[i]['facets'], _.isEqual);
                         console.log('Added ' + value + ' to ' + group + ' (multiSelect: ' + multiSelect + ')');
                         buildParamsForUrl();
                         return true;
                    }
               });
          } else {
               const i = _.findIndex(SEARCH.pendingFilters, ['field', group]);
               if (i !== -1) {
                    if (multiSelect) {
                         SEARCH.pendingFilters[i]['facets'] = _.concat(SEARCH.pendingFilters[i]['facets'], values);
                    } else {
                         SEARCH.pendingFilters[i]['facets'] = _.castArray(values);
                    }
                    SEARCH.pendingFilters[i]['facets'] = _.uniqWith(SEARCH.pendingFilters[i]['facets'], _.isEqual);
                    console.log('Added ' + values + ' to ' + group + ' (multiSelect: ' + multiSelect + ')');
                    buildParamsForUrl();
                    return true;
               }
          }
     }
     return false;
}

export function removeAppliedFilter(group, values) {
     if (group) {
          if (_.isArray(values) || _.isObject(values)) {
               _.forEach(values, function (value) {
                    const i = _.findIndex(SEARCH.pendingFilters, ['field', group]);
                    if (i !== -1) {
                         SEARCH.pendingFilters[i]['facets'] = _.pull(SEARCH.pendingFilters[i]['facets'], value);
                         console.log('Removed ' + value + ' from ' + group);
                         buildParamsForUrl();
                         return true;
                    }
               });
          } else {
               const i = _.findIndex(SEARCH.pendingFilters, ['field', group]);
               if (i !== -1) {
                    SEARCH.pendingFilters[i]['facets'] = _.pull(SEARCH.pendingFilters[i]['facets'], values);
                    console.log('Removed ' + values + ' from ' + group);
                    buildParamsForUrl();
                    return true;
               }
          }
     }
     return false;
}

export function getPendingFacets(cluster) {
     if (cluster) {
          return _.filter(SEARCH.pendingFilters, ['field', cluster]);
     }
     return [];
}

export function getCurrentSort() {
     return _.filter(SEARCH.sortList, 'selected');
}

export function resetSearchGlobals() {
     SEARCH.term = null;
     SEARCH.id = null;
     SEARCH.hasPendingChanges = false;
     SEARCH.sortMethod = 'relevance';
     SEARCH.appliedFilters = [];
     SEARCH.sortList = [];
     SEARCH.availableFacets = [];
     SEARCH.pendingFilters = [];
     SEARCH.appendedParams = '';
     console.log('Reset global search variables');
}