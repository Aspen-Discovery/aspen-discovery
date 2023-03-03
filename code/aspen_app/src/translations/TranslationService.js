import React from 'react';
import { MaterialIcons } from '@expo/vector-icons';
import {create} from 'apisauce';
import _ from 'lodash';
import { Box, Button, Icon, Menu, Pressable } from 'native-base';

import {createAuthTokens, getHeaders} from '../util/apiAuth';
import {GLOBALS} from '../util/globals';
import {LanguageContext, LibrarySystemContext} from '../context/initialContext';
import {saveLanguage} from '../util/api/user';
import defaults from './defaults.json';

/** *******************************************************************
 * General
 ******************************************************************* **/
export const LanguageSwitcher = () => {
  const { library } = React.useContext(LibrarySystemContext);
  const { language, updateLanguage, languages, updateDictionary } = React.useContext(LanguageContext);
  const [label, setLabel] = React.useState(getLanguageDisplayName(language, languages));

  const changeLanguage = async (val) => {
    await saveLanguage(val, library.baseUrl).then(async result => {
      if(result) {
        updateLanguage(val);
        setLabel(getLanguageDisplayName(val, languages));
      } else {
        console.log("there was an error updating the language...");
      }
    });
  };

  if(_.isArray(languages) && _.size(languages) > 1) {
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
                    return <Menu.ItemOption key={index} value={language.code}>{language.displayName}</Menu.ItemOption>;
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
    baseURL: url + "/API",
    timeout: GLOBALS.timeoutAverage,
    headers: getHeaders(),
    auth: createAuthTokens(),
  });
  const response = await api.get("/SystemAPI?method=getTranslation", {term, language});
  if (response.ok) {
    return response?.data?.result?.translations[term] ?? term;
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
    baseURL: url + "/API",
    timeout: GLOBALS.timeoutAverage,
    headers: getHeaders(),
    auth: createAuthTokens(),
  });
  const response = await api.get("/SystemAPI?method=getTranslation", {
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
 * Returns the display name for the given language code
 * @param {string} code
 * @param {string} languages
 **/
export function getLanguageDisplayName(code, languages) {
  let language = _.filter(languages, ["code", code]);
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
        baseURL: url + "/API",
        timeout: GLOBALS.timeoutFast,
        headers: getHeaders(true),
        auth: createAuthTokens(),
        params: {
            language
        },
    });
    const response = await api.post("/SystemAPI?method=getBulkTranslations", {
        terms: defaults,
    },
        {
            headers: {
                'Content-Type': 'application/json'
            }
        }
    );
    if(response.ok) {
        const translation = response?.data?.result[language] ?? defaults;
        if(_.isObject(translation)) {
            const obj = {
                [language]: translation
            }
            translationsLibrary = _.merge(translationsLibrary, obj);
        }
    } else {
        // error, just plug in defaults for given language
        const obj = {
            [language]: defaults
        }
        translationsLibrary = _.merge(translationsLibrary, obj);
    }
}

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

export async function getTranslatedTermsForAllLanguages(languages, url) {
    _.map(languages, async function (language) {
        console.log("Getting translations for " + language + "...");
        await getTranslatedTerm(language, url);
    })
}

export const getTermFromDictionary = (language, key) => {
    if(language && key) {
        const { dictionary } = React.useContext(LanguageContext);
        if(dictionary[language]) {
            const thisDictionary = dictionary[language];
            if(thisDictionary[key]) {
                return dictionary[language][key]
            } else {
                if(dictionary.en) {
                    const englishDictionary = dictionary.en;
                    if(englishDictionary[key]) {
                        return englishDictionary[key]
                    }
                }
            }
        }
    }
    let defaults = require('../translations/defaults.json');
    return defaults[key]
}