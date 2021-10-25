import React, { Component } from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';

const appVersion = Constants.manifest.version;

const userKey = await SecureStore.getItemAsync("userKey");
const passKey = await SecureStore.getItemAsync("secretKey");

const libraryId = await SecureStore.getItemAsync("library");
const libraryName = await SecureStore.getItemAsync("libraryName");
const libraryId = await SecureStore.getItemAsync("library");
const libraryName = await SecureStore.getItemAsync("libraryName");
const locationId = await SecureStore.getItemAsync("locationId");
const locationSolrScope = await SecureStore.getItemAsync("solrScope");
const libraryUrl = await SecureStore.getItemAsync("pathUrl");
const libraryLogo = await SecureStore.getItemAsync("logo");
const libraryFavicon = await SecureStore.getItemAsync("favicon");

export { appVersion, userKey, passKey, libraryId, libraryName, locationId, locationSolrScope, libraryUrl, libraryUrl, libraryFavicon };