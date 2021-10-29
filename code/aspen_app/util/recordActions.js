import React, { useEffect, useState } from "react";
import { Toast } from "native-base";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import * as Random from 'expo-random';
import moment from "moment";
import { create, CancelToken } from 'apisauce';
import * as WebBrowser from 'expo-web-browser';

import { badServerConnectionToast } from "../components/loadError";

export async function getGroupedWork(itemId) {

    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
    const response = await api.get('/ItemAPI?method=getAppGroupedWork', { id: itemId });

    if(response.ok) {
        const fetchedData = response.data;
        return fetchedData;
    } else {
        badServerConnectionToast();
    }
}

export async function checkoutItem(itemId, source, patronId) {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
    const response = await api.get('/UserAPI?method=checkoutItem', { username: global.userKey, password: global.secretKey, itemId: itemId, itemSource: source, patronId: patronId });


    if(response.ok) {
        const responseData = response.data;
        const results = responseData.result;
        return results;
    } else {
        badServerConnectionToast();
    }
}

export async function placeHold(itemId, source, patronId, pickupBranch) {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
    const response = await api.get('/UserAPI?method=placeHold', { username: global.userKey, password: global.secretKey, itemId: itemId, itemSource: source, patronId: patronId, pickupBranch: pickupBranch });

    if(response.ok) {
        const responseData = response.data;
        const results = responseData.result;
        return results;
    } else {
        badServerConnectionToast();
    }
}

export async function overDriveSample(formatId, itemId, sampleNumber) {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 5000 });
    const response = await api.get('/UserAPI?method=viewOnlineItem', { username: global.userKey, password: global.secretKey, overDriveId: itemId, formatId: formatId, sampleNumber: sampleNumber, itemSource: "overdrive", isPreview: "true" });

    if(response.ok) {
        const result = response.data;
        const accessUrl = result.result.url;

        await WebBrowser.openBrowserAsync(accessUrl)
          .then(res => {
            console.log(res);
          })
          .catch(async err => {
            if (err.message === "Another WebBrowser is already being presented.") {

             try {
                  WebBrowser.dismissBrowser();
                  await WebBrowser.openBrowserAsync(accessUrl)
                    .then(response => {
                      console.log(response);
                    })
                    .catch(async error => {
                      console.log("Unable to close previous browser session.");
                    });
                } catch(error) {
                    console.log ("Really borked.");
                }
            } else {
              console.log("Unable to open browser window.");
            }
          });


    } else {
        badServerConnectionToast();
    }
}