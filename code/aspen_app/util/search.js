import React from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import * as Random from 'expo-random';
import moment from "moment";
import { create, CancelToken } from 'apisauce';

import { badServerConnectionToast } from "../components/loadError";

export async function searchResults(searchTerm, pageSize, page) {

   const thisSearchTerm = searchTerm.replace(" ", "+");

   const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
   const response = await api.get('/SearchAPI?method=search', { lookfor: thisSearchTerm, pageSize: pageSize, page: page });

   if(response.ok) {
       const result = response.data;
       const fetchedData = result.result;

       if (fetchedData.success == true) {

        var searchResults = fetchedData.recordSet.map(({ title_display, author_display, description, id }) => ({
            key: id,
            title: title_display,
            author: author_display,
            description: description,
        }));

       } else {
           console.log("Connection made, but library location not found.")
       }

       return searchResults;

   } else {
       badServerConnectionToast();
   }
}