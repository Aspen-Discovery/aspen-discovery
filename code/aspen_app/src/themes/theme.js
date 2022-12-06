import { MaterialIcons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'apisauce';
import chroma from 'chroma-js';
import _ from 'lodash';
import { extendTheme, Box, Icon, IconButton, useColorMode, useColorModeValue } from 'native-base';
import React, { useState } from 'react';

import { createAuthTokens, getHeaders } from '../util/apiAuth';
import { GLOBALS } from '../util/globals';
import { getAppSettings, getLibraryInfo } from '../util/loadLibrary';

export const ThemeData = () => {
     const [value, setValue] = useState([]);
     const discovery = create({
          baseURL: GLOBALS.url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: GLOBALS.themeId,
          },
     });
     discovery
          .get('/SystemAPI?method=getThemeInfo')
          .then((response) => {
               let theme = [];
               if (!_.isUndefined(response.data.result.theme)) {
                    const result = response.data.result.theme;
                    const COLOR_SCHEMES = [result.primaryBackgroundColor, result.secondaryBackgroundColor, result.tertiaryBackgroundColor];
                    theme = COLOR_SCHEMES.map(generateSwatches);
               } else {
                    const COLOR_SCHEMES = ['#3dbdd6', '#9acf87', '#c1adcc'];
                    theme = COLOR_SCHEMES.map(generateSwatches);
               }
               setValue(theme);
          })
          .catch((err) => {
               console.log(err);
          });

     return value;
};

const getThemeId = () => {
     const [value, setValue] = useState();
     const discovery = create({
          baseURL: GLOBALS.url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: GLOBALS.libraryId,
          },
     });
     discovery
          .get('/SystemAPI?method=getLibraryInfo')
          .then((response) => {
               const data = response.data.result.success;
               let themeId = 1;
               if (!_.isUndefined(data)) {
                    themeId = data.themeId;
               }
               setValue(themeId);
          })
          .catch((err) => {
               console.log(err);
          });

     return value;
};

export async function getThemeInfo() {
     await getAppSettings(GLOBALS.url, 10000, GLOBALS.slug);
     const api = create({
          baseURL: GLOBALS.url + '/API',
          timeout: 10000,
          headers: getHeaders(),
          auth: createAuthTokens(),
     });
     const response = await api.get('/SystemAPI?method=getThemeInfo', {
          id: GLOBALS.themeId ?? 1,
     });
     if (response.ok) {
          const result = response.data.result.theme;
          if (typeof result !== 'undefined') {
               const COLOR_SCHEMES = [result.primaryBackgroundColor, result.secondaryBackgroundColor, result.tertiaryBackgroundColor];
               const palettes = COLOR_SCHEMES.map(generateSwatches);
               console.log('Theme downloaded and swatches generated.');
               return palettes;
          } else {
               const COLOR_SCHEMES = ['#3dbdd6', '#9acf87', '#c1adcc'];
               const palettes = COLOR_SCHEMES.map(generateSwatches);
               console.log('Backup theme loaded.');
               //console.log(response);
               return palettes;
          }
     } else {
          const COLOR_SCHEMES = ['#3dbdd6', '#9acf87', '#c1adcc'];
          const palettes = COLOR_SCHEMES.map(generateSwatches);
          console.log('Backup theme loaded.');
          //console.log(response);
          return palettes;
     }
}

const getColorNumber = (index) => (index === 0 ? 50 : index * 100);

const getContrastText = (color) => (chroma.contrast(color, '#ffffff') < 7 ? '#000000' : '#ffffff');

