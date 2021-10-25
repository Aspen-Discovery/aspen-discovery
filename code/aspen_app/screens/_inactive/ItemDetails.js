import React, { Component, useState, setState } from "react";
import { FlatList, Dimensions, Animated, Linking, Platform, View } from "react-native";
import {
	Center,
	NativeBaseProvider,
	Box,
	Spinner,
	HStack,
	VStack,
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
	ScrollView,
	Icon,
	Badge,
	Stack,
	Heading
} from "native-base";
import * as SecureStore from 'expo-secure-store';
import { ListItem } from "react-native-elements";
import { Ionicons, MaterialIcons } from "@expo/vector-icons";
import * as WebBrowser from 'expo-web-browser';
import base64 from 'react-native-base64';
import Constants from 'expo-constants';
import ExpoFastImage from 'expo-fast-image';
import { create, CancelToken } from 'apisauce';

export default class ItemDetails extends Component {
	static navigationOptions = { title: "Book Details" };
	constructor() {
		super();
		this.state = {
		    isLoading: true,
		    locations: [],
		    hasError: false,
		    error: null,
		    isExpanded: false,
		};
		this.locations = [];
	}

	authorSearch = (author) => {
		this.props.navigation.push("SearchResults", { searchTerm: author });
	};

	componentDidMount = async () => {
        await this._fetchItemData();
        this._fetchLocations();
	};

	_fetchItemData = async () => {

	    this.setState({
	        isLoading: true,
	    });

        const itemId = await this.props.navigation.state.params.item;

        console.log("Searching for... " + itemId);

        await getBasicItemInfo(itemId).then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: "Connection to the library timed out.",
                    isLoading: false,
                });
            } else {
                this.setState({
                    data: response,
                    hasError: false,
                    error: null,
                    isLoading: false,
                });
            }
        })
	}

	_fetchLocations = () => {
	const url = 'https://aspen-test.bywatersolutions.com/app/aspenPickUpLocations.php?library=m&barcode=' + base64.decode(global.userKey) + '&pin=' + base64.decode(global.secretKey) + '&sessionId=' + global.sessionId;
        fetch(url, {
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
                        isLoading: false,
                    });
                    this.locations = res.pickup;
                },
                (err) => {
                    console.log("Unable to fetch data from: <" + url + "> in ItemDetails");
                    this.setState({
                        isLoading: false,
                        hasError: true,
                        error: "There was a problem loading data from the library."
                    })
                }
            );
	}

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
		} else if (this.state.hasError) {
             return(
                <Center flex={1}>
                 <HStack>
                      <Icon as={MaterialIcons} name="error" size="md" mt={.5} mr={1} color="error.500" />
                      <Heading color="error.500" mb={2}>Error</Heading>
                 </HStack>
                 <Text bold w="75%" textAlign="center">There was an error loading results from the library. Please try again.</Text>
                  <Button
                      mt={5}
                      colorScheme="primary"
                      onPress={() => this._fetchItemData()}
                      startIcon={<Icon as={MaterialIcons} name="refresh" size={5} />}
                  >
                      Reload
                  </Button>
                  <Text fontSize="xs" w="75%" mt={5} color="muted.500" textAlign="center">ERROR: {this.state.error}</Text>
                 </Center>
             )
        }

		const groupedWork = this.state.data;
		const title = this.state.data.title;
		const author = this.state.data.author;
		const bookSummary = this.state.data.description;
		const manifestations = this.state.data.manifestations;
		const itemImage = this.state.data.cover;
		const groupedWorkId = this.state.data.id;

		console.log(manifestations);

		return (
			<ScrollView>
				<Box h={125} w="100%" bgColor="muted.200" zIndex={-1} position="absolute" left={0} top={0}></Box>
				<Box flex={1} safeArea={5}>
					<Center mt={5}>
					    <ExpoFastImage cacheKey={groupedWorkId} uri={itemImage} alt={title} resizeMode="contain" style={{ width: 200, height: 250, borderRadius:4 }} />
						<Text fontSize="xl" bold pt={5} pb={2}>
							{title}
						</Text>
						{this.showAuthor(author)}
						{manifestations.map((manifestation, index) => {
                        return <Button.Group><Manifestation format={manifestation.format} records={manifestation.records} status={manifestation.status} action={manifestation.action} /></Button.Group>
                        })}
					</Center>
					<Text mt={5} mb={5} fontSize="md" lineHeight={22}>
						{bookSummary}
					</Text>
				</Box>
			</ScrollView>
		);
	}
}

export const Manifestation = (props) => {
    const { format, records, status, action } = props;
    const [expanded, setExpanded] = useState("");

    const allRecords = Object.entries(records);

    function renderRecord(items) {
        const entries = items["allRecords"][0];
        entries.map((record) => {
            return (
            null
            )
        })
    }

    return (

        <Button>{format}</Button>

    );


}

