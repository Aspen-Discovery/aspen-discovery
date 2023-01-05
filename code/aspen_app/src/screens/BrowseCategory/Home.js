import { MaterialIcons } from '@expo/vector-icons';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import CachedImage from 'expo-cached-image';
import { Box, Button, Icon, Pressable, ScrollView, Container, HStack, Text, Badge, Center } from 'native-base';
import React from 'react';
import _ from 'lodash';

// custom components and helper files
import { loadError } from '../../components/loadError';
import { loadingSpinner } from '../../components/loadingSpinner';
import { userContext } from '../../context/user';
import { translate } from '../../translations/translations';
import { dismissBrowseCategory } from '../../util/accountActions';
import { formatDiscoveryVersion, getBrowseCategories, getPickupLocations, LIBRARY, reloadBrowseCategories, UpdateBrowseCategoryContext, updatePatronBrowseCategories } from '../../util/loadLibrary';
import { getBrowseCategoryListForUser, getCheckedOutItems, getHolds, getILSMessages, getPatronBrowseCategories, getProfile, getViewers, reloadHolds, updateBrowseCategoryStatus, getLinkedAccounts } from '../../util/loadPatron';
import BrowseCategory from './BrowseCategory';
import DisplayBrowseCategory from './Category';
import { BrowseCategoryContext, CheckoutsContext, HoldsContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { getLists } from '../../util/api/list';
import { navigateStack } from '../../helpers/RootNavigator';

let maxCategories = 5;

export const DiscoverHomeScreen = () => {
     const [loading, setLoading] = React.useState(true);
     const navigation = useNavigation();
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { category, updateBrowseCategories, updateBrowseCategoryList, updateMaxCategories } = React.useContext(BrowseCategoryContext);
     const { checkouts, updateCheckouts } = React.useContext(CheckoutsContext);
     const { holds, updateHolds } = React.useContext(HoldsContext);

     const [unlimited, setUnlimitedCategories] = React.useState(false);

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    await reloadBrowseCategories(maxCategories, library.baseUrl).then((result) => {
                         if (maxCategories === 9999) {
                              setUnlimitedCategories(true);
                         }

                         if (category !== result) {
                              setLoading(true);
                              updateBrowseCategories(result);
                              setLoading(false);
                         }
                    });

                    reloadHolds(library.baseUrl).then((result) => {
                         if (holds !== result) {
                              updateHolds(result);
                         }
                    });

                    getCheckedOutItems(library.baseUrl).then((result) => {
                         if (checkouts !== result) {
                              updateCheckouts(result);
                         }
                    });

                    getILSMessages(library.baseUrl);
                    getLists(library.baseUrl);
                    getPickupLocations(library.baseUrl);
                    console.log('updated patron things');
               };
               update().then(() => {
                    return () => update();
               });
          }, [])
     );

     const renderHeader = (title, key, user, url) => {
          return (
               <Box>
                    <HStack space={3} alignItems="center" justifyContent="space-between" pb={2}>
                         <Text
                              maxWidth="80%"
                              bold
                              mb={1}
                              fontSize={{
                                   base: 'lg',
                                   lg: '2xl',
                              }}>
                              {title}
                         </Text>
                         <Button size="xs" colorScheme="trueGray" variant="ghost" onPress={() => onHideCategory(url, key)} startIcon={<Icon as={MaterialIcons} name="close" size="xs" mr={-1.5} />}>
                              {translate('general.hide')}
                         </Button>
                    </HStack>
               </Box>
          );
     };

     const renderRecord = (data, url, version, index) => {
          const item = data.item;
          let type = 'grouped_work';
          if (!_.isUndefined(item.source)) {
               type = item.source;
          }

          const imageUrl = library.baseUrl + '/bookcover.php?id=' + item.id + '&size=medium&type=' + type.toLowerCase();

          let isNew = false;
          if (typeof item.isNew !== 'undefined') {
               isNew = item.isNew;
          }

          return (
               <Pressable
                    ml={1}
                    mr={3}
                    onPress={() => onPressItem(item.id, type, item.title_display, version)}
                    width={{
                         base: 100,
                         lg: 200,
                    }}
                    height={{
                         base: 125,
                         lg: 250,
                    }}>
                    {version >= '22.08.00' && isNew ? (
                         <Container zIndex={1}>
                              <Badge colorScheme="warning" shadow={1} mb={-2} ml={-1} _text={{ fontSize: 9 }}>
                                   {translate('general.new')}
                              </Badge>
                         </Container>
                    ) : null}
                    <CachedImage
                         cacheKey={item.id}
                         alt={item.title_display}
                         source={{
                              uri: `${imageUrl}`,
                              expiresIn: 86400,
                         }}
                         style={{
                              width: '100%',
                              height: '100%',
                              borderRadius: 4
                         }}

                    />
               </Pressable>
          );
     };

     const onPressItem = (key, type, title, version) => {
          if (version >= '22.07.00') {
               if (type === 'List') {
                    navigateStack('SearchTab', 'SearchByList', {
                         id: key,
                         url: library.baseUrl,
                         title: title,
                         userContext: user,
                         libraryContext: library,
                    });
               } else if (type === 'SavedSearch') {
                    navigateStack('SearchTab', 'SearchBySavedSearch', {
                         id: key,
                         url: library.baseUrl,
                         title: title,
                         userContext: user,
                         libraryContext: library,
                    });
               } else {
                    navigateStack('HomeTab', 'GroupedWorkScreen', {
                         id: key,
                         title: title,
                         prevRoute: 'DiscoveryScreen',
                    });
               }
          } else {
               navigateStack('HomeTab', 'GroupedWorkScreen', {
                    id: key,
                    url: library.baseUrl,
                    title: title,
                    userContext: user,
                    libraryContext: library,
                    prevRoute: 'DiscoveryScreen',
               });
          }
     };

     const renderLoadMore = () => {};

     const onHideCategory = async (url, category) => {
          setLoading(true);
          await updateBrowseCategoryStatus(category).then(async (response) => {
               await onRefreshCategories();
               await getBrowseCategoryListForUser().then((result) => {
                    updateBrowseCategoryList(result);
                    setLoading(false);
               });
          });
     };

     const onRefreshCategories = async () => {
          setLoading(true);
          await reloadBrowseCategories(maxCategories, library.baseUrl).then((result) => {
               updateBrowseCategories(result);
               setLoading(false);
          });
     };

     const onLoadAllCategories = () => {
          maxCategories = 9999;
          updateMaxCategories(9999);
          setUnlimitedCategories(true);
          onRefreshCategories();
     };

     const onPressSettings = (url, patronId) => {
          const version = formatDiscoveryVersion(library.discoveryVersion);
          let screen = 'SettingsHomeScreen';
          if (version >= '22.12.00') {
               screen = 'SettingsBrowseCategories';
          }
          navigateStack('AccountScreenTab', screen, {
               url,
               patronId,
          });
     };

     const handleOnPressCategory = (label, key, source) => {
          let screen = 'SearchByCategory';
          if (source === 'List') {
               screen = 'SearchByList';
          } else if (source === 'SavedSearch') {
               screen = 'SearchBySavedSearch';
          }

          navigateStack('SearchTab', screen, {
               title: label,
               id: key,
               url: library.baseUrl,
               libraryContext: library,
               userContext: user,
          });
     };

     if (loading === true) {
          return loadingSpinner();
     }

     return (
          <ScrollView>
               <Box safeArea={5}>
                    {category.map((item, index) => {
                         return <DisplayBrowseCategory key={index} categoryLabel={item.title} categoryKey={item.key} id={item.id} records={item.records} isHidden={item.isHidden} categorySource={item.source} renderRecords={renderRecord} header={renderHeader} hideCategory={onHideCategory} user={user} libraryUrl={library.baseUrl} loadMore={renderLoadMore} discoveryVersion={library.version} onPressCategory={handleOnPressCategory} categoryList={category} />;
                    })}
                    <ButtonOptions libraryUrl={library.baseUrl} patronId={user.id} onPressSettings={onPressSettings} onRefreshCategories={onRefreshCategories} discoveryVersion={library.discoveryVersion} loadAll={unlimited} onLoadAllCategories={onLoadAllCategories} />
               </Box>
          </ScrollView>
     );
};

