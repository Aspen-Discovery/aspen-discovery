import Constants from 'expo-constants';
import * as Updates from 'expo-updates';
import { Platform } from 'react-native';

const iOSDist = Constants.expoConfig.ios.buildNumber;
const androidDist = Constants.expoConfig.android.versionCode;
const iOSBundle = Constants.expoConfig.ios.bundleIdentifier;
const androidBundle = Constants.expoConfig.android.package;
const releaseChannel = Updates.channel ?? Updates.releaseChannel;

export const GLOBALS = {
     timeoutAverage: 60000,
     timeoutSlow: 100000,
     timeoutFast: 30000,
     appVersion: Constants.expoConfig.version,
     appBuild: Platform.OS === 'android' ? androidDist : iOSDist,
     appSessionId: Constants.expoConfig.sessionid,
     appPatch: Constants.expoConfig.extra.patch,
     showSelectLibrary: true,
     runGreenhouse: true,
     slug: Constants.expoConfig.slug,
     url: Constants.expoConfig.extra.apiUrl,
     releaseChannel: __DEV__ ? 'DEV' : releaseChannel,
     language: 'en',
     country: 'us',
     lastSeen: null,
     prevLaunched: false,
     pendingSearchFilters: [],
     availableFacetClusters: [],
     hasPendingChanges: false,
     solrScope: 'unknown',
     libraryId: Constants.expoConfig.extra.libraryId,
     themeId: Constants.expoConfig.extra.themeId,
     bundleId: Platform.OS === 'android' ? androidBundle : iOSBundle,
     greenhouse: Constants.expoConfig.extra.greenhouseUrl,
     privacyPolicy: 'https://bywatersolutions.com/lida-app-privacy-policy',
     iosStoreUrl: Constants.expoConfig.extra.iosStoreUrl,
     androidStoreUrl: Constants.expoConfig.extra.androidStoreUrl,
};

export const LOGIN_DATA = {
     showSelectLibrary: true,
     runGreenhouse: true,
     num: 0,
     nearbyLocations: [],
     allLocations: [],
     extra: [],
     hasPendingChanges: false,
     loadedInitialData: false,
     themeSaved: false,
};