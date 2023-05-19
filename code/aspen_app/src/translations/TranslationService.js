import React from 'react';
import { MaterialIcons } from '@expo/vector-icons';
import { create } from 'apisauce';
import _ from 'lodash';
import { Box, Button, Icon, Menu, Pressable } from 'native-base';

import { createAuthTokens, getHeaders } from '../util/apiAuth';
import { GLOBALS } from '../util/globals';
import { LanguageContext, LibrarySystemContext } from '../context/initialContext';
import { saveLanguage } from '../util/api/user';

/** *******************************************************************
 * General
 ******************************************************************* **/
export const LanguageSwitcher = () => {
     const { library } = React.useContext(LibrarySystemContext);
     const { language, updateLanguage, languages, updateDictionary } = React.useContext(LanguageContext);
     const [label, setLabel] = React.useState(getLanguageDisplayName(language, languages));

     const changeLanguage = async (val) => {
          await saveLanguage(val, library.baseUrl).then(async (result) => {
               if (result) {
                    updateLanguage(val);
                    setLabel(getLanguageDisplayName(val, languages));
               } else {
                    console.log('there was an error updating the language...');
               }
          });
     };

     if (_.isArray(languages) && _.size(languages) > 1) {
          return (
               <Box>
                    <Menu
                         closeOnSelect
                         w="190"
                         trigger={(triggerProps) => {
                              return (
                                   <Pressable {...triggerProps}>
                                        <Button size="sm" variant="ghost" colorScheme="secondary" leftIcon={<Icon as={MaterialIcons} name="language" size="xs" />} {...triggerProps}>
                                             {label}
                                        </Button>
                                   </Pressable>
                              );
                         }}>
                         {_.isArray(languages) ? (
                              <Menu.OptionGroup defaultValue={language} title="Select a Language" type="radio" onChange={(val) => changeLanguage(val)}>
                                   {languages.map((language, index) => {
                                        return (
                                             <Menu.ItemOption key={index} value={language.code}>
                                                  {language.displayName}
                                             </Menu.ItemOption>
                                        );
                                   })}
                              </Menu.OptionGroup>
                         ) : null}
                    </Menu>
               </Box>
          );
     }

     return null;
};

/**
 * Returns translation of a single term for the given language
 * @param {string} term
 * @param {string} language
 * @param {string} url
 **/
export async function getTranslation(term, language, url) {
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(),
          auth: createAuthTokens(),
     });
     const response = await api.get('/SystemAPI?method=getTranslation', { term, language });
     if (response.ok) {
          if (response.data?.success) {
               if (response?.data?.result[language][term]) {
                    console.log(response?.data?.result[language][term]);
                    return Object.values(response?.data?.result[language][term]);
               }
          }
     }
     // return the original term as a fallback
     return term;
}

/**
 * Returns translation of an array of terms for the given language
 * @param {array} terms
 * @param {string} language
 * @param {string} url
 **/
export async function getTranslations(terms, language, url) {
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(),
          auth: createAuthTokens(),
     });
     const response = await api.get('/SystemAPI?method=getTranslation', {
          terms: terms,
          language: language,
     });

     if (response.ok) {
          return response.data.result.translations;
     } else {
          console.log(response);
          // no data yet
     }
}

/**
 * Returns translation of a term with interchangeable values in the given language
 * getTranslationsWithValues('last_updated_on', $value, 'en', $url)
 * getTranslationsWithValues('filter_by_source', [$value1, $value2], 'en', $url)
 *
 * @param {string} key
 * @param {array || string} values
 * @param {string} language
 * @param {string} url
 * @param {boolean} addToDictionary
 **/
export async function getTranslationsWithValues(key, values, language, url, addToDictionary = false) {
     let defaults = require('../translations/defaults.json');
     const term = defaults[key];

     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(),
          auth: createAuthTokens(),
     });
     const response = await api.get('/SystemAPI?method=getTranslationWithValues', {
          term,
          values: values,
          language: language,
     });

     if (response.ok) {
          if (response.data?.result?.translation) {
               if (Object.values(response.data?.result?.translation) && addToDictionary) {
                    const translation = Object.values(response.data?.result?.translation);
                    const obj = {
                         [language]: {
                              [key]: translation[0],
                         },
                    };
                    translationsLibrary = _.merge(translationsLibrary, obj);
               }
               return Object.values(response.data?.result?.translation);
          }
     }
     // it didn't work we should return the untranslated term back
     return term;
}

/**
 * Returns the display name for the given language code
 * @param {string} code
 * @param {string} languages
 **/
export function getLanguageDisplayName(code, languages) {
     let language = _.filter(languages, ['code', code]);
     language = _.values(language[0]);
     return language[2];
}

/**
 * Local storage for translated terms
 */
export let translationsLibrary = {};

/**
 * Returns translation of terms used in Aspen LiDA for the given language
 * @param {string} language
 * @param {string} url
 **/
export async function getTranslatedTerm(language, url) {
     // Load in the terms used for Aspen LiDA
     let defaults = require('../translations/defaults.json');
     const api = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               language,
          },
     });
     const response = await api.post('/SystemAPI?method=getBulkTranslations', { terms: defaults }, { headers: { 'Content-Type': 'application/json' } });
     if (response.ok) {
          const translation = response?.data?.result[language] ?? defaults;
          if (_.isObject(translation)) {
               const obj = {
                    [language]: translation,
               };
               translationsLibrary = _.merge(translationsLibrary, obj);
          }
     } else {
          // error, just plug in defaults for given language
          const obj = {
               [language]: defaults,
          };
          translationsLibrary = _.merge(translationsLibrary, obj);
     }
}

