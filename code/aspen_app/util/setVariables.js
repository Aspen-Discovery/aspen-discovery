import React from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import * as Random from 'expo-random';
import moment from "moment";

// custom components and helper files
import { translate } from "../util/translations";
import { popToast, popAlert } from "../components/loadError";

export async function setInitialVariables() {
    try {
        global.releaseChannel = await SecureStore.getItemAsync("releaseChannel");
        global.latitude = await SecureStore.getItemAsync("latitude");
        global.longitude = await SecureStore.getItemAsync("longitude");
    } catch {
        console.log("Error setting initial global variables.");
    }
};

export async function setGlobalVariables() {
    try {
        // prepare app data
        global.version = Constants.manifest.version;
        global.timeout = 10000;

        // prepare user data
        global.userKey = await SecureStore.getItemAsync("userKey");
        global.secretKey = await SecureStore.getItemAsync("secretKey");
        global.patron = await SecureStore.getItemAsync("patronName");

        // prepare library data
        global.libraryId = await SecureStore.getItemAsync("library");
        global.libraryName = await SecureStore.getItemAsync("libraryName");
        global.locationId = await SecureStore.getItemAsync("locationId");
        global.solrScope = await SecureStore.getItemAsync("solrScope");
        global.libraryUrl = await SecureStore.getItemAsync("pathUrl");
        global.logo = await SecureStore.getItemAsync("logo");
        global.favicon = await SecureStore.getItemAsync("favicon");


        // set timeout options
        global.timeoutFast = 3000;
        global.timeoutAverage = 5000;

        console.log("Global variables set.")

    } catch(e) {
        console.log("Error setting global variables.");
        console.log(e);
    }
};

export async function setSession() {
   try {
       const guid = Random.getRandomBytes(32);
       global.sessionId = guid;
   } catch {
       const random = moment().unix();
       global.sessionId = random;
   }

   console.log("Session created.")

};