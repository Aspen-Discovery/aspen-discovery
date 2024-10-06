import { MaterialIcons } from '@expo/vector-icons';
import { create } from 'apisauce';
import _ from 'lodash';
import moment from 'moment';
import { Box, Button, Icon, Menu, Pressable } from 'native-base';
import React from 'react';
import { LanguageContext, LibrarySystemContext } from '../context/initialContext';
import { saveLanguage } from '../util/api/user';

import { createAuthTokens, decodeHTML, getHeaders } from '../util/apiAuth';
import { GLOBALS } from '../util/globals';

/** *******************************************************************
 * General
 ******************************************************************* **/
export const LanguageSwitcher = () => {
     const { library } = React.useContext(LibrarySystemContext);
     const { language, updateLanguage, languages, updateDictionary, languageDisplayName, updateLanguageDisplayName } = React.useContext(LanguageContext);
     const [label, setLabel] = React.useState(getLanguageDisplayName(language, languages));

     const changeLanguage = async (val) => {
          await saveLanguage(val, library.baseUrl).then(async (result) => {
               if (result) {
                    updateLanguage(val);
                    updateLanguageDisplayName(getLanguageDisplayName(val, languages));
                    await getTranslatedTermsForUserPreferredLanguage(val, library.baseUrl).then(() => {
                         updateDictionary(translationsLibrary);
                    });
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
                                             {languageDisplayName}
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
                    const lastUpdated = {
                         lastUpdated: moment(),
                    };
                    translationsLibrary = _.merge(translationsLibrary, lastUpdated);

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
     return decodeHTML(term);
}

/**
 * Returns the display name for the given language code
 * @param {string} code
 * @param {string} languages
 **/
export function getLanguageDisplayName(code, languages) {
     let language = _.filter(languages, ['code', code]);
     language = _.values(language[0]);
     return language[3];
}

/**
 * Local storage for translated terms
 */
export let translationsLibrary = {
     lastUpdated: moment(),
};

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
          const lastUpdated = {
               lastUpdated: moment(),
          };
          translationsLibrary = _.merge(translationsLibrary, lastUpdated);
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

/**
 * Returns translation of terms used in Aspen LiDA for the given language
 * @param {array} terms
 * @param {string} language
 * @param {string} url
 **/
async function getTranslatedTermWithValues(terms, language, url) {
     _.map(terms, async function (term) {
          await getTranslationsWithValues(term.key, term.value, language, url, true);
     });
}

/**
 * Updates dictionary for translations used in Aspen LiDA for the given language
 * @param {string} language // the language code used in Aspen Discovery
 * @param {string} url
 **/
export async function getTranslatedTermsForUserPreferredLanguage(language, url) {
     console.log('Getting translations for ' + language + '...');

     await getTranslatedTerm(language, url);

     console.log('getTranslatedTermsForUserPreferredLanguage:' + translationsLibrary.lastUpdated);

     const titlesOnHold = [
          {
               key: 'titles_on_hold_for_ils',
               value: getTermFromDictionary(language, 'physical_materials'),
          },
          {
               key: 'titles_on_hold_for_libby',
               value: getTermFromDictionary(language, 'libby'),
          },
          {
               key: 'titles_on_hold_for_cloud_library',
               value: getTermFromDictionary(language, 'cloud_library'),
          },
          {
               key: 'titles_on_hold_for_boundless',
               value: getTermFromDictionary(language, 'boundless'),
          },
          {
               key: 'titles_on_hold_for_palace_project',
               value: getTermFromDictionary(language, 'palace_project'),
          },
     ];
     const checkouts = [
          {
               key: 'checkouts_for_ils',
               value: getTermFromDictionary(language, 'physical_materials'),
          },
          {
               key: 'checkouts_for_libby',
               value: getTermFromDictionary(language, 'libby'),
          },
          {
               key: 'checkouts_for_hoopla',
               value: getTermFromDictionary(language, 'hoopla'),
          },
          {
               key: 'checkouts_for_cloud_library',
               value: getTermFromDictionary(language, 'cloud_library'),
          },
          {
               key: 'checkouts_for_boundless',
               value: getTermFromDictionary(language, 'boundless'),
          },
          {
               key: 'checkouts_for_palace_project',
               value: getTermFromDictionary(language, 'palace_project'),
          },
     ];
     const filterBy = [
          {
               key: 'filter_by_ils',
               value: getTermFromDictionary(language, 'physical_materials'),
          },
          {
               key: 'filter_by_libby',
               value: getTermFromDictionary(language, 'libby'),
          },
          {
               key: 'filter_by_hoopla',
               value: getTermFromDictionary(language, 'hoopla'),
          },
          {
               key: 'filter_by_cloud_library',
               value: getTermFromDictionary(language, 'cloud_library'),
          },
          {
               key: 'filter_by_boundless',
               value: getTermFromDictionary(language, 'boundless'),
          },
          {
               key: 'filter_by_palace_project',
               value: getTermFromDictionary(language, 'palace_project'),
          },
          {
               key: 'filter_by_all',
               value: getTermFromDictionary(language, 'all'),
          },
     ];
     const sortBy = [
          {
               key: 'sort_by_title',
               value: getTermFromDictionary(language, 'title'),
          },
          {
               key: 'sort_by_author',
               value: getTermFromDictionary(language, 'author'),
          },
          {
               key: 'sort_by_format',
               value: getTermFromDictionary(language, 'format'),
          },
          {
               key: 'sort_by_status',
               value: getTermFromDictionary(language, 'status'),
          },
          {
               key: 'sort_by_date_placed',
               value: getTermFromDictionary(language, 'date_placed'),
          },
          {
               key: 'sort_by_position',
               value: getTermFromDictionary(language, 'position'),
          },
          {
               key: 'sort_by_pickup_location',
               value: getTermFromDictionary(language, 'pickup_location'),
          },
          {
               key: 'sort_by_library_account',
               value: getTermFromDictionary(language, 'library_account'),
          },
          {
               key: 'sort_by_expiration',
               value: getTermFromDictionary(language, 'expiration'),
          },
          {
               key: 'sort_by_date_added',
               value: getTermFromDictionary(language, 'date_added'),
          },
          {
               key: 'sort_by_recently_added',
               value: getTermFromDictionary(language, 'recently_added'),
          },
          {
               key: 'sort_by_user_defined',
               value: getTermFromDictionary(language, 'user_defined'),
          },
          {
               key: 'sort_by_due_asc',
               value: getTermFromDictionary(language, 'due_asc'),
          },
          {
               key: 'sort_by_due_desc',
               value: getTermFromDictionary(language, 'due_desc'),
          },
          {
               key: 'sort_by_times_renewed',
               value: getTermFromDictionary(language, 'times_renewed'),
          },
     ];

     await getTranslatedTermWithValues(titlesOnHold, language, url);
     await getTranslatedTermWithValues(checkouts, language, url);
     await getTranslatedTermWithValues(filterBy, language, url);
     await getTranslatedTermWithValues(sortBy, language, url);

     return true;
}

export async function getTranslatedTermsForAllLanguages(languages, url) {
     const languagesArray = [];
     _.forEach(languages, function (value) {
          languagesArray.push(value.code);
     });
     _.map(languagesArray, async function (language) {
          console.log('Getting translations for ' + language + '...');
          await getTranslatedTerm(language, url);

          const titlesOnHold = [
               {
                    key: 'titles_on_hold_for_ils',
                    value: getTermFromDictionary(language, 'physical_materials'),
               },
               {
                    key: 'titles_on_hold_for_libby',
                    value: getTermFromDictionary(language, 'libby'),
               },
               {
                    key: 'titles_on_hold_for_cloud_library',
                    value: getTermFromDictionary(language, 'cloud_library'),
               },
               {
                    key: 'titles_on_hold_for_boundless',
                    value: getTermFromDictionary(language, 'boundless'),
               },
               {
                    key: 'titles_on_hold_for_palace_project',
                    value: getTermFromDictionary(language, 'palace_project'),
               },
          ];
          const checkouts = [
               {
                    key: 'checkouts_for_ils',
                    value: getTermFromDictionary(language, 'physical_materials'),
               },
               {
                    key: 'checkouts_for_libby',
                    value: getTermFromDictionary(language, 'libby'),
               },
               {
                    key: 'checkouts_for_hoopla',
                    value: getTermFromDictionary(language, 'hoopla'),
               },
               {
                    key: 'checkouts_for_cloud_library',
                    value: getTermFromDictionary(language, 'cloud_library'),
               },
               {
                    key: 'checkouts_for_boundless',
                    value: getTermFromDictionary(language, 'boundless'),
               },
               {
                    key: 'checkouts_for_palace_project',
                    value: getTermFromDictionary(language, 'palace_project'),
               },
          ];
          const filterBy = [
               {
                    key: 'filter_by_ils',
                    value: getTermFromDictionary(language, 'physical_materials'),
               },
               {
                    key: 'filter_by_libby',
                    value: getTermFromDictionary(language, 'libby'),
               },
               {
                    key: 'filter_by_hoopla',
                    value: getTermFromDictionary(language, 'hoopla'),
               },
               {
                    key: 'filter_by_cloud_library',
                    value: getTermFromDictionary(language, 'cloud_library'),
               },
               {
                    key: 'filter_by_boundless',
                    value: getTermFromDictionary(language, 'boundless'),
               },
               {
                    key: 'filter_by_palace_project',
                    value: getTermFromDictionary(language, 'palace_project'),
               },
               {
                    key: 'filter_by_all',
                    value: getTermFromDictionary(language, 'all'),
               },
          ];
          const sortBy = [
               {
                    key: 'sort_by_title',
                    value: getTermFromDictionary(language, 'title'),
               },
               {
                    key: 'sort_by_author',
                    value: getTermFromDictionary(language, 'author'),
               },
               {
                    key: 'sort_by_format',
                    value: getTermFromDictionary(language, 'format'),
               },
               {
                    key: 'sort_by_status',
                    value: getTermFromDictionary(language, 'status'),
               },
               {
                    key: 'sort_by_date_placed',
                    value: getTermFromDictionary(language, 'date_placed'),
               },
               {
                    key: 'sort_by_position',
                    value: getTermFromDictionary(language, 'position'),
               },
               {
                    key: 'sort_by_pickup_location',
                    value: getTermFromDictionary(language, 'pickup_location'),
               },
               {
                    key: 'sort_by_library_account',
                    value: getTermFromDictionary(language, 'library_account'),
               },
               {
                    key: 'sort_by_expiration',
                    value: getTermFromDictionary(language, 'expiration'),
               },
               {
                    key: 'sort_by_date_added',
                    value: getTermFromDictionary(language, 'date_added'),
               },
               {
                    key: 'sort_by_recently_added',
                    value: getTermFromDictionary(language, 'recently_added'),
               },
               {
                    key: 'sort_by_user_defined',
                    value: getTermFromDictionary(language, 'user_defined'),
               },
          ];

          await getTranslatedTermWithValues(titlesOnHold, language, url);
          await getTranslatedTermWithValues(checkouts, language, url);
          await getTranslatedTermWithValues(filterBy, language, url);
          await getTranslatedTermWithValues(sortBy, language, url);
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
