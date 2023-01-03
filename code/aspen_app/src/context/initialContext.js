import React, {useState} from 'react';
import _ from 'lodash';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import Constants from 'expo-constants';
import {BRANCH, formatDiscoveryVersion} from '../util/loadLibrary';
import {PATRON} from '../util/loadPatron';

export const ThemeContext = React.createContext({
     theme: [],
     updateTheme: () => {
     },
     resetTheme: () => {
     },
});
export const DiscoveryContext = React.createContext();
export const UserContext = React.createContext({
     updateUser: () => {
     },
     user: [],
     updateLinkedAccounts: () => {
     },
     accounts: [],
     updateLists: () => {
     },
     lists: [],
     updateLanguage: () => {
     },
     language: [],
     updatePickupLocations: () => {
     },
     locations: [],
     cards: [],
     updateCards: () => {
     },
     notificationSettings: [],
     updateNotificationSettings: () => {
     },
     expoToken: false,
     aspenToken: false,
     resetUser: () => {
     },
});
export const LibrarySystemContext = React.createContext({
     updateLibrary: () => {
     },
     library: [],
     version: '',
     url: '',
     resetLibrary: () => {
     },
});
export const LibraryBranchContext = React.createContext({
     updateLocation: () => {
     },
     location: [],
     resetLocation: () => {
     },
     scope: '',
     updateScope: () => {
     },
});
export const BrowseCategoryContext = React.createContext({
     updateBrowseCategories: () => {
     },
     category: [],
     updateBrowseCategoryList: () => {
     },
     list: [],
     updateMaxCategories: () => {
     },
     maxNum: 5,
     resetBrowseCategories: () => {
     },
});
export const CheckoutsContext = React.createContext({
     updateCheckouts: () => {
     },
     checkouts: [],
     resetCheckouts: () => {
     },
});
export const HoldsContext = React.createContext({
     updateHolds: () => {
     },
     holds: [],
     resetHolds: () => {
     },
});
export const GroupedWorkContext = React.createContext({
     updateGroupedWork: () => {
     },
     updateFormat: () => {
     },
     updateLanguage: () => {
     },
     groupedWork: [],
     format: [],
     language: [],
     resetGroupedWork: () => {
     },
})

export const ThemeProvider = ({children}) => {
     const [theme, setTheme] = useState([]);

     const updateTheme = (data) => {
          setTheme(data);
     };

     const resetTheme = () => {
          setTheme([]);
     };

     return (
         <ThemeContext.Provider
             value={{
                  theme,
                  updateTheme,
                  resetTheme,
             }}>
              {children}
         </ThemeContext.Provider>
     );
};

export const DiscoveryProvider = ({children}) => {
     const [version, setVersion] = useState();
     const [url, setUrl] = useState();

     const updateVersion = (data) => {
          const thisVersion = formatDiscoveryVersion(data);
          setVersion(thisVersion);
     };

     const updateUrl = (data) => {
          setUrl(data);
     };

     return (
         <DiscoveryContext.Provider
             value={{
                  version,
                  url,
                  updateVersion,
                  updateUrl,
             }}>
              {children}
         </DiscoveryContext.Provider>
     );
};

export const LibrarySystemProvider = ({children}) => {
     const [library, setLibrary] = useState();
     const [version, setVersion] = useState();
     const [url, setUrl] = useState();

     const updateLibrary = (data) => {
          if (!_.isUndefined(data.discoveryVersion)) {
               const discovery = formatDiscoveryVersion(data.discoveryVersion);
               setVersion(discovery);
               console.log('updated version in LibrarySystemContext');
          }

          if (!_.isUndefined(data.baseUrl)) {
               setUrl(data.baseUrl);
               console.log('updated url in LibrarySystemContext');
          }
          setLibrary(data);
          console.log('updated LibrarySystemContext');
     };

     const resetLibrary = () => {
          setLibrary({});
          setVersion({});
          setUrl({});
          console.log('reset LibrarySystemContext');
     };

     return (
         <LibrarySystemContext.Provider
             value={{
                  library,
                  version,
                  url,
                  updateLibrary,
                  resetLibrary,
             }}>
              {children}
         </LibrarySystemContext.Provider>
     );
};