/* export class BrowseCategoryHome extends PureComponent {
 static contextType = userContext;

 constructor(props, context) {
 super(props, context);
 this.state = {
 data: [],
 page: 1,
 isLoading: true,
 isLoadingMore: false,
 hasError: false,
 error: null,
 refreshing: false,
 filtering: false,
 categories: [],
 browseCategories: [],
 categoriesLoaded: false,
 prevLaunch: 0,
 showButtons: false,
 loadAllCategories: false,
 user: [],
 library: [],
 location: [],
 };
 this._isMounted = false;
 }

 loadBrowseCategories = async (libraryUrl, patronId) => {
 await getPatronBrowseCategories(LIBRARY.url, patronId).then((response) => {
 this.setState({
 browseCategories: response,
 categoriesLoaded: true,
 isLoading: false,
 });
 this.loadPatronItems();
 });
 };

 componentDidMount = async () => {
 this._isMounted = true;

 if (this._isMounted) {
 if (LIBRARY.version) {
 this.setState({
 discoveryVersion: LIBRARY.version,
 });
 } else {
 this.setState({
 discoveryVersion: '22.06.00',
 });
 }

 const userContext = JSON.parse(this.props.route.params.userContext);
 const libraryContext = JSON.parse(this.props.route.params.libraryContext);
 const locationContext = JSON.parse(this.props.route.params.locationContext);

 this.setState({
 isLoading: false,
 user: userContext.user,
 library: libraryContext.library,
 location: locationContext.location,
 });

 if (LIBRARY.url) {
 await this.loadPatronItems();
 }
 }
 };

 componentDidUpdate(prevProps, prevState) {
 if (prevProps.route.params.browseCategoriesContext.category !== this.props.route.params.browseCategoriesContext.category) {
 console.log('browseCategoriesContext is different now.');
 }
 }

 componentWillUnmount() {
 this._isMounted = false;
 }

 loadPatronItems = async () => {
 await getCheckedOutItems(LIBRARY.url);
 await getHolds(LIBRARY.url);
 await getILSMessages(LIBRARY.url);
 await getLists(LIBRARY.url);
 await getPickupLocations(LIBRARY.url);
 };

 onHideCategory = async (libraryUrl, categoryId, patronId) => {
 this.setState({ isLoading: true });
 await dismissBrowseCategory(libraryUrl, categoryId, patronId, this.state.discoveryVersion).then(async (res) => {
 await getBrowseCategories(libraryUrl, this.state.discoveryVersion).then((response) => {
 this.context.browseCategories = response;
 this.setState({
 isLoading: false,
 });
 });
 });
 };

 onRefreshCategories = async () => {
 this.setState({ isLoading: true });

 await getBrowseCategories(this.context.library.baseUrl, this.state.discoveryVersion).then((response) => {
 this.context.browseCategories = response;
 this.setState({
 isLoading: false,
 });
 });
 };

 onLoadAllCategories = () => {
 UpdateBrowseCategoryContext(null);
 };

 handleRefreshProfile = async () => {
 await getProfile(true).then((response) => {
 this.context.user = response;
 });
 };

 onPressItem = (key, type, title, discoveryVersion) => {
 const libraryUrl = this.context.library.baseUrl;
 if (discoveryVersion >= '22.07.00') {
 if (type === 'List') {
 this.props.navigation.navigate('SearchByList', {
 id: key,
 libraryUrl: libraryUrl,
 title: title,
 userContext: this.props.route.params.userContext.user,
 libraryContext: this.props.route.params.libraryContext.library,
 });
 } else if (type === 'SavedSearch') {
 this.props.navigation.navigate('SearchBySavedSearch', {
 id: key,
 libraryUrl: libraryUrl,
 title: title,
 userContext: this.props.route.params.userContext.user,
 libraryContext: this.props.route.params.libraryContext.library,
 });
 } else {
 this.props.navigation.navigate({
 name: 'GroupedWorkScreen',
 params: {
 id: key,
 libraryUrl: libraryUrl,
 title: title,
 userContext: this.props.route.params.userContext.user,
 libraryContext: this.props.route.params.libraryContext.library,
 },
 merge: true,
 });
 }
 } else {
 this.props.navigation.navigate('GroupedWorkScreen', {
 id: key,
 libraryUrl: libraryUrl,
 title: title,
 userContext: this.props.route.params.userContext.user,
 libraryContext: this.props.route.params.libraryContext.library,
 });
 }
 };

 onLoadMore = (item) => {
 this.props.navigation.navigate('GroupedWorkScreen', { item });
 };

 onPressSettings = (libraryUrl, patronId) => {
 this.props.navigation.navigate('AccountScreenTab', {
 screen: 'SettingsHomeScreen',
 params: { libraryUrl, patronId },
 });
 };

 // for discovery older than 22.05
 _renderNativeItem = (data, libraryUrl) => {
 if (typeof libraryUrl !== 'undefined') {
 try {
 //const Image = createImageProgress(ExpoFastImage);
 const imageUrl = libraryUrl + '/bookcover.php?id=' + data.key + '&size=large&type=grouped_work';
 //console.log(data);
 return (
 <Pressable mr={1.5} onPress={() => this.onPressItem(data.key)} width={{ base: 100, lg: 200 }} height={{ base: 150, lg: 250 }}>
 <CachedImage cacheKey={data.key} alt={data.title} source={{ uri: `${imageUrl}` }} style={{ width: '100%', height: '100%' }} />
 </Pressable>
 );
 } catch (e) {
 console.log(e);
 }
 }
 };

 _renderHeader = (title, key, user, libraryUrl) => {
 return (
 <Box>
 <HStack space={3} alignItems="center" justifyContent="space-between" pb={2}>
 <Text maxWidth="80%" bold mb={1} fontSize={{ base: 'lg', lg: '2xl' }}>
 {title}
 </Text>
 <Button size="xs" colorScheme="trueGray" variant="ghost" onPress={() => this.hideCategory(libraryUrl, key, user)} startIcon={<Icon as={MaterialIcons} name="close" size="xs" mr={-1.5} />}>
 {translate('general.hide')}
 </Button>
 </HStack>
 </Box>
 );
 };

 _renderRecords = (data, user, libraryUrl, discoveryVersion) => {
 let type = 'grouped_work';
 if (data.source) {
 type = data.source;
 }
 const imageUrl = libraryUrl + '/bookcover.php?id=' + data.id + '&size=medium&type=' + type.toLowerCase();

 let isNew = false;
 if (typeof data.isNew !== 'undefined') {
 isNew = data.isNew;
 }

 //console.log(data);

 return (
 <Pressable ml={1} mr={3} onPress={() => this.onPressItem(data.id, type, data.title_display, discoveryVersion)} width={{ base: 100, lg: 200 }} height={{ base: 125, lg: 250 }}>
 {discoveryVersion >= '22.08.00' && isNew ? (
 <Container zIndex={1}>
 <Badge colorScheme="warning" shadow={1} mb={-2} ml={-1} _text={{ fontSize: 9 }}>
 {translate('general.new')}
 </Badge>
 </Container>
 ) : null}
 <CachedImage
 cacheKey={data.id}
 alt={data.title_display}
 source={{
 uri: `${imageUrl}`,
 expiresIn: 86400,
 }}
 style={{ width: '100%', height: '100%' }}
 />
 </Pressable>
 );
 };

 _renderLoadMore = (categoryLabel, categoryKey, libraryUrl, categorySource, recordCount, discoveryVersion) => {
 const { navigation } = this.props;

 let searchBy = 'SearchByCategory';
 if (categorySource === 'List') {
 searchBy = 'SearchByList';
 } else if (categorySource === 'SavedSearch') {
 searchBy = 'SearchBySavedSearch';
 } else {
 searchBy = 'SearchByCategory';
 }
 if (recordCount >= 5 && discoveryVersion >= '22.07.00') {
 return (
 <Box alignItems="center">
 <Pressable
 width={{ base: 100, lg: 200 }}
 height={{ base: 150, lg: 250 }}
 onPress={() => {
 navigation.navigate(searchBy, {
 title: categoryLabel,
 id: categoryKey,
 libraryUrl,
 });
 }}>
 <Box width={{ base: 100, lg: 200 }} height={{ base: 150, lg: 250 }} bg="secondary.200" borderRadius="4" p="5" alignItems="center" justifyContent="center">
 <Center>
 <Text bold fontSize="md">
 {translate('general.load_more')}
 </Text>
 </Center>
 </Box>
 </Pressable>
 </Box>
 );
 }
 };

 _handleOnPressCategory = (categoryLabel, categoryKey, categorySource) => {
 let searchBy = 'SearchByCategory';
 if (categorySource === 'List') {
 searchBy = 'SearchByList';
 } else if (categorySource === 'SavedSearch') {
 searchBy = 'SearchBySavedSearch';
 } else {
 searchBy = 'SearchByCategory';
 }
 this.props.navigation.navigate(searchBy, {
 title: categoryLabel,
 id: categoryKey,
 libraryUrl: LIBRARY.url,
 });
 };

 render() {
 const { isLoading, loadAllCategories } = this.state;
 const user = this.props.route.params.userContext.user;
 const library = this.props.route.params.libraryContext.library;
 const browseCategories = this.props.route.params.browseCategoriesContext.category;

 let discoveryVersion = LIBRARY.version;
 if (library.discoveryVersion) {
 let version = library.discoveryVersion;
 version = version.split(' ');
 discoveryVersion = version[0];
 } else {
 discoveryVersion = '22.06.00';
 }

 if (this.state.isLoading === true) {
 return loadingSpinner();
 }

 if (this.state.hasError) {
 return loadError(this.state.error);
 }

 if (typeof browseCategories === 'undefined') {
 //return (loadingSpinner());
 return loadError('No categories found', this.onRefreshCategories());
 }

 if (discoveryVersion >= '22.05.00' && browseCategories) {
 //console.log(browseCategories);
 //console.log(discoveryVersion + " is newer than or equal to 22.05.00");
 return (
 <ScrollView>
 <Box safeArea={5}>
 {browseCategories.map((category) => {
 return <DisplayBrowseCategory categoryLabel={category.title} categoryKey={category.key} id={category.id} records={category.records} isHidden={category.isHidden} categorySource={category.source} renderRecords={this._renderRecords} header={this._renderHeader} hideCategory={this.onHideCategory} user={user} libraryUrl={library.baseUrl} loadMore={this._renderLoadMore} discoveryVersion={discoveryVersion} onPressCategory={this._handleOnPressCategory} />;
 })}
 <ButtonOptions libraryUrl={library.baseUrl} patronId={user.id} onPressSettings={this.onPressSettings} onRefreshCategories={this.onRefreshCategories} discoveryVersion={discoveryVersion} loadAll={loadAllCategories} onLoadAllCategories={this.onLoadAllCategories} />
 </Box>
 </ScrollView>
 );
 } else {
 //console.log(discoveryVersion + " is older than 22.05.00");
 return (
 <ScrollView>
 <Box safeArea={5}>
 {browseCategories.map((category) => {
 return <BrowseCategory isLoading={isLoading} categoryLabel={category.title} categoryKey={category.key} isHidden={category.isHidden} renderItem={this._renderNativeItem} loadMore={this.onLoadMore} hideCategory={this.onHideCategory} user={user} libraryUrl={library.baseUrl} />;
 })}
 <ButtonOptions libraryUrl={library.baseUrl} patronId={user.id} onPressSettings={this.onPressSettings} onRefreshCategories={this.onRefreshCategories} />
 </Box>
 </ScrollView>
 );
 }
 }
 } */

