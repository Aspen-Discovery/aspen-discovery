import * as Application from 'expo-application';
import Constants from 'expo-constants';
import * as Updates from 'expo-updates';
import { Platform } from 'react-native';

export const GLOBALS = {
     timeoutAverage: 60000,
     timeoutSlow: 100000,
     timeoutFast: 30000,
     appVersion: Constants.manifest2?.extra?.expoClient?.version ?? Constants.manifest.version,
     appBuild: Application.nativeBuildVersion ?? (Platform.OS === 'android' ? Constants.manifest.android.versionCode : Constants.manifest.ios.buildNumber),
     appSessionId: Constants.manifest2?.extra?.expoClient?.sessionid ?? Constants.sessionId,
     appPatch: 0.4,
     showSelectLibrary: true,
     runGreenhouse: true,
     slug: Constants.manifest2?.extra?.expoClient?.slug ?? Constants.manifest.slug,
     url: Constants.manifest2?.extra?.expoClient?.extra?.apiUrl ?? Constants.manifest.extra.apiUrl,
     releaseChannel: Updates.channel ?? Updates.releaseChannel,
     language: 'en',
     lastSeen: null,
     prevLaunched: false,
     pendingSearchFilters: [],
     availableFacetClusters: [],
     hasPendingChanges: false,
     solrScope: 'unknown',
     libraryId: Constants.manifest2?.extra?.expoClient?.extra?.libraryId ?? Constants.manifest.extra.libraryId,
     themeId: Constants?.manifest2?.extra?.expoClient?.extra?.themeId ?? Constants.manifest.extra.themeId,
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