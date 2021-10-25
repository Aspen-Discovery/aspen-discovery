import React, { Component, useEffect, setState, useState } from "react";
import { SectionList, View, TouchableWithoutFeedback } from "react-native";
import { Image, Button, Icon, Center, Box, Spinner, HStack, Select, Heading, Toast, CheckIcon, FormControl, Text, Flex, Container, Pressable, ScrollView, FlatList } from "native-base";
import { FlatGrid } from "react-native-super-grid";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import { MaterialIcons, Entypo } from "@expo/vector-icons";
import ExpoFastImage from 'expo-fast-image'
import NavigationService from '../components/NavigationService';
import BrowseCategory from './BrowseCategory';
import { create, CancelToken } from 'apisauce';
import base64 from 'react-native-base64';

export default class Discovery extends Component {
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
        this.props.navigation.navigate("ItemDetails", { item });
    };

    _renderNativeItem = (data) => {
	    const imageUrl = global.libraryUrl + "/bookcover.php?id=" + data.key + "&size=medium&type=grouped_work";
		return (
        <Pressable mr={1.5} onPress={() => this.onPressItem(data.key)}>
            <ExpoFastImage cacheKey={data.key} uri={imageUrl} alt={data.title} resizeMode="cover" style={{ width: 100, height: 150, borderRadius:8 }} />
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
                    {categories.map((category) => {
                        return (
                            <BrowseCategory
                            isLoading={isLoading}
                            categoryLabel={category.title}
                            categoryKey={category.key}
                            renderItem={this._renderNativeItem}
                            emptyComponent={this._listEmptyComponent}
                            />
                        );
                    })}
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
        const userKey = await SecureStore.getItemAsync("userKey");
        global.userKey = base64.encode(userKey);
        const secretKey = await SecureStore.getItemAsync("secretKey");
        global.secretKey = base64.encode(secretKey);
        global.sessionId = await SecureStore.getItemAsync("sessionId");
        global.pickUpLocation = await SecureStore.getItemAsync("pickUpLocation");
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
        global.aspenBrowseCategory = global.libraryUrl + "/app/aspenBrowseCategory.php?library=" + global.solrScope;
        global.aspenDiscover = global.libraryUrl + "/app/aspenDiscover.php?library=" + global.solrScope + "&lida=true";
        global.aspenAccountDetails = global.libraryUrl + "/app/aspenAccountDetails.php?library=" + global.solrScope + "&barcode=" + global.userKey + "&pin=" + global.secretKey + "&sessionId=" + global.sessionId;
        global.aspenRenew = global.libraryUrl + '/app/aspenRenew.php?library=' + global.solrScope + '&barcode=' + global.userKey + '&pin=' + global.secretKey + '&sessionId=' + global.sessionId;
        global.aspenListCKO = global.libraryUrl + '/app/aspenListCKO.php?library=' + global.solrScope + '&barcode=' + global.userKey + '&pin=' + global.secretKey + '&sessionId=' + global.sessionId;
        global.aspenMoreDetails = global.libraryUrl + "/app/aspenMoreDetails.php?id=" + global.locationId + "&library=" + global.solrScope + "&version=" + global.version + "&index=";
        global.aspenListHolds = global.libraryUrl + '/app/aspenListHolds.php?library=' + global.solrScope + '&barcode=' + global.userKey + '&pin=' + global.secretKey + '&sessionId=' + global.sessionId + '&action=ilsCKO';
        global.aspenPickupLocations = global.libraryUrl + "/app/aspenPickUpLocations.php?library=" + global.solrScope + "&barcode=" + global.userKey + "&pin=" + global.secretKey + "&sessionId=" + global.sessionId;
        global.aspenSearch = global.libraryUrl + "/app/aspenSearchLists.php?library=" + global.solrScope;
        global.aspenSearchResults = global.libraryUrl + "/app/aspenSearchResults.php?library=" + global.solrScope + "&lida=true";
        // we won't use this one by the time globals are set, but lets build it just in case we need to verify later on in the app
        global.aspenLogin = global.libraryUrl + "/app/aspenLogin.php?barcode=" + global.userKey + "&pin=" + global.secretKey + "&sessionId=" + global.sessionId;

        console.log("Global variables set.")

    } catch(e) {
        console.log("Error setting global variables.");
        console.log(e);
    }
};

async function setSession() {
    var S4 = function () {
        return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
    };

    var guid = S4() + S4() + "-" + S4() + "-" + S4() + "-" + S4() + "-" + S4() + S4() + S4();

    try {
        await SecureStore.setItemAsync("sessionId", guid);
    } catch {
        const random = new Date().getTime()
        await SecureStore.setItemAsync("sessionId", random);
    }

    console.log("Session created.")

};

async function getPatronCheckedOutItems() {

    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=getPatronCheckedOutItems', { source: 'all', username: base64.decode(global.userKey), password: base64.decode(global.secretKey) });

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

    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=getPatronHolds', { source: 'all', username: base64.decode(global.userKey), password: base64.decode(global.secretKey) });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;
        const allHolds = fetchedData.holds;

        global.allHolds = allHolds;
        global.unavailableHolds = Object.values(allHolds.unavailable);
        global.availableHolds = Object.values(allHolds.available);

        console.log("Patron holds saved.")

    } else {
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }
}

async function setPatronProfile() {

    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=getPatronProfile', { username: base64.decode(global.userKey), password: base64.decode(global.secretKey) });

    if(response.ok) {
        const result = response.data;
        const patronProfile = result.result;
        const profile = patronProfile.profile;

        try {
            await SecureStore.setItemAsync("homeLocationId", profile.homeLocationId);
            await SecureStore.setItemAsync("barcode", profile.cat_username);
            await SecureStore.setItemAsync("interfaceLanguage", profile.interfaceLanguage);
            await SecureStore.setItemAsync("patronId", profile.id);
            await SecureStore.setItemAsync("overdriveEmail", profile.overdriveEmail);
            await SecureStore.setItemAsync("pickupLocationId", profile.pickupLocationId);
            await SecureStore.setItemAsync("promptForOverdriveEmail", profile.promptForOverdriveEmail);
            await SecureStore.setItemAsync("rememberHoldPickupLocation", profile.rememberHoldPickupLocation);
            await SecureStore.setItemAsync("holdInfoLastLoaded", profile.holdInfoLastLoaded);
            await SecureStore.setItemAsync("checkoutInfoLastLoaded", profile.checkoutInfoLastLoaded);
            await SecureStore.setItemAsync("numCheckedOutIls", profile.numCheckedOutIls.toString());
            await SecureStore.setItemAsync("numCheckedOutOverDrive", profile.numCheckedOutOverDrive.toString());
            //await SecureStore.setItemAsync("numOverdue", profile.numOverdue.toString());
            await SecureStore.setItemAsync("numHoldsIls", profile.numHoldsIls.toString());
            await SecureStore.setItemAsync("numHoldsOverDrive", profile.numHoldsOverDrive.toString());
            await SecureStore.setItemAsync("numHoldsAvailableIls", profile.numHoldsAvailableIls.toString());
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
                await SecureStore.setItemAsync("libraryHomeLink", profile.homeLink);
                await SecureStore.setItemAsync("libraryAddress", profile.address);
                await SecureStore.setItemAsync("libraryPhone", profile.phone);
                await SecureStore.setItemAsync("libraryHoursMessage", profile.hoursMessage);
                await SecureStore.setItemAsync("libraryHours", JSON.stringify(profile.hours));
                await SecureStore.setItemAsync("libraryLatitude", profile.latitude);
                await SecureStore.setItemAsync("libraryLongitude", profile.longitude);

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
