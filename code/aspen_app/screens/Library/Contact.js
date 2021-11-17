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

// custom components and helper files
import { translate } from '../../util/translations';
import { loadingSpinner } from "../../components/loadingSpinner";
import { getLocationInfo, getLibraryInfo } from '../../util/loadLibrary';
import HoursAndLocation from "./HoursAndLocation";

export default class Contact extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			showLibraryHours: 0,
			website: null,
			address: null,
			phone: null,
			email: null,
			description: null,
		};
	}

	componentDidMount = async () => {
	    await getLocationInfo();
	    await getLibraryInfo();

		try {
            this.setState({
                showLibraryHours: await AsyncStorage.getItem('@libraryShowHours'),
                hoursMessage: await AsyncStorage.getItem('@libraryHoursMessage'),
                website: await AsyncStorage.getItem('@libraryHomeLink'),
                address: await AsyncStorage.getItem('@libraryAddress'),
                phone: await AsyncStorage.getItem('@libraryPhone'),
                email: await AsyncStorage.getItem('@libraryEmail'),
                description: await AsyncStorage.getItem('@libraryDescription'),
                latitude: await AsyncStorage.getItem("@libraryLatitude"),
                longitude: await AsyncStorage.getItem("@libraryLongitude"),
                hours: JSON.parse(await AsyncStorage.getItem("@libraryHours")),
                userLatitude: await SecureStore.getItemAsync("latitude"),
                userLongitude: await SecureStore.getItemAsync("longitude"),
                isLoading: false,
            })

		} catch (error) {
		    console.log("Unable to load state data.")
		}


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
	        googleForceLatLon: true,
	    })
	};


	render() {
		if (this.state.isLoading) {
			return ( loadingSpinner() );
		}

		console.log("showLibraryHours: " + this.state.showLibraryHours);
		console.log("website: " + this.state.website);
		console.log("latitude: " + this.state.latitude);
		console.log("longitude: " + this.state.longitude);

		return (
            <Box safeArea={5}>
            <Center>
                {this.state.showLibraryHours == 1 ? <HoursAndLocation hoursMessage={this.state.hoursMessage} hours={this.state.hours} description={this.state.description} /> : null }
                <Box>
                    {this.state.phone ? <Button mb={3} onPress={() => { this.dialCall(this.state.phone); }} startIcon={<Icon as={MaterialIcons} name="call" size="sm" />} >{translate('library_contact.call_button')}</Button> : null }
                    {this.state.email ? <Button mb={3} onPress={() => { this.sendEmail(this.state.email); }} startIcon={<Icon as={MaterialIcons} name="email" size="sm" />} >{translate('library_contact.email_button')}</Button> : null }
                    {this.state.latitude != 0 ? <Button mb={3} onPress={() => { this.getDirections(); }} startIcon={<Icon as={MaterialIcons} name="map" size="sm" />} >{translate('library_contact.directions_button')}</Button> : null }
                    {this.state.website ? <Button onPress={() => { this.openWebsite(this.state.website); }} startIcon={<Icon as={MaterialIcons} name="home" size="sm" />} >{translate('library_contact.website_button')}</Button> : null }
                </Box>
            </Center>
            </Box>
		);
	}
}
