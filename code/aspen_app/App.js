import 'expo-dev-client';
import React, { Component } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import { Center, HStack, NativeBaseProvider, Spinner, StatusBar } from 'native-base';
import { SSRProvider } from '@react-aria/ssr';
import App from './src/components/navigation';
import { createTheme, saveTheme } from './src/themes/theme';
import { userContext } from './src/context/user';
import { create } from 'apisauce';
import _ from 'lodash';
import * as Sentry from 'sentry-expo';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
// Access any @sentry/react-native exports via:
// Sentry.Native.*
import { LogBox } from 'react-native';
import { createAuthTokens, getHeaders, postData } from './src/util/apiAuth';
import { GLOBALS } from './src/util/globals';

import { enableScreens } from 'react-native-screens';
import { getPatronBrowseCategories } from './src/util/loadPatron';
import { getBrowseCategories, reloadBrowseCategories } from './src/util/loadLibrary';
import { SplashScreen } from './src/screens/Auth/Splash';

enableScreens();

// react query client instance
const queryClient = new QueryClient();

// Hide log error/warning popups in simulator (useful for demoing)
LogBox.ignoreLogs(['Warning: ...']); // Ignore log notification by message
LogBox.ignoreAllLogs(); //Ignore all log notifications

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
          };
          this.aspenTheme = null;
     }

     componentDidMount = async () => {
          this.setState({ appReady: false });
          await createTheme().then(async (response) => {
               if (this.state.themeSetSession !== Constants.sessionId) {
                    this.aspenTheme = response;
                    this.setState({
                         themeSet: true,
                         themeSetSession: Constants.sessionId,
                    });
                    this.aspenTheme.colors.primary['baseContrast'] === '#000000' ? this.setState({ statusBar: 'dark-content' }) : this.setState({ statusBar: 'light-content' });
                    console.log('Theme set from createTheme in App.js');
                    await saveTheme();
               } else {
                    console.log('Theme previously saved.');
               }
          });
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
}