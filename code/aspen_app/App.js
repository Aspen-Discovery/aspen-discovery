import 'expo-dev-client';
import React from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { NativeBaseProvider, StatusBar } from 'native-base';
import { SSRProvider } from '@react-aria/ssr';
import App from './src/components/navigation';
import { createTheme, saveTheme } from './src/themes/theme';
import _ from 'lodash';
import * as Sentry from 'sentry-expo';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
// Access any @sentry/react-native exports via:
// Sentry.Native.*
import { LogBox } from 'react-native';

import { enableScreens } from 'react-native-screens';

import { SplashScreenNative } from './src/screens/Auth/SplashNative';

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
LogBox.ignoreLogs(['Warning: ...']); // Ignore log notification by message
LogBox.ignoreAllLogs(); //Ignore all log notifications

export default function AppContainer() {
     const [isLoading, setLoading] = React.useState(true);
     const [aspenTheme, setAspenTheme] = React.useState([]);
     const [colorMode, setColorMode] = React.useState(null);
     const [statusBarColor, setStatusBarColor] = React.useState('light-content');

     React.useEffect(() => {
          const setupNativeBaseTheme = async () => {
               try {
                    await AsyncStorage.getItem('@colorMode').then(async (mode) => {
                         if (mode === 'light' || mode === 'dark') {
                              setColorMode(mode);
                         } else {
                              setColorMode('light');
                         }
                    });
               } catch (e) {
                    // something went wrong (or the item didn't exist yet in storage)
                    // so just set it to the default: light
                    setColorMode('light');
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
     }, [colorMode]);

     if (isLoading) {
          return <SplashScreenNative />;
     }

     return (
          <QueryClientProvider client={queryClient}>
               <SSRProvider>
                    <Sentry.Native.TouchEventBoundary>
                         <NativeBaseProvider theme={aspenTheme}>
                              <StatusBar barStyle={statusBarColor} />
                              <App />
                         </NativeBaseProvider>
                    </Sentry.Native.TouchEventBoundary>
               </SSRProvider>
          </QueryClientProvider>
     );
}

/*
export default class AppContainer extends Component {
     constructor(props) {
          super(props);
          this.state = {
               themeSet: false,
               themeSetSession: 0,
               user: [],
               library: [],
               location: [],
               browseCategories: [],
               hasLoaded: false,
               appReady: false,
          };
          this.aspenTheme = null;
     }

     componentDidMount = async () => {
          let colorMode = 'light';
          const storedColorMode = await AsyncStorage.getItem('@colorMode');
          console.log("storedColorMode: " + storedColorMode);
          if(storedColorMode === 'light' || storedColorMode === 'dark') {
               colorMode = storedColorMode;
          }
          console.log('App.js: ' + colorMode);

          this.setState({
               appReady: false
          });

          React.useEffect(async () => {
               if (colorMode) {
                    const fetchTheme = async () => {
                         const response =  await createTheme(colorMode);
                         this.aspenTheme = response;
                         console.log(response);
                         this.setState({
                              themeSet: true,
                              themeSetSession: Constants.sessionId,
                         });
                         this.aspenTheme.colors.primary['baseContrast'] === '#000000' ? this.setState({statusBar: 'dark-content'}) : this.setState({statusBar: 'light-content'});
                         console.log('Theme set from createTheme in App.js');
                         await saveTheme();
                    }

                    fetchTheme()
                    .catch(console.error);
               }
          }, [colorMode])

     };

     render() {
          const user = this.state.user;
          const library = this.state.library;
          const location = this.state.location;
          const browseCategories = this.state.browseCategories;

          if (this.state.themeSet) {
               return (
                    <QueryClientProvider client={queryClient}>
                         <userContext.Provider
                              value={{
                                   user,
                                   library,
                                   location,
                                   browseCategories,
                              }}>
                              <SSRProvider>
                                   <Sentry.Native.TouchEventBoundary>
                                        <NativeBaseProvider theme={this.aspenTheme}>
                                             <StatusBar barStyle={this.state.statusBar} />
                                             <App />
                                        </NativeBaseProvider>
                                   </Sentry.Native.TouchEventBoundary>
                              </SSRProvider>
                         </userContext.Provider>
                    </QueryClientProvider>
               );
          } else {
               return (
                    <QueryClientProvider client={queryClient}>
                         <userContext.Provider
                              value={{
                                   user,
                                   library,
                                   location,
                                   browseCategories,
                              }}>
                              <SSRProvider>
                                   <Sentry.Native.TouchEventBoundary>
                                        <NativeBaseProvider>
                                             <StatusBar barStyle="dark-content" />
                                             <SplashScreen />
                                        </NativeBaseProvider>
                                   </Sentry.Native.TouchEventBoundary>
                              </SSRProvider>
                         </userContext.Provider>
                    </QueryClientProvider>
               );
          }
     }
}*/