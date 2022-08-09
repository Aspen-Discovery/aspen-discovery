import React, {Component} from "react";
import {Center, VStack, Spinner, Heading} from "native-base";
import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from "expo-constants";
import _ from "lodash";
import {create} from 'apisauce';
import {userContext} from "../../context/user";
import {createAuthTokens, getHeaders, postData} from "../../util/apiAuth";
import {GLOBALS} from "../../util/globals";
import {getBrowseCategories} from "../../util/loadLibrary";
import {getPatronBrowseCategories} from "../../util/loadPatron";

class LoadingScreen extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			userToken: null,
			user: [],
			library: [],
			location: [],
			browseCategories: [],
		};
		this.context = {
			user: [],
			library: [],
			location: [],
			browseCategories: [],
		}
	}

	componentDidMount = async () => {
		let userToken;
		let libraryUrl;
		let libraryId;
		let librarySolrScope;
		let locationId;
		let libName;
		try {
			userToken = await AsyncStorage.getItem('@userToken');
			//userProfile = await AsyncStorage.getItem('@patronProfile');
			this.setState({
				userToken: userToken,
			})
		} catch (e) {
			console.log(e);
		}

		if(userToken) {
			try {
				libraryUrl = await AsyncStorage.getItem('@pathUrl');
				libName = await AsyncStorage.getItem('@libName');
				libraryId = await AsyncStorage.getItem('@libraryId');
				librarySolrScope = await AsyncStorage.getItem('@solrScope');
				locationId = await AsyncStorage.getItem('@locationId');
			} catch (e) {
				console.log(e);
			}

			if (libraryUrl) {
				//console.log("Connecting to " + libName + " using " + libraryUrl);
				let postBody = await postData();
				const api = create({
					baseURL: libraryUrl + '/API',
					timeout: GLOBALS.timeoutAverage,
					headers: getHeaders(true),
					auth: createAuthTokens()
				});

				//const patronProfile = await AsyncStorage.getItem('@patronProfile');
				if (_.isEmpty(this.state.user)) {
					//console.log("fetching getPatronProfile...");
					const response = await api.post('/UserAPI?method=getPatronProfile&linkedUsers=true', postBody);
					if (response.ok) {
						let data = [];
						if (response.data.result.profile) {
							data = response.data.result.profile;
							this.setState({user: data});
							this.context.user = data;
							await AsyncStorage.setItem('@patronProfile', JSON.stringify(this.state.user));
							console.log("patron loaded into context");
						}
					}
				} //end user check

				if(libraryId) {
					const api = create({
						baseURL: libraryUrl + '/API',
						timeout: GLOBALS.timeoutAverage,
						headers: getHeaders(),
						auth: createAuthTokens()
					});

					if(_.isEmpty(this.state.library)) {
						const response = await api.get('/SystemAPI?method=getLibraryInfo', {id: libraryId});
						if(response.ok) {
							let data = [];
							if(response.data.result.library) {
								data = response.data.result.library;
								this.setState({library: data});
								this.context.library = data;
								await AsyncStorage.setItem('@libraryInfo', JSON.stringify(this.state.library));
								console.log("library loaded into context");
							}
						}
					}
				} //end library check

				if(locationId && librarySolrScope) {
					const api = create({
						baseURL: libraryUrl + '/API',
						timeout: GLOBALS.timeoutAverage,
						headers: getHeaders(),
						auth: createAuthTokens()
					});

					//const locationProfile = await AsyncStorage.getItem('@locationInfo');
					if(_.isEmpty(this.state.location)) {
						//console.log("fetching getLocationInfo...");
						const response = await api.get('/SystemAPI?method=getLocationInfo', {id: locationId, library: librarySolrScope, version: Constants.manifest.version});
						if(response.ok) {
							let data = [];
							if(response.data.result.location) {
								data = response.data.result.location;
								this.setState({location: data});
								this.context.location = data;
								await AsyncStorage.setItem('@locationInfo', JSON.stringify(data));
								console.log("location loaded into context");
							}
						}
					}
				}// end location check

				let discoveryVersion;
				if (this.state.library.discoveryVersion) {
					let version = this.state.library.discoveryVersion;
					version = version.split(" ");
					discoveryVersion = version[0];
				} else {
					discoveryVersion = "22.06.00";
				} // end discovery version check


				if (discoveryVersion >= "22.07.00") {
					await getBrowseCategories(libraryUrl, discoveryVersion, 5).then(response => {
						this.setState({
							browseCategories: response,
						});
						this.context.browseCategories = response;
						console.log("browse categories loaded into context");
					})
				} else if(discoveryVersion >= "22.05.00") {
					await getBrowseCategories(libraryUrl, discoveryVersion).then(response => {
						this.setState({
							browseCategories: response,
						});
						this.context.browseCategories = response;
						console.log("browse categories loaded into context");
					})
				} else {
					const user = this.state;
					await getPatronBrowseCategories(libraryUrl, user.id).then(response => {
						this.setState({
							browseCategories: response,
						});
						this.context.browseCategories = response;
						console.log("browse categories loaded into context");
					})
				}


			} //end if libraryUrl

			this.setState({
				isLoading: false,
			})
		}

		if (!this.state.isLoading) {
			this.props.navigation.navigate('Tabs');
		}
	}

	checkContext = () => {
		this.setState({
			isLoading: false,
		})
	}

	static contextType = userContext;

	render() {
		const user = this.state.user;
		const location = this.state.location;
		const library = this.state.library;
		const browseCategories = this.state.browseCategories;

		if(_.isEmpty(user) || _.isEmpty(location) || _.isEmpty(library) || _.isEmpty(browseCategories)) {
			return (
				<Center flex={1} px="3">
					<VStack space={5} alignItems="center">
						<Spinner size="lg" />
						<Heading color="primary.500" fontSize="md">
							Dusting the shelves...
						</Heading>
					</VStack>
				</Center>
			)
		} else {
			this.props.navigation.navigate('Tabs');
		}

		return null;
	}
}

export default LoadingScreen;
