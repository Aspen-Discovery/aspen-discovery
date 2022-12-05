import { MaterialIcons } from '@expo/vector-icons';
import { Box, Button, FlatList, HStack, Icon, Image, Pressable, Text, VStack } from 'native-base';
import React from 'react';
import { SafeAreaView } from 'react-native';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import { translate } from '../../../translations/translations';
import EditList from './EditList';
import { useNavigation, useFocusEffect, useRoute } from '@react-navigation/native';
import { LibrarySystemContext } from '../../../context/initialContext';
import { getListTitles, removeTitlesFromList } from '../../../util/api/list';

export const MyList = () => {
     const providedList = useRoute().params.details;
     const id = providedList.id;
     const navigation = useNavigation();
     const [loading, setLoading] = React.useState(true);
     const { library } = React.useContext(LibrarySystemContext);
     const [titles, setListTitles] = React.useState();
     const [list] = React.useState(providedList);

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    await getListTitles(id, library.baseUrl).then((result) => {
                         setListTitles(result);
                    });
                    setLoading(false);
               };
               update().then(() => {
                    return () => update();
               });
          }, [])
     );

     const handleOpenItem = (id) => {
          navigation.navigate('AccountScreenTab', {
               screen: 'GroupedWork',
               params: {
                    id: id,
                    url: library.baseUrl,
               },
          });
     };

     const renderItem = (item) => {
          return (
               <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => handleOpenItem(item.id)}>
                    <HStack space={3} justifyContent="flex-start" alignItems="flex-start">
                         <VStack w="25%">
                              <Image source={{ uri: item.image }} alt={item.title} borderRadius="md" size="90px" />
                              <Button
                                   onPress={() => {
                                        removeTitlesFromList(id, item.id, library.baseUrl).then(async () => {
                                             await getListTitles(id, library.baseUrl).then((result) => {
                                                  setListTitles(result);
                                             });
                                        });
                                   }}
                                   colorScheme="danger"
                                   leftIcon={<Icon as={MaterialIcons} name="delete" size="xs" />}
                                   size="sm"
                                   variant="ghost">
                                   {translate('general.delete')}
                              </Button>
                         </VStack>
                         <VStack w="65%">
                              <Text
                                   _dark={{ color: 'warmGray.50' }}
                                   color="coolGray.800"
                                   bold
                                   fontSize={{
                                        base: 'sm',
                                        lg: 'md',
                                   }}>
                                   {item.title}
                              </Text>
                              {item.author ? (
                                   <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800" fontSize="xs">
                                        {translate('grouped_work.by')} {item.author}
                                   </Text>
                              ) : null}
                         </VStack>
                    </HStack>
               </Pressable>
          );
     };

     if (loading) {
          return loadingSpinner();
     } else {
          return (
               <SafeAreaView style={{ flex: 1 }}>
                    <Box safeArea={2} pb={10}>
                         <EditList data={list} listId={id} />
                         <FlatList data={titles} renderItem={({ item }) => renderItem(item, library.baseUrl)} keyExtractor={(item, index) => index.toString()} />
                    </Box>
               </SafeAreaView>
          );
     }
};

