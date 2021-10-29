import React from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import * as Random from 'expo-random';
import moment from "moment";
import { create, CancelToken } from 'apisauce';

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

            global.holdInfoLastLoaded = profile.holdInfoLastLoaded;
            global.checkoutInfoLastLoaded = profile.checkoutInfoLastLoaded;
            global.numCheckedOutIls = profile.numCheckedOutIls;
            global.numCheckedOutOverDrive = profile.numCheckedOutOverDrive;
            global.numOverdue = profile.numOverdue;
            global.numHoldsIls = profile.numHoldsIls;
            global.numHoldsOverDrive = profile.numHoldsOverDrive;
            global.numHoldsAvailableIls = profile.numHoldsAvailableIls;

            console.log("Patron profile set.");
        } catch (error) {
            console.log("Unable to set patron profile.");
            console.log(error);
        }

    } else {
        const fetchedData = response.problem;
        console.log(fetchedData);
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
            console.log("Patron checkouts saved.")

        } else {
            const fetchedData = response.problem;
            console.log(fetchedData);
            return fetchedData;
        }
    } else {
        console.log("Forcing reload...");
        var response = await api.get('/UserAPI?method=getPatronCheckedOutItems', { source: 'all', username: global.userKey, password: global.secretKey, refreshCheckouts: forceReload });
        if(response.ok) {
            const result = response.data;
            const fetchedData = result.result;

            global.checkedOutItems = fetchedData.checkedOutItems;
            console.log("Patron checkouts saved.")

        } else {
            const fetchedData = response.problem;
            console.log(fetchedData);
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

            console.log("Patron holds saved.")
            return allHolds;

        } else {
            const fetchedData = response.problem;
            console.log(fetchedData);
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

            console.log("Patron holds saved.")
            return allHolds;

        } else {
            const fetchedData = response.problem;
            console.log(fetchedData);
            return fetchedData;
        }
    }
}