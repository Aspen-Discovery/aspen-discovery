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

export async function removeData() {
    const keys = ['@libraryHomeLink', '@libraryAddress', '@libraryPhone',
                  '@libraryEmail',  '@libraryShowHours',  '@libraryHoursMessage',
                  '@libraryHours',  '@libraryLatitude',  '@libraryLongitude'];

    await logoutUser().then(response => {
        try {
            SecureStore.deleteItemAsync("patronName");
            SecureStore.deleteItemAsync("library");
            SecureStore.deleteItemAsync("libraryName");
            SecureStore.deleteItemAsync("locationId");
            SecureStore.deleteItemAsync("solrScope");
            SecureStore.deleteItemAsync("pathUrl");
            SecureStore.deleteItemAsync("version");
            SecureStore.deleteItemAsync("userKey");
            SecureStore.deleteItemAsync("secretKey");
            SecureStore.deleteItemAsync("userToken");
            SecureStore.deleteItemAsync("logo");
            SecureStore.deleteItemAsync("favicon");
            AsyncStorage.multiRemove(keys);
            global.promptForOverdriveEmail = "";
            global.overdriveEmail = "";
            global.patronId = "";
            global.barcode = "";
            global.rememberHoldPickupLocation = "";
            global.pickupLocationId = "";
            global.homeLocationId = "";
            global.interfaceLanguage = "";
            global.numCheckedOut = "";
            global.numOverdue = "";
            global.numHolds = "";
            global.numHoldsAvailable = "";
            global.userKey = "";
            global.secretKey = "";
            global.allHolds = "";
            global.unavailableHolds = "";
            global.availableHolds = "";
            global.allUserHolds = "";
            global.checkedOutItems = "";
        } catch(error) {
            popAlert("Unable to logout", "Something went wrong when clearing user data. Try again.", "error");
            console.log("Unable to remove user data.");
            console.log(error);
        }
    })
}

export async function logoutUser() {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 3000 });
    const response = await api.get('/UserAPI?method=logout');

    if(response.ok) {
        const result = response.data;
        return result;
    } else {
        const result = response.problem;
        return result;
    }
}