import { useQuery, useInfiniteQuery } from '@tanstack/react-query';
import axios from 'axios';
import { createAuthTokens, ENDPOINT, getHeaders } from '../util/apiAuth';
import { LIBRARY } from '../util/loadLibrary';
import { getAppliedFilters, getAvailableFacets, getSortList, SEARCH } from '../util/search';
import { GLOBALS } from '../util/globals';

const endpoint = ENDPOINT.search;

export default function useFetchSearchResults(term) {
     const client = axios.create({
          baseURL: 'https://aspen-test.bywatersolutions.com/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(endpoint.isPost),
          auth: createAuthTokens(),
     });

     const request = ({ ...options }) => {
          const onSuccess = function (response) {
               return response.data;
          };
          const onError = function (error) {
               return Promise.reject(error.response);
          };
          return client(options).then(onSuccess).catch(onError);
     };

     const fetch = (pageParam) =>
          request({
               url: '/SearchAPI?method=searchLite',
               params: {
                    library: 'm',
                    lookfor: term,
                    pageSize: 25,
                    page: pageParam,
               },
          });

     const getSearchResults = async ({ pageParam = 1 }) => {
          const res = await fetch(pageParam).then(async (response) => {
               SEARCH.id = response.result.id;
               SEARCH.sortMethod = response.result.sort;
               SEARCH.term = response.result.lookfor;
          });
          await getSortList();
          await getAvailableFacets();
          await getAppliedFilters();
          return {
               data: res.result.items,
               nextPage: pageParam + 1,
          };
     };

     return useInfiniteQuery(['searchResults'], getSearchResults, {
          getNextPageParam: (lastPage) => {
               //console.log(lastPage);
               const items = lastPage.data;
               if (items.length < 10) {
                    return undefined;
               }
               return lastPage.nextPage;
          },
     });
}