/* const RenderBrowseCategory = (data) => {
 let type = 'grouped_work';
 if (data.source) {
 type = data.source;
 }
 const imageUrl = LIBRARY.url + '/bookcover.php?id=' + data.id + '&size=medium&type=' + type.toLowerCase();

 let isNew = false;
 if (typeof data.isNew !== 'undefined') {
 isNew = data.isNew;
 }

 return (
 <Pressable ml={1} mr={3} onPress={() => this.onPressItem(data.id, type, data.title_display, LIBRARY.version)} width={{ base: 100, lg: 200 }} height={{ base: 125, lg: 250 }}>
 {LIBRARY.version >= '22.08.00' && isNew ? (
 <Container zIndex={1}>
 <Badge colorScheme="warning" shadow={1} mb={-2} ml={-1} _text={{ fontSize: 9 }}>
 {translate('general.new')}
 </Badge>
 </Container>
 ) : null}
 <CachedImage
 cacheKey={data.id}
 alt={data.title_display}
 source={{
 uri: `${imageUrl}`,
 expiresIn: 86400,
 }}
 style={{ width: '100%', height: '100%' }}
 />
 </Pressable>
 );
 };

 const DisplayBrowseCategories220500 = (categories) => {
 return (
 <ScrollView>
 <Box safeArea={5}>
 {categories.map((category) => {
 return <BrowseCategory isLoading={isLoading} categoryLabel={category.title} categoryKey={category.key} isHidden={category.isHidden} renderItem={this._renderNativeItem} loadMore={this.onLoadMore} hideCategory={this.onHideCategory} user={user} libraryUrl={library.baseUrl} />;
 })}
 <ButtonOptions libraryUrl={library.baseUrl} patronId={user.id} onPressSettings={this.onPressSettings} onRefreshCategories={this.onRefreshCategories} />
 </Box>
 </ScrollView>
 );
 }; */

