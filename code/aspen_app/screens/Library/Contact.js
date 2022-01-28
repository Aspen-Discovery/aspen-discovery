import React, {Component} from "react";
import {Box, Button, Center, Icon, Heading} from "native-base";
import {MaterialIcons} from "@expo/vector-icons";
import * as SecureStore from 'expo-secure-store';
import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';
import {showLocation} from 'react-native-map-link';

// custom components and helper files
import {translate} from '../../util/translations';
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
			showLibraryHours: 0,
			website: null,
			address: null,
			phone: null,
			email: null,
			description: null,
			libraryName: global.libraryName,
		};
	}

	componentDidMount = async () => {
		await getLocationInfo();
		await getLibraryInfo(global.libraryId, global.libraryUrl, global.timeoutAverage);

		this.setState({
			showLibraryHours: global.location_showInLocationsAndHoursList,
			hoursMessage: global.location_hoursMessage,
			website: global.location_homeLink,
			address: global.location_address,
			phone: global.location_phone,
			email: global.location_email,
			description: global.location_description,
			latitude: global.location_latitude,
			longitude: global.location_longitude,
			hours: JSON.parse(global.location_hours),
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
		if (url === '/') {
			WebBrowser.openBrowserAsync(global.libraryUrl)
		} else {
			WebBrowser.openBrowserAsync(url);
		}
	}

	getDirections = async () => {
		showLocation({
			latitude: this.state.latitude,
			longitude: this.state.longitude,
			sourceLatitude: this.state.userLatitude,
			sourceLongitude: this.state.userLongitude,
			googleForceLatLon: true,
		})
	};


	render() {
		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
			<Box safeArea={5}>
				<Center>
					<Heading mb={3}>{this.state.libraryName}</Heading>
					{this.state.showLibraryHours === 1 ?
						<HoursAndLocation hoursMessage={this.state.hoursMessage} hours={this.state.hours}
						                  description={this.state.description}/> : null}
					<Box>
						{this.state.phone ? <Button mb={3} onPress={() => {
							this.dialCall(this.state.phone);
						}} startIcon={<Icon as={MaterialIcons} name="call"
						                    size="sm"/>}>{translate('library_contact.call_button')}</Button> : null}
						{this.state.email ? <Button mb={3} onPress={() => {
							this.sendEmail(this.state.email);
						}} startIcon={<Icon as={MaterialIcons} name="email"
						                    size="sm"/>}>{translate('library_contact.email_button')}</Button> : null}
						{this.state.latitude !== 0 ? <Button mb={3} onPress={() => {
							this.getDirections();
						}} startIcon={<Icon as={MaterialIcons} name="map"
						                    size="sm"/>}>{translate('library_contact.directions_button')}</Button> : null}
						{this.state.website ? <Button onPress={() => {
							this.openWebsite(this.state.website);
						}} startIcon={<Icon as={MaterialIcons} name="home"
						                    size="sm"/>}>{translate('library_contact.website_button')}</Button> : null}
					</Box>
				</Center>
			</Box>
		);
	}
}
