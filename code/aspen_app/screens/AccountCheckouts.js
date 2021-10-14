import React, { Component, useState } from "react";
import { View, Dimensions, Animated, StatusBar } from "react-native";
import { Center, Stack, HStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Avatar, Menu, Pressable, IconButton, FlatList } from "native-base";
import * as SecureStore from 'expo-secure-store';
import { ListItem } from "react-native-elements";
import { TabView, SceneMap, TabBar, NavigationState, SceneRendererProps } from "react-native-tab-view";
import { MaterialIcons, Entypo } from "@expo/vector-icons";
import moment from "moment";

export default class AccountCheckouts extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			error: false,
			message: "",
		};
	}

	componentDidMount = async () => {
		// grab the checkouts
		this.getCheckOuts();
	};

	// grabs the items checked out to the account
	getCheckOuts = () => {
		const url =
			global.libraryUrl +
			"/app/aspenListCKO.php?library=" +
			global.solrScope +
			"&barcode=" +
			global.userKey +
			"&pin=" +
			global.secretKey +
			"&action=ilsCKO&sessionId=" +
			global.sessionId;
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
				console.log("Unable to fetch data from: <" + url + "> in getCheckouts");
				this.setState({ error: true });
			});
	};

	// renders the items on the screen
	renderNativeItem = (item) => {
		var dateDue = moment(item.dateDue).format("MMM D, YYYY");
		return (
        <ListItem.Swipeable bottomDivider
            style={{ backgroundColor: "tertiary.300" }}
            leftContent={
            <Center bg="tertiary.400" height="100%" width="100%">
            <IconButton
                size="2xl"
                icon={<Icon as={MaterialIcons} name="refresh" />}
                accessibilityLabel="Renew"
                onPress={() => { this.onPressRenewSingle(item) }}
            />
            </Center>
            }
        >
            <Avatar source={{ uri: item.thumbnail }} size="lg" />
            <ListItem.Content>
                <Text fontSize="md" bold>
                    {item.key}
                </Text>
                <Text fontSize="xs">
                    <Text bold fontSize="xs">
                        Author:{" "}
                    </Text>
                    {item.author}
                </Text>
                <Text fontSize="xs">
                    <Text bold fontSize="xs">
                        Due:{" "}
                    </Text>
                    {dateDue}
                </Text>
            </ListItem.Content>
        </ListItem.Swipeable>
		);
	};

	// handles the on press action and attempts to renew selected item
	onPressRenewSingle = (item) => {
	const url = global.libraryUrl +
                "/app/aspenRenew.php?library=" +
                global.solrScope +
                "&barcode=" +
                global.userKey +
                "&pin=" +
                global.secretKey +
                "&itemId=" +
                item.itemBarcode +
                "&sessionId=" +
                global.sessionId;
    fetch(url)
		.then((res) => res.json())
		.then((res) => {
			let renewed = res.renewed;
			console.log(renewed);
			let message = res.message;
            console.log(message);

			if (renewed) {
                Toast.show({
                    title: "Renew successful",
                    description: res.message,
                    status: "success",
                    isClosable: true,
                    duration: 8000,
                    accessibilityAnnouncement: res.message,
                });
			} else {
                Toast.show({
                    title: "Error renewing",
                    description: res.message,
                    status: "warning",
                    isClosable: true,
                    duration: 8000,
                    accessibilityAnnouncement: res.message,
                });
			}
		})
	}

	// handles the on press action and attempts to renew all items
	onPressRenewAll = () => {
		const url = global.libraryUrl + '/app/aspenRenew.php?library=' + global.solrScope + '&barcode=' + global.userKey + '&pin=' + global.secretKey + '&itemId=all&sessionId=' + global.sessionId;

		fetch(url)
			.then((res) => res.json())
			.then((res) => {
				let renewed = res.renewed;
				let message = res.message;

				// handle a failed renewal and then exit function
				if (renewed) {
					this.setState({
						message: message,
					});
                    Toast.show({
                        title: "Renew successful",
                        description: this.state.message,
                        status: "success",
                        isClosable: true,
                        duration: 8000,
                        accessibilityAnnouncement: this.state.message,
                    });
				} else {
					this.setState({
						message: message,
					});
                    Toast.show({
                        title: "Error renewing",
                        description: this.state.message,
                        status: "warning",
                        isClosable: true,
                        duration: 8000,
                        accessibilityAnnouncement: this.state.message,
                    });
				}
			})
			.catch((error) => {
				console.log("Unable to fetch data from: <" + url + "> in onPressRenewAll");
                Toast.show({
                    title: "Could not connect to library",
                    description: "Your items were not renewed successfully",
                    status: "error",
                    isClosable: true,
                    duration: 8000,
                    accessibilityAnnouncement: "Could not connect to library. Your items were not renewed successfully.",
                });
			});
	};

	_listEmptyComponent = () => {
        if(this.state.error) {
            return (
                <Center mt={5} mb={5}>
                    <Text bold fontSize="lg">
                        Error loading checkouts. Please try again later.
                    </Text>
                </Center>
            )
        }
		return (
			<Center mt={5} mb={5}>
				<Text bold fontSize="lg">
					You have no items checked out.
				</Text>
			</Center>
		);
	};

	render() {
		if (this.state.isLoading) {
			return (
				<Center flex={1}>
					<HStack>
						<Spinner accessibilityLabel="Loading..." />
					</HStack>
				</Center>
			);
		}

		return (
		<Box>
            <Center bg="white" pt={3} pb={3}>
                <Button.Group>
                    <Button
                        size="sm"
                        colorScheme="primary"
                        onPress={() => this.onPressRenewAll()}
                        startIcon={<Icon as={MaterialIcons} name="autorenew" size={5} />}
                    >
                        Try to Renew All
                    </Button>
                    <Button
                    size="sm"
                        onPress={() => this.getCheckOuts()}
                        startIcon={<Icon as={MaterialIcons} name="refresh" size={5} />}
                    >
                        Reload Checkouts
                    </Button>
                </Button.Group>
            </Center>
			<FlatList
				data={this.state.data}
				ListEmptyComponent={this._listEmptyComponent()}
				renderItem={({ item }) => this.renderNativeItem(item)}
				keyExtractor={(item) => item.barcode}
			/>
        </Box>
		);

	}
}