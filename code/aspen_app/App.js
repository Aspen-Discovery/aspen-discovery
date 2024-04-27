import 'expo-dev-client';
import { config } from '@gluestack-ui/config';
import { GluestackUIProvider, useColorMode } from '@gluestack-ui/themed-native-base';
import { SSRProvider } from '@react-aria/ssr';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { NativeBaseProvider, StatusBar } from 'native-base';
import React from 'react';
import Toast from 'react-native-toast-message';

import { LogBox } from 'react-native';

import { enableScreens } from 'react-native-screens';
import * as Sentry from '@sentry/react-native';
import App from './src/components/navigation';
import { ThemeContext } from './src/context/initialContext';

import { SplashScreenNative } from './src/screens/Auth/SplashNative';
import { createTheme, saveTheme } from './src/themes/theme';

enableScreens();

// react query client instance
const queryClient = new QueryClient({
     defaultOptions: {
          queries: {
               staleTime: 1000 * 60 * 60 * 24,
               cacheTime: 1000 * 60 * 60 * 24,
          },
     },
});

// Hide log error/warning popups in simulator (useful for demoing)
const IGNORED_LOGS = ['Non-serializable values were found in the navigation state', 'Warning: ...', 'Warn: ...', 'If you do not provide children, you must specify an aria-label for accessibility '];
LogBox.ignoreLogs(IGNORED_LOGS);
LogBox.ignoreAllLogs(); //Ignore all log notifications
// Workaround for Expo 45
if (__DEV__) {
     const withoutIgnored =
          (logger) =>
          (...args) => {
               const output = args.join(' ');

               if (!IGNORED_LOGS.some((log) => output.includes(log))) {
                    logger(...args);
               }
          };

     console.log = withoutIgnored(console.log);
     console.info = withoutIgnored(console.info);
     console.warn = withoutIgnored(console.warn);
     console.error = withoutIgnored(console.error);
}

export default function AppContainer() {
     const [isLoading, setLoading] = React.useState(true);
     const [aspenTheme, setAspenTheme] = React.useState([]);
     const [colorMode, setColorMode] = React.useState(null);
     const { mode, updateColorMode } = React.useContext(ThemeContext);
     const [statusBarColor, setStatusBarColor] = React.useState('light-content');

     const glueColorMode = useColorMode();

     React.useEffect(() => {
          const setupNativeBaseTheme = async () => {
               console.log('Running setupNativeBaseTheme...');
               try {
                    await AsyncStorage.getItem('@colorMode').then(async (mode) => {
                         if (mode === 'light' || mode === 'dark') {
                              setColorMode(mode);
                              updateColorMode(mode);
                         } else {
                              setColorMode('light');
                              updateColorMode('light');
                         }
                    });
               } catch (e) {
                    // something went wrong (or the item didn't exist yet in storage)
                    // so just set it to the default: light
                    setColorMode('light');
                    updateColorMode('light');
               }

               if (colorMode) {
                    await createTheme(colorMode).then(async (result) => {
                         setAspenTheme(result);
                         if (result.colors?.primary['baseContrast'] === '#000000') {
                              setStatusBarColor('dark-content');
                         } else {
                              setStatusBarColor('light-content');
                         }
                         await saveTheme(result);
                    });

                    setLoading(false);
               }
          };
          setupNativeBaseTheme().then(() => {
               return () => setupNativeBaseTheme();
          });
     }, [colorMode, mode]);

     if (isLoading) {
          return <SplashScreenNative />;
     }

     return (
          <QueryClientProvider client={queryClient}>
               <SSRProvider>
                    <Sentry.TouchEventBoundary>
                         <GluestackUIProvider config={config}>
                              <NativeBaseProvider theme={aspenTheme}>
                                   <StatusBar barStyle={statusBarColor} />
                                   <App />
                              </NativeBaseProvider>
                         </GluestackUIProvider>
                    </Sentry.TouchEventBoundary>
               </SSRProvider>
               <Toast />
          </QueryClientProvider>
     );
}