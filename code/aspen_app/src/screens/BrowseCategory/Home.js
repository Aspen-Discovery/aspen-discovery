import React, {Component, PureComponent} from "react";
import {Box, Button, Icon, Pressable, ScrollView, Container, HStack, Text, Badge, Center} from "native-base";
import {MaterialIcons} from "@expo/vector-icons";
import CachedImage from 'expo-cached-image'
import AsyncStorage from '@react-native-async-storage/async-storage';

// custom components and helper files
import BrowseCategory from './BrowseCategory';
import {getBrowseCategories, getPickupLocations} from '../../util/loadLibrary';
import {dismissBrowseCategory} from "../../util/accountActions";
import {loadingSpinner} from "../../components/loadingSpinner";
import {loadError} from "../../components/loadError";
import {userContext} from "../../context/user";
import {
	getCheckedOutItems,
	getHolds,
	getILSMessages,
	getLists,
	getPatronBrowseCategories,
	getProfile
} from "../../util/loadPatron";
import DisplayBrowseCategory from "./Category";

export default class BrowseCategoryHome extends PureComponent {
	static contextType = userContext;
	constructor(props, context) {
		super(props, context);
		this.state = {
			data: [],
			page: 1,
			isLoading: true,
			isLoadingMore: false,
			hasError: false,
			error: null,
			refreshing: false,
			filtering: false,
			categories: [],
			browseCategories: [],
			categoriesLoaded: false,
			prevLaunch: 0,
			showButtons: false,
			loadAllCategories: false,
		};
		//getBrowseCategories();
	}

	loadBrowseCategories = async (libraryUrl, patronId) => {
		await getPatronBrowseCategories(libraryUrl, patronId).then(response => {
			this.setState({
				browseCategories: response,
				categoriesLoaded: true,
				isLoading: false,
			})
			this.loadPatronItems(libraryUrl);
		})
	}

	loadPatronItems = async (libraryUrl) => {
		await getCheckedOutItems(libraryUrl);
		await getHolds(libraryUrl);
		await getILSMessages(libraryUrl);
		await getLists(libraryUrl);
	}

	componentDidMount = async () => {
		if(this.context.library.discoveryVersion) {
			let version = this.context.library.discoveryVersion;
			version = version.split(" ");
			this.setState({
				discoveryVersion: version[0],
			})
		} else {
			this.setState({
				discoveryVersion: "22.06.00",
			})
		}

		this.setState({
			categoriesLoaded: false,
			isLoading: false,
		})


/*		let libraryUrl = "";
		try {
			libraryUrl = await AsyncStorage.getItem('@pathUrl');
		} catch (e) {
			console.log(e);
		}

		let patronId = "";
		try {
			let patron = await AsyncStorage.getItem('@patronProfile');
			patron = JSON.parse(patron);
			if(!_.isNull(patron)) {
				patronId = patron.id;
			}
		} catch(e) {
			console.log(e);
		}

		sleep(5000);

		if (patronId) {
			await getPatronBrowseCategories(libraryUrl, patronId).then(response => {
				this.setState({
					browseCategories: response,
					categoriesLoaded: true,
					isLoading: false,
				})
			})
		}*/

		const buttonDelay = setTimeout(() => {
			this.setState({
				showButtons: true,
			})
		}, 0);


		//sleep(5000);

		let libraryUrl = "";
		try {
			libraryUrl = await AsyncStorage.getItem('@pathUrl');
		} catch (e) {
			console.log(e);
		}

		if (libraryUrl) {
			await getCheckedOutItems(libraryUrl);
			await getHolds(libraryUrl);
			await getPickupLocations(libraryUrl);
			await getLists(libraryUrl);
			await getILSMessages(libraryUrl);
			//await getPatronBrowseCategories(libraryUrl);
		}

/*		this.interval = setInterval(async () => {
			this.setState({checkingForUpdates: true})
			await getBrowseCategories(libraryUrl).then(response => {
				this.context.browseCategories = response;
				this.setState({
					checkingForUpdates: false,
				})
			})
		}, 10000)*/

		//console.log(this.state.discoveryVersion);
	}

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	onHideCategory = async (libraryUrl, categoryId, patronId) => {
		this.setState({isLoading: true });
		await dismissBrowseCategory(libraryUrl, categoryId, patronId, this.state.discoveryVersion).then(async res => {
			await getBrowseCategories(libraryUrl, this.state.discoveryVersion).then(response => {
				this.context.browseCategories = response;
				this.setState({
					isLoading: false,
				})
			})
		});
	};

	onRefreshCategories = async (libraryUrl) => {
		this.setState({isLoading: true});

		await getBrowseCategories(libraryUrl, this.state.discoveryVersion).then(response => {
			this.context.browseCategories = response;
			//console.log(response);
			this.setState({
				isLoading: false,
			})
		})
	}

