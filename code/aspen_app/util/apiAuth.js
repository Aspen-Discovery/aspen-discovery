import React from "react";
import Constants from "expo-constants";
import * as Device from 'expo-device';
import * as SecureStore from 'expo-secure-store';
import base64 from 'react-native-base64';
import _ from "lodash";
import {API_KEY_1, API_KEY_2, API_KEY_3, API_KEY_4, API_KEY_5} from '@env';

// polyfill for base64 (required for authentication)
if (!global.btoa) { global.btoa = base64.encode; }
if (!global.atob) { global.atob = base64.decode; }

export function createAuthTokens() {
    var tokens = {};
    tokens['username'] = makeNewSecret();
    tokens['password'] = makeNewSecret();
    return tokens;
}

export async function postData() {
    var content = null;
    var content = new FormData();

    try {
        var userKey = await SecureStore.getItemAsync("userKey");
    } catch (e) {
        var userKey = null;
        console.log(e)
    }

    try {
        var secretKey = await SecureStore.getItemAsync("secretKey");
    } catch (e) {
        var userKey = null;
        console.log(e)
    }

    content.append('username', userKey);
    content.append('password', secretKey);

    return content;
}

export function getHeaders(isPost = false) {
    var headers = {};

    headers['User-Agent'] = 'Aspen LiDA ' + Device.modelName + ' ' + Device.osName + '/' + Device.osVersion;
    headers['Version'] = 'v' + global.version + ' [b' + global.build + ']';

    if(isPost) {
        headers['Content-Type'] = 'application/x-www-form-urlencoded';
    };

    return headers;
}

function makeNewSecret() {
    const tokens = [API_KEY_1, API_KEY_2, API_KEY_3, API_KEY_4, API_KEY_5];
    // Shuffle array & select random key
    var thisKey = _.sample(_.shuffle(tokens));
    return base64.encode(thisKey);
}

async function getTokenValue(key) {
    let result = await SecureStore.getItemAsync(key);
    return result
};