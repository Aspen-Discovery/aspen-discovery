import React, {Component, useRef} from "react";
import {Platform, View} from "react-native";
import {
	Avatar,
	Badge,
	Box,
	Button,
	Center,
	FlatList,
	FormControl,
	Heading,
	Icon,
	Image,
	Input,
	KeyboardAvoidingView,
	Modal,
	Text,
	Toast
} from "native-base";
import {ListItem} from "react-native-elements";
import * as SecureStore from 'expo-secure-store';
import {Ionicons, MaterialIcons} from "@expo/vector-icons";
import _ from "lodash";
import {create} from 'apisauce';

// custom components and helper files
import {translate} from "../util/translations";
import {loadingSpinner} from "../components/loadingSpinner";
import {createAuthTokens, getHeaders} from "../util/apiAuth";
import {popAlert, popToast} from "../components/loadError";

export default class Login extends Component {
	// set default values for the login information in the constructor
	constructor(props) {
		super(props);
		this._bootstrapAsync();
		this.state = {
			isLoading: true,
			libraryData: [],
			query: "",
			fetchError: null,
			isFetching: false,
			fetchAll: true,
			listen: null,
			error: false,
			isBeta: false
		};

		// create arrays to store Greenhouse data from
		this.arrayHolder = [];
		this.filteredLibraries = [];

		// check for beta release channel
		if (global.releaseChannel == 'beta') {
			this.setState({isBeta: true});
		}
	}

	// Fetch the token from storage then navigate to our appropriate place
	_bootstrapAsync = async () => {
		const userToken = await SecureStore.getItemAsync("userToken");

		// This will switch to the App screen or Auth screen and this loading
		// screen will be unmounted and thrown away.
		this.props.navigation.navigate(userToken ? 'Loading' : 'Auth');
	};

	// handles the mount information, setting session variables, etc
	componentDidMount = async () => {
		// store the values into the state
		this.setState({
			isLoading: false,
			isFetching: true,
		});

		// fetch global variables set in App.js
		await setGlobalVariables();

		// fetch Greenhouse data to populate libraries
		await this.makeGreenhouseRequest();
		await this.makeFullGreenhouseRequest();

	};

	// handles the opening or closing of the showLibraries() modal
	handleModal = () => {
		this.setState({
			modalOpened: !this.state.modalOpened,
		});
	};

