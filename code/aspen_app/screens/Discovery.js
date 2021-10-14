import React, { Component, useEffect, setState } from "react";
import { Image, Center, Box, Spinner, HStack, Select, Heading, CheckIcon, FormControl, Text, Flex, Container, Pressable } from "native-base";
import { FlatGrid } from "react-native-super-grid";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";

export default class Discovery extends Component {
	constructor() {
		super();
		this.state = {
			browseCat: {},
			data: {},
			isLoading: true,
			hasError: false,
			limiter: "",
		};

		this.handleCategoryChange = this.handleCategoryChange.bind(this);

	}

	// store the values into the state
	componentDidMount = async () => {
		await setSession();
        await setGlobalVariables();

		this.grabBrowseCategory();
		this.grabListData(this.state.limiter);
	};

    handleCategoryChange(itemValue) {
        this.setState({ selectedLabel: itemValue });
        this.grabListData(itemValue);
    }

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

	// Grab the browse categories
	grabBrowseCategory = () => {
		const url = global.libraryUrl + "/app/aspenBrowseCategory.php?library=" + global.solrScope;

		fetch(url)
			.then((res) => res.json())
			.then((res) => {
				this.setState({
					browseCat: res.Items,
					limiter: res.default,
				});
			})
			.catch((error) => {
				console.log("Unable to fetch data from: <" + url + "> in grabBrowseCategory");
				this.setState({
					error: "Unable to fetch browse category",
					isLoading: false,
					hasError: true,
				});
			});
	};

	// call the list data in a function in order to reload when changes happen
	grabListData = (restriction) => {
		this.setState({
			isLoading: true,
			limiter: restriction,
		});

		const url = global.libraryUrl + "/app/aspenDiscover.php?library=" + global.solrScope + "&limiter=" + restriction;
        console.log(url);
		fetch(url)
			.then((res) => res.json())
			.then((res) => {
				this.setState({
					data: res.Items,
					isLoading: false,
				});
			})
			.catch((error) => {
				console.log("Unable to fetch data from: <" + url + "> in grabListData");
				this.setState({
					error: "Unable to fetch browse categories",
					isLoading: false,
					hasError: true,
				});
			});
	};

	// route user to page that allows them to place a hold
	onPressItem = (item) => {
		this.props.navigation.navigate("ItemDetails", { item });
	};

	// renders the items on the screen
	renderNativeItem = (item) => {
		return (
			<Pressable onPress={() => this.props.navigation.navigate("ItemDetails", { item })}>
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
				<Center flex={5} mb={5}>
					<Text bold fontSize="lg">
						<Text>Error loading titles. Please try again later.</Text>
					</Text>
				</Center>
			);
		}

		return (
			<>
				<Box bg="white">{this.getHeader()}</Box>
				<FlatGrid
					data={this.state.data}
					itemDimension={120}
					keyExtractor={(item) => item.key}
					renderItem={({ item }) => this.renderNativeItem(item)}
					extraData={this.state}
					style={{ marginTop: 5, flex: 1 }}
					spacing={1}
					ListEmptyComponent={this._listEmptyComponent()}
				/>
			</>
		);
	}
};

async function setGlobalVariables() {
    try {
    await SecureStore.setItemAsync("version", Constants.manifest.version);
    global.userKey = await SecureStore.getItemAsync("userKey");
    global.secretKey = await SecureStore.getItemAsync("secretKey");
    global.sessionId = await SecureStore.getItemAsync("sessionId");
    global.pickUpLocation = await SecureStore.getItemAsync("pickUpLocation");
    global.patron = await SecureStore.getItemAsync("patronName");
    global.libraryId = await SecureStore.getItemAsync("library");
    global.libraryName = await SecureStore.getItemAsync("libraryName");
    global.locationId = await SecureStore.getItemAsync("locationId");
    global.solrScope = await SecureStore.getItemAsync("solrScope");
    global.libraryUrl = await SecureStore.getItemAsync("pathUrl");
    global.version = await SecureStore.getItemAsync("version");
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
