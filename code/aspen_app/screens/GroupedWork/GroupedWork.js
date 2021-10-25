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
import { ListItem, Rating } from "react-native-elements";
import { Ionicons, MaterialIcons } from "@expo/vector-icons";
import * as WebBrowser from 'expo-web-browser';
import Constants from 'expo-constants';
import ExpoFastImage from 'expo-fast-image';
import { create, CancelToken } from 'apisauce';
import _ from "lodash";

import Manifestation from "./Manifestation";
import StatusIndicator from "./Item/StatusIndicator";

import Error from "../../components/Error.js";

export default class GroupedWork extends Component {
	static navigationOptions = { title: "Book Details" };
	constructor() {
		super();
		this.state = {
		    isLoading: true,
		    locations: [],
		    hasError: false,
		    error: null,
		    isExpanded: false,
		    items: [],
		    format: null,
		    language: null,
		    status: null,
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

        const groupedWorkId = await this.props.navigation.state.params.item;

        console.log("Searching for... " + groupedWorkId);

        await getGroupedWork(groupedWorkId).then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: "Connection to the library timed out.",
                    isLoading: false,
                });
            } else {
                console.log(response);
                try {
                    this.setState({
                        data: response,
                        formats: response.filterOn.format,
                        languages: response.filterOn.language,
                        format: response.filterOn.format[0].format,
                        language: response.filterOn.language[0].language,
                        hasError: false,
                        error: null,
                        isLoading: false,
                    });
                } catch (error) {
                    this.setState({
                        hasError: true,
                        error: "Unable to load filter options.",
                        isLoading: false,
                    })
                }
            }
        })
	}

	_fetchLocations = () => {
	const url = 'https://aspen-test.bywatersolutions.com/app/aspenPickUpLocations.php?library=m&barcode=' + global.userKey + '&pin=' + global.secretKey + '&sessionId=' + global.sessionId;
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
				<Button
					pt={2}
					size={{ base: "xs", lg:"md" }}
					variant="link"
					_text={{
						color: "primary.500",
						fontWeight: "600",
					}}
					onPress={() => this.authorSearch(author)}
				>
					{author}
				</Button>
			);
		}
	};

	formatOptions = () => {
	    return this.state.formats.map((format, index) => {

            if(this.state.format === format.format) {
                var btnVariant = "solid";
            } else {
                var btnVariant = "outline";
            }

            return <Button variant={btnVariant} size={{ base: "sm", lg: "lg" }} mb={1} onPress={() => this.setState({format: format.format})}>{format.format}</Button>
        })
	}

	languageOptions = () => {

	    return this.state.languages.map((language, index) => {

            if(this.state.language == language.language) {
                var btnVariant = "solid";
            } else {
                var btnVariant = "outline";
            }

            return <Button variant={btnVariant} size={{ base: "sm", lg: "lg" }} onPress={() => this.setState({language: language.language})}>{language.language}</Button>
        })
	}

	selectedItemStatus = (item) => {
	    return (
	        <StatusIndicator />
	    )
	}

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
                null
             )
        }

		const groupedWork = this.state.data;
		const title = this.state.data.title;
		const subtitle = this.state.data.subtitle;
		const author = this.state.data.author;
		const bookSummary = this.state.data.description;
		const variations = this.state.data.variation;
		const itemImage = this.state.data.cover;
		const groupedWorkId = this.state.data.id;

		const ratingAverage = this.state.data.ratingData.average;
		const ratingCount = this.state.data.ratingData.count;

		const language = this.state.language;


		return (
			<ScrollView>
				<Box h={{ base: 125, lg: 200}} w="100%" bgColor="muted.200" zIndex={-1} position="absolute" left={0} top={0}></Box>
				<Box flex={1} safeArea={5}>
					<Center mt={5}>
					    <Box w={{ base: 200, lg: 300}} h={{ base: 250, lg: 350}} shadow={3}>
					    <ExpoFastImage cacheKey={groupedWorkId} uri={itemImage} alt={title} resizeMode="contain" style={{ width: '100%', height: '100%', borderRadius: 4 }} />
						</Box>
						<Text fontSize={{ base: "lg", lg: "2xl" }} bold pt={5} alignText="center">
							{title} {subtitle}
						</Text>
						{this.showAuthor(author)}
						{ratingCount != 0 && <Rating imageSize={20} readonly count={ratingCount} startingValue={ratingAverage} type='custom' tintColor="#F2F2F2" ratingBackgroundColor="#E5E5E5" />}
                    </Center>
                    <Text fontSize={{ base: "xs", lg: "md" }} bold mt={3} mb={1}>Format:</Text>
                    <Button.Group style={{flex: 1, flexWrap: 'wrap'}}>{this.formatOptions()}</Button.Group>
                    <Text fontSize={{ base: "xs", lg: "md" }} bold mt={3} mb={1}>Language:</Text>
                    <Button.Group colorScheme="tertiary">{this.languageOptions()}</Button.Group>

                    <StatusIndicator data={variations} format={this.state.format} language={this.state.language} />

					<Text mt={5} mb={5} fontSize={{ base: "md", lg: "lg" }} lineHeight={{ base: "22px", lg: "26px" }}>
						{bookSummary}
					</Text>
				</Box>

			</ScrollView>
		);
	}
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

async function getGroupedWork(itemId) {

    const api = create({ baseURL: 'http://demo.localhost:8888/API', timeout: 10000 });
    const response = await api.get('/ItemAPI?method=getAppGroupedWork', { id: itemId });

    if(response.ok) {
        const fetchedData = response.data;
        return fetchedData;
    } else {
        const fetchedData = response.problem;
        return fetchedData;
    }
}