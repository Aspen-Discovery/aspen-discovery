import React, { Component, useState } from "react";
import { Dimensions, Animated } from "react-native";
import { Center, Stack, HStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Avatar, IconButton, FlatList } from "native-base";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import { ListItem } from "react-native-elements";
import { TabView, SceneMap, TabBar, NavigationState, SceneRendererProps } from "react-native-tab-view";
import { MaterialIcons, Entypo } from "@expo/vector-icons";
import moment from "moment";

export default class AccountHolds extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			error: false,
		};
	}

	// handles the mount information, setting session variables, etc
	componentDidMount = async () => {
		// grab the checkouts
		this.getHolds();
	};

	// grabs the items checked out to the account
	getHolds = () => {
		const url = global.libraryUrl + '/app/aspenListHolds.php?library=' + global.solrScope + '&barcode=' + global.userKey + '&pin=' + global.secretKey + '&action=ilsCKO&sessionId=' + global.sessionId;
		fetch(url)
			.then((res) => res.json())
			.then((res) => {
				this.setState({
					data: res.Items,
					isLoading: false,
				});
			})
			.catch((error) => {
				console.log("Unable to fetch data from: <" + url + "> in getHolds");
				this.setState({ error: true, isLoading: false });
                Toast.show({
                    title: "Connection error",
                    description: "Could not connect to library. Your items may not be up to date.",
                    status: "error",
                    isClosable: true,
                    duration: 8000,
                    accessibilityAnnouncement: "Could not connect to library. Your items may not be up to date.",
                });
			});
	};

	// renders the items on the screen
	renderNativeItem = (item) => {
		if (isNaN(item.position)) {
			var positionLabel = "Ready for Pickup Until: ";
			var position = moment(item.position).format("MMM D, YYYY");
		} else {
			var positionLabel = "Position in Queue: ";
			var position = item.position;
		}

		return (
            <ListItem.Swipeable bottomDivider>
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
                        {positionLabel} {position}
                    </Text>
                </Text>
            </ListItem.Content>
        </ListItem.Swipeable>
		);
	};

	_listEmptyComponent = () => {
        if(this.state.error) {
            return (
                <Center mt={5} mb={5}>
                    <Text bold fontSize="lg">
                        Error loading holds. Please try again later.
                    </Text>
                </Center>
            )
        }
		return (
			<Center mt={5} mb={5}>
				<Text bold fontSize="lg">
					You have no items on hold.
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
                <Button
                    size="sm"
                    onPress={() => this.getHolds()}
                    startIcon={<Icon as={MaterialIcons} name="refresh" size={5} />}
                >
                    Reload Holds
                </Button>
            </Center>
				<FlatList
					data={this.state.data}
					ListEmptyComponent={this._listEmptyComponent()}
					renderItem={({ item }) => this.renderNativeItem(item)}
					keyExtractor={(item) => item.key.concat(":", item.position, ":", global.sessionId)}
				/>
            </Box>
		);
	}
}