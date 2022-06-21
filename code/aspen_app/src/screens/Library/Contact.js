import React, {Component} from "react";
import {Box, Button, Center, Icon, Heading, Text} from "native-base";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {MaterialIcons} from "@expo/vector-icons";
import * as SecureStore from 'expo-secure-store';
import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';
import {showLocation} from 'react-native-map-link';

// custom components and helper files
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
			location: [],
			library: [],
		};
	}

	loadLocation = async () => {
		const tmp = await AsyncStorage.getItem('@locationInfo');
		const profile = JSON.parse(tmp);
		this.setState({
			location: profile,
			isLoading: false,
		})
	}

	loadLibrary = async () => {
		const tmp = await AsyncStorage.getItem('@patronLibrary');
		const profile = JSON.parse(tmp);
		this.setState({
			library: profile,
			isLoading: false,
		})
	}

	componentDidMount = async () => {
		this.setState({
			userLatitude: await SecureStore.getItemAsync("latitude"),
			userLongitude: await SecureStore.getItemAsync("longitude"),
			isLoading: false,
		})

		await this.loadLibrary();
		await this.loadLocation();

		this.interval = setInterval(() => {
			this.loadLibrary()
			this.loadLocation();
		}, 5000)

		return () => clearInterval(this.interval)
	};

	componentWillUnmount() {
		clearInterval(this.interval);
	}

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
		if (url === '/') {
			WebBrowser.openBrowserAsync(global.libraryUrl)
		} else {
			WebBrowser.openBrowserAsync(url);
		}
	}

	getDirections = async () => {
		showLocation({
			latitude: this.state.location.latitude,
			longitude: this.state.location.longitude,
			sourceLatitude: this.state.userLatitude,
			sourceLongitude: this.state.userLongitude,
			googleForceLatLon: true,
		})
	};


	render() {

		const {location, library} = this.state;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
			<Box safeArea={5}>
				<Center>
					<Heading mb={2}>{library.name}</Heading>
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
							this.getDirections();
						}} startIcon={<Icon as={MaterialIcons} name="map"
						                    size="sm"/>}>{translate('library_contact.directions_button')}</Button> : null}
						{location.homeLink ? <Button onPress={() => {
							this.openWebsite(location.homeLink);
						}} startIcon={<Icon as={MaterialIcons} name="home"
						                    size="sm"/>}>{translate('library_contact.website_button')}</Button> : null}
					</Box>
				</Center>
			</Box>
		);
	}
}