const ButtonOptions = (props) => {
     const [loading, setLoading] = React.useState(false);
     const [refreshing, setRefreshing] = React.useState(false);
     const { onPressSettings, onRefreshCategories, libraryUrl, patronId, discoveryVersion, loadAll, onLoadAllCategories } = props;

     const version = formatDiscoveryVersion(discoveryVersion);

     if (version >= '22.07.00') {
          return (
               <Box>
                    {!loadAll ? (
                         <Button
                              isLoading={loading}
                              size="md"
                              colorScheme="primary"
                              onPress={() => {
                                   setLoading(true);
                                   onLoadAllCategories(libraryUrl, patronId);
                                   setTimeout(function () {
                                        setLoading(false);
                                   }, 5000);
                              }}
                              startIcon={<Icon as={MaterialIcons} name="schedule" size="sm" />}>
                              {translate('browse_category.load_all_categories')}
                         </Button>
                    ) : null}
                    <Button
                         size="md"
                         mt="3"
                         colorScheme="primary"
                         onPress={() => {
                              onPressSettings(libraryUrl, patronId);
                         }}
                         startIcon={<Icon as={MaterialIcons} name="settings" size="sm" />}>
                         {translate('browse_category.manage_categories')}
                    </Button>
                    <Button
                         isLoading={refreshing}
                         size="md"
                         mt="3"
                         colorScheme="primary"
                         onPress={() => {
                              setRefreshing(true);
                              onRefreshCategories();
                              setTimeout(function () {
                                   setRefreshing(false);
                              });
                         }}
                         startIcon={<Icon as={MaterialIcons} name="refresh" size="sm" />}>
                         {translate('browse_category.refresh_categories')}
                    </Button>
               </Box>
          );
     }

     return (
          <Box>
               <Button
                    size="md"
                    colorScheme="primary"
                    onPress={() => {
                         onPressSettings(libraryUrl, patronId);
                    }}
                    startIcon={<Icon as={MaterialIcons} name="settings" size="sm" />}>
                    {translate('browse_category.manage_categories')}
               </Button>
               <Button
                    size="md"
                    mt="3"
                    colorScheme="primary"
                    onPress={() => {
                         onRefreshCategories(libraryUrl);
                    }}
                    startIcon={<Icon as={MaterialIcons} name="refresh" size="sm" />}>
                    {translate('browse_category.refresh_categories')}
               </Button>
          </Box>
     );
};