import React, { Component, useEffect, setState } from "react";
import { SectionList, FlatList } from "react-native";
import { Image, Button, Icon, Center, Box, Spinner, HStack, Select, Heading, CheckIcon, FormControl, Text, Flex, Container, Pressable, ScrollView } from "native-base";
import { FlatGrid } from "react-native-super-grid";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import { MaterialIcons, Entypo } from "@expo/vector-icons";
import FastImage from 'react-native-fast-image';

export default class Discovery extends Component {
	constructor() {
		super();
		this.state = {
			browseCat: {},
			data: {},
			isLoading: true,
			hasError: false,
			limiter: "",
			error: null,
			browseData: [],
		};
        this.arrayHolder = [];
	}

	// store the values into the state
	componentDidMount = async () => {
		await setSession();
        await setGlobalVariables();

		this.getBrowseData();
	};

	_listEmptyComponent = () => {
        if(this.state.error) {
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

	getBrowseData = async () => {
        try {
            const response = await fetch('http://demo.localhost:8888/app/aspenDiscover.php?library=bywaterconsortium&lida=true');
            const result = await response.json();
            this.setState({ browseData: result, isLoading: false });
        } catch (error) {
            console.error(error);
            this.setState({ hasError: true, error: 'Unable to fetch browse categories', isLoading: false })
        }
	}

	// route user to page that allows them to place a hold
	onPressItem = (item) => {
		this.props.navigation.navigate("ItemDetails", { item });
	};

	// renders the items on the screen
	renderNativeItem = (item) => {
		return (
			<Pressable onPress={() => this.onPressItem(item)}>
				<Flex justify="flex-end" m={0.5} h={200}>
					<Image borderRadius={8} h="100%" source={{ uri: item.image }} alt={item.title} />
				</Flex>
			</Pressable>
		);
	};

	renderSelectItem(options){
        if (options) {
           return(
            options.map((item,key) => (
            <Picker.Item label={item.name} value={item.id} />
            ))
        );
        }
      }


	getHeader = () => {
		var options = this.state.browseCat;

		return (
			<>
				<FormControl>
					<Select
						bg="white"
						selectedValue={this.state.selectedLabel}
						variant="underlined"
						accessibilityLabel="Tap to discover more"
						placeholder="Tap to discover more"
						_selectedItem={{
							bg: "tertiary.300",
							endIcon: <CheckIcon size={5} />,
						}}
						onValueChange={this.handleCategoryChange}
					>
						{options.map((item, index) => {
							return <Select.Item label={item.title} value={item.reference} key={item.reference} />;
						})}
					</Select>
				</FormControl>
			</>
		);
	};

	browseCategories() {
	var browseData = this.state.browseData;

        return browseData.map((category) => {
            return (
            <>
            <Box mb={3}>
                <Text bold mb={0.5} fontSize="lg">{category.title}</Text>
                <FlatList
                horizontal
                data={category.items}
                renderItem={({ item }) => this.renderBrowseCategoryItem(item)}
                keyExtractor={item => item.key}
                extraData={this.state}
                />
            </Box>
            </>
            )
        })
	};

	renderBrowseCategoryItem = (item) => {
		return (
        <Pressable mr={1.5}>
            <Image borderRadius={8} h={150} w={100} source={{ uri: item.image }} alt={item.title} resizeMode="cover" />
        </Pressable>
		);
	};

	render() {
		if (this.state.isLoading) {
			return (
				<Center flex={1}>
					<HStack>
						<Spinner accessibilityLabel="Finding titles..." />
					</HStack>
				</Center>
			);
		} else if (this.state.hasError) {
			return (
				<Center flex={1}>
				<HStack>
                    <Icon as={MaterialIcons} name="error" size="md" mt={.5} mr={1} color="error.500" />
                    <Heading color="error.500" mb={2}>Error</Heading>
				</HStack>
				<Text bold w="75%" textAlign="center">There was an error loading titles from the library. Please try again.</Text>
                <Button
                    mt={5}
                    colorScheme="primary"
                    onPress={() => this.grabListData()}
                    startIcon={<Icon as={MaterialIcons} name="refresh" size={5} />}
                >
                    Reload
                </Button>
                <Text fontSize="xs" w="75%" mt={5} color="muted.500" textAlign="center">ERROR: {this.state.error}</Text>
				</Center>
			);
		}

		return (
		<ScrollView>
            <Box safeArea={3}>
                {this.browseCategories()}
            </Box>
        </ScrollView>
		);
	}
};

const BrowseItem = ({ item, navigation }) => {
    return (
        <Pressable m={1} onPress={() => navigation.navigate("ItemDetails", { item })}>
        <Image borderRadius={8} h={200} w={120} source={{ uri: item.image }} alt={item.title} />
        </Pressable>
    );
};

async function setGlobalVariables() {
    try {
    // prepare app data
    global.version = Constants.manifest.version;
    global.timeout = 8000;

    // prepare user data
    global.userKey = await SecureStore.getItemAsync("userKey");
    global.secretKey = await SecureStore.getItemAsync("secretKey");
    global.sessionId = await SecureStore.getItemAsync("sessionId");
    global.pickUpLocation = await SecureStore.getItemAsync("pickUpLocation");
    global.patron = await SecureStore.getItemAsync("patronName");

    // prepare library data
    global.libraryId = await SecureStore.getItemAsync("library");
    global.libraryName = await SecureStore.getItemAsync("libraryName");
    global.locationId = await SecureStore.getItemAsync("locationId");
    global.solrScope = await SecureStore.getItemAsync("solrScope");
    global.libraryUrl = await SecureStore.getItemAsync("pathUrl");

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

    } catch {
        console.log("Error setting global variables.");
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
};