	onLoadAllCategories = async (libraryUrl, patronId) => {
		this.setState({isLoading: true});

		await getBrowseCategories(libraryUrl, this.state.discoveryVersion).then(response => {
			this.context.browseCategories = response;
			//console.log(response);
			this.setState({
				isLoading: false,
				loadAllCategories: true,
			})
		})
	}

	handleRefreshProfile = async () => {
		await getProfile(true).then(response => {
			this.context.user = response;
		})
	}

	onPressItem = (item, libraryUrl, type, title, discoveryVersion) => {
		if(discoveryVersion >= "22.07.00") {
			if(type === "List") {
				this.props.navigation.navigate("SearchByList", {category: item, libraryUrl: libraryUrl, categoryLabel: title});
			} else if (type === "SavedSearch") {
				this.props.navigation.navigate("SearchBySavedSearch", {category: item, libraryUrl: libraryUrl, categoryLabel: title});
			} else {
				this.props.navigation.navigate("GroupedWorkScreen", {item: item, libraryUrl: libraryUrl});
			}
		} else {
			this.props.navigation.navigate("GroupedWorkScreen", {item: item, libraryUrl: libraryUrl})
		}
	};

	onLoadMore = (item) => {
		this.props.navigation.navigate("GroupedWorkScreen", {item});
	};

	onPressSettings = (libraryUrl, patronId) => {
		this.props.navigation.navigate("AccountScreenTab", {screen: "SettingsHomeScreen", params: {libraryUrl: libraryUrl, patronId: patronId}});
	};

	// for discovery older than 22.05
	_renderNativeItem = (data, libraryUrl) => {
		if(typeof libraryUrl !== "undefined") {
			try {
				//const Image = createImageProgress(ExpoFastImage);
				const imageUrl = libraryUrl + "/bookcover.php?id=" + data.key + "&size=large&type=grouped_work";
				return (
					<Pressable mr={1.5} onPress={() => this.onPressItem(data.key, libraryUrl)}
					           width={{base: 100, lg: 200}}
					           height={{base: 150, lg: 250}}>
						<CachedImage
							cacheKey={data.key}
							alt={data.title}
							source={{ uri:  `${imageUrl}` }}
							style={{width: '100%', height: '100%'}}
						/>
					</Pressable>
				);
			} catch (e) {
				console.log(e);
			}
		}
	};

	_renderHeader = (title, key, user, libraryUrl) => {
		return (
			<Box>
				<HStack space={3} alignItems="center" justifyContent="space-between" pb={2}>
					<Text maxWidth="80%" bold mb={1} fontSize={{base: "lg", lg: "2xl"}}>{title}</Text>
					<Button size="xs" colorScheme="trueGray" variant="ghost" onPress={() => this.hideCategory(libraryUrl, key, user)}
					        startIcon={<Icon as={MaterialIcons} name="close" size="xs" mr={-1.5}/>}>Hide</Button>
				</HStack>
			</Box>
		)
	};

	_renderRecords = (data, user, libraryUrl, discoveryVersion) => {
		const title = data.title_display;
		let type = "grouped_work";
		if(data.source) {
			type = data.source;
		}
		const imageUrl = libraryUrl + "/bookcover.php?id=" + data.id + "&size=medium&type=" + type.toLowerCase();

		let isNew = false;
		if(typeof data.isNew !== "undefined") {
			isNew = data.isNew;
		}

		return (
			<Pressable ml={1} mr={3} onPress={() => this.onPressItem(data.id, libraryUrl, type, title, discoveryVersion)}
			           width={{base: 100, lg: 200}}
			           height={{base: 125, lg: 250}}>
				{discoveryVersion >= "22.08.00" && isNew ? (<Container zIndex={1}><Badge colorScheme="warning" shadow={1} mb={-2} ml={-1} _text={{fontSize: 9}}>New!</Badge></Container>) : null}
				<CachedImage
					cacheKey={data.id}
					alt={title}
					source={{
						uri:  `${imageUrl}`,
						expiresIn: 86400,
					}}
					style={{width: '100%', height: '100%'}}
				/>
			</Pressable>
		);
	}

	_renderLoadMore = (categoryLabel, categoryKey, libraryUrl, categorySource, recordCount, discoveryVersion) => {
		const { navigation } = this.props;

		let searchBy = "SearchByCategory";
		if(categorySource === "List") {
			searchBy = "SearchByList";
		} else if (categorySource === "SavedSearch") {
			searchBy = "SearchBySavedSearch";
		} else {
			searchBy = "SearchByCategory";
		}
		if(recordCount >= 5 && discoveryVersion >= "22.07.00") {
			return (
				<Box alignItems="center">
					<Pressable width={{base: 100, lg: 200}}
					           height={{base: 150, lg: 250}}
					           onPress={
						           () => {
							           navigation.navigate(searchBy, {
								           categoryLabel: categoryLabel,
								           category: categoryKey,
								           libraryUrl: libraryUrl,
							           })}
					           }>
						<Box width={{base: 100, lg: 200}} height={{base: 150, lg: 250}} bg="secondary.200" borderRadius="4" p="5" alignItems="center" justifyContent="center">
							<Center><Text bold fontSize="md">Load More</Text></Center>
						</Box>
					</Pressable>

				</Box>
			)
		}
	}

