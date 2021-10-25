import React, { Component, useEffect, setState, useState } from "react";
import { SectionList, View, TouchableWithoutFeedback } from "react-native";
import { Image, Button, Icon, Center, Box, Spinner, HStack, Select, Heading, Toast, CheckIcon, FormControl, Text, Flex, Container, Pressable, ScrollView, FlatList } from "native-base";
import { FlatGrid } from "react-native-super-grid";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import { MaterialIcons, Entypo } from "@expo/vector-icons";
import ExpoFastImage from 'expo-fast-image'
import NavigationService from '../../components/NavigationService';
import BrowseCategory from './BrowseCategory';
import * as Random from 'expo-random';
import { create, CancelToken } from 'apisauce';
import moment from "moment";
import base64 from 'react-native-base64';

export default class BrowseCategoryHome extends Component {
	constructor() {
		super();
		this.state = {
			data: [],
			page: 1,
            isLoading: true,
            isLoadingMore: false,
			hasError: false,
			error: null,
            refreshing: false,
            filtering: false,
            categories: null,
		};
	}

	componentDidMount = async () => {
        this.setState({
            isLoading: true,

        });

       console.log("Ready to work.")
       console.log("Preparing...")
       console.log("Creating session...")
       await setSession();
       await setGlobalVariables();
       await getPatronCheckedOutItems();
       await getPatronHolds();
       await setPatronProfile();
       await setLibraryProfile();
       console.log("Jobs done.")

       await this.getActiveBrowseCategories();
	}

    componentWillUnmount() {
    }

	getActiveBrowseCategories = () => {
        const api = create({ baseURL: 'http://demo.localhost:8888/API/' , timeout: 10000});
        api.get("SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true")
            .then(response => {
                if(response.ok) {
                    const items = response.data;
                        this.setState({
                            isLoading: false,
                            categories: items.result,
                        })
                } else {
                    console.log(response);
                }
            })
    }

    onPressItem = (item) => {
        this.props.navigation.navigate("GroupedWork", { item });
    };

    onLoadMore = (item) => {
        this.props.navigation.navigate("GroupedWork", { item });
    };

    _renderNativeItem = (data) => {
	    const imageUrl = global.libraryUrl + "/bookcover.php?id=" + data.key + "&size=medium&type=grouped_work";
		return (
        <Pressable mr={1.5} onPress={() => this.onPressItem(data.key)} width={{ base: 100, lg: 200 }} height={{ base: 125, lg: 275 }}>
            <ExpoFastImage cacheKey={data.key} uri={imageUrl} alt={data.title} resizeMode="cover" style={{ width: '100%', height: '100%', borderRadius:8 }} />
        </Pressable>
		);
	};

    _listFooterComponent = () => {
		return (
        <Pressable onPress={() => this.onLoadMore(data.key)} width={{ base: 100, lg: 200 }} height={{ base: 125, lg: 275 }} bgColor="secondary.400" rounded={8}>
            <Center flex={1}><Text fontSize={{ base: "lg", lg: "2xl" }} bold>Load More</Text></Center>
        </Pressable>
		);
	};

	_listEmptyComponent = () => {
        if(this.state.hasError) {
            return (
                <Center mt={5} mb={5}>
                    <Text bold fontSize="lg">
                        Error loading items. Please try again later.
                    </Text>
                </Center>
            )
        }
		return (
			<Center mt={5} mb={5}>
				<Text bold fontSize="lg">
					No items to load
				</Text>
				<Text>Try a different category</Text>
			</Center>
		);
	};

	render() {
	    const { isLoading, categories } = this.state;

        if(this.state.isLoading == true) {
           return (
               <Center flex={1}>
                   <HStack>
                       <Spinner accessibilityLabel="Loading..." />
                   </HStack>
               </Center>
           );
        } else {
            return (
            <ScrollView>
                <Box safeArea={5}>
                    {categories.map((category) => {
                        return (
                            <BrowseCategory
                            isLoading={isLoading}
                            categoryLabel={category.title}
                            categoryKey={category.key}
                            renderItem={this._renderNativeItem}
                            emptyComponent={this._listEmptyComponent}
                            footerComponent={this._listFooterComponent}
                            loadMore={this.onLoadMore}
                            />
                        );
                    })}
                </Box>
            </ScrollView>
            );
        }
    }

}

