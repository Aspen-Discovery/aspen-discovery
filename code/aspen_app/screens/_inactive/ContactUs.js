import React, { Component } from "react";
import { Center, HStack, Spinner, Box, Button, Text, Heading, Icon, Divider, FlatList } from "native-base";
import { Ionicons, MaterialIcons } from "@expo/vector-icons";
import { Platform } from "react-native";
import * as SecureStore from 'expo-secure-store';
import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';
import { showLocation } from 'react-native-map-link';
import moment from "moment";

export default class News extends Component {
	static navigationOptions = { title: "Contact the Library" };

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
		    hoursMessage: await SecureStore.getItemAsync("libraryHoursMessage"),
		    homeLink: await SecureStore.getItemAsync("libraryHomeLink"),
		    address: await SecureStore.getItemAsync("libraryAddress"),
		    phone: await SecureStore.getItemAsync("libraryPhone"),
            latitude: await SecureStore.getItemAsync("libraryLatitude"),
            longitude: await SecureStore.getItemAsync("libraryLongitude"),
            hours: JSON.parse(await SecureStore.getItemAsync("libraryHours")),
		    isLoading: false,
		})

		console.log(this.state.hours[0].open);

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
	        sourceLatitude: await SecureStore.getItemAsync("latitude"),
	        sourceLongitude: await SecureStore.getItemAsync("longitude"),
	        title: global.libraryName,
	    })
	};

    renderNativeItem = (item) => {

    const openTime = moment(item.open, "HH:mm").format("h:mm A");
    const closingTime = moment(item.close, "HH:mm").format("h:mm A");

    if(item.isClosed) {
        var hours = "Closed";
    } else {
        var hours = openTime + " - " + closingTime;
    }

        return (
        <Box>
        <HStack space={3} alignItems="flex-start">
            <Text bold fontSize="sm">{item.dayName}</Text>
            <Text fontSize="sm">{hours}</Text>
        </HStack>
        {item.notes &&
            <Text bold>Note: <Text>{item.notes}</Text></Text>
        }
        </Box>
        );
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
                <Box mb={4}>
                <HStack space={3}>
                  <Icon as={MaterialIcons} name="schedule" size="sm" mt={0.3} mr={-1} />
                  <Text fontSize="lg" bold>
                    Today's Hours
                  </Text>
                </HStack>
                <Text>{this.state.hoursMessage}</Text>
                </Box>
                <FlatList
                    data={this.state.hours}
                    renderItem={({ item }) => this.renderNativeItem(item)}
                    keyExtractor={(item) => item.day}
                    mb={3}
                />
                <Divider mb={10} />
                <Box>
                <Button mb={3} onPress={() => { this.dialCall(this.state.phone); }} startIcon={<Icon as={MaterialIcons} name="call" size="sm" />} >Call the Library</Button>
                <Button mb={3} onPress={() => { this.sendEmail("a fake email"); }} startIcon={<Icon as={MaterialIcons} name="email" size="sm" />} >Email a Librarian</Button>
                <Button mb={3} onPress={() => { this.getDirections(); }} startIcon={<Icon as={MaterialIcons} name="map" size="sm" />} >Get Directions</Button>
                <Button onPress={() => { this.openWebsite(this.state.homeLink); }} startIcon={<Icon as={MaterialIcons} name="home" size="sm" />} >Visit Our Website</Button>
                </Box>
            </Center>
            </Box>
		);
	}
}