export async function getTranslatedTermsForAllLanguages(languages, url) {
     const languagesArray = [];
     _.forEach(languages, function (value) {
          languagesArray.push(value.code);
     });
     _.map(languagesArray, async function (language) {
          console.log('Getting translations for ' + language + '...');
          await getTranslatedTerm(language, url);
          await getTranslationsWithValues('titles_on_hold_for_ils', 'Physical Materials', language, url, true);
          await getTranslationsWithValues('titles_on_hold_for_overdrive', 'OverDrive', language, url, true);
          await getTranslationsWithValues('titles_on_hold_for_cloud_library', 'cloudLibrary', language, url, true);
          await getTranslationsWithValues('titles_on_hold_for_axis_360', 'Axis 360', language, url, true);
          await getTranslationsWithValues('checkouts_for_ils', 'Physical Materials', language, url, true);
          await getTranslationsWithValues('checkouts_for_overdrive', 'OverDrive', language, url, true);
          await getTranslationsWithValues('checkouts_for_hoopla', 'Hoopla', language, url, true);
          await getTranslationsWithValues('checkouts_for_cloud_library', 'cloudLibrary', language, url, true);
          await getTranslationsWithValues('checkouts_for_axis_360', 'Axis 360', language, url, true);
          await getTranslationsWithValues('filter_by_ils', 'Physical Materials', language, url, true);
          await getTranslationsWithValues('filter_by_overdrive', 'OverDrive', language, url, true);
          await getTranslationsWithValues('filter_by_cloud_library', 'cloudLibrary', language, url, true);
          await getTranslationsWithValues('filter_by_axis_360', 'Axis 360', language, url, true);
          await getTranslationsWithValues('filter_by_hoopla', 'Hoopla', language, url, true);
          await getTranslationsWithValues('filter_by_all', 'All', language, url, true);
          await getTranslationsWithValues('sort_by_title', 'Title', language, url, true);
          await getTranslationsWithValues('sort_by_author', 'Author', language, url, true);
          await getTranslationsWithValues('sort_by_format', 'Format', language, url, true);
          await getTranslationsWithValues('sort_by_status', 'Status', language, url, true);
          await getTranslationsWithValues('sort_by_date_placed', 'Date Placed', language, url, true);
          await getTranslationsWithValues('sort_by_position', 'Position', language, url, true);
          await getTranslationsWithValues('sort_by_pickup_location', 'Pickup Location', language, url, true);
          await getTranslationsWithValues('sort_by_library_account', 'Library Account', language, url, true);
          await getTranslationsWithValues('sort_by_expiration', 'Expiration Date', language, url, true);
          await getTranslationsWithValues('sort_by_date_added', 'Date Added', language, url, true);
          await getTranslationsWithValues('sort_by_recently_added', 'Recently Added', language, url, true);
          await getTranslationsWithValues('sort_by_user_defined', 'User Defined', language, url, true);
     });
     return true;
}

export const getTermFromDictionary = (language = 'en', key, ellipsis = false) => {
     if (language && key) {
          let tmpDictionary = translationsLibrary;
          try {
               const { dictionary } = React.useContext(LanguageContext);
               if (!_.isUndefined(dictionary)) {
                    tmpDictionary = dictionary;
               }
          } catch (e) {
               // can't use context in this scenario
          }
          if (!_.isUndefined(tmpDictionary)) {
               if (tmpDictionary[language]) {
                    const thisDictionary = tmpDictionary[language];
                    if (thisDictionary[key]) {
                         if (ellipsis) {
                              return tmpDictionary[language][key] + '...';
                         }
                         return tmpDictionary[language][key];
                    } else {
                         if (tmpDictionary.en) {
                              const englishDictionary = tmpDictionary.en;
                              if (englishDictionary[key]) {
                                   if (ellipsis) {
                                        return englishDictionary[key] + '...';
                                   }
                                   return englishDictionary[key];
                              }
                         }
                    }
               }
          }
     }
     let defaults = require('../translations/defaults.json');
     if (ellipsis) {
          return defaults[key] + '...';
     }
     return defaults[key];
};

export const getVariableTermFromDictionary = async (language, key, url) => {
     if (language && key) {
          let tmpDictionary = translationsLibrary;
          try {
               const { dictionary } = React.useContext(LanguageContext);
               if (!_.isUndefined(dictionary)) {
                    tmpDictionary = dictionary;
               }
          } catch (e) {
               // can't use context in this scenario
          }
          if (tmpDictionary[language]) {
               const thisDictionary = tmpDictionary[language];
               if (thisDictionary[key]) {
                    console.log(Object.values(tmpDictionary[language][key]));
                    return Object.values(tmpDictionary[language][key]);
               } else {
                    // fetch translated term from Discovery and add to dictionary for later
                    //const {library} = React.useContext(LibrarySystemContext);
                    let localDictionary = tmpDictionary;
                    const term = await getTranslation(key, language, url);
                    const obj = {
                         [language]: {
                              [key]: term,
                         },
                    };
                    localDictionary = _.merge(localDictionary, obj);
                    translationsLibrary = _.merge(translationsLibrary, obj);
                    //updateDictionary(localDictionary);
               }
          }
     }
     return key;
};

/*
 export async function getTranslatedTerm_Original(language, url) {
 // Load in the terms used for Aspen LiDA
 let defaults = require('../translations/defaults.json');
 return Promise.all (
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
 translationsLibrary = _.merge(translationsLibrary, obj);
 }
 } else {
 console.log(response);
 }
 })
 )
 }
 */