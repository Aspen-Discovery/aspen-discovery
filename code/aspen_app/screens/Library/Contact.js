import React, { Component } from "react";
import { Center, HStack, Spinner, Box, Button, Text, Heading, Icon, Divider, FlatList } from "native-base";
import { Ionicons, MaterialIcons } from "@expo/vector-icons";
import { Platform } from "react-native";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';
import { showLocation } from 'react-native-map-link';
import moment from "moment";

import HoursAndLocation from "./HoursAndLocation";

export default class Contact extends Component {
	constructor() {
		super();

		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
		};
	}

	componentDidMount = async () => {
		this.setState({
		    libraryShowHours: await AsyncStorage.getItem('@libraryShowHours'),
		    hoursMessage: await AsyncStorage.getItem('@libraryHoursMessage'),
		    homeLink: await AsyncStorage.getItem('@libraryHomeLink'),
		    address: await AsyncStorage.getItem('@libraryAddress'),
		    phone: await AsyncStorage.getItem('@libraryPhone'),
		    email: await AsyncStorage.getItem('@libraryEmail'),
            latitude: await AsyncStorage.getItem("@libraryLatitude"),
            longitude: await AsyncStorage.getItem("@libraryLongitude"),
            hours: JSON.parse(await AsyncStorage.getItem("@libraryHours")),
            userLatitude: await SecureStore.getItemAsync("latitude"),
            userLongitude: await SecureStore.getItemAsync("longitude"),
		    isLoading: false,
		})


	};

	dialCall = (number) => {
		let phoneNumber = "";
		phoneNumber = `tel:${number}`;
		Linking.openURL(phoneNumber);
	};

	sendEmail = (email) => {
	    let emailAddress = "";
	    emailAddress = `mailto:${email}`;
	    Linking.openURL(emailAddress);
	};

	openWebsite = async (url) => {
	    if(url == '/') {
	        WebBrowser.openBrowserAsync(global.libraryUrl)
	    } else {
	    	WebBrowser.openBrowserAsync(url);
	    }
	}

	handleClick = (linkToFollow) => {
		Linking.canOpenURL(linkToFollow).then((supported) => {
			if (supported) {
				Linking.openURL(linkToFollow);
			}
		});
	};

	getDirections = async () => {
	    showLocation({
	        latitude: this.state.latitude,
	        longitude: this.state.longitude,
	        sourceLatitude: this.state.userLatitude,
	        sourceLongitude: this.state.userLongitude,
	        title: global.libraryName,
	    })
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
            <Box safeArea={5}>
            <Center>
                {this.state.showLibraryHours == 1 && <HoursAndLocation hoursMessage={this.state.hoursMessage} hours={this.state.hours} /> }
                <Box>
                    <Button mb={3} onPress={() => { this.dialCall(this.state.phone); }} startIcon={<Icon as={MaterialIcons} name="call" size="sm" />} >Call the Library</Button>
                    <Button mb={3} onPress={() => { this.sendEmail(this.state.email); }} startIcon={<Icon as={MaterialIcons} name="email" size="sm" />} >Email a Librarian</Button>
                    <Button mb={3} onPress={() => { this.getDirections(); }} startIcon={<Icon as={MaterialIcons} name="map" size="sm" />} >Get Directions</Button>
                    <Button onPress={() => { this.openWebsite(this.state.homeLink); }} startIcon={<Icon as={MaterialIcons} name="home" size="sm" />} >Visit Our Website</Button>
                </Box>
            </Center>
            </Box>
		);
	}
}
