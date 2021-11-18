import React from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import * as Random from 'expo-random';
import moment from "moment";
import { create, CancelToken } from 'apisauce';

import { badServerConnectionToast } from "../components/loadError";

export async function getLocationInfo() {
   const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
   const response = await api.get('/SystemAPI?method=getLocationInfo', { id: global.locationId, library: global.solrScope, version: global.version });

   if(response.ok) {
       const result = response.data;
       const libraryProfile = result.result;

       if (libraryProfile.success == true) {
           const profile = libraryProfile.location;

           console.log(profile);

           try {
               await AsyncStorage.setItem('@libraryHomeLink', profile.homeLink);
               await AsyncStorage.setItem('@libraryAddress', profile.address);
               await AsyncStorage.setItem('@libraryPhone', profile.phone);
               await AsyncStorage.setItem('@libraryEmail', profile.email);
               await AsyncStorage.setItem('@libraryShowHours', profile.showInLocationsAndHoursList);
               await AsyncStorage.setItem('@libraryHoursMessage', profile.hoursMessage);
               await AsyncStorage.setItem('@libraryHours', JSON.stringify(profile.hours));
               await AsyncStorage.setItem('@libraryLatitude', profile.latitude);
               await AsyncStorage.setItem('@libraryLongitude', profile.longitude);

               console.log("Library profile set.")
           } catch (error) {
               console.log("Unable to set library profile.");
               console.log(error);
           }

       } else {
           console.log("Connection made, but library location not found.")
       }

       return libraryProfile;

   } else {
       badServerConnectionToast();
   }
}

export async function getPickupLocations() {
   const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
   const response = await api.get('/UserAPI?method=getValidPickupLocations', { username: global.userKey, password: global.secretKey });

   if(response.ok) {
       const result = response.data;
       const fetchedData = result.result;

       if (fetchedData.success == true) {

        var locations = fetchedData.pickupLocations.map(({ displayName, code, locationId }) => ({
            key: code,
            locationId: locationId,
            name: displayName,
        }));

       } else {
           console.log("Connection made, but library location not found.")
       }

       return locations;

   } else {
       badServerConnectionToast();
   }
}