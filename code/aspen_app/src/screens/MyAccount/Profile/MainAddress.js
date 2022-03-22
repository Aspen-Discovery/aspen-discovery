import React, {useState} from "react";
import {Heading, Box, Text, FormControl, Input, Icon} from "native-base";
import {MaterialCommunityIcons} from "@expo/vector-icons";

// custom components and helper files

const Profile_MainAddress = (props) => {
	return (
		<Box py={5}>
			<Text bold>Address</Text>
			<Text>{props.address}</Text>
			<Text bold mt={2}>City</Text>
			<Text>{props.city}</Text>
			<Text bold mt={2}>State</Text>
			<Text>{props.state}</Text>
			<Text bold mt={2}>Zip Code</Text>
			<Text>{props.zipCode}</Text>
		</Box>
	)
}

export default Profile_MainAddress;