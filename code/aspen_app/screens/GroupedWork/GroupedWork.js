import React, { Component, useState, setState, useRef, TouchableOpacity, useEffect } from "react";
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
	Heading,
	AlertDialog,
	Input,
	Checkbox,
} from "native-base";
import * as SecureStore from 'expo-secure-store';
import { ListItem, Rating } from "react-native-elements";
import { Ionicons, MaterialIcons } from "@expo/vector-icons";
import * as WebBrowser from 'expo-web-browser';
import Constants from 'expo-constants';
import ExpoFastImage from 'expo-fast-image';
import { create, CancelToken } from 'apisauce';
import _ from "lodash";

import StatusIndicator from "./StatusIndicator";

import { loadingSpinner } from "../../components/loadingSpinner";
import { loadError, renderAlert, PopAlert, AlertDialogComponent } from "../../components/loadError";

import { getGroupedWork } from "../../util/recordActions";
import { getPickupLocations } from "../../util/loadLibrary";
import { updateOverDriveEmail } from "../../util/accountActions";

export default class GroupedWork extends Component {
	static navigationOptions = { title: "Book Details" };
	constructor() {
		super();
		this.state = {
		    isLoading: true,
		    locations: [],
		    hasError: false,
		    error: null,
		    items: [],
		    data: [],
		    ratingData: null,
		    variations: null,
		    formats: null,
		    languages: null,
		    format: null,
		    language: null,
		    status: null,
		    alert: false,
		};
		this.locations = [];
	}

	authorSearch = (author) => {
		this.props.navigation.push("SearchResults", { searchTerm: author });
	};

	componentDidMount = async () => {
        await this._fetchItemData();
        await this._fetchLocations();

        this.setState({ patronId: global.patronId });
	};

