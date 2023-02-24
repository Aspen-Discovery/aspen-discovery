import AsyncStorage from "@react-native-async-storage/async-storage";
import { create } from "apisauce";
import _ from "lodash";

import { createAuthTokens, getHeaders } from "../util/apiAuth";
import { GLOBALS } from "../util/globals";
import { LIBRARY } from "../util/loadLibrary";
import defaults from './defaults.json';

export async function getTranslation(term, language, url) {
  const api = create({
    baseURL: url + "/API",
    timeout: GLOBALS.timeoutAverage,
    headers: getHeaders(),
    auth: createAuthTokens(),
  });
  const response = await api.get("/SystemAPI?method=getTranslation", {
    term,
    language,
  });

  if (response.ok) {
    const translation = response?.data?.result?.translations[term] ?? term;
    console.log(translation);
    return translation;
  }
  // just return the original term back if we don't get a good translation
  return term;
}

const defaultTranslation = require('../translations/defaults.json');
export async function getTranslations(language, libraryUrl) {
  const api = create({
    baseURL: libraryUrl + "/API",
    timeout: GLOBALS.timeoutAverage,
    headers: getHeaders(),
    auth: createAuthTokens(),
  });
  const response = await api.get("/SystemAPI?method=getTranslation", {
    terms: _.toArray(defaultTranslation.user_profile),
    language: 'es',
  });

  if (response.ok) {
    return response.data.result.translations;
  } else {
    console.log(response);
    // no data yet
  }
}

export async function getDefaultTranslations(libraryUrl) {
  const api = create({
    baseURL: libraryUrl + "/API",
    timeout: GLOBALS.timeoutAverage,
    headers: getHeaders(),
    auth: createAuthTokens(),
  });
  const terms = [
    "Version",
    "Build",
    "Patch",
    "Beta",
    "Login",
    "Logout",
    "OK",
    "Save",
    "Privacy Policy",
    "Contact",
    "Close",
    "Hide",
    "Updating",
    "Loading",
    "Library Barcode",
    "Password/PIN",
    "Discover",
    "Search",
    "Search Results",
    "Card",
    "Library Card",
    "Account",
    "More",
    "Note",
    "Select Your Library",
    "Find Your Library",
    "Reset Geolocation",
    "By",
    "Item Details",
    "Language",
    "No matches found",
    "View Item Details",
    "Where is it?",
    "Holds",
    "Place Hold",
    "Titles on Hold",
    "Ready for Pickup",
    "Author",
    "Format",
    "On Hold For",
    "Pickup Location",
    "Pickup By",
    "Position",
    "View Item Details",
    "Cancelling",
    "Cancel Hold",
    "Cancel All",
    "Freezing",
    "Freeze Hold",
    "Freeze All",
    "Thawing",
    "Thaw Hold",
    "Thaw All",
    "You have no items on hold",
    "Change Pickup Location",
    "Renew",
    "Renew All",
    "Due",
    "Overdue",
    "Return Now",
    "Access Online",
    "Read Online",
    "Listen Online",
    "Watch Online",
    "Settings",
    "Account Summary",
  ];
  const tmp = await AsyncStorage.getItem("@libraryLanguages");
  let languages = JSON.parse(tmp);
  languages = _.values(languages);
  languages.map(async (language) => {
    //language.code
    //map through the languages with term list, and save to their unique array named by language code?

    const response = await api.get("/SystemAPI?method=getTranslation", {
      terms,
      language: language.code,
    });

    if (response.ok) {
      if (response.data.result.success) {
        const translations = response.data.result.translations;
        await AsyncStorage.setItem(language.code, JSON.stringify(translations));
        console.log(language.displayNameEnglish + " translations saved");
      } else {
        // error
        console.log(response);
      }
    } else {
      console.log(response);
      // no data yet
    }
  });
}

export async function getDefaultTranslation(term, language, libraryUrl) {
  const api = create({
    baseURL: libraryUrl + "/API",
    timeout: GLOBALS.timeoutAverage,
    headers: getHeaders(),
    auth: createAuthTokens(),
  });
  const response = await api.get("/SystemAPI?method=getDefaultTranslation", {
    term,
    languageCode: language,
  });

  if (response.ok) {
  } else {
    console.log(response);
    // no data yet
  }
}

export function getLanguageDisplayName(code, languages) {
  let language = _.filter(languages, ["code", code]);
  language = _.values(language[0]);
  return language[2];
}

export async function getAvailableTranslations() {}

export let translations = {};

export async function getTranslatedTerm(language, url) {
  let defaults = require('../translations/defaults.json');
  _.map(defaults, async function (terms, index, array) {
    const api = create({
      baseURL: url + "/API",
      timeout: GLOBALS.timeoutFast,
      headers: getHeaders(true),
      auth: createAuthTokens(),
    });
    const response = await api.get("/SystemAPI?method=getTranslation", {
      terms: _.toArray(terms),
      language
    });
    if(response.ok) {
      const translation = response?.data?.result?.translations ?? terms;
      if(_.isObject(translation)) {
        const obj = {
          [language]: {
            [index]: translation,
          }
        }
        _.merge(translations, obj);
      }
      return true;
    }
    return false;
  })
}