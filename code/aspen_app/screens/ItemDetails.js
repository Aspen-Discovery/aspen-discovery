import React, { Component, useState, setState } from "react";
import { FlatList, Dimensions, Animated } from "react-native";
import {
	Center,
	NativeBaseProvider,
	Box,
	Spinner,
	HStack,
	FormControl,
	Button,
	Modal,
	Link,
	Select,
	CheckIcon,
	Text,
	Image,
	Divider,
	Flex,
	Toast,
	Avatar,
	ScrollView
} from "native-base";
import * as SecureStore from 'expo-secure-store';
import { ListItem } from "react-native-elements";

export default class ItemDetails extends Component {
	static navigationOptions = { title: "Book Details" };
	constructor() {
		super();
		this.state = { isLoading: true, locations: [] };
		this.locations = [];
	}

	authorSearch = (author) => {
		this.props.navigation.push("SearchResults", { searchTerm: author });
	};

	componentDidMount = async () => {
		this.setState({
			isLoading: false,
		});

		const pickuplocationUrl =
			global.libraryUrl +
			"/app/aspenPickUpLocations.php?library=" +
			global.solrScope +
			"&barcode=" +
			global.userKey +
			"&pin=" +
			global.secretKey +
			"&sessionId=" +
			global.sessionId;

		fetch(pickuplocationUrl, {
			header: {
				Accept: "application/json",
				"Content-Type": "application/json",
			},
			timeout: 5000,
		})
			.then((res) => res.json())
			.then(
				(res) => {
					this.setState({
						locations: res.pickup,
					});
					this.locations = res.pickup;
				},
				(err) => {
					console.warn("Its borked! Unable to connect to the library. Attempted connecting to <" + pickuplocationUrl + ">");
					console.warn("Error: ", err);
				}
			);
	};

	// shows the author information on the screen and allows the link to be clickable. hides it if there is no author.
	showAuthor = (author) => {
		if (author) {
			return (
				<Link
					pb={2}
					_text={{
						color: "primary.500",
						fontWeight: "600",
					}}
					onPress={() => this.authorSearch(author)}
				>
					{author}
				</Link>
			);
		}
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

		const title = this.props.navigation.state.params.item.title;
		const author = this.props.navigation.state.params.item.author;
		const bookSummary = this.props.navigation.state.params.item.summary;
		const itemList = this.props.navigation.state.params.item.itemList;
		const itemImage = this.props.navigation.state.params.item.image;

		return (
			<ScrollView>
				<Box h={125} w="100%" bgColor="muted.200" zIndex={-1} position="absolute" left={0} top={0}></Box>
				<Box flex={1} safeArea={5}>
					<Center mt={5}>
						<Image source={{ uri: itemImage }} alt={title} w={135} h={200} borderRadius={4} fallbackSource={{ itemImage }} />
						<Text fontSize="xl" bold pt={5} pb={2}>
							{title}
						</Text>
						{this.showAuthor(author)}

						<PlaceHold
							title={title}
							formats={this.props.navigation.state.params.item.itemList}
							locations={this.state.locations}
						/>
					</Center>
					<Divider mt={5} mb={5} />
					<Text mb={5} fontSize="sm">
						{bookSummary}
					</Text>
				</Box>
			</ScrollView>
		);
	}
}

