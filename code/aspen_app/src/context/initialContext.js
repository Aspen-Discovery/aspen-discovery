import React, { useState } from 'react';
import { BRANCH, formatDiscoveryVersion } from '../util/loadLibrary';
import { PATRON } from '../util/loadPatron';
import _ from 'lodash';

export const ThemeContext = React.createContext({
     theme: [],
     updateTheme: () => {},
     resetTheme: () => {},
});
export const DiscoveryContext = React.createContext();
export const UserContext = React.createContext({
     updateUser: () => {},
     user: [],
     updateLinkedAccounts: () => {},
     accounts: [],
     updateLists: () => {},
     lists: [],
     updateLanguage: () => {},
     language: [],
     updatePickupLocations: () => {},
     locations: [],
     resetUser: () => {},
});
export const LibrarySystemContext = React.createContext({
     updateLibrary: () => {},
     library: [],
     version: '',
     url: '',
     resetLibrary: () => {},
});
export const LibraryBranchContext = React.createContext({
     updateLocation: () => {},
     location: [],
     resetLocation: () => {},
     scope: '',
     updateScope: () => {},
});
export const BrowseCategoryContext = React.createContext({
     updateBrowseCategories: () => {},
     category: [],
     updateBrowseCategoryList: () => {},
     list: [],
     updateMaxCategories: () => {},
     maxNum: 5,
     resetBrowseCategories: () => {},
});
export const CheckoutsContext = React.createContext({
     updateCheckouts: () => {},
     checkouts: [],
     resetCheckouts: () => {},
});
export const HoldsContext = React.createContext({
     updateHolds: () => {},
     holds: [],
     resetHolds: () => {},
});

export const ThemeProvider = ({ children }) => {
     const [theme, setTheme] = useState([]);

     const updateTheme = (data) => {
          setTheme(data);
     };

     const resetTheme = () => {
          setTheme([]);
     };

     return <ThemeContext.Provider value={{ theme, updateTheme, resetTheme }}>{children}</ThemeContext.Provider>;
};

export const DiscoveryProvider = ({ children }) => {
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

export const LibrarySystemProvider = ({ children }) => {
     const [library, setLibrary] = useState();
     const [version, setVersion] = useState();
     const [url, setUrl] = useState();

     const updateLibrary = (data) => {
          const discovery = formatDiscoveryVersion(data.discoveryVersion);
          setVersion(discovery);
          console.log('updated version in LibrarySystemContext');
          setUrl(data.baseUrl);
          console.log('updated url in LibrarySystemContext');
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

export const LibraryBranchProvider = ({ children }) => {
     const [location, setLocation] = useState();
     const [scope, setScope] = useState();

     const updateLocation = (data) => {
          setLocation(data);
          BRANCH.vdxFormId = data.vdxFormId;
          BRANCH.vdxLocation = data.vdxLocation;
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

export const UserProvider = ({ children }) => {
     const [user, setUser] = useState();
     const [accounts, setLinkedAccounts] = useState();
     const [lists, setLists] = useState();
     const [language, setLanguage] = useState();
     const [locations, setPickupLocations] = useState();

     const updateUser = (data) => {
          if (_.isUndefined(data)) {
               console.log(data);
          }
          setUser(data);
          console.log(data);
          PATRON.listLastUsed = data.lastListUsed ?? null;
          PATRON.num.holds = data.numHolds;
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

     const updateLanguage = (data) => {
          setLanguage(data);
          console.log('updated language in UserContext');
     };

     const updatePickupLocations = (data) => {
          setPickupLocations(data);
          console.log('updated pickup locations in UserContext');
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
                    language,
                    updateLanguage,
                    locations,
                    updatePickupLocations,
               }}>
               {children}
          </UserContext.Provider>
     );
};

export const BrowseCategoryProvider = ({ children }) => {
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

export const CheckoutsProvider = ({ children }) => {
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

export const HoldsProvider = ({ children }) => {
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