	_fetchItemData = async () => {

	    this.setState({ isLoading: true });

        console.log("Searching for... " + this.props.navigation.state.params.item);

        await getGroupedWork(this.props.navigation.state.params.item).then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: "Connection to the library timed out.",
                    isLoading: false,
                });
            } else {
                try {
                    this.setState({
                        data: response,
                        ratingData: response.ratingData,
                        variations: response.variation,
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

	_fetchLocations = async () => {
        await getPickupLocations().then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: "Connection to the library timed out.",
                    isLoading: false,
                });
            } else {
                try {
                    this.setState({
                        locations: response,
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

	// shows the author information on the screen and allows the link to be clickable. hides it if there is no author.
	showAuthor = () => {
		if (this.state.data.author) {
			return (
				<Button
					pt={2}
					size={{ base: "xs", lg:"md" }}
					variant="link"
					_text={{
						color: "primary.500",
						fontWeight: "600",
					}}
					onPress={() => this.authorSearch(this.state.data.author)}
				>
					{this.state.data.author}
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

	showAlert = (response) => {
	    console.log(response);
        if(response.message) {
            this.setState({
                alert: true,
                alertTitle: response.title,
                alertMessage: response.message,
                alertAction: response.action,
                alertStatus: response.success,
            })

            if(response.action) {
                if(response.action.includes("Checkouts")) {
                    this.setState({
                        alertNavigateTo: "Account",
                    });
                } else if(response.action.includes("Holds")) {
                    this.setState({
                        alertNavigateTo: "Account",
                    });
                }
            }
        } else if (response.getPrompt == true) {
            this.setState({
                prompt: true,
                promptItemId: response.itemId,
                promptSource: response.source,
                promptPatronId: response.patronId,
                promptTitle: "OverDrive Hold Options",
            });
        }
    }

    hideAlert = () => {
        this.setState({ alert: false })
    }

    hidePrompt = () => {
        this.setState({ prompt: false })
    }

    cancelRef = () => {
        useEffect(() => {
            React.useRef();
        })
    }

    initialRef = () => {
        useEffect(() => {
            React.useRef();
        })
    }

    setEmail = (email) => {
        this.setState({ overdriveEmail: email });
    }

    setRememberPrompt = (remember) => {
        this.setState({ promptForOverdriveEmail: remember });
    }

	render() {
		if (this.state.isLoading) {
			return ( loadingSpinner() );
		}

        if (this.state.hasError) {
            return ( loadError(this.state.error, this._fetchResults) );
        }

        if (!this.state.isLoading && !this.state.data.title) {
            return ( loadError("We're unable to load data for this title.") );
        }

		return (
			<ScrollView>
				<Box h={{ base: 125, lg: 200}} w="100%" bgColor="muted.200" zIndex={-1} position="absolute" left={0} top={0}></Box>
				<Box flex={1} safeArea={5}>
					<Center mt={5}>
					    <Box w={{ base: 200, lg: 300}} h={{ base: 250, lg: 350}} shadow={3}>
					    <ExpoFastImage cacheKey={this.state.data.id} uri={this.state.data.cover} alt={this.state.data.title} resizeMode="contain" style={{ width: '100%', height: '100%', borderRadius: 4 }} />
						</Box>
						<Text fontSize={{ base: "lg", lg: "2xl" }} bold pt={5} alignText="center">
							{this.state.data.title} {this.state.data.subtitle}
						</Text>
						{this.showAuthor()}
						{this.state.ratingData.count > 0 ? <Rating imageSize={20} readonly count={this.state.ratingData.count} startingValue={this.state.ratingData.average} type='custom' tintColor="#F2F2F2" ratingBackgroundColor="#E5E5E5" style={{ paddingTop: 5}} /> : null }
                    </Center>
                    <Text fontSize={{ base: "xs", lg: "md" }} bold mt={3} mb={1}>Format:</Text>
                    {this.state.formats ? <Button.Group style={{flex: 1, flexWrap: 'wrap'}}>{this.formatOptions()}</Button.Group> : null }
                    <Text fontSize={{ base: "xs", lg: "md" }} bold mt={3} mb={1}>Language:</Text>
                    {this.state.languages ? <Button.Group colorScheme="tertiary">{this.languageOptions()}</Button.Group> : null }

                    {this.state.variations ? <StatusIndicator data={this.state.variations} format={this.state.format} language={this.state.language} patronId={this.state.patronId} locations={this.state.locations} showAlert={this.showAlert} /> : null}

					<Text mt={5} mb={5} fontSize={{ base: "md", lg: "lg" }} lineHeight={{ base: "22px", lg: "26px" }}>
						{this.state.data.description}
					</Text>
				</Box>
				    <Center>
                      <AlertDialog
                        leastDestructiveRef={this.cancelRef}
                        isOpen={this.state.alert}
                      >
                        <AlertDialog.Content>
                          <AlertDialog.Header fontSize="lg" fontWeight="bold">
                            {this.state.alertTitle}
                          </AlertDialog.Header>
                          <AlertDialog.Body>
                            {this.state.alertMessage}
                          </AlertDialog.Body>
                          <AlertDialog.Footer>
                          {this.state.alertAction ?
                          <Button onPress={() => this.props.navigation.navigate(this.state.alertNavigateTo)}>
                            {this.state.alertAction}
                          </Button>
                           : null}
                            <Button onPress={this.hideAlert} ml={3} variant="outline" colorScheme="primary">
                              OK
                            </Button>
                          </AlertDialog.Footer>
                        </AlertDialog.Content>
                      </AlertDialog>
                    </Center>
                    <Modal
                      isOpen={this.state.prompt}
                      onClose={this.hidePrompt}
                      initialFocusRef={this.initialRef}
                      avoidKeyboard
                      closeOnOverlayClick={false}
                    >
                    <Modal.Content>
                        <Modal.CloseButton />
                        <Modal.Header>{this.state.promptTitle}</Modal.Header>
                        <Modal.Body mt={4}>
                            <FormControl>
                                <Stack>
                                    <FormControl.Label>Enter an email to be notified when the title is ready for you.</FormControl.Label>
                                    <Input
                                        autoCapitalize="none"
                                        autoCorrect={false}
                                        id="overdriveEmail"
                                        onChangeText={text => this.setEmail(text)}
                                    />
                                    <Checkbox
                                        value="yes"
                                        my={2}
                                        id="promptForOverdriveEmail"
                                        onChange={isSelected => this.setRememberPrompt(isSelected)}
                                    >Remember these settings</Checkbox>
                                </Stack>
                            </FormControl>

                        </Modal.Body>
                        <Modal.Footer>
                        <Button.Group space={2} size="md">
                            <Button colorScheme="primary" variant="ghost" onPress={this.hidePrompt}>Close</Button>
                            <Button onPress={ async () => { await updateOverDriveEmail(this.state.promptItemId, this.state.promptSource, this.state.promptPatronId, this.state.overdriveEmail, this.state.promptForOverdriveEmail).then(response => { this.showAlert(response) }) }}>Place Hold</Button>
                        </Button.Group>
                        </Modal.Footer>
                    </Modal.Content>
                    </Modal>
			</ScrollView>
		);
	}
}