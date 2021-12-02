import React from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import * as Device from 'expo-device';
import * as Random from 'expo-random';
import moment from "moment";
import { create, CancelToken } from 'apisauce';
import _ from "lodash";

// custom components and helper files
import { translate } from "../util/translations";
import { createAuthTokens, postData, getHeaders } from "../util/apiAuth";
import { popToast, popAlert } from "../components/loadError";

export async function getLocationInfo() {
   const api = create({ baseURL: global.libraryUrl + '/API', timeout: global.timeoutAverage, headers: getHeaders(), auth: createAuthTokens() });
   const response = await api.get('/SystemAPI?method=getLocationInfo', { id: global.locationId, library: global.solrScope, version: global.version });
   if(response.ok) {
       const result = response.data.result;
       const profile = result.location;

        global.location_homeLink = profile.homeLink;
        global.location_address = profile.address;
        global.location_phone = profile.phone;
        global.location_description = profile.description;
        global.location_email = profile.email;
        global.location_showInLocationsAndHoursList = profile.showInLocationsAndHoursList;
        if(profile.showInLocationsAndHoursList == 1) {
            global.location_hoursMessage = profile.hoursMessage;
            global.location_hours = JSON.stringify(profile.hours);
        } else {
            global.location_hoursMessage = null;
            global.location_hours = null;
        }
        global.location_latitude = profile.latitude;
        global.location_longitude = profile.longitude;

       console.log("Location profile set")
       return profile;
   } else {
       // no data yet
   }
}

export async function getLibraryInfo() {
   const libraryId = await SecureStore.getItemAsync("library");
   const api = create({ baseURL: global.libraryUrl + '/API', timeout: global.timeoutAverage, headers: getHeaders(), auth: createAuthTokens() });
   const response = await api.get('/SystemAPI?method=getLibraryInfo', { id: libraryId });

   if(response.ok) {
       const result = response.data.result;
       const profile = result.library;

       global.barcodeStyle = profile.barcodeStyle;

       console.log("Library profile set")
       return profile;
   } else {
       // no data yet
       if(_.isUndefined(global.barcodeStyle)) {
            global.barcodeStyle = 0
       }
   }
}

export async function getPickupLocations() {
   const api = create({ baseURL: global.libraryUrl + '/API', timeout: global.timeoutAverage, headers: getHeaders(), auth: createAuthTokens() });
   const response = await api.get('/UserAPI?method=getValidPickupLocations', { username: global.userKey, password: global.secretKey });

   if(response.ok) {
       const result = response.data;
       const fetchedData = result.result;
        var locations = fetchedData.pickupLocations.map(({ displayName, code, locationId }) => ({
            key: locationId,
            locationId: locationId,
            code: code,
            name: displayName,
        }));
       return locations;
   } else {
       popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
   }
}

export async function getActiveBrowseCategories() {
   const api = create({ baseURL: global.libraryUrl + '/API', timeout: global.timeoutAverage, headers: getHeaders(), auth: createAuthTokens() });
   const response = await api.get('/SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true', { username: global.userKey, password: global.secretKey });

   if(response.ok) {
        const items = response.data;
        const results = items.result;
        var allCategories = [];
        const categoriesArray = results.map(function (category, index, array) {
            const subCategories = category['subCategories'];

            if(subCategories.length != 0) {
                subCategories.forEach(item => allCategories.push({'key':item.key, 'title':item.title, 'isHidden': false }))
            } else {
                allCategories.push({'key':category.key, 'title':category.title, 'isHidden': false });
            }
        });
        return allCategories;
   } else {
       popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
   }
}