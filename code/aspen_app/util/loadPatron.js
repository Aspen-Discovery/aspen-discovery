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

export async function getProfile() {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 3000 });
    const response = await api.get('/UserAPI?method=getPatronProfile', { username: global.userKey, password: global.secretKey });

    if(response.ok) {
        const results = response.data;
        const result = results.result;
        const profile = result.profile;
        try {
            global.homeLocationId = profile.homeLocationId;
            global.barcode = profile.cat_username;
            global.interfaceLanguage = profile.interfaceLanguage;
            global.patronId = profile.id;
            global.rememberHoldPickupLocation = profile.rememberHoldPickupLocation;
            global.pickupLocationId = profile.pickupLocationId;
            global.promptForOverdriveEmail = profile.promptForOverdriveEmail;
            global.overdriveEmail = profile.overdriveEmail;
            global.numCheckedOut = profile.numCheckedOut;
            global.numOverdue = profile.numOverdue;
            global.numHolds = profile.numHolds;
            global.numHoldsAvailable = profile.numHoldsAvailable;
        } catch (error) {
            // no data saved yet
            console.log("Unable to save data.")
            console.log(error);
        }
    } else {
        const fetchedData = response.problem;
        return fetchedData;
    }
}

export async function getCheckedOutItems(forceReload = false) {
    const api = create({ baseURL: global.libraryUrl + '/API' });
    if(forceReload == false) {
        var response = await api.get('/UserAPI?method=getPatronCheckedOutItems', { source: 'all', username: global.userKey, password: global.secretKey });
        if(response.ok) {
            const result = response.data;
            const fetchedData = result.result;
            global.checkedOutItems = fetchedData.checkedOutItems;
        } else {
            const fetchedData = response.problem;
            return fetchedData;
        }
    } else {
        var response = await api.get('/UserAPI?method=getPatronCheckedOutItems', { source: 'all', username: global.userKey, password: global.secretKey, refreshCheckouts: forceReload });
        if(response.ok) {
            const result = response.data;
            const fetchedData = result.result;
            global.checkedOutItems = fetchedData.checkedOutItems;
            popAlert("Reload complete", "Checked out items have been refreshed", "success");
        } else {
            const fetchedData = response.problem;
            popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
            return fetchedData;
        }
    }
}

export async function getHolds(forceReload = false) {
    const api = create({ baseURL: global.libraryUrl + '/API' });
    if(forceReload == false) {
        var response = await api.get('/UserAPI?method=getPatronHolds', { source: 'all', username: global.userKey, password: global.secretKey });
        if(response.ok) {
            const result = response.data;
            const fetchedData = result.result;
            const allHolds = fetchedData.holds;
            global.allHolds = allHolds;
            global.unavailableHolds = Object.values(allHolds.unavailable);
            global.availableHolds = Object.values(allHolds.available);
            global.allUserHolds = global.availableHolds.concat(global.unavailableHolds);
            return allHolds;
        } else {
            const fetchedData = response.problem;
            return fetchedData;
        }
    } else {
        var response = await api.get('/UserAPI?method=getPatronHolds', { source: 'all', username: global.userKey, password: global.secretKey, refreshHolds: forceReload });
        if(response.ok) {
            const result = response.data;
            const fetchedData = result.result;
            const allHolds = fetchedData.holds;
            global.allHolds = allHolds;
            global.unavailableHolds = Object.values(allHolds.unavailable);
            global.availableHolds = Object.values(allHolds.available);
            global.allUserHolds = global.availableHolds.concat(global.unavailableHolds);
            popAlert("Reload complete", "Holds have been refreshed", "success");
            return allHolds;
        } else {
            const fetchedData = response.problem;
            popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
            return fetchedData;
        }
    }
}