	// fetch the list of libraries based on distance and initial population of showLibraries modal
	makeGreenhouseRequest = () => {

		// build url to Greenhouse
		const url =
			"https://aspen-test.bywatersolutions.com/API/GreenhouseAPI?method=getLibraries&latitude=" + global.latitude + "&longitude=" + global.longitude + "&release_channel=" + global.releaseChannel;

		// set state to fetching to display spinner
		this.setState({isFetching: true});

		// fetch greenhouse data
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
					this.filteredLibraries = [];
					this.setState({
						libraryData: res.libraries,
						isFetching: false,
						value: "",
					});

					this.filteredLibraries = _.uniqBy(res.libraries, v => [v.librarySystem, v.name].join());
				},
				(err) => {
					this.setState({error: true});
					Toast.show({
						title: "Unable to connect",
						description: "There was an error fetching the libraries. Please try again.",
						isClosable: true,
						duration: 8000,
						status: "error",
						accessibilityAnnouncement: "There was an error fetching the libraries. Please try again.",
					});
				}
			);
		console.log("Greenhouse request using geolocation made to " + url);
	};

	// fetch the entire list of available libraries to search from showLibraries modal search box
	makeFullGreenhouseRequest = () => {
		// build url to Greenhouse
		const url = "https://aspen-test.bywatersolutions.com/API/GreenhouseAPI?method=getLibraries&release_channel=" + global.releaseChannel;

		// set state to fetching to display spinner
		this.setState({isFetching: true});

		// fetch greenhouse data
		fetch(url, {
			header: {
				Accept: "application/json",
				"Content-Type": "application/json",
			},
			timeout: 10000,
		})
			.then((res) => res.json())
			.then(
				(res) => {
					this.arrayHolder = [];
					this.setState({
						fullData: res.libraries,
						isFetching: false,
					});
					this.arrayHolder = _.uniqBy(res.libraries, v => [v.librarySystem, v.name].join());
				},
				(err) => {
					console.warn("Its borked! Aspen was unable to connect to the Greenhouse. Attempted connecting to <" + url + ">");
					console.warn("Error: ", err);
					Toast.show({
						title: "Unable to connect",
						description: "There was an error fetching the libraries. Please try again.",
						isClosable: true,
						duration: 8000,
						status: "error",
						accessibilityAnnouncement: "There was an error fetching the libraries. Please try again.",
					});
				}
			);
	};

	/**
    // showLibraries() function
    // Renders the list of libraries in a modal
    // When a library is picked it stores information from the Greenhouse API response used to validate login
	 **/
	showLibraries = () => {
		const uniqueLibraries = _.uniqBy(this.state.libraryData, v => [v.librarySystem, v.name].join());
		return (
			<>
				<Modal isOpen={this.state.modalOpened} onClose={this.handleModal} size="lg">
					<Modal.Content>
						<Modal.CloseButton/>
						<Modal.Header>{translate('login.find_your_library')}</Modal.Header>
						<Modal.Body>
							<FlatList
								data={uniqueLibraries}
								refreshing={this.state.isFetching}
								renderItem={({item}) => (
									<ListItem bottomDivider onPress={() => this.onPressLibrary(item)}>
										<Avatar bg="muted.50" source={{uri: item.favicon}} size="36px"/>
										<ListItem.Content>
											<Box _text={{fontWeight: 600}}>{item.name}</Box>
											<Box
												_text={{
													color: "muted.500",
													fontSize: "sm",
													fontWeight: 400,
												}}
											>
												{item.librarySystem}
											</Box>
										</ListItem.Content>
									</ListItem>
								)}
								keyExtractor={(item) => item.siteId}
								ItemSeparatorComponent={this.renderListSeparator}
								ListHeaderComponent={this.renderListHeader}
								extraData={this.state}
							/>
						</Modal.Body>
					</Modal.Content>
				</Modal>

				<Button colorScheme="primary" m={5} onPress={this.handleModal} size="md"
				        startIcon={<Icon as={MaterialIcons} name="place" size={5}/>}>
					{this.state.libraryName ? this.state.libraryName : translate('login.select_your_library')}
				</Button>
			</>
		);
	};

	// FlatList: Renders the search box for filtering
	renderListHeader = () => {
		return (
			<Box pb={5}>
				<Input
					variant="underlined"
					autoCorrect={false}
					onChangeText={(text) => this.searchFilterFunction(text)}
					status="info"
					placeholder={translate('search.title')}
					clearButtonMode="always"
					value={this.state.query}
				/>
			</Box>
		);
	};

	// FlatList: ListItems separator
	renderListSeparator = () => {
		return (
			<View
				style={{
					height: 1,
					width: "86%",
					backgroundColor: "#CED0CE",
					marginLeft: "5%",
				}}
			/>
		);
	};

	// FlatList: Make sure something loads if nothing from Greenhouse is available
	renderListEmpty = () => {
		if (this.state.error) {
			return (
				<Center mt={5} mb={5}>
					<Text bold fontSize="lg">
						Error loading libraries. Please try again.
					</Text>
				</Center>
			)
		}
		return (
			<Center>
				<Heading>Unable to find nearby libraries</Heading>
				<Text bold>Try searching instead</Text>
			</Center>
		);
	};

	// showLibraries: handles searching the array returned from makeFullGreenhouseRequest
	searchFilterFunction = (text) => {
		this.setState({libraryData: [], isFetching: true});
		const updatedData = this.arrayHolder.filter((item) => {
			const itemData = `${item.name.toUpperCase()}, ${item.librarySystem.toUpperCase()}`;
			const textData = text.toUpperCase();
			return itemData.indexOf(textData) > -1;
		});
		this.setState({libraryData: updatedData, query: text, isFetching: false});
		console.log(this.state.libraryData);
	};

	// showLibraries: handles storing the states based on selected library to use later on in validation
	onPressLibrary = (item) => {
		this.setState({
			libraryName: item.name,
			libraryUrl: item.baseUrl,
			solrScope: item.solrScope,
			libraryId: item.libraryId,
			locationId: item.locationId,
			modalOpened: false,
			favicon: item.favicon,
			logo: item.logo,
		});
	};

	/**
    // end of showLibraries() setup
	 **/

	// render the Login screen
	render() {
		const isBeta = this.state.isBeta;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}


		return (
			<Box flex={1} alignItems="center" justifyContent="center" safeArea={5}>
				<Image source={require("../themes/default/lidaLogo.png")} rounded={25} size="216px"
				       alt={translate('app.name')}/>

				{this.showLibraries()}

				<KeyboardAvoidingView behavior={Platform.OS === "ios" ? "padding" : "padding"} style={{width: "100%"}}>
					{this.state.libraryName ?
						<GetLoginForm
							libraryName={this.state.libraryName}
							locationId={this.state.locationId}
							libraryId={this.state.libraryId}
							libraryUrl={this.state.libraryUrl}
							solrScope={this.state.solrScope}
							favicon={this.state.favicon}
							logo={this.state.logo}
							sessionId={this.state.sessionId}
							navigation={this.props.navigation}
						/>
						: null}

					<Button
						onPress={this.makeGreenhouseRequest}
						mt={8}
						size="xs"
						variant="subtle"
						colorScheme="light"
						startIcon={<Icon as={Ionicons} name="navigate-circle-outline" size={5}/>}
					>
						{translate('login.reset_geolocation')}
					</Button>
					<Center>{isBeta ? <Badge colorScheme="secondary" rounded={5}
					                         mt={5}>{translate('app.beta')}</Badge> : null}</Center>
					<Center><Text mt={5} fontSize="xxs" color="coolGray.600">v{global.version} [b{global.build}]</Text></Center>
				</KeyboardAvoidingView>
			</Box>
		);
	}
}