function generateSwatches(swatch) {
     const LIGHTNESS_MAP = [0.95, 0.85, 0.75, 0.65, 0.55, 0.45, 0.35, 0.25, 0.15, 0.05];
     const SATURATION_MAP = [0.32, 0.16, 0.08, 0.04, 0, 0, 0.04, 0.08, 0.16, 0.32];
     const HUE_MAP = [0, 4, 8, 12, 16, 20, 24, 28, 32, 36];

     let primaryColor = swatch.replace('#', '');
     if (!chroma.valid(primaryColor)) {
          primaryColor = '#C70833';
     }
     const lightnessGoal = chroma(primaryColor).get('hsl.l');

     const closestLightness = LIGHTNESS_MAP.reduce((prev, curr) => (Math.abs(curr - lightnessGoal) < Math.abs(prev - lightnessGoal) ? curr : prev));

     const baseColorIndex = LIGHTNESS_MAP.findIndex((l) => l === closestLightness);

     const colors = LIGHTNESS_MAP.map((l) => chroma(primaryColor).set('hsl.l', l))
          .map((color) => chroma(color))
          .map((color, i) => {
               const saturationDelta = SATURATION_MAP[i] - SATURATION_MAP[baseColorIndex];
               return saturationDelta >= 0 ? color.saturate(saturationDelta) : color.desaturate(saturationDelta * -1);
          });

     const colorsHueUp = colors.map((color, i) => {
          const hueDelta = HUE_MAP[i] - HUE_MAP[baseColorIndex];
          return hueDelta >= 0 ? color.set('hsl.h', `+${hueDelta}`) : color.set('hsl.h', `+${(hueDelta * -1) / 2}`);
     });

     const colorsHueDown = colors.map((color, i) => {
          const hueDelta = HUE_MAP[i] - HUE_MAP[baseColorIndex];
          return hueDelta >= 0 ? color.set('hsl.h', `-${hueDelta}`) : color.set('hsl.h', `-${(hueDelta * -1) / 2}`);
     });

     const object = {};
     const properties = colors.map((color, i) => {
          const num = getColorNumber(i);
          const baseIndex = getColorNumber(baseColorIndex);
          if (baseIndex === num) {
               var baseColor = color.hex();
               var baseContrast = getContrastText(baseColor);
          }
          const numContrast = num + '-text';
          const property = {
               [num]: color.hex(),
               [numContrast]: getContrastText(color),
               base: baseColor,
               baseContrast,
          };
          _.merge(object, property);
     });

     return object;
}

export async function createTheme() {
     const response = await getThemeInfo();
     const theme = extendTheme({
          colors: {
               primary: response[0],
               secondary: response[1],
               tertiary: response[2],
          },
          config: {
               useAccessibleColors: true,
          },
     });
     console.log('Theme created and saved.');
     return theme;
}

export async function saveTheme() {
     await createTheme().then(async (response) => {
          const primaryColors = ['primaryColors', JSON.stringify(response.colors.primary)];
          const secondaryColors = ['secondaryColors', JSON.stringify(response.colors.secondary)];
          const tertiaryColors = ['tertiaryColors', JSON.stringify(response.colors.tertiary)];

          try {
               await AsyncStorage.multiSet([primaryColors, secondaryColors, tertiaryColors]);
               console.log('Essential colors stored in async storage in theme.js');
          } catch (e) {
               //save error
               console.log('Unable to save essential colors to async storage in theme.js');
               console.log(e);
          }
     });
}

export async function fetchTheme() {
     let colors;
     try {
          colors = await AsyncStorage.multiGet(['primaryColors', 'secondaryColors', 'tertiaryColors']);
          const jsonValue = await AsyncStorage.getItem('primaryColors');
          const parsedJson = JSON.parse(jsonValue);
          //console.log(parsedJson);
          console.log('Essential colors fetched from async storage.');
          return colors;
     } catch (e) {
          console.log('Unable to fetch essential colors from async storage.');
          console.log(e);
     }
}

export function UseColorMode() {
     const { toggleColorMode } = useColorMode();
     const currentMode = useColorModeValue('nightlight-round', 'wb-sunny');
     return (
          <Box alignItems="center">
               <IconButton onPress={toggleColorMode} icon={<Icon as={MaterialIcons} name={currentMode} />} borderRadius="full" _icon={{ size: 'sm' }} />
          </Box>
     );
}