async function setGlobalVariables() {

    try {
        // prepare app data
        global.version = Constants.manifest.version;
        global.timeout = 10000;

        // prepare user data
        global.userKey = await SecureStore.getItemAsync("userKey");
        global.secretKey = await SecureStore.getItemAsync("secretKey");
        global.patron = await SecureStore.getItemAsync("patronName");

        // prepare library data
        global.libraryId = await SecureStore.getItemAsync("library");
        global.libraryName = await SecureStore.getItemAsync("libraryName");
        global.locationId = await SecureStore.getItemAsync("locationId");
        global.solrScope = await SecureStore.getItemAsync("solrScope");
        global.libraryUrl = await SecureStore.getItemAsync("pathUrl");
        global.logo = await SecureStore.getItemAsync("logo");
        global.favicon = await SecureStore.getItemAsync("favicon");

        // prepare urls for API calls
        // global.aspenBrowseCategory = global.libraryUrl + "/app/aspenBrowseCategory.php?library=" + global.solrScope;
        // global.aspenDiscover = global.libraryUrl + "/app/aspenDiscover.php?library=" + global.solrScope + "&lida=true";
        // global.aspenAccountDetails = global.libraryUrl + "/app/aspenAccountDetails.php?library=" + global.solrScope + "&barcode=" + global.userKey + "&pin=" + global.secretKey + "&sessionId=" + global.sessionId;
        // global.aspenRenew = global.libraryUrl + '/app/aspenRenew.php?library=' + global.solrScope + '&barcode=' + global.userKey + '&pin=' + global.secretKey + '&sessionId=' + global.sessionId;
        // global.aspenListCKO = global.libraryUrl + '/app/aspenListCKO.php?library=' + global.solrScope + '&barcode=' + global.userKey + '&pin=' + global.secretKey + '&sessionId=' + global.sessionId;
        global.aspenMoreDetails = global.libraryUrl + "/app/aspenMoreDetails.php?id=" + global.locationId + "&library=" + global.solrScope + "&version=" + global.version + "&index=";
        // global.aspenListHolds = global.libraryUrl + '/app/aspenListHolds.php?library=' + global.solrScope + '&barcode=' + global.userKey + '&pin=' + global.secretKey + '&sessionId=' + global.sessionId + '&action=ilsCKO';
        global.aspenPickupLocations = global.libraryUrl + "/app/aspenPickUpLocations.php?library=" + global.solrScope + "&barcode=" + global.userKey + "&pin=" + global.secretKey + "&sessionId=" + global.sessionId;
        // global.aspenSearch = global.libraryUrl + "/app/aspenSearchLists.php?library=" + global.solrScope;
        // global.aspenSearchResults = global.libraryUrl + "/app/aspenSearchResults.php?library=" + global.solrScope + "&lida=true";

        console.log("Global variables set.")

    } catch(e) {
        console.log("Error setting global variables.");
        console.log(e);
    }
};

async function setSession() {
    try {
        const guid = Random.getRandomBytes(32);
        global.sessionId = guid;
    } catch {
        const random = moment().unix();
        global.sessionId = random;
    }

    console.log("Session created.")

};

async function getPatronCheckedOutItems() {

    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=getPatronCheckedOutItems', { source: 'all', username: global.userKey, password: global.secretKey });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;

        global.checkedOutItems = fetchedData.checkedOutItems;
        console.log("Patron checkouts saved.")

    } else {
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }
}

async function getPatronHolds() {

    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=getPatronHolds', { source: 'all', username: global.userKey, password: global.secretKey });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;
        const allHolds = fetchedData.holds;

        global.allHolds = allHolds;

        console.log("Patron holds saved.")

    } else {
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }
}

async function setPatronProfile() {

    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=getPatronProfile', { username: global.userKey, password: global.secretKey });

    if(response.ok) {
        const results = response.data;
        const result = results.result;
        const profile = result.profile;

        console.log(result);

        try {
            global.homeLocationId = profile.homeLocationId;
            global.barcode = profile.cat_username;
            global.interfaceLanguage = profile.interfaceLanguage;
            global.patronId = profile.id;

            global.rememberHoldPickupLocation = profile.rememberHoldPickupLocation;
            global.pickupLocationId = profile.pickupLocationId;

            global.promptForOverdriveEmail = profile.promptForOverdriveEmail;
            global.overdriveEmail = profile.overdriveEmail;

            global.holdInfoLastLoaded = profile.holdInfoLastLoaded;
            global.checkoutInfoLastLoaded = profile.checkoutInfoLastLoaded;
            global.numCheckedOutIls = profile.numCheckedOutIls;
            global.numCheckedOutOverDrive = profile.numCheckedOutOverDrive;
            global.numOverdue = profile.numOverdue;
            global.numHoldsIls = profile.numHoldsIls;
            global.numHoldsOverDrive = profile.numHoldsOverDrive;
            global.numHoldsAvailableIls = profile.numHoldsAvailableIls;

            console.log("Patron profile set.");
        } catch (error) {
            console.log("Unable to set patron profile.");
            console.log(error);
        }

    } else {
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }
}

async function setLibraryProfile() {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 10000 });
    const response = await api.get('/SystemAPI?method=getLocationInfo', { id: global.locationId, library: global.solrScope, version: global.version });

    if(response.ok) {
        const result = response.data;
        const libraryProfile = result.result;

        if (libraryProfile.success == true) {
            const profile = libraryProfile.location;

            try {
                await AsyncStorage.setItem('@libraryHomeLink', profile.homeLink);
                await AsyncStorage.setItem('@libraryAddress', profile.address);
                await AsyncStorage.setItem('@libraryPhone', profile.phone);
                await AsyncStorage.setItem('@libraryEmail', "aspen@bywatersolutions.com");
                await AsyncStorage.setItem('@libraryShowHours', "1");
                await AsyncStorage.setItem('@libraryHoursMessage', profile.hoursMessage);
                await AsyncStorage.setItem('@libraryHours', JSON.stringify(profile.hours));
                await AsyncStorage.setItem('@libraryLatitude', profile.latitude);
                await AsyncStorage.setItem('@libraryLongitude', profile.longitude);

                console.log("Library profile set.")
            } catch (error) {
                console.log("Unable to set library profile.");
                console.log(error);
            }

        } else {
            console.log("Connection made, but library location not found.")
        }

    } else {
        const fetchedData = response.problem;
        console.log("Error setting library profile.");
        console.log(fetchedData);
        return fetchedData;
    }
}