export const Record = (props) => {

    const { data, status, action, shelfLocation, callNumber, totalCopies, availableCopies, format, key, holdable } = props;

    console.log(status);

    if(format == "Book") {
           return (
               <ListItem key={key} bottomDivider style={{ width: (Dimensions.get('window').width), backgroundColor: "transparent" }}>
               <ListItem.Content>
               <VStack alignItems="flex-start">
                   <ItemStatus available={available} status={status} />
                   <CallNumber shelfLocation={shelfLocation} callNumber={callNumber} />
               </VStack>
               </ListItem.Content>

               </ListItem>
           )
    } else {
           return (
               <ListItem key={key} bottomDivider style={{ width: (Dimensions.get('window').width) }}>

               <ListItem.Content>
               <VStack alignItems="flex-start">
                  <ItemStatus available={available} />
                  <Text bold>eContent Source</Text>
               </VStack>
               </ListItem.Content>

               </ListItem>
           )
    }


}

export const ItemStatus = (props) => {
    const { available, status } = props;


    if (available == true) {
        var color = "success";
        var message = "Available";
    } else if (available == false) {
        var color = "danger"
        var message = "Checked out";
    }
    else {
        var color = "info"
        var message = "Status unknown";
    }

    return (
        <Badge colorScheme={color} variant="solid">{message}</Badge>
    );
}

export const CallNumber = (props) => {
    const { shelfLocation, callNumber } = props;

    return (
    <Stack>
        <Text bold>{shelfLocation}</Text>
        <Text fontSize="sm">{callNumber}</Text>
    </Stack>
    );
}

export const ItemAction = (props) => {

    const { showPlaceHold, showCheckout, available } = props;

    if(showPlaceHold == true) {
        var action = "Place Hold";
    } else {
        var action = "Checkout";
    }

    if(showPlaceHold == 1 && available == false) {
        var action = "Place Hold";
    } else if (showPlaceHold == 0 && available == true) {
        var action = "In-library Only";
    } else {
        var action = "Checkout";
    }

    return (
    <Stack>
        <Button size="sm" mb={1}>{action}</Button>
    </Stack>
    )
}

export const PlaceHold = (props) => {

    const locations = props.locations;

	const [showModal, setShowModal] = useState(false);
	let [item, setItem] = React.useState("");
	let [location, setLocation] = React.useState("");

	let formatCount = 0;
	let onlineOnly = 0;
	let actionAvailable = false;
	props.formats.map((item, index) => {
	    console.log(item.status);
        formatCount++;
        if(item.source != 'ils' && item.source != 'overdrive') {
            onlineOnly++;
        }
	});

	if(formatCount != onlineOnly) {
	    actionAvailable = true
	}

	if(formatCount == 0) {
	    actionAvailable = false
	}

    async function openAspen(recordUrl) {
        WebBrowser.openBrowserAsync(recordUrl);
    };

    async function onPressItem(item, location, action) {
        console.log(action);
        const thisItem = item.split(':');
        const itemSource = thisItem[0];
        const itemId = thisItem[1];
        if(action == 'hold') {
            placeHold(itemSource, itemId, location);
        } else if(action == 'checkout') {
            tryCheckout(itemSource, itemId, location);
        }
    };

    const recordUrl = global.libraryUrl + '/GroupedWork/' + props.groupedWorkId + '#main-content';

	return (
		<>
			{actionAvailable ? <Button onPress={() => setShowModal(true)} colorScheme="secondary" size="md">
				Place a Hold
			</Button> : <Button colorScheme="secondary" size="md" onPress={() => { openAspen(recordUrl); }} startIcon={<Icon as={MaterialIcons} name="launch" size="sm" />}>Open in Catalog</Button>}
			<Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false}>
				<Modal.Content>
					<Modal.CloseButton />
					<Modal.Header>Place Hold on <Text noOfLines={2} maxW={250} bold fontSize="xl">{props.title}</Text></Modal.Header>
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
                                    if(item.source == 'ils' || item.source == 'overdrive' ) {
                                        return <Select.Item label={item.name} value={item.id} key={item.id} />;
                                    } else {
                                        const label = item.name + ' (Not yet available)';
                                        return <Select.Item label={label} value={item.id} key={item.id} disabled/>;
                                    }
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
                                            let action = "hold";
                                            onPressItem(item, location, action);
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
                                            let action = "hold";
                                            let location = null;
                                            onPressItem(item, location, action);
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

const makeButton = (props) => {
    return (
        <Button onPress={() => setShowModal(true)} colorScheme="secondary" size="md">Place a Hold</Button>
    )
}

const placeHold = async (itemSource, itemId, location) => {
const url = global.libraryUrl + '/API/UserAPI?method=placeHold&username=' + base64.decode(global.userKey) + '&password=' + base64.decode(global.secretKey) + '&itemSource=' + itemSource + '&itemId=' + itemId + '&pickupBranch=' + location;
console.log(url);
	fetch(url)
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

async function getBasicItemInfo(itemId) {

    const api = create({ baseURL: 'http://demo.localhost:8888/API', timeout: 10000 });
    const response = await api.get('/ItemAPI?method=getAppBasicItemInfo', { id: itemId });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;
        return fetchedData;
    } else {
        const fetchedData = response.problem;
        return fetchedData;
    }
}