/*
 export class MyList extends Component {
 static contextType = userContext;

 constructor(props, context) {
 super(props, context);
 this.state = {
 isLoading: true,
 hasError: false,
 error: null,
 user: [],
 list: [],
 listDetails: [],
 id: null,
 hasPendingChanges: false,
 };
 this._isMounted = false;
 }

 loadList = async () => {
 this.setState({
 isLoading: true,
 });
 const { route } = this.props;
 const givenListId = route.params?.id ?? 0;
 const libraryUrl = this.context.library.baseUrl;

 await getListTitles(givenListId, libraryUrl).then((response) => {
 this.setState({
 list: response,
 id: givenListId,
 isLoading: false,
 hasPendingChanges: false,
 });
 });
 };

 componentDidMount = async () => {
 this._isMounted = true;
 const { route } = this.props;
 const givenList = route.params?.details ?? '';

 console.log(givenList);

 this._isMounted &&
 (await this.loadList().then((res) => {
 this.setState({
 isLoading: false,
 listDetails: givenList,
 libraryUrl: this.context.library.baseUrl,
 hasPendingChanges: false,
 });
 }));
 };

 componentWillUnmount() {
 this._isMounted = false;
 }

 async componentDidUpdate(prevProps, prevState) {
 const { navigation } = this.props;
 const library = this.context.library;
 const routes = navigation.getState()?.routes;
 const prevRoute = routes[routes.length - 2];
 if (prevRoute) {
 navigation.setOptions({
 headerLeft: () => (
 <Pressable
 onPress={() => {
 this.props.navigation.navigate('Lists', {
 hasPendingChanges: true,
 libraryUrl: library.baseUrl,
 });
 }}>
 <ChevronLeftIcon color="primary.baseContrast" />
 </Pressable>
 ),
 });
 }

 const { route } = prevProps;
 console.log(prevProps.route.params?.details);
 if (route.params?.details !== this.state.listDetails) {
 const { route } = this.props;
 const givenListId = route.params?.id ?? 0;
 const libraryUrl = this.context.library.baseUrl;
 console.log(this.state.listDetails);
 }
 }

 _updateRouteParam = (newTitle) => {
 const { navigation, route } = this.props;
 navigation.dispatch({
 ...CommonActions.setParams({ title: newTitle }),
 source: route.key,
 });
 };

 _updateListDetail = (key, value) => {
 this.setState((prevState) => ({
 ...prevState,
 listDetails: {
 ...prevState.listDetails,
 [key]: value,
 },
 }));
 };

 // renders the items on the screen
 renderItem = (item, libraryUrl) => {
 return (
 <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => this.openItem(item.id, this.state.libraryUrl)}>
 <HStack space={3} justifyContent="flex-start" alignItems="flex-start">
 <VStack w="25%">
 <Image source={{ uri: item.image }} alt={item.title} borderRadius="md" size="90px" />
 <Button
 onPress={async () => {
 await removeTitlesFromList(this.state.id, item.id, this.state.libraryUrl);
 await this.loadList();
 }}
 colorScheme="danger"
 leftIcon={<Icon as={MaterialIcons} name="delete" size="xs" />}
 size="sm"
 variant="ghost">
 {translate('general.delete')}
 </Button>
 </VStack>
 <VStack w="65%">
 <Text
 _dark={{ color: 'warmGray.50' }}
 color="coolGray.800"
 bold
 fontSize={{
 base: 'sm',
 lg: 'md',
 }}>
 {item.title}
 </Text>
 {item.author ? (
 <Text _dark={{ color: 'warmGray.50' }} color="coolGray.800" fontSize="xs">
 {translate('grouped_work.by')} {item.author}
 </Text>
 ) : null}
 </VStack>
 </HStack>
 </Pressable>
 );
 };

 openItem = (id, libraryUrl) => {
 this.props.navigation.navigate('AccountScreenTab', {
 screen: 'GroupedWork',
 params: {
 id,
 libraryUrl: this.state.libraryUrl,
 },
 });
 };

 _listEmpty = () => {
 return (
 <Center mt={5} mb={5}>
 <Text bold fontSize="lg">
 {translate('lists.empty')}
 </Text>
 </Center>
 );
 };

 render() {
 const { list } = this.state;
 const user = this.context.user;
 const location = this.context.location;
 const library = this.context.library;
 const { route } = this.props;
 const givenListId = route.params?.id ?? 0;

 if (this.state.isLoading) {
 return loadingSpinner();
 }

 return (
 <SafeAreaView style={{ flex: 1 }}>
 <Box safeArea={2} pb={10}>
 <EditList data={this.state.listDetails} listId={givenListId} navigation={this.props.navigation} libraryUrl={this.state.libraryUrl} loadList={this.loadList} _updateRouteParam={this._updateRouteParam} _updateListDetail={this._updateListDetail} />
 <FlatList data={this.state.list} renderItem={({ item }) => this.renderItem(item, this.state.libraryUrl)} keyExtractor={(item, index) => index.toString()} />
 </Box>
 </SafeAreaView>
 );
 }
 }*/