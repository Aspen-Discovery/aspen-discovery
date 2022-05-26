import React, {Component, useRef} from "react";
import {Platform} from "react-native";
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
	Toast,
	Pressable,
	VStack,
	HStack
} from "native-base";
import * as SecureStore from 'expo-secure-store';
import {create} from 'apisauce';
import {Ionicons, MaterialIcons} from "@expo/vector-icons";
import _ from "lodash";
import Constants from "expo-constants";
import * as Updates from "expo-updates";

// custom components and helper files
import {translate} from "../../translations/translations";
import {AuthContext} from "../../components/navigation";
import {getHeaders, problemCodeMap} from "../../util/apiAuth";
import {popToast} from "../../components/loadError";
import {GLOBALS} from "../../util/globals";

export default class Login extends Component {

	// set default values for the login information in the constructor
	constructor(props) {
		super(props);
		this.state = {
			isLoading: true,
			libraryData: [],
			query: "",
			fetchError: null,
			isFetching: false,
			fetchAll: true,
			listen: null,
			error: false,
			isBeta: false,
			locationNum: -1
		};

		// create arrays to store Greenhouse data from
		this.arrayHolder = [];
		this.filteredLibraries = [];

		// check for beta release channel
		if (Updates.releaseChannel === 'beta') {
			this.setState({isBeta: true});
		} else if (Updates.releaseChannel === 'dev') {
			this.setState({isBeta: true})
		}
	}

	// handles the mount information, setting session variables, etc
	componentDidMount = async () => {
		// store the values into the state
		this.setState({
			isLoading: false,
			isFetching: true,
		});

		// fetch global variables set in App.js
		await setGlobalVariables();

		await this.makeGreenhouseRequest();

		if(Constants.manifest.slug === "aspen-lida") {
			// fetch greenhouse data to populate libraries for community app
			await this.makeFullGreenhouseRequest();
		}

	};

	// handles the opening or closing of the showLibraries() modal
	handleModal = () => {
		this.setState({
			modalOpened: !this.state.modalOpened,
		});
	};

	// fetch the list of libraries based on distance and initial population of showLibraries modal
	makeGreenhouseRequest = async () => {
		// set state to fetching to display spinner
		this.setState({isFetching: true});
		let method;
		let baseApiUrl;
		if(Constants.manifest.slug === "aspen-lida") { method = "getLibraries"; } else { method = "getLibrary"; }
		if(Constants.manifest.slug === "aspen-lida") { baseApiUrl = Constants.manifest.extra.greenhouse; } else { baseApiUrl = Constants.manifest.extra.apiUrl; }

		let latitude = 0;
		let longitude = 0;
		try {
			latitude = await SecureStore.getItemAsync("latitude");
			longitude = await SecureStore.getItemAsync("longitude");
		} catch(e) {
			console.log(e);
		}
		const api = create({
			baseURL: baseApiUrl + '/API',
			timeout: 100000,
			headers: getHeaders(),
		});
		const response = await api.get('/GreenhouseAPI?method=' + method, {
			latitude: latitude,
			longitude: longitude,
			release_channel: Updates.releaseChannel
		});
		//console.log(response);
		if (response.ok) {
			let res = response.data;
			if(Constants.manifest.slug === "aspen-lida") {
				this.filteredLibraries = [];
				this.setState({
					libraryData: res.libraries,
					isFetching: false,
					value: "",
				});

				this.filteredLibraries = _.uniqBy(res.library, v => [v.locationId, v.libraryId].join());
			} else {
				this.filteredLibraries = [];
				this.setState({
					locationNum: res.count,
					libraryData: res.library,
					isFetching: false,
					value: "",
				});

				this.filteredLibraries = _.uniqBy(res.library, v => [v.locationId, v.name].join());
			}
		} else {
			this.setState({error: true});
			console.log(response);
			const problem = problemCodeMap(response.problem);
			popToast(problem.title, problem.message, "warning");
		}
		console.log("Greenhouse request completed.");
	};

	// fetch the entire list of available libraries to search from showLibraries modal search box
	makeFullGreenhouseRequest = async () => {
		// set state to fetching to display spinner
		this.setState({isFetching: true});
		const api = create({
			baseURL: Constants.manifest.extra.greenhouse + '/API',
			timeout: 10000,
			headers: getHeaders(),
		});
		const response = await api.get('/GreenhouseAPI?method=getLibraries', {
			release_channel: Updates.releaseChannel
		});
		if(response.ok) {
			let results = response.data;
			this.arrayHolder = [];
			this.setState({
				fullData: results.libraries,
				isFetching: false,
			});
			this.arrayHolder = _.uniqBy(results.libraries, v => [v.librarySystem, v.name].join());
		} else {
			this.setState({error: true});
			console.log(response);
		}
		console.log("Full greenhouse request completed.");
	};

