import { CommonActions } from '@react-navigation/native';
import moment from 'moment';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import { Badge, Box, Center, FlatList, HStack, Image, Pressable, Text, VStack } from 'native-base';
import React, { Component } from 'react';
import { SafeAreaView } from 'react-native';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import { userContext } from '../../../context/user';
import { translate } from '../../../translations/translations';
import { getCheckedOutItems, getHolds, getILSMessages, PATRON } from '../../../util/loadPatron';
import CreateList from './CreateList';
import { LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { getLists } from '../../../util/api/list';
import { getPickupLocations } from '../../../util/loadLibrary';

export const MyLists = () => {
     const navigation = useNavigation();
     const { library } = React.useContext(LibrarySystemContext);
     const { lists, updateLists } = React.useContext(UserContext);
     const [loading, setLoading] = React.useState(true);

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    await getLists(library.baseUrl).then((result) => {
                         if (lists !== result) {
                              updateLists(result);
                         }
                    });
                    setLoading(false);
               };
               update().then(() => {
                    return () => update();
               });
          }, [])
     );

     const handleOpenList = (item) => {
          navigation.navigate('AccountScreenTab', {
               screen: 'List',
               params: {
                    id: item.id,
                    details: item,
                    title: item.title,
                    libraryUrl: library.baseUrl,
               },
          });
     };

     const listEmptyComponent = () => {
          return (
               <Center mt={5} mb={5}>
                    <Text bold fontSize="lg">
                         {translate('lists.no_lists_yet')}
                    </Text>
               </Center>
          );
     };

     const renderList = (item) => {
          let lastUpdated = moment.unix(item.dateUpdated);
          lastUpdated = moment(lastUpdated).format('MMM D, YYYY');
          let privacy = translate('general.private');
          if (item.public === 1 || item.public === true || item.public === 'true') {
               privacy = translate('general.public');
          }
          if (item.id !== 'recommendations') {
               return (
                    <Pressable
                         onPress={() => {
                              handleOpenList(item);
                         }}
                         borderBottomWidth="1"
                         _dark={{ borderColor: 'gray.600' }}
                         borderColor="coolGray.200"
                         pl="1"
                         pr="1"
                         py="2">
                         <HStack space={3} justifyContent="flex-start">
                              <VStack space={1}>
                                   <Image source={{ uri: item.cover }} alt={item.title} size="lg" resizeMode="contain" />
                                   <Badge mt={1}>{privacy}</Badge>
                              </VStack>
                              <VStack space={1} justifyContent="space-between" maxW="80%">
                                   <Box>
                                        <Text bold fontSize="md">
                                             {item.title}
                                        </Text>
                                        {item.description ? (
                                             <Text fontSize="xs" mb={2}>
                                                  {item.description}
                                             </Text>
                                        ) : null}
                                        <Text fontSize="9px" italic>
                                             {translate('general.last_updated_on', { date: lastUpdated })}
                                        </Text>
                                        <Text fontSize="9px" italic>
                                             {translate('lists.num_items_on_list', {
                                                  num: item.numTitles,
                                             })}
                                        </Text>
                                   </Box>
                              </VStack>
                         </HStack>
                    </Pressable>
               );
          }
     };

     if (loading) {
          return loadingSpinner();
     }

     return (
          <SafeAreaView style={{ flex: 1 }}>
               <Box safeArea={2} t={10} pb={10}>
                    <CreateList />
                    <FlatList data={lists} ListEmptyComponent={listEmptyComponent} renderItem={({ item }) => renderList(item, library.baseUrl)} keyExtractor={(item, index) => index.toString()} />
               </Box>
          </SafeAreaView>
     );
};

/*
 export default class MyLists extends Component {
 constructor(props, context) {
 super(props, context);
 this.state = {
 isLoading: true,
 hasError: false,
 error: null,
 user: [],
 library: [],
 lists: PATRON.lists,
 listChanges: false,
 };
 this._isMounted = false;
 }

 _fetchLists = async () => {
 this.setState({
 isLoading: true,
 });

 this._isMounted &&
 (await getLists().then((response) => {
 this.setState({
 lists: response,
 isLoading: false,
 });
 }));
 };

 componentDidMount = async () => {
 this._isMounted = true;

 this._isMounted &&
 (await this._fetchLists().then((r) => {
 this.setState({
 isLoading: false,
 });
 }));
 };

 componentWillUnmount() {
 this._isMounted = false;
 }

 async componentDidUpdate(prevProps, prevState) {
 const { navigation } = this.props;
 if (prevState.lists !== PATRON.lists) {
 this.setState({
 lists: PATRON.lists,
 });
 navigation.dispatch({
 ...CommonActions.setParams({ hasPendingChanges: false }),
 });
 }
 }

 // renders the items on the screen
 renderList = (item, libraryUrl) => {
 let lastUpdated = moment.unix(item.dateUpdated);
 lastUpdated = moment(lastUpdated).format('MMM D, YYYY');
 let privacy = translate('general.private');
 if (item.public === 1 || item.public === true || item.public === 'true') {
 privacy = translate('general.public');
 }
 if (item.id !== 'recommendations') {
 return (
 <Pressable
 onPress={() => {
 this.openList(item.id, item, libraryUrl);
 }}
 borderBottomWidth="1"
 _dark={{ borderColor: 'gray.600' }}
 borderColor="coolGray.200"
 pl="1"
 pr="1"
 py="2">
 <HStack space={3} justifyContent="flex-start">
 <VStack space={1}>
 <Image source={{ uri: item.cover }} alt={item.title} size="lg" resizeMode="contain" />
 <Badge mt={1}>{privacy}</Badge>
 </VStack>
 <VStack space={1} justifyContent="space-between" maxW="80%">
 <Box>
 <Text bold fontSize="md">
 {item.title}
 </Text>
 {item.description ? (
 <Text fontSize="xs" mb={2}>
 {item.description}
 </Text>
 ) : null}
 <Text fontSize="9px" italic>
 {translate('general.last_updated_on', { date: lastUpdated })}
 </Text>
 <Text fontSize="9px" italic>
 {translate('lists.num_items_on_list', {
 num: item.numTitles,
 })}
 </Text>
 </Box>
 </VStack>
 </HStack>
 </Pressable>
 );
 }
 };

 openList = (id, item, libraryUrl) => {
 this.props.navigation.navigate('AccountScreenTab', {
 screen: 'List',
 params: {
 id,
 details: item,
 title: item.title,
 libraryUrl,
 },
 });
 };

 _listEmptyComponent = () => {
 return (
 <Center mt={5} mb={5}>
 <Text bold fontSize="lg">
 {translate('lists.no_lists_yet')}
 </Text>
 </Center>
 );
 };

 render() {
 console.log(this.props.libraryContext);
 const { lists, library } = this.state;

 if (this.state.isLoading) {
 return loadingSpinner();
 }

 return (
 <SafeAreaView style={{ flex: 1 }}>
 <Box safeArea={2} t={10} pb={10}>
 <CreateList libraryUrl={library.baseUrl} _fetchLists={this._fetchLists} />
 <FlatList data={lists} ListEmptyComponent={this._listEmptyComponent()} renderItem={({ item }) => this.renderList(item, library.baseUrl)} keyExtractor={(item, index) => index.toString()} />
 </Box>
 </SafeAreaView>
 );
 }
 }*/