import React, {Component, useEffect} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {MaterialIcons} from "@expo/vector-icons";
import {
	AlertDialog,
	Box,
	Button,
	Center,
	Checkbox,
	FormControl,
	Input,
	Modal,
	ScrollView,
	Stack,
	Text,
	Icon
} from "native-base";
import {Rating} from "react-native-elements";
import ExpoFastImage from 'expo-fast-image';

// custom components and helper files
import {translate} from '../../translations/translations';
import Manifestation from "./Manifestation";
import {loadingSpinner} from "../../components/loadingSpinner";
import {loadError} from "../../components/loadError";
import {getGroupedWork, getItemDetails} from "../../util/recordActions";
import {getPickupLocations} from "../../util/loadLibrary";
import {updateOverDriveEmail} from "../../util/accountActions";
import {AddToListFromItem} from "./AddToList";
import {userContext} from "../../context/user";

export default class GroupedWork extends Component {
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
			itemDetails: null,
			status: null,
			alert: false,
			shouldReload: false,
		};
		this.locations = [];
	}

	authorSearch = (author, libraryUrl) => {
		this.props.navigation.push("SearchResults", {searchTerm: author, libraryUrl: libraryUrl});
	};

	openCheckouts = () => {
		this.props.navigation.navigate("CheckedOut");
	};

	componentDidMount = async () => {
		await this._fetchItemData();
		await this._fetchLocations();
	};

	_fetchItemData = async () => {

		this.setState({isLoading: true});
		const { navigation, route } = this.props;
		const givenItem = route.params?.item ?? 'null';
		const libraryUrl = route.params?.libraryUrl ?? 'null';

		await getGroupedWork(libraryUrl, givenItem).then(response => {
			if (response === "TIMEOUT_ERROR") {
				this.setState({
					hasError: true,
					error: translate('error.timeout'),
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
						groupedWorkId: response.id,
						groupedWorkTitle: response.title,
						hasError: false,
						error: null,
						isLoading: false,
					});
				} catch (error) {
					this.setState({
						hasError: true,
						error: translate('error.no_data'),
						isLoading: false,
					})
				}
			}
		})

		await this.loadItemDetails(libraryUrl);
	}

	_fetchLocations = async () => {

		const { navigation, route } = this.props;
		const libraryUrl = route.params?.libraryUrl ?? 'null';

		await getPickupLocations(libraryUrl).then(response => {
			this.setState({
				locations: response,
				hasError: false,
				error: null,
				isLoading: false,
			});
		})
	}

	// shows the author information on the screen and allows the link to be clickable. hides it if there is no author.
	showAuthor = (libraryUrl) => {
		if (this.state.data.author) {
			return (
				<Button
					pt={2}
					size={{base: "sm", lg: "md"}}
					variant="link"
					colorScheme="tertiary"
					_text={{
						fontWeight: "600",
					}}
					leftIcon={<Icon as={MaterialIcons} name="search" size="xs" mr="-1"/>}
					onPress={() => this.authorSearch(this.state.data.author, libraryUrl)}
				>
					{this.state.data.author}
				</Button>
			);
		}
	};

	formatOptions = () => {
		return this.state.formats.map((format, index) => {

			const btnVariant = this.state.format === format.format ? "solid" : "outline";

			return <Button variant={btnVariant} size={{base: "sm", lg: "lg"}} mb={1}
			               onPress={() => this.setState({format: format.format})}>{format.format}</Button>
		})
	}

	languageOptions = () => {

		return this.state.languages.map((language, index) => {

			const btnVariant = this.state.language === language.language ? "solid" : "outline";

			return <Button variant={btnVariant} size={{base: "sm", lg: "lg"}}
			               onPress={() => this.setState({language: language.language})}>{language.language}</Button>
		})
	}

	showAlert = (response) => {
		if (response.message) {
			this.setState({
				alert: true,
				alertTitle: response.title,
				alertMessage: response.message,
				alertAction: response.action,
				alertStatus: response.success,
			})

			if (response.action) {
				if (response.action.includes("Checkouts")) {
					this.setState({
						alertNavigateTo: "CheckedOut",
					});
				} else if (response.action.includes("Holds")) {
					this.setState({
						alertNavigateTo: "Holds",
					});
				}
			}
		} else if (response.getPrompt === true) {
			this.setState({
				prompt: true,
				promptItemId: response.itemId,
				promptSource: response.source,
				promptPatronId: response.patronId,
				promptTitle: translate('holds.hold_options'),
			});
		}
	}

	hideAlert = () => {
		this.setState({alert: false});
		setTimeout(
			function () {
				this._fetchItemData();
			}.bind(this), 1000
		);
	}

	hidePrompt = () => {
		this.setState({prompt: false});
		setTimeout(
			function () {
				this._fetchItemData();
			}.bind(this), 1000
		);
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

	loadItemDetails = async (libraryUrl) => {
		await getItemDetails(libraryUrl, this.state.groupedWorkId, this.state.format).then(response =>{
			this.setState({
				itemDetails: response,
			});
		})
	}

	setEmail = (email) => {
		this.setState({overdriveEmail: email});
	}

	setRememberPrompt = (remember) => {
		this.setState({promptForOverdriveEmail: remember});
	}

	static contextType = userContext;

	render() {
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		if (this.state.hasError) {
			return (loadError(this.state.error, this._fetchResults));
		}

		let ratingCount = 0;
		if(this.state.ratingData != null) {
			ratingCount = this.state.ratingData.count;
		}

		let ratingAverage = 0;
		if(this.state.ratingData != null) {
			ratingAverage = this.state.ratingData.average;
		}

		return (
			<ScrollView style={{ marginBottom: 80 }}>
				<Box h={{base: 125, lg: 200}} w="100%" bgColor="warmGray.200" _dark={{ bgColor: "coolGray.900" }} zIndex={-1} position="absolute" left={0}
				     top={0}></Box>
				<Box flex={1} safeArea={5}>
					<Center mt={5}>
						<Box w={{base: 200, lg: 300}} h={{base: 250, lg: 350}} shadow={3}>
							<ExpoFastImage cacheKey={this.state.data.id} uri={this.state.data.cover}
							               alt={this.state.data.title} resizeMode="contain"
							               style={{width: '100%', height: '100%', borderRadius: 4}}/>
						</Box>
						<Text fontSize={{base: "lg", lg: "2xl"}} bold pt={5} alignText="center">
							{this.state.data.title} {this.state.data.subtitle}
						</Text>
						{this.showAuthor(library.baseUrl)}
						{ratingCount > 0 ?
							<Rating imageSize={20} readonly count={ratingCount}
							        startingValue={ratingAverage} type='custom' tintColor="white"
							        ratingBackgroundColor="#E5E5E5" style={{paddingTop: 5}}/> : null}
					</Center>
					<Text fontSize={{base: "xs", lg: "md"}} bold mt={3} mb={1}>{translate('grouped_work.format')}</Text>
					{this.state.formats ?
						<Button.Group colorScheme="secondary" style={{flex: 1, flexWrap: 'wrap'}}>{this.formatOptions()}</Button.Group> : null}
					<Text fontSize={{base: "xs", lg: "md"}} bold mt={3}
					      mb={1}>{translate('grouped_work.language')}</Text>
					{this.state.languages ?
						<Button.Group colorScheme="secondary">{this.languageOptions()}</Button.Group> : null}

					{this.state.variations ? <Manifestation data={this.state.variations} format={this.state.format}
					                                          language={this.state.language}
					                                          patronId={user.id}
					                                          locations={this.state.locations}
					                                          showAlert={this.showAlert}
					                                          itemDetails={this.state.itemDetails}
					                                          groupedWorkId={this.state.groupedWorkId}
					                                          groupedWorkTitle={this.state.groupedWorkTitle}
					                                          user={user}
					                                          library={library}
					                                          openCheckouts={this.openCheckouts}/> : null}

					<Text mt={5} mb={5} fontSize={{base: "md", lg: "lg"}} lineHeight={{base: "22px", lg: "26px"}}>
						{this.state.data.description}
					</Text>
					<AddToListFromItem user={user} item={this.state.groupedWorkId} libraryUrl={library.baseUrl} />
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
									<Button onPress={() => {
										this.setState({alert: false})
										this.props.navigation.navigate("AccountScreenTab", {
											screen: this.state.alertNavigateTo,
											params: {libraryUrl: library.baseUrl}
										})
									}}>
										{this.state.alertAction}
									</Button>
									: null}
								<Button onPress={this.hideAlert} ml={3} variant="outline" colorScheme="primary">
									{translate('general.button_ok')}
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
						<Modal.CloseButton/>
						<Modal.Header>{this.state.promptTitle}</Modal.Header>
						<Modal.Body mt={4}>
							<FormControl>
								<Stack>
									<FormControl.Label>{translate('overdrive.email_field')}</FormControl.Label>
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
									>{translate('user_profile.remember_settings')}</Checkbox>
								</Stack>
							</FormControl>

						</Modal.Body>
						<Modal.Footer>
							<Button.Group space={2} size="md">
								<Button colorScheme="primary" variant="ghost"
								        onPress={this.hidePrompt}>{translate('general.close_window')}</Button>
								<Button onPress={async () => {
									await updateOverDriveEmail(this.state.promptItemId, this.state.promptSource, this.state.promptPatronId, this.state.overdriveEmail, this.state.promptForOverdriveEmail, library.baseUrl).then(response => {
										this.showAlert(response);
									})
								}}>{translate('holds.place_hold')}</Button>
							</Button.Group>
						</Modal.Footer>
					</Modal.Content>
				</Modal>
			</ScrollView>
		);
	}
}