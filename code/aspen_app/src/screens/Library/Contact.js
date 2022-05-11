import React, {Component} from "react";
import {Box, Button, Center, Icon, Heading, Text} from "native-base";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {MaterialIcons} from "@expo/vector-icons";
import * as SecureStore from 'expo-secure-store';
import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';
import {showLocation} from 'react-native-map-link';

// custom components and helper files
import {userContext} from "../../context/user";
import {translate} from '../../translations/translations';
import {loadingSpinner} from "../../components/loadingSpinner";
import {getLibraryInfo, getLocationInfo} from '../../util/loadLibrary';
import HoursAndLocation from "./HoursAndLocation";

export default class Contact extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			userLatitude: 0,
			userLongitude: 0
		};
	}

	componentDidMount = async () => {
		this.setState({
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

	openWebsite = async (url, libraryUrl) => {
		if (url === '/') {
			WebBrowser.openBrowserAsync(libraryUrl)
		} else {
			WebBrowser.openBrowserAsync(url);
		}
	}

	getDirections = async (locationLatitude, locationLongitude) => {
		showLocation({
			latitude: locationLatitude,
			longitude: locationLongitude,
			sourceLatitude: this.state.userLatitude,
			sourceLongitude: this.state.userLongitude,
			googleForceLatLon: true,
		})
	};

	static contextType = userContext;

	render() {

		const location = this.context.location;
		const library = this.context.library;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
			<Box safeArea={5}>
				<Center>
					<Heading>{library.displayName}</Heading>
					<Text mb={2}>{location.displayName}</Text>
					{location.showInLocationsAndHoursList === "1" ?
						<HoursAndLocation hoursMessage={location.hoursMessage} hours={location.hours}
						                  description={location.description}/> : null}
					<Box>
						{location.phone ? <Button mb={3} onPress={() => {
							this.dialCall(location.phone);
						}} startIcon={<Icon as={MaterialIcons} name="call"
						                    size="sm"/>}>{translate('library_contact.call_button')}</Button> : null}
						{location.email ? <Button mb={3} onPress={() => {
							this.sendEmail(location.email);
						}} startIcon={<Icon as={MaterialIcons} name="email"
						                    size="sm"/>}>{translate('library_contact.email_button')}</Button> : null}
						{location.latitude !== 0 ? <Button mb={3} onPress={() => {
							this.getDirections(location.latitude, location.longitude);
						}} startIcon={<Icon as={MaterialIcons} name="map"
						                    size="sm"/>}>{translate('library_contact.directions_button')}</Button> : null}
						{location.homeLink ? <Button onPress={() => {
							this.openWebsite(location.homeLink, library.baseUrl);
						}} startIcon={<Icon as={MaterialIcons} name="home"
						                    size="sm"/>}>{translate('library_contact.website_button')}</Button> : null}
					</Box>
				</Center>
			</Box>
		);
	}
}
