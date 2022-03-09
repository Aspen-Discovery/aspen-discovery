import React from "react";
import { Center, Heading, Text, HStack, Icon, Pressable, Button, IconButton, Spacer } from 'native-base';
import {MaterialIcons, MaterialCommunityIcons} from "@expo/vector-icons";

const AppHeader = ({ options, route, back, navigation }) => {
	return (
		<HStack bg="primary.500" space={5} justifyContent="space-around" alignItems="center" pt={12} pb={1}>
			{back ? (
			<IconButton
				onPress={() => navigation.goBack()}
				size={8}
				variant="ghost"
				_icon={{
					as: MaterialIcons,
					name: "chevron-left"
				}}
			/>) : <Text></Text>}
			<Center width="200" >
			{options.title ? (<Heading size="sm" isTruncated maxW="200">{options.title}</Heading>) : null}
			</Center>
				<IconButton
				onPress={() => navigation.openDrawer()}
				size={8}
				variant="ghost"
				_icon={{
					as: MaterialCommunityIcons,
					name: "account"
				}}
			/>
		</HStack>
	)
}

export default AppHeader;