export const LibraryBranchProvider = ({children}) => {
     const [location, setLocation] = useState();
     const [scope, setScope] = useState();

     const updateLocation = (data) => {
          setLocation(data);

          if (!_.isUndefined(data.vdxFormId)) {
               BRANCH.vdxFormId = data.vdxFormId;
          }

          if (!_.isUndefined(data.vdxLocation)) {
               BRANCH.vdxLocation = data.vdxLocation;
          }

          console.log('updated LibraryBranchContext');
     };

     const updateScope = (data) => {
          setScope(data);
          console.log('updated scope in LibraryBranchContext');
     };

     const resetLocation = () => {
          setLocation({});
          setScope({});
          console.log('reset LibraryBranchContext');
     };

     return (
         <LibraryBranchContext.Provider
             value={{
                  location,
                  scope,
                  updateLocation,
                  resetLocation,
                  updateScope,
             }}>
              {children}
         </LibraryBranchContext.Provider>
     );
};

export const UserProvider = ({children}) => {
     const [user, setUser] = useState();
     const [accounts, setLinkedAccounts] = useState();
     const [viewers, setLinkedViewerAccounts] = useState();
     const [lists, setLists] = useState();
     const [language, setLanguage] = useState();
     const [locations, setPickupLocations] = useState();
     const [readingHistory, setReadingHistory] = useState();
     const [cards, setCards] = useState();
     const [notificationSettings, setNotificationSettings] = useState();
     const [expoToken, setExpoToken] = useState();
     const [aspenToken, setAspenToken] = useState();

     const updateUser = (data) => {
          if (_.isObject(data) && !_.isUndefined(data.lastListUsed)) {
               PATRON.listLastUsed = data.lastListUsed;
          }

          if (_.isObject(data) && !_.isUndefined(data.numHolds)) {
               PATRON.num.holds = data.numHolds;
          }

          if (_.isObject(data) && !_.isUndefined(data.notification_preferences)) {
               updateNotificationSettings(data.notification_preferences);
          }

          setUser(data);
          console.log('updated UserContext');
     };

     const resetUser = () => {
          setUser({});
          setLists({});
          setLinkedAccounts({});
          setLanguage({});
          console.log('reset UserContext');
     };

     const updateLists = (data) => {
          setLists(data);
          console.log('updated lists in UserContext');
     };

     const updateLinkedAccounts = (data) => {
          setLinkedAccounts(data);
          console.log('updated linked accounts in UserContext');
     };

     const updateLinkedViewerAccounts = (data) => {
          setLinkedViewerAccounts(data);
          console.log('updated linked viewer accounts in UserContext');
     };

     const updateLanguage = (data) => {
          setLanguage(data);
          console.log('updated language in UserContext');
     };

     const updatePickupLocations = (data) => {
          setPickupLocations(data);
          console.log('updated pickup locations in UserContext');
     };

     const updateReadingHistory = (data) => {
          setReadingHistory(data);
          console.log('updated reading history in UserContext');
     };

     const updateLibraryCards = (data) => {
          setCards(data);
          console.log('updated library cards in UserContext');
     };

     const updateNotificationSettings = async (data) => {
          if (Constants.isDevice) {
               if (!_.isEmpty(data)) {
                    const device = Device.modelName;
                    if (_.find(data, _.matchesProperty('device', device))) {
                         console.log('Found settings for this device model');
                         const deviceSettings = _.filter(data, {device: device});
                         const settings = [];
                         settings.push(
                             {
                                  id: 0,
                                  label: 'Saved searches',
                                  option: 'notifySavedSearch',
                                  description: null,
                                  allow: deviceSettings[0].notifySavedSearch ?? 0,
                             },
                             {
                                  id: 1,
                                  label: 'Alerts from your library',
                                  option: 'notifyCustom',
                                  description: null,
                                  allow: deviceSettings[0].notifyCustom ?? 0,
                             },
                             {
                                  id: 2,
                                  label: 'Alerts about my library account',
                                  option: 'notifyAccount',
                                  description: null,
                                  allow: deviceSettings[0].notifyAccount ?? 0,
                             }
                         );
                         setNotificationSettings(settings);
                         setExpoToken(deviceSettings[0]?.token ?? false);
                         setAspenToken(true);
                    } else {
                         console.log('No settings found for this device model yet');
                         setExpoToken(false);
                         setAspenToken(false);
                    }
               } else {
                    setExpoToken(false);
                    setAspenToken(false);
               }
          } else {
               setExpoToken(false);
               setAspenToken(false);
          }
          //maybe set allowNotifications at this point for initial load?
          console.log('updated notification settings in UserContext');
     };

     return (
         <UserContext.Provider
             value={{
                  user,
                  updateUser,
                  resetUser,
                  lists,
                  updateLists,
                  accounts,
                  updateLinkedAccounts,
                  viewers,
                  updateLinkedViewerAccounts,
                  language,
                  updateLanguage,
                  locations,
                  updatePickupLocations,
                  readingHistory,
                  updateReadingHistory,
                  cards,
                  updateLibraryCards,
                  notificationSettings,
                  updateNotificationSettings,
                  expoToken,
                  aspenToken,
             }}>
              {children}
         </UserContext.Provider>
     );
};