/**
// Create the form used for logging in
// Validates the login attempt
// If valid, saves variables as key/value pairs into the Secure Store
 **/
const GetLoginForm = (props) => {
	// securely set and store key:value pairs
	const [keyUser, onChangeKeyUser] = React.useState('');
	const [valueUser, onChangeValueUser] = React.useState('');
	const [keySecret, onChangeKeySecret] = React.useState('');
	const [valueSecret, onChangeValueSecret] = React.useState('');

	// show:hide data from password field
	const [show, setShow] = React.useState(false)
	const handleClick = () => setShow(!show)


	async function storeToken() {
		// store login data for safe keeping
		SecureStore.setItemAsync("userKey", valueUser);
		SecureStore.setItemAsync("secretKey", valueSecret);
		SecureStore.deleteItemAsync("userToken");

		// call function to validate login
		validateLogin();
	}

	// checks for valid tokens, can be used by passing the key used when saving token in Secure Store
	async function getTokenValue(key) {
		let result = await SecureStore.getItemAsync(key);
		if (result) {
			return result
		}
	}

	// tries to validate login
	async function validateLogin() {
		try {
			// fetch username and password from Secure Store to try and login
			try {
				var userKey = await SecureStore.getItemAsync("userKey");
				var secretKey = await SecureStore.getItemAsync("secretKey");
			} catch (error) {
				console.log(error);
			}

			var bodyFormData = null;
			var bodyFormData = new FormData();
			bodyFormData.append('username', userKey);
			bodyFormData.append('password', secretKey);

			// build URL to verify if the login credentials match the system
			const api = create({
				baseURL: props.libraryUrl + '/API',
				timeout: 5000,
				headers: getHeaders(true),
				auth: createAuthTokens()
			});
			const response = await api.post('/UserAPI?method=validateAccount', bodyFormData);
			if (response.ok) {
				const result = response.data.result.success;

				if (result != false) {
					if (result['id'] != null) {
						console.log("Valid user: " + result.firstname + " " + result.lastname);
						const key = "ValidLogin";
						const token = JSON.stringify(result.firstname + " " + result.lastname);
						storeLoginToken(key, token);
					} else {
						console.log("Invalid user. Unable to store data.");
						SecureStore.deleteItemAsync("userKey");
						SecureStore.deleteItemAsync("secretKey");
						popAlert(translate('login.unable_to_login'), translate('login.invalid_user'), "error");
					}
				} else {
					console.log("Invalid user. Unable to store data.");
					SecureStore.deleteItemAsync("userKey");
					SecureStore.deleteItemAsync("secretKey");
					popAlert(translate('login.unable_to_login'), translate('login.invalid_user'), "error");
				}

			} else {
				const result = response.problem;
				popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), "warning");
				console.log(result);
			}
		} catch (error) {
			popAlert(translate('login.unable_to_login'), translate('login.not_enough_data'), "error");
			console.log(error);
		}
	}

	// tries to store variables
	async function storeLoginToken(thisLogin, thisName) {

		// Parse to reverse stringify needed to store name from response
		let name = JSON.parse(thisName);
		// grab just the first name of the user's full name
		let patronName = name.substr(0, name.indexOf(" "));

		// if patronName is in all uppercase, force it to sentence-case
		if (patronName == patronName.toUpperCase()) {
			patronName = patronName.toLowerCase();
			patronName = patronName.split(' ');
			for (var i = 0; i < patronName.length; i++) {
				patronName[i] = patronName[i].charAt(0).toUpperCase() + patronName[i].slice(1);
			}
			patronName = patronName.join(' ');
		}

		// save variables in the Secure Store to access later on
		await SecureStore.setItemAsync("patronName", patronName);
		await SecureStore.setItemAsync("library", props.libraryId);
		await SecureStore.setItemAsync("libraryName", props.libraryName);
		await SecureStore.setItemAsync("locationId", props.locationId);
		await SecureStore.setItemAsync("solrScope", props.solrScope);
		await SecureStore.setItemAsync("pathUrl", props.libraryUrl);
		await SecureStore.setItemAsync("logo", props.logo);
		await SecureStore.setItemAsync("favicon", props.favicon);

		await SecureStore.setItemAsync("userToken", thisLogin);

		// to confirm the save was completed, try to access login token from the Secure Store
		try {
			const token = getTokenValue("userToken");

		} catch (e) {
			// if token was unable to be accessed delete the username and password just in case it was stored anyway
			SecureStore.deleteItemAsync("userKey");
			SecureStore.deleteItemAsync("secretKey");
			popToast(translate('error.unable_to_login'), translate('error.no_session_created'), "warning")
		}

		props.navigation.navigate("App");

	}

	// make ref to move the user to next input field
	const passwordRef = useRef();
	const loginRef = useRef();

	return (

		<>

			<FormControl>
				<FormControl.Label
					_text={{
						color: "muted.700",
						fontSize: "sm",
						fontWeight: 600,
					}}
				>
					{translate('login.username')}
				</FormControl.Label>
				<Input
					autoCapitalize="none"
					autoCorrect={false}
					variant="filled"
					id="barcode"
					onChangeText={text => onChangeValueUser(text)}
					returnKeyType="next"
					textContentType="username"
					required
					onSubmitEditing={() => {
						passwordRef.current.focus();
					}}
					blurOnSubmit={false}
				/>
			</FormControl>
			<FormControl mt={3}>
				<FormControl.Label
					_text={{
						color: "muted.700",
						fontSize: "sm",
						fontWeight: 600,
					}}
				>
					{translate('login.password')}
				</FormControl.Label>
				<Input
					variant="filled"
					type={show ? "text" : "password"}
					returnKeyType="next"
					textContentType="password"
					ref={passwordRef}
					InputRightElement={
						<Icon
							as={<Ionicons name={show ? "eye-outline" : "eye-off-outline"}/>}
							size="md"
							ml={1}
							mr={3}
							onPress={handleClick}
							roundedLeft={0}
							roundedRight="md"
						/>
					}
					onChangeText={text => onChangeValueSecret(text)}
					required
				/>
			</FormControl>

			<Center>
				<Button
					mt={3}
					size="md"
					color="#30373b"
					onPress={() => {
						storeToken()
					}}
				>
					{translate('general.login')}
				</Button>
			</Center>
		</>

	);

}

// fetch the user coordinates and release channel set in App.js when opening the app
async function setGlobalVariables() {
	try {
		global.releaseChannel = await SecureStore.getItemAsync("releaseChannel");
		global.latitude = await SecureStore.getItemAsync("latitude");
		global.longitude = await SecureStore.getItemAsync("longitude");
	} catch {
		console.log("Error setting global variables.");
	}
}

