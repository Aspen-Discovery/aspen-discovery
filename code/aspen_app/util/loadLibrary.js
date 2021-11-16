import React from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import * as Random from 'expo-random';
import moment from "moment";
import { create, CancelToken } from 'apisauce';

// custom components and helper files
import { translate } from "../util/translations";
import { popToast, popAlert } from "../components/loadError";

export async function getLocationInfo() {
   const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
   const response = await api.get('/SystemAPI?method=getLocationInfo', { id: global.locationId, library: global.solrScope, version: global.version });

   if(response.ok) {
       const result = response.data;
       const libraryProfile = result.result;
       const profile = libraryProfile.location;

       try {
           await AsyncStorage.setItem('@libraryHomeLink', profile.homeLink);
           await AsyncStorage.setItem('@libraryAddress', profile.address);
           await AsyncStorage.setItem('@libraryPhone', profile.phone);
           await AsyncStorage.setItem('@libraryDescription', profile.description);

           if(profile.email) {
                await AsyncStorage.setItem('@libraryEmail', profile.email);
           } else {
                await AsyncStorage.setItem('@libraryEmail', "null");
           }

           await AsyncStorage.setItem('@libraryShowHours', profile.showInLocationsAndHoursList);

           if(profile.showInLocationsAndHoursList == 1) {
              await AsyncStorage.setItem('@libraryHoursMessage', profile.hoursMessage);
              await AsyncStorage.setItem('@libraryHours', JSON.stringify(profile.hours));
           }

           await AsyncStorage.setItem('@libraryLatitude', profile.latitude);
           await AsyncStorage.setItem('@libraryLongitude', profile.longitude);

       } catch (error) {
         // unable to save data at this time
         console.log("Unable to save data.")
         console.log(error);
       }

       console.log("Location profile set")
       return libraryProfile;
   } else {
       // no data yet
   }
}

export async function getLibraryInfo() {
   const libraryId = await SecureStore.getItemAsync("library");
   const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
   const response = await api.get('/SystemAPI?method=getLibraryInfo', { id: libraryId });

   if(response.ok) {
       const result = response.data;
       const libraryProfile = result.result;
       const profile = libraryProfile.library;

       try {
           await AsyncStorage.setItem('@libraryBarcodeStyle', profile.barcodeStyle);
       } catch (error) {
         // unable to save data at this time
          console.log("Unable to save data.")
          console.log(error);
       }

       console.log("Library profile set")
       return libraryProfile;
   } else {
       // no data yet
   }
}

export async function getPickupLocations() {
   const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
   const response = await api.get('/UserAPI?method=getValidPickupLocations', { username: global.userKey, password: global.secretKey });

   if(response.ok) {
       const result = response.data;
       const fetchedData = result.result;
        var locations = fetchedData.pickupLocations.map(({ displayName, code, locationId }) => ({
            key: code,
            locationId: locationId,
            name: displayName,
        }));
       return locations;
   } else {
       popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
   }
}