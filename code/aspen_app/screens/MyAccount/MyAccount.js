import React, { Component, useState } from "react";
import { Dimensions, Animated, RefreshControl } from "react-native";
import { Center, Stack, HStack, Button, Divider, Flex, Box, Text, CloseIcon, Icon, Menu, Pressable, IconButton, FlatList, Badge, VStack, Alert } from "native-base";
import * as SecureStore from 'expo-secure-store';
import AsyncStorage from "@react-native-async-storage/async-storage";
import { MaterialIcons, Entypo, Ionicons } from "@expo/vector-icons";
import moment from "moment";
import Constants from 'expo-constants';
import { ListItem } from "react-native-elements";
import * as WebBrowser from 'expo-web-browser';
import { create, CancelToken } from 'apisauce';

// custom components and helper files
import { translate } from "../../util/translations";
import { loadingSpinner } from "../../components/loadingSpinner";
import { loadError } from "../../components/loadError";
import { getProfile, getILSMessages } from '../../util/loadPatron';
import { showILSMessage } from '../../components/Notifications';

export default class MyAccount extends Component {
	constructor() {
		super();
		this.state = {
            isLoading: true,
            hasError: false,
            error: null,
            hasUpdated: false,
            isRefreshing: false,
            ilsMessages: [],
            defaultMenuItems: [
                {
                    key: '0',
                    title: translate('checkouts.title'),
                    path: 'CheckedOut',
                    icon: null,
                    external: false,
                    description: null,
                },
                {
                    key: '1',
                    title: translate('holds.title'),
                    path: 'Holds',
                    icon: null,
                    external: false,
                    description: null,
                },
                {
                    key: '2',
                    title: translate('user_profile.home_screen_settings'),
                    path: 'SettingsHomeScreen',
                    icon: 'settings',
                    external: false,
                    description: translate('user_profile.home_screen_settings_description'),
                }
            ]
		 };
	}

	componentDidMount = async () => {
        this.setState({
            barcode: global.barcode,
            numCheckedOut: global.numCheckedOut,
            numHolds: global.numHolds,
            numHoldsAvailable: global.numHoldsAvailable,
            numOverdue: global.numOverdue,
            isLoading: false,
            thisPatron: global.patron + "'s",
        });

        await this._fetchProfile();
        await this._fetchILSMessages();
	};

    _fetchProfile = async () => {

        this.setState({
            isLoading: true,
        });

        const forceReload = this.state.isRefreshing;

        await getProfile(true).then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: translate('error.timeout'),
                    isLoading: false,
                });
            } else {
                this.setState({
                    hasError: false,
                    error: null,
                    isLoading: false,
                });
            }
        })
    }

    _fetchILSMessages = async () => {

        this.setState({
            isLoading: true,
        });

        await getILSMessages().then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: translate('error.timeout'),
                    isLoading: false,
                });
            } else {
                var messageCount = response.length;
                this.setState({
                    hasError: false,
                    error: null,
                    isLoading: false,
                    ilsMessages: response,
                    ilsMessageCount: messageCount,
                });
            }
        })
    }

	renderNativeItem = (item) => {
        if(item.external) {
            return (
                <ListItem bottomDivider onPress={() => {this.openWebsite(item.path)} }>
                {item.icon ? <Icon as={MaterialIcons} name={item.icon} /> : null }
                    <ListItem.Content>
                        <Text bold>{item.title}</Text>
                        {item.description != null ? <Text fontSize="xs">{item.description}</Text> : null}
                    </ListItem.Content>
                    <ListItem.Chevron />
                </ListItem>
            );
        } else {
            return (
                <ListItem bottomDivider onPress={() => {this.onPressMenuItem(item.path)} }>
                {item.icon ? <Icon as={MaterialIcons} name={item.icon} /> : null }
                    <ListItem.Content>
                        <Text bold>{item.title}</Text>
                        {item.description != null ? <Text fontSize="xs">{item.description}</Text> : null}
                    </ListItem.Content>
                    <ListItem.Chevron />
                </ListItem>
            );
        }
	};

	onPressMenuItem = (item) => {
		this.props.navigation.navigate(item, { item });
	};

	onPressLogout = async () => {
	    await removeData();
	    this.props.navigation.navigate("Permissions");
	};

	openWebsite = async (url) => {
	    WebBrowser.openBrowserAsync(url);
	};

	displayILSMessage = (messages) => {
	console.log(this.state.ilsMessages);
        return (
            messages.map((item, index) => {
                return showILSMessage(item.messageStyle, item.message);
            })
        )
	};

	_onRefresh() {
	    this.setState({ isRefreshing: true }, () => {
            this._fetchProfile().then(() => {
                this.setState({ isRefreshing: false });
            });
	    });
	};

	_renderSettingsHeader = () => {
        return (
        <Box safeArea={5}>
            <Center>
            <Text fontSize="2xl">{this.state.thisPatron} {translate('user_profile.title')}</Text>
                {this.state.barcode ?
                <HStack space={1} alignItems="center" mt={2} mb={2}><Icon as={Ionicons} name="card" size="sm" />
                <Text bold fontSize="md" mr={0.5}>
                    {this.state.barcode}
                </Text>
                </HStack>
                : null}
            </Center>
            <Divider mt={2} mb={2} />
            <HStack space={1} pb={10}>
            <VStack width="50%">
            <Center>
                <Text fontSize="md" mb={1}>
                    <Text bold>{translate('checkouts.title')}: </Text>{this.state.numCheckedOut}
                </Text>
                {this.state.numOverdue >= 0 ? <Badge colorScheme="danger" rounded="4px"><Text fontSize="xs" bold>{translate('checkouts.overdue_summary', { count: this.state.numOverdue })}</Text></Badge> : null}
                </Center>
            </VStack>
            <VStack width="50%">
            <Center>
                <Text fontSize="md" mb={1}>
                    <Text bold>{translate('holds.holds')}: </Text>{this.state.numHolds}
                </Text>
                {this.state.numHoldsAvailable >= 0 ? <Badge colorScheme="success" rounded="4px"><Text fontSize="xs" bold>{translate('holds.ready_for_pickup', { count: this.state.numHoldsAvailable})}</Text></Badge> : null}
                </Center>
            </VStack>
            </HStack>
            {this.state.ilsMessageCount >= 1 ? this.displayILSMessage(this.state.ilsMessages) : null}
            </Box>
        )
	}

    _renderSettingsFooter = () => {
        return (
        <Center pt={10}>
            <Button variant="outline" size="xs" onPress={() => {this._fetchProfile()} } startIcon={<Icon as={MaterialIcons} name="refresh" size="xs" />}>Reload Account Data</Button>
        </Center>
        )
    }



	render() {
		if (this.state.isLoading) {
			return ( loadingSpinner() );
		}

		if (this.state.hasError) {
            return ( loadError(this.state.error) );
		}

		return (
            <Box style={{ backgroundColor: "white" }} pb={10}>
				<FlatList
					data={this.state.defaultMenuItems}
					renderItem={({ item }) => this.renderNativeItem(item)}
					keyExtractor={(item, index) => index.toString()}
					ListHeaderComponent={this._renderSettingsHeader()}
					ListFooterComponent={this._renderSettingsFooter()}
					refreshControl={
					    <RefreshControl
					        refreshing={this.state.isRefreshing}
					        onRefresh={this._onRefresh.bind(this)}
                        />
					}
				/>
			</Box>
		);
	}
}