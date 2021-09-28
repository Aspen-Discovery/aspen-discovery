import React, { Component } from "react";
import { Linking, Platform } from "react-native";
import * as SecureStore from 'expo-secure-store';
import { Center, HStack, Spinner, Box, Button, Text, Heading, Icon, Divider } from "native-base";
import { Ionicons, MaterialIcons } from "@expo/vector-icons";

export default class News extends Component {
	static navigationOptions = { title: "Contact the Library" };

	constructor() {
		super();

		this.state = {
			isLoading: true,
		};
	}

	componentDidMount = async () => {

		const url = global.libraryUrl + "/app/aspenMoreDetails.php?id=" + global.locationId + "&library=" + global.solrScope + "&index=" + "&version=" + global.version;

		fetch(url)
			.then((res) => res.json())
			.then((res) => {
				this.setState({
					dataContactUs: res.contactUs,
					dataUniversal: res.universal,
					isLoading: false,
				});
			})
			.catch((error) => {
				console.log("get data error from:" + url + " error:" + error);
			});
	};

	dialCall = (number) => {
		let phoneNumber = "";
		if (Platform.OS === "android") {
			phoneNumber = `tel:${number}`;
		} else {
			phoneNumber = `telprompt:${number}`;
		}
		Linking.openURL(phoneNumber);
	};

	handleClick = (linkToFollow) => {
		Linking.canOpenURL(linkToFollow).then((supported) => {
			if (supported) {
				Linking.openURL(linkToFollow);
			}
		});
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
                <Box mb={8}>
                <Text fontSize="xl" bold>{this.state.dataContactUs.blurb}</Text>
                </Box>
                <Box mb={8}>
                <HStack space={2}>
                  <Icon as={MaterialIcons} name="schedule" size="sm" mt={0.3} mr={-1} />
                  <Text fontSize="lg" bold>
                    Today's Hours
                  </Text>
                </HStack>
                <Text>{this.state.dataUniversal.todayHours}</Text>
                </Box>
                <Divider mb={10} />
                <Box>
                <Button mb={3} onPress={() => { this.dialCall(this.state.dataUniversal.phone); }} startIcon={<Icon as={MaterialIcons} name="call" size="sm" />} >Call the Library</Button>
                <Button mb={3} onPress={() => { this.handleClick(this.state.dataContactUs.email); }} startIcon={<Icon as={MaterialIcons} name="email" size="sm" />} >Email a Librarian</Button>
                <Button onPress={() => { this.handleClick(this.state.dataContactUs.website); }} startIcon={<Icon as={MaterialIcons} name="home" size="sm" />} >Visit Our Website</Button>
                </Box>
            </Center>
            </Box>
		);
	}
}
