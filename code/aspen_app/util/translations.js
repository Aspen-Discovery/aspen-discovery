import ReactNative from 'react-native';
import * as Localization from 'expo-localization';
import i18n from 'i18n-js';
import moment from 'moment';

// import language files from locales folder
import en from './locales/en.json';

// sets the locale from the device to determine the best language to load
if (global.interfaceLanguage) {
	i18n.locale = global.interfaceLanguage;
} else {
	i18n.locale = Localization.locale;
}

// when a value is missing from a language it'll fallback to another language with the key present
i18n.fallbacks = true;

// add 2-letter language code to translation map
i18n.translations = {
	en,
};

const currentLocale = i18n.currentLocale();

// is it a RTL language?
export const isRTL = currentLocale.indexOf('he') === 0 || currentLocale.indexOf('ar') === 0;

// allow RTL alignment in RTL languages
//ReactNative.I18nManager.allowRTL(isRTL);

// localizing momentjs to Hebrew or English to format dates
if (currentLocale.indexOf('he') === 0) {
	require('moment/locale/he.js');
	moment.locale('he');
} else {
	moment.locale('en');
}

// The method we'll use to call translations
export function translate(name, params = {}) {
	return i18n.t(name, params);
}


export default i18n;