	/**
    // showLibraries() function
    // Renders the list of libraries in a modal
    // When a library is picked it stores information from the Greenhouse API response used to validate login
	 **/
	showLibraries = () => {
		let uniqueLibraries = [];
		let showSelectLibrary = true;
		if(Constants.manifest.slug === "aspen-lida") {
			uniqueLibraries = _.uniqBy(this.state.libraryData, v => [v.librarySystem, v.name].join());
		} else {
			uniqueLibraries = _.values(this.state.libraryData);
			uniqueLibraries = _.uniqBy(uniqueLibraries, v => [v.libraryId, v.name].join());
			if(this.state.locationNum <= 1) {
				showSelectLibrary = false;
				//console.log("showLibraries:");
				//console.log(uniqueLibraries[0]);
				this.setLibraryBranch(uniqueLibraries[0]);
			}
		}
		return (
			<>
				<Modal isOpen={this.state.modalOpened} onClose={this.handleModal} size="xl">
					<Modal.Content bg="white" _dark={{ bg: "coolGray.800" }}>
						<Modal.CloseButton/>
						<Modal.Header>{translate('login.find_your_library')}</Modal.Header>
						<Modal.Body>
							<FlatList
								data={uniqueLibraries}
								refreshing={this.state.isFetching}
								renderItem={({item}) => this.renderListItem(item)}
								keyExtractor={(item) => item.siteId}
								ListHeaderComponent={this.renderListHeader}
								extraData={this.state}
							/>
						</Modal.Body>
					</Modal.Content>
				</Modal>

				{showSelectLibrary ?
					<Button colorScheme="primary" m={5} onPress={this.handleModal} size={{base: "md", lg: "lg"}}
					        startIcon={<Icon as={MaterialIcons} name="place" size={5}/>}>
						{this.state.libraryName ? this.state.libraryName : translate('login.select_your_library')}
					</Button>
					: null}
			</>
		);
	};

	renderListItem = (item) => {
		let isCommunity = true;
		if(Constants.manifest.slug !== "aspen-lida") { isCommunity = false; }
		return (
			<Pressable borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" onPress={() => this.setLibraryBranch(item)} pl="4" pr="5" py="2">
				<HStack space={3} alignItems="center">
					<Avatar source={{ uri: item.favicon }} borderRadius={3} size="xs" alt={item.name} bg="white"  _dark={{ bg: "coolGray.800" }} />
					<VStack>
						<Text bold fontSize={{base: "sm", lg: "md"}}>{item.name}</Text>
						{isCommunity ? <Text fontSize={{base: "xs", lg: "sm"}}>{item.librarySystem}</Text> : null }
					</VStack>
				</HStack>
			</Pressable>
		)
	}

	// FlatList: Renders the search box for filtering
	renderListHeader = () => {
		return (
			<Box pb={3}>
				<Input
					variant="filled"
					size="lg"
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
	};

	// showLibraries: handles storing the states based on selected library to use later on in validation
	setLibraryBranch = async (item) => {
		this.setState({
			libraryName: item.name,
			libraryUrl: item.baseUrl,
			solrScope: item.solrScope,
			libraryId: item.libraryId,
			locationId: item.locationId,
			modalOpened: false,
			favicon: item.favicon,
			logo: item.logo,
			patronsLibrary: item,
		});
	};

	/**
    // end of showLibraries() setup
	 **/
	// render the Login screen
	render() {
		const isBeta = this.state.isBeta;
		const slug = Constants.manifest.slug;
		const logo = Constants.manifest.extra.loginLogo;

		let isCommunity = true;
		if(Constants.manifest.slug !== "aspen-lida") { isCommunity = false; }

		//console.log(this.state);

		// TODO: Get library logo, fallback on LiDA
		return (
			<Box flex={1} alignItems="center" justifyContent="center" safeArea={5}>
				<Image source={{ uri: logo }} rounded={25} size={{base: "xl", lg: "2xl"}}
				       alt={translate('app.name')} />

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
							patronsLibrary={this.state.patronsLibrary}
						/>
						: null}

					{isCommunity ?
						<Button
							onPress={this.makeGreenhouseRequest}
							mt={8}
							size={{base: "xs", lg: "md"}}
							variant="ghost"
							colorScheme="secondary"
							startIcon={<Icon as={Ionicons} name="navigate-circle-outline" size={5}/>}
						>
							{translate('login.reset_geolocation')}
						</Button>
					: null }
					<Center>{isBeta ? <Badge rounded={5}
					                         mt={5}>{translate('app.beta')}</Badge> : null}</Center>
					<Center><Text mt={5} fontSize={{base: "xs", lg: "sm"}} color="coolGray.600">v{Constants.manifest.version} b[{Constants.nativeAppVersion}] p[{GLOBALS.appPatch}]</Text></Center>
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

	const [loading, setLoading] = React.useState(false);

	// securely set and store key:value pairs
	const [valueUser, onChangeValueUser] = React.useState('');
	const [valueSecret, onChangeValueSecret] = React.useState('');

	// show:hide data from password field
	const [show, setShow] = React.useState(false)
	const handleClick = () => setShow(!show)

	// make ref to move the user to next input field
	const passwordRef = useRef();
	const { signIn } = React.useContext(AuthContext);
	const libraryUrl = props.libraryUrl;
	const patronsLibrary = props.patronsLibrary;

	return (
		<>
			<FormControl>
				<FormControl.Label
					_text={{
						fontSize: "sm",
						fontWeight: 600,
					}}
				>
					{translate('login.username')}
				</FormControl.Label>
				<Input
					autoCapitalize="none"
					size="xl"
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
						fontSize: "sm",
						fontWeight: 600,
					}}
				>
					{translate('login.password')}
				</FormControl.Label>
				<Input
					variant="filled"
					size="xl"
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
					size={{base: "md", lg: "lg"}}
					color="#30373b"
					isLoading={loading}
					isLoadingText="Logging in..."
					onPress={() => {
						setLoading(true);
						signIn({ valueUser, valueSecret, libraryUrl, patronsLibrary});
						setTimeout(
							function () {
								setLoading(false);
							}.bind(this), 1500
						);
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
