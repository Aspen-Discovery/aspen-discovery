import React from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import * as Random from 'expo-random';
import moment from "moment";
import { create, CancelToken } from 'apisauce';

// custom components and helper files
import { createAuthTokens, postData, getHeaders } from "../util/apiAuth";
import { translate } from "../util/translations";
import { popToast, popAlert } from "../components/loadError";

export async function searchResults(searchTerm, pageSize = 100, page) {
   const thisSearchTerm = searchTerm.replace(" ", "+");

   const api = create({ baseURL: global.libraryUrl + '/API', timeout: global.timeoutSlow, headers: getHeaders, params: { library: global.solrScope, lookfor: thisSearchTerm, pageSize: pageSize, page: page }, auth: createAuthTokens() });
   const response = await api.get('/SearchAPI?method=getAppSearchResults');

   if(response.ok) {
       return response;
   } else {
       popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
   }
}