export const BrowseCategoryProvider = ({children}) => {
     const [category, setCategories] = useState();
     const [list, setCategoryList] = useState();
     const [maxNum, setMaxCategories] = useState();

     const updateBrowseCategories = (data) => {
          setCategories(data);
          console.log('updated BrowseCategoryContext');
     };

     const updateBrowseCategoryList = (data) => {
          setCategoryList(data);
          console.log('updated list in BrowseCategoryContext');
     };

     const updateMaxCategories = (data) => {
          setMaxCategories(data);
          console.log('updated max categories in BrowseCategoryContext');
     };

     const resetBrowseCategories = () => {
          setCategories({});
          setCategoryList({});
          console.log('reset BrowseCategoryContext');
     };

     return (
         <BrowseCategoryContext.Provider
             value={{
                  category,
                  list,
                  maxNum,
                  updateBrowseCategories,
                  updateBrowseCategoryList,
                  updateMaxCategories,
                  resetBrowseCategories,
             }}>
              {children}
         </BrowseCategoryContext.Provider>
     );
};

export const CheckoutsProvider = ({children}) => {
     const [checkouts, setCheckouts] = useState();

     const updateCheckouts = (data) => {
          setCheckouts(data);
          console.log('updated CheckoutsContext');
     };

     const resetCheckouts = () => {
          setCheckouts({});
          console.log('reset CheckoutsContext');
     };

     return (
         <CheckoutsContext.Provider
             value={{
                  checkouts,
                  updateCheckouts,
                  resetCheckouts,
             }}>
              {children}
         </CheckoutsContext.Provider>
     );
};

export const HoldsProvider = ({children}) => {
     const [holds, setHolds] = useState();

     const updateHolds = (data) => {
          setHolds(data);
          console.log('updated HoldsContext');
     };

     const resetHolds = () => {
          setHolds({});
          console.log('reset HoldsContext');
     };

     return (
         <HoldsContext.Provider
             value={{
                  holds,
                  updateHolds,
                  resetHolds,
             }}>
              {children}
         </HoldsContext.Provider>
     );
};

export const GroupedWorkProvider = ({children}) => {
     const [groupedWork, setGroupedWork] = useState();
     const [format, setFormat] = useState();
     const [language, setLanguage] = useState();

     const updateGroupedWork = (data) => {
          setGroupedWork(data);
          console.log('updated GroupedWorkContext');

          const keys = _.keys(data.formats);
          setFormat(_.first(keys));
          console.log("updated format in GroupedWorkContext");

          setLanguage(data.language);
          console.log("updated language in GroupedWorkContext");

     }

     const updateFormat = (data) => {
          setFormat(data);
          console.log("updated format in GroupedWorkContext");
     }

     const updateLanguage = (data) => {
          setLanguage(data);
          console.log("updated language in GroupedWorkContext");
     }

     const resetGroupedWork = () => {
          setGroupedWork([]);
          console.log('reset GroupedWorkContext');
     }

     return (
         <GroupedWorkContext.Provider
             value={{groupedWork, format, language, updateGroupedWork, updateFormat, updateLanguage, resetGroupedWork}}>
              {children}
         </GroupedWorkContext.Provider>
     )
}