export const PlaceHold = (props) => {
	const [showModal, setShowModal] = useState(false);
	let [item, setItem] = React.useState("");
	let [location, setLocation] = React.useState("");

	return (
		<>
			<Button onPress={() => setShowModal(true)} colorScheme="secondary" size="md">
				Place a Hold
			</Button>
			<Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false}>
				<Modal.Content>
					<Modal.CloseButton />
					<Modal.Header>Place Hold on {props.title}</Modal.Header>
					<Modal.Body>
						<FormControl pb={3}>
							<FormControl.Label>Select a format</FormControl.Label>
							<Select
								size="sm"
								variant="filled"
								selected={item}
								accessibilityLabel="Select a format"
								_selectedItem={{
									bg: "tertiary.300",
									endIcon: <CheckIcon size={5} />,
								}}
								mt="1"
								onValueChange={(itemValue) => {
									setItem(itemValue);
								}}
							>
								{props.formats.map((item, index) => {
									return <Select.Item label={item.name} value={item.type} key={item.type} />;
								})}
							</Select>
						</FormControl>
						{item.includes('ils') &&
						<FormControl>
                            <FormControl.Label>I want to pick this up at</FormControl.Label>
                            <Select
                                size="sm"
                                variant="filled"
                                accessibilityLabel="Select a pickup location"
                                selected={location}
                                onValueChange={(itemValue) => {
                                    setLocation(itemValue);
                                }}
                                _selectedItem={{
                                    bg: "tertiary.300",
                                    endIcon: <CheckIcon size={5} />,
                                }}
                                mt="1"
                            >
                                {props.locations.map((item, index) => {
                                    return <Select.Item label={item.displayName} value={item.code} key={item.code} />;
                                })}
                            </Select>
                        </FormControl>
						}
					</Modal.Body>
					<Modal.Footer>
						<Button.Group>
							<Button onPress={() => setShowModal(false)} variant="subtle">Cancel</Button>
							<Button
								onPress={() => {
                                    if(item.includes('ils')){
                                        if (item && location ) {
                                            const url =
                                                global.libraryUrl +
                                                "/app/aspenPlaceHold.php?library=" +
                                                global.solrScope +
                                                "&barcode=" +
                                                global.userKey +
                                                "&pin=" +
                                                global.secretKey +
                                                "&item=" +
                                                item +
                                                "&location=" +
                                                location +
                                                "&sessionId=" +
                                                global.sessionId;
                                            placeTheHold(url, location);
                                        } else {
                                            Toast.show({
                                                title: "Unable to place hold",
                                                description: "A format and pickup location are required.",
                                                isClosable: true,
                                                duration: 8000,
                                                status: "error",
                                                accessibilityAnnouncement: "A format and pickup location are required.",
                                            });
                                        }
                                    } else {
                                        if (item) {
                                            const url =
                                                global.libraryUrl +
                                                "/app/aspenPlaceHold.php?library=" +
                                                global.solrScope +
                                                "&barcode=" +
                                                global.userKey +
                                                "&pin=" +
                                                global.secretKey +
                                                "&item=" +
                                                item +
                                                "&location=" +
                                                location +
                                                "&sessionId=" +
                                                global.sessionId;
                                            placeTheHold(url, location);
                                        } else {
                                            Toast.show({
                                                title: "Unable to place hold",
                                                description: "A format is required.",
                                                isClosable: true,
                                                duration: 8000,
                                                status: "error",
                                                accessibilityAnnouncement: "A format is required.",
                                            });
                                        }
                                    } setShowModal(false);
								}}
							>
								Place Hold
							</Button>
						</Button.Group>
					</Modal.Footer>
				</Modal.Content>
			</Modal>
		</>
	);
};

const placeTheHold = async (placeHoldUrl, location) => {
	fetch(placeHoldUrl)
		.then((res) => res.json())
		.then((res) => {
			let holdStatus = res.data.hold.ok;
			let holdMessage = res.data.hold.message;

			if (res.data.hold.ok == false) {
                Toast.show({
                    title: "Error",
                    description: res.data.hold.message,
                    isClosable: true,
                    duration: 8000,
                    status: "warning",
                    accessibilityAnnouncement: res.data.hold.message,
                });
			} else {
                Toast.show({
                    title: "Success",
                    description: res.data.hold.message,
                    isClosable: true,
                    duration: 8000,
                    status: "success",
                    accessibilityAnnouncement: res.data.hold.message,
                });
			}
		})
		.catch((error) => {
			console.log("Unable to fetch data from <" + placeHoldUrl + ">");
			console.log("Error: " + error);
            Toast.show({
                title: "Unable to connect to the library",
                description: "Your hold was not placed. Please try again.",
                isClosable: true,
                duration: 8000,
                status: "error",
                accessibilityAnnouncement: "Your hold was not placed. Please try again.",
            });
		});

	try {
		await SecureStore.setItemAsync("pickUpLocation", location);
		console.log("Pickup preferences saved.");
	} catch (error) {
		console.log("Unable to save pickup preferences.");
	}
};
