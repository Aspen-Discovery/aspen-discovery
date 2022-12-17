import {MaterialIcons} from '@expo/vector-icons';
import _ from 'lodash';
import {
    Box,
    Button,
    FlatList,
    HStack,
    Icon,
    Image,
    Pressable,
    Text,
    VStack,
    ScrollView,
    FormControl,
    CheckIcon,
    Select
} from 'native-base';
import React from 'react';
import {SafeAreaView} from 'react-native';
import {useQuery, useQueryClient} from '@tanstack/react-query';

// custom components and helper files
import {loadingSpinner} from '../../../components/loadingSpinner';
import {translate} from '../../../translations/translations';
import EditList from './EditList';
import {useNavigation, useFocusEffect, useRoute} from '@react-navigation/native';
import {LibrarySystemContext} from '../../../context/initialContext';
import {getListTitles, removeTitlesFromList} from '../../../util/api/list';
import {navigateStack} from '../../../helpers/RootNavigator';
import {getCleanTitle} from '../../../helpers/item';
import {fetchReadingHistory} from '../../../util/api/user';
import {loadError} from '../../../components/loadError';

export const MyList = () => {
    const providedList = useRoute().params.details;
    const id = providedList.id;
    const [page, setPage] = React.useState(1);
    const [sort, setSort] = React.useState('dateAdded');
    const [pageSize, setPageSize] = React.useState(25);
    const {library} = React.useContext(LibrarySystemContext);
    const [list] = React.useState(providedList);

    const {
        status,
        data,
        error,
        isFetching,
        isPreviousData
    } = useQuery(['myList', id, library.baseUrl, page, pageSize, sort], () => getListTitles(id, library.baseUrl, page, pageSize, pageSize, sort), {
        keepPreviousData: true,
        staleTime: 1000,
    });

    const handleOpenItem = (id, title) => {
        navigateStack('AccountScreenTab', 'ListItem', {
            id: id,
            url: library.baseUrl,
            title: getCleanTitle(title),
        });
    };

    if (status !== 'loading') {
        if (!_.isUndefined(data.defaultSort)) {
            setSort(data.defaultSort);
        }
    }

    const queryClient = useQueryClient();
    const renderItem = (item) => {
        return (
            <Pressable borderBottomWidth="1" _dark={{borderColor: 'gray.600'}} borderColor="coolGray.200" pl="4" pr="5"
                       py="2" onPress={() => handleOpenItem(item.id, item.title)}>
                <HStack space={3} justifyContent="flex-start" alignItems="flex-start">
                    <VStack w="25%">
                        <Image source={{uri: item.image}} alt={item.title} borderRadius="md" size="90px"/>
                        <Button
                            onPress={() => {
                                removeTitlesFromList(id, item.id, library.baseUrl).then(async () => {
                                    queryClient.invalidateQueries({queryKey: ['myList', id, library.baseUrl, page, pageSize, sort]});
                                });
                            }}
                            colorScheme="danger"
                            leftIcon={<Icon as={MaterialIcons} name="delete" size="xs"/>}
                            size="sm"
                            variant="ghost">
                            {translate('general.delete')}
                        </Button>
                    </VStack>
                    <VStack w="65%">
                        <Text
                            _dark={{color: 'warmGray.50'}}
                            color="coolGray.800"
                            bold
                            fontSize={{
                                base: 'sm',
                                lg: 'md',
                            }}>
                            {item.title}
                        </Text>
                        {item.author ? (
                            <Text _dark={{color: 'warmGray.50'}} color="coolGray.800" fontSize="xs">
                                {translate('grouped_work.by')} {item.author}
                            </Text>
                        ) : null}
                    </VStack>
                </HStack>
            </Pressable>
        );
    };

    const Paging = () => {
        return (
            <Box
                safeArea={2}
                bgColor="coolGray.100"
                borderTopWidth="1"
                _dark={{
                    borderColor: 'gray.600',
                    bg: 'coolGray.700',
                }}
                borderColor="coolGray.200"
                flexWrap="nowrap"
                alignItems="center">
                <ScrollView horizontal>
                    <Button.Group size="sm">
                        <Button onPress={() => setPage(page - 1)} isDisabled={page === 1}>
                            {translate('general.previous')}
                        </Button>
                        <Button
                            onPress={() => {
                                if (!isPreviousData && data?.hasMore) {
                                    console.log('Adding to page');
                                    setPage(page + 1);
                                }
                            }}
                            isDisabled={isPreviousData || !data?.hasMore}>
                            {translate('general.next')}
                        </Button>
                    </Button.Group>
                </ScrollView>
                <Text mt={2} fontSize="sm">
                    Page {page} of {data?.totalPages}
                </Text>
            </Box>
        );
    };

    const getActionButtons = () => {
        return (
            <Box
                safeArea={2}
                bgColor="coolGray.100"
                borderBottomWidth="1"
                _dark={{
                    borderColor: 'gray.600',
                    bg: 'coolGray.700',
                }}
                borderColor="coolGray.200"
                flexWrap="nowrap">
                <ScrollView horizontal>
                    <HStack space={2}>
                        <FormControl w={150}>
                            <Select
                                name="sortBy"
                                selectedValue={sort}
                                accessibilityLabel="Select a Sort Method"
                                _selectedItem={{
                                    bg: 'tertiary.300',
                                    endIcon: <CheckIcon size="5"/>,
                                }}
                                onValueChange={(itemValue) => setSort(itemValue)}>
                                <Select.Item label="Sort By Title" value="title" key={0}/>
                                <Select.Item label="Sort By Date Added" value="dateAdded" key={1}/>
                                <Select.Item label="Sort By Recently Added" value="recentlyAdded" key={2}/>
                                <Select.Item label="Sort By User Defined" value="custom" key={3}/>
                            </Select>
                        </FormControl>
                        <EditList data={list} listId={id}/>
                    </HStack>
                </ScrollView>
            </Box>
        );
    };

    return (
        <SafeAreaView style={{flex: 1}}>
            {status === 'loading' || isFetching ? (
                loadingSpinner()
            ) : status === 'error' ? (
                loadError('Error', '')
            ) : (
                <>
                    <Box safeArea={2} pb={10}>
                        {getActionButtons()}
                        <FlatList data={data.listTitles} ListFooterComponent={Paging}
                                  renderItem={({item}) => renderItem(item, library.baseUrl)}
                                  keyExtractor={(item, index) => index.toString()}/>
                    </Box>
                </>
            )}
        </SafeAreaView>
    );
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