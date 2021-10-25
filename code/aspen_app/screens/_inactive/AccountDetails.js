import React, { Component, useState } from "react";
import { Dimensions, Animated } from "react-native";
import { Center, Stack, HStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Avatar, Menu, Pressable, IconButton } from "native-base";
import * as SecureStore from 'expo-secure-store';
import { TabView, SceneMap, TabBar, NavigationState, SceneRendererProps } from "react-native-tab-view";
import { MaterialIcons } from "@expo/vector-icons";
import moment from "moment";
import Constants from 'expo-constants';

import Summary from "./Summary";
import CheckedOut from "./Checkouts";
import Holds from "./Holds";

const initialLayout = { width: Dimensions.get("window").width };
const renderScene = ({ route, navigation }) => {
      switch (route.key) {
        case 'first':
           return <CheckedOut navigation={navigation}/>;
        case 'second':
          return <Holds navigation={navigation}/>;
        default:
          return null;
      }
}

const handleIndexChange = (index: number) => this.setState({ index });

export default function MyItems() {
	const [index, setIndex] = React.useState(0);
	const [routes] = React.useState([
		{ key: "first", title: "Checked Out" },
		{ key: "second", title: "On Hold" },
	]);

	const renderTabBar = (props) => {
		const inputRange = props.navigationState.routes.map((x, i) => i);
		return (
			<Box flexDirection="row" backgroundColor="white">
				{props.navigationState.routes.map((route, i) => {
					const opacity = props.position.interpolate({
						inputRange,
						outputRange: inputRange.map((inputIndex) => (inputIndex === i ? 1 : 0.5)),
					});
					const color = index === i ? "#27272a" : "#d4d4d8";
					const weight = index === i ? "bold" : "normal";
					const borderColor = index === i ? "primary.900" : "coolGray.300";
					const backgroundColor = index === i ? "#d4d4d4" : "#fafafa";

					return (
						<Box
							backgroundColor={backgroundColor}
							borderBottomWidth={3}
							borderColor={borderColor}
							flex={1}
							alignItems="center"
							p={3}
							borderTopRightRadius={8}
							borderTopLeftRadius={8}
						>
							<Pressable
								onPress={() => {
									setIndex(i);
								}}
							>
								<Animated.Text style={{ color, fontWeight: weight }}>{route.title}</Animated.Text>
							</Pressable>
						</Box>
					);
				})}
			</Box>
		);
	};

	return (
		<>
			<AccountSummary />
			<TabView
				navigationState={{ index, routes }}
				renderScene={renderScene}
				renderTabBar={renderTabBar}
				onIndexChange={setIndex}
				initialLayout={initialLayout}
				swipeEnabled={false}
			/>
		</>
	);
}