	render() {
		const {isLoading, categoriesLoaded, loadAllCategories} = this.state;
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;
		const browseCategories = this.context.browseCategories;

		//console.log(browseCategories);

		let discoveryVersion;
		if(library.discoveryVersion) {
			let version = library.discoveryVersion;
			version = version.split(" ");
			discoveryVersion = version[0];
		} else {
			discoveryVersion = "22.06.00";
		}


		if (this.state.isLoading === true) {
			return (loadingSpinner());
		}

		if (this.state.hasError) {
			return (loadError(this.state.error));
		}

		if (typeof browseCategories === 'undefined') {
			//return (loadingSpinner());
			return (loadError("No categories found"));
		}

		if(discoveryVersion >= "22.05.00") {
			//console.log(browseCategories);
			//console.log(discoveryVersion + " is newer than or equal to 22.05.00");
			return (
				<ScrollView>
					<Box safeArea={5}>
						{browseCategories.map((category) => {
							return (
								<DisplayBrowseCategory
									categoryLabel={category.title}
									categoryKey={category.key}
									id={category.id}
									records={category.records}
									isHidden={category.isHidden}
									categorySource={category.source}
									renderRecords={this._renderRecords}
									header={this._renderHeader}
									hideCategory={this.onHideCategory}
									user={user}
									libraryUrl={library.baseUrl}
									loadMore={this._renderLoadMore}
									discoveryVersion={discoveryVersion}
								/>
							);
						})}
						<ButtonOptions libraryUrl={library.baseUrl} patronId={user.id} onPressSettings={this.onPressSettings} onRefreshCategories={this.onRefreshCategories} discoveryVersion={discoveryVersion} loadAll={loadAllCategories} onLoadAllCategories={this.onLoadAllCategories}/>
					</Box>
				</ScrollView>
			);
		} else {
			//console.log(discoveryVersion + " is older than 22.05.00");
			return (
				<ScrollView>
					<Box safeArea={5}>
						{browseCategories.map((category) => {
							return (
								<BrowseCategory
									isLoading={isLoading}
									categoryLabel={category.title}
									categoryKey={category.key}
									isHidden={category.isHidden}
									renderItem={this._renderNativeItem}
									loadMore={this.onLoadMore}
									hideCategory={this.onHideCategory}
									user={user}
									libraryUrl={library.baseUrl}
								/>
							);
						})}
						<ButtonOptions libraryUrl={library.baseUrl} patronId={user.id} onPressSettings={this.onPressSettings} onRefreshCategories={this.onRefreshCategories} />
					</Box>
				</ScrollView>
			);
		}

	}

}

const ButtonOptions = (props) => {
	const {onPressSettings, onRefreshCategories, libraryUrl, patronId, discoveryVersion, loadAll, onLoadAllCategories} = props;
	const [isLoading, setLoading] = React.useState(true);

	if(discoveryVersion >= "22.07.00") {
		if(loadAll) {
			return (
				<Box>
					<Button size="md" colorScheme="primary" onPress={() => {
						onPressSettings(libraryUrl, patronId)
					}} startIcon={<Icon as={MaterialIcons} name="settings" size="sm"/>}>Manage Categories</Button>
					<Button size="md" mt="3" colorScheme="primary" onPress={() => {
						onRefreshCategories(libraryUrl)
					}} startIcon={<Icon as={MaterialIcons} name="refresh" size="sm"/>}>Refresh Categories</Button>
				</Box>
			)
		} else {
			return (
				<Box>
					<Button size="md" colorScheme="primary" onPress={() => {
						onLoadAllCategories(libraryUrl, patronId)
					}} startIcon={<Icon as={MaterialIcons} name="schedule" size="sm"/>}>Load All Categories</Button>
				</Box>
			)
		}
	}

	return <Box>
		<Button size="md" colorScheme="primary" onPress={() => {
			onPressSettings(libraryUrl, patronId)
		}} startIcon={<Icon as={MaterialIcons} name="settings" size="sm"/>}>Manage Categories</Button>
		<Button size="md" mt="3" colorScheme="primary" onPress={() => {
			onRefreshCategories(libraryUrl)
		}} startIcon={<Icon as={MaterialIcons} name="refresh" size="sm"/>}>Refresh Categories</Button>
	</Box>
}

function sleep(milliseconds) {
	const date = Date.now();
	let currentDate = null;
	do {
		currentDate = Date.now();
	} while (currentDate - date < milliseconds);
}