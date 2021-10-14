import React, { Component, useState } from "react";
import { Center, Stack, HStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Avatar, Pressable, IconButton } from "native-base";
import * as SecureStore from 'expo-secure-store';
import { TabView, SceneMap, TabBar, NavigationState, SceneRendererProps } from "react-native-tab-view";
import { MaterialIcons, Entypo } from "@expo/vector-icons";
import moment from "moment";

export default class AccountSummary extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
		};
	}

	componentDidMount = async () => {
		this.getAccountDetails();
	};

	getAccountDetails = () => {
		const url =
			global.libraryUrl +
			"/app/aspenAccountDetails.php?library=" +
			global.solrScope +
			"&barcode=" +
			global.userKey +
			"&pin=" +
			global.secretKey +
			"&sessionId=" +
			global.sessionId;

		fetch(url)
			.then((res) => res.json())
			.then((res) => {
				this.setState({
					checkOuts: res.numCheckedOut,
					holdsILS: res.holdsILS,
					holdsEProduct: res.holdsEProduct,
					fines: res.fines,
					isLoading: false,
				});
			})
			.catch((error) => {
				console.log("Unable to fetch data from: <" + url + "> in getAccountDetails");
			});
	};

	render() {
		return (
			<Box safeArea={5} style={{ backgroundColor: "white" }}>
					<Text fontSize="xl">{global.patron}'s Account Summary</Text>
					<Text fontSize="sm">
						<Text bold fontSize="sm" mr={0.5}>
							Barcode:{" "}
						</Text>
						{global.userKey}
					</Text>
					<Text fontSize="sm">
						<Text bold fontSize="sm">
							Items checked out:{" "}
						</Text>
						{this.state.checkOuts}
					</Text>
					<Text fontSize="sm">
						<Text bold fontSize="sm">
							Items on hold:{" "}
						</Text>
						{this.state.holdsILS}
					</Text>
					<Text fontSize="sm">
						<Text bold fontSize="sm">
							eItems on hold:{" "}
						</Text>
						{this.state.holdsEProduct}
					</Text>
			</Box>
		);
	}
}