import React, { Component, useState, useEffect } from "react";
import { View, ScrollView, RefreshControl } from "react-native";
import {
	NativeBaseProvider,
	Box,
	FormControl,
	Input,
	Button,
	Alert,
	HStack,
	Spinner,
	Center,
	Toast,
	Modal,
	Pressable,
	Badge,
	Divider,
	Icon,
	IconButton,
	Avatar,
	Heading,
	FlatList,
	Image,
	Text,
	KeyboardAvoidingView
} from "native-base";
import { SearchBar, ListItem } from "react-native-elements";
import { NavigationScreenProps, NavigationScreenComponent } from '@react-navigation/native';
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import * as Location from "expo-location";
import * as Updates from "expo-updates";
import Constants from "expo-constants";
import { Ionicons, MaterialIcons } from "@expo/vector-icons";
import filter from "lodash";

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
			isBeta: false
		};

		this.arrayHolder = [];
		this.filteredLibraries = [];

        let result = SecureStore.getItemAsync("releaseChannel");
		if(result == 'beta') {
            this.setState({ isBeta: true });
		}
	}

	// handles the mount information, setting session variables, etc
	componentDidMount = async () => {
		// store the values into the state
		this.setState({
			isLoading: false,
			isFetching: true,
		});

		await setGlobalVariables();

		await this.makeGreenhouseRequest();
		await this.makeFullGreenhouseRequest();

	};

	onRefresh() {
		this.setState({ isFetching: true }, function () {
			this.makeGreenhouseRequest();
		});
	}

	handleModal = () => {
		this.setState({
			modalOpened: !this.state.modalOpened,
		});
	};

	searchFilterFunction = (text) => {
		this.setState({ libraryData: [], isFetching: true });
		const updatedData = this.arrayHolder.filter((item) => {
			const itemData = `${item.name.toUpperCase()}, ${item.librarySystem.toUpperCase()}`;
			const textData = text.toUpperCase();
			return itemData.indexOf(textData) > -1;
		});
		this.setState({ libraryData: updatedData, query: text, isFetching: false });
		console.log(this.state.libraryData);
	};

	renderListHeader = () => {
		return (
			<Box pb={5}>
				<Input
					variant="underlined"
					autoCorrect={false}
					onChangeText={(text) => this.searchFilterFunction(text)}
					status="info"
					placeholder="Search"
					clearButtonMode="always"
					value={this.state.query}
				/>
			</Box>
		);
	};

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

	renderListEmpty = () => {
        if(this.state.error) {
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

	makeGreenhouseRequest = () => {

		this.setState({ isFetching: true });

		const url =
			"https://aspen-test.bywatersolutions.com/API/GreenhouseAPI?method=getLibraries&latitude=" + global.latitude + "&longitude=" + global.longitude + "&release_channel=" + global.releaseChannel;

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
						libraryData: [],
						libraryData: res.libraries,
						isFetching: false,
						value: "",
					});

					this.filteredLibraries = res.libraries;
				},
				(err) => {
				    this.setState({ error: true });
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
		console.log("Greenhouse request using geolocation made to " + url);
	};

	makeFullGreenhouseRequest = () => {
		const { releaseChannel } = this.state;
		const url = "https://aspen-test.bywatersolutions.com/API/GreenhouseAPI?method=getLibraries&release_channel=" + global.releaseChannel;

		this.setState({ isFetching: true });
		// fetch greenhouse data
		fetch(url, {
			header: {
				Accept: "application/json",
				"Content-Type": "application/json",
			},
			timeout: 15000,
		})
			.then((res) => res.json())
			.then(
				(res) => {
					this.arrayHolder = [];
					this.setState({
						fullData: [],
						fullData: res.libraries,
						isFetching: false,
					});
					this.arrayHolder = res.libraries;
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

		console.log("Greenhouse request made to " + url);
	};

	// shows the options for locations
	showLocationPulldown = () => {
		return (
			<>
				<Modal isOpen={this.state.modalOpened} onClose={this.handleModal} size="lg">
					<Modal.Content>
						<Modal.CloseButton />
						<Modal.Header>Find Your Library</Modal.Header>
						<Modal.Body>
								<FlatList
									data={this.state.libraryData}
									refreshing={this.state.isFetching}
									renderItem={({ item }) => (
										<ListItem bottomDivider onPress={() => this.onPressLibrary(item)}>
											<Avatar />
											<ListItem.Content>
												<Box _text={{ fontWeight: 600 }}>{item.name}</Box>
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
									keyExtractor={(item) => item.baseUrl.concat("|", item.solrScope, "|", item.libraryId, "|", item.locationId, "|", global.sessionId)}
									ItemSeparatorComponent={this.renderListSeparator}
									ListHeaderComponent={this.renderListHeader}
									extraData={this.state}
								/>
						</Modal.Body>
					</Modal.Content>
				</Modal>

				<Button colorScheme="primary" m={5} onPress={this.handleModal} size="md" startIcon={<Icon as={MaterialIcons} name="place" size={5} />}>
					{this.state.libraryName ? this.state.libraryName : "Select Your Library"}
				</Button>
			</>
		);
	};

	// handles the on press action and
	onPressLibrary = (item) => {
		this.setState({
			libraryName: item.name,
			libraryUrl: item.baseUrl,
			solrScope: item.solrScope,
			libraryId: item.libraryId,
			locationId: item.locationId,
			modalOpened: false,
		});
	};

	render() {
		const isBeta = this.state.isBeta;

		if (this.state.isLoading) {
			return (
				<Center flex={1}>
					<HStack>
						<Spinner accessibilityLabel="Loading..." />
					</HStack>
				</Center>
			);
		};

        return (
            <>
                <Box flex={1} alignItems="center" justifyContent="center" safeArea={5}>
                    <Image source={require("../assets/aspenLogo.png")} size="225px" borderRadius={25} alt="Aspen Discovery" />

                    {this.showLocationPulldown()}

                    <GetLoginForm
                        libraryName={this.state.libraryName}
                        locationId={this.state.locationId}
                        libraryId={this.state.libraryId}
                        libraryUrl={this.state.libraryUrl}
                        solrScope={this.state.solrScope}
                        sessionId={this.state.sessionId}
                        navigation={this.props.navigation}
                    />

                    <Button
                        onPress={this.makeGreenhouseRequest}
                        mt={8}
                        size="xs"
                        variant="subtle"
                        color="#30373b"
                        startIcon={<Icon as={Ionicons} name="navigate-circle-outline" size={5} />}
                    >
                        Find Nearby Libraries
                    </Button>
                    <Box>{isBeta ? <Badge>BETA</Badge> : null}</Box>
                </Box>
            </>
        );
	}
}


const GetLoginForm = (props) => {
  // securely set and store key:value pairs
  const [keyUser, onChangeKeyUser] = React.useState('');
  const [valueUser, onChangeValueUser] = React.useState('');
  const [keySecret, onChangeKeySecret] = React.useState('');
  const [valueSecret, onChangeValueSecret] = React.useState('');

  // show:hide data from password field
  const [show, setShow] = React.useState(false)
  const handleClick = () => setShow(!show)


  // store the token then navigate to the app's main screen
  async function storeToken() {

      // store login data for safe keeping
      SecureStore.setItemAsync("userKey", valueUser);
      SecureStore.setItemAsync("secretKey", valueSecret);
      SecureStore.deleteItemAsync("userToken");

      // make sure that login information is valid
      validateLogin();

  };

    async function getTokenValue(key) {
        let result = await SecureStore.getItemAsync(key);
        if (result) {
            return result
        } else {
            console.log("No keys found")
        }
    };

    async function validateLogin() {
        // build URL to validate
        const userKey = await SecureStore.getItemAsync("userKey");
        const secretKey = await SecureStore.getItemAsync("secretKey");
        const url = props.libraryUrl + "/app/aspenLogin.php?barcode=" + userKey + "&pin=" + secretKey + "&rand=" + props.sessionId;

        fetch(url)
            .then((res) => res.json())
            .then((res) => {
                // verify if the login credentials match the system
                if (res.ValidLogin === 'Yes') {
                    storeLoginToken(res.ValidLogin, res.Name);
                } else {
                    console.log("Unable to store data");
                    // login failed, remove bad data from storage
                    SecureStore.deleteItemAsync("userKey");
                    SecureStore.deleteItemAsync("secretKey");
                    Toast.show({
                        title: "Unable to login",
                        description: "Barcode and/or PIN is incorrect.",
                        status: "error",
                        duration: 8000,
                        isClosable: true,
                        accessibilityAnnouncement: "Unable to login. Barcode and/or PIN is incorrect."
                    });

                }
            })
            .catch((error) => {
                console.log("Unable to get data when trying: <" + url + ">");
                console.log("Error: " + error);
            });
    };

    async function storeLoginToken(thisLogin, thisName) {

        let patronName = thisName.substr(0, thisName.indexOf(" "));

        // if patronName is in all uppercase, force it to sentence-case
        if (patronName == patronName.toUpperCase()) {
            patronName = patronName.toLowerCase();
            patronName = patronName.split(' ');
            for (var i = 0; i < patronName.length; i++) {
                patronName[i] = patronName[i].charAt(0).toUpperCase() + patronName[i].slice(1);
                }
            patronName = patronName.join(' ');
        }

        await SecureStore.setItemAsync("patronName", patronName);
        await SecureStore.setItemAsync("library", props.libraryId);
        await SecureStore.setItemAsync("libraryName", props.libraryName);
        await SecureStore.setItemAsync("locationId", props.locationId);
        await SecureStore.setItemAsync("solrScope", props.solrScope);
        await SecureStore.setItemAsync("pathUrl", props.libraryUrl);
        await SecureStore.setItemAsync("userToken", thisLogin);

        try {
            const token = getTokenValue("userToken");
        } catch (e) {
            Toast.show({
                id: "loginError",
                title: "Unable to start session",
                description: "Something went wrong. Please try to login again.",
                status: "error",
                duration: 8000,
                isClosable: true,
                accessibilityAnnouncement: "Something went wrong. Please try to login again."
            });

            SecureStore.deleteItemAsync("userKey");
            SecureStore.deleteItemAsync("secretKey");
        }
        props.navigation.navigate("App");

    }



    return(
      <>
        <FormControl m={1}>
            <FormControl.Label
                _text={{
                    color: "muted.700",
                    fontSize: "sm",
                    fontWeight: 600,
                }}
            >
                Library Barcode
            </FormControl.Label>
            <Input
                autoCapitalize="none"
                autoCorrect={false}
                variant="filled"
                id="barcode"
                onChangeText={text => onChangeValueUser(text)}
                onSubmitEditing={() => this.passwordInput.focus()}
                returnKeyType="next"
                required
            />
        </FormControl>
        <FormControl m={1}>
            <FormControl.Label
                _text={{
                    color: "muted.700",
                    fontSize: "sm",
                    fontWeight: 600,
                }}
            >
                Password/PIN
            </FormControl.Label>
            <Input
                variant="filled"
                type={show ? "text" : "password"}
                InputRightElement={
                    <Icon
                        as={<Ionicons name={show ? "eye-outline" : "eye-off-outline"} />}
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

        <Button
        mt={3}
        size="md"
        color="#30373b"
        onPress={() => {
            if (props.libraryName) {
              storeToken();
            } else {
                Toast.show({
                    title: "No library selected",
                    description: "Please select a library",
                    isClosable: true,
                    duration: 8000,
                    status: "error",
                    accessibilityAnnouncement: "A library was not selected, please select one to login.",
                });
            }
        }}
        >
        Login
        </Button>
        </>
      );

}

async function setGlobalVariables() {
    try {
    global.releaseChannel = await SecureStore.getItemAsync("releaseChannel");
    global.latitude = await SecureStore.getItemAsync("latitude");
    global.longitude = await SecureStore.getItemAsync("longitude");
    } catch {
        console.log("Error setting global variables.");
    }
};


