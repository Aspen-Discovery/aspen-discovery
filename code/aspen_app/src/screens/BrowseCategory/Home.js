import React, {Component, PureComponent} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {Box, Button, Icon, Pressable, ScrollView, Image, HStack, Text, FlatList, Center} from "native-base";
import {MaterialIcons} from "@expo/vector-icons";
//import ExpoFastImage from 'expo-fast-image';
import CachedImage from 'expo-cached-image'
import _ from "lodash";

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
import {AuthContext} from "../../components/navigation";
import {isNull} from "lodash";
import {removeData} from "../../util/logout";
import DisplayBrowseCategory from "./Category";

export default class BrowseCategoryHome extends PureComponent {
	static contextType = userContext;
	constructor() {
		super();
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

		if (libraryUrl) {
			await getCheckedOutItems(libraryUrl);
			await getHolds(libraryUrl);
			await getPickupLocations(libraryUrl);
			await getLists(libraryUrl);
			await getILSMessages(libraryUrl);
			await getBrowseCategories(libraryUrl).then(response => {
				this.context.browseCategories = response;
			})
			//await getPatronBrowseCategories(libraryUrl);
		}

		return () => clearTimeout(buttonDelay);
	}

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	onHideCategory = async (libraryUrl, categoryId, patronId) => {
		console.log("Trying to hide category...");
		this.setState({isLoading: true });
		await dismissBrowseCategory(libraryUrl, categoryId, patronId).then(async res => {
			await getBrowseCategories(libraryUrl).then(response => {
				this.context.browseCategories = response;
				this.setState({
					isLoading: false,
				})
			})
		});
	};

	onRefreshCategories = async (libraryUrl, patronId) => {
		this.setState({isLoading: true});

		await getBrowseCategories(libraryUrl).then(response => {
			this.context.browseCategories = response;
			this.setState({
				isLoading: false,
			})
		})
	}

	handleRefreshProfile = async () => {
		await getProfile(true).then(response => {
			this.context.user = response;
		})
	}

	onPressItem = (item, libraryUrl) => {
		this.props.navigation.navigate("GroupedWorkScreen", {item: item, libraryUrl: libraryUrl});
	};

	onLoadMore = (item) => {
		this.props.navigation.navigate("GroupedWorkScreen", {item});
	};

	onPressSettings = (libraryUrl, patronId) => {
		this.props.navigation.navigate("AccountScreenTab", {screen: "SettingsHomeScreen", params: {libraryUrl: libraryUrl, patronId: patronId}});
	};

	_renderNativeItem = (data, libraryUrl) => {
		if(typeof libraryUrl !== "undefined") {
			try {
				//const Image = createImageProgress(ExpoFastImage);
				const imageUrl = libraryUrl + "/bookcover.php?id=" + data.key + "&size=medium&type=grouped_work";
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

	_renderRecords = (data, user, libraryUrl) => {
		const title = data.title_display;
		const imageUrl = libraryUrl + "/bookcover.php?id=" + data.id + "&size=small&type=grouped_work";
		return (
			<Pressable mr={1.5} onPress={() => this.onPressItem(data.id, libraryUrl)}
			           width={{base: 100, lg: 200}}
			           height={{base: 150, lg: 250}}>
				<CachedImage
					cacheKey={data.id}
					alt={title}
					source={{ uri:  `${imageUrl}` }}
					style={{width: '100%', height: '100%'}}
				/>
			</Pressable>
		);
	}

	_renderSubCategories = (category, user, libraryUrl) => {
		const subCategory = category.subCategories;

		return (
			<FlatList
				horizontal
				data={subCategory}
				renderItem={({item}) => this._renderRecords(item, libraryUrl)}
				initialNumToRender={5}
				ListHeaderComponent={({item}) => this._renderHeader(item.title, item.key, user, libraryUrl)}
			/>
		)
	}

	_renderViewAll = (categoryLabel, categoryKey, libraryUrl) => {
		const { navigation } = this.props;
		return (
			<Box flex={1} alignItems="center">
				<Button size="md" colorScheme="tertiary" variant="ghost" onPress={() => {
					navigation.navigate("SearchByCategory", {
						categoryLabel: categoryLabel,
						category: categoryKey,
						libraryUrl: libraryUrl,
					})
				}}>View More</Button>
			</Box>
		)
	}

	render() {
		const {isLoading, categoriesLoaded} = this.state;
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;
		const browseCategories = this.context.browseCategories;

		let discoveryVersion = "22.04.00";
		if(library.discoveryVersion) {
			let version = library.discoveryVersion;
			version = version.split(" ");
			discoveryVersion = version[0];
		}

/*
		if(_.isEmpty(this.state.browseCategories) && !_.isEmpty(this.context.library)) {
			this.loadBrowseCategories(library.baseUrl, user.id);
		}
*/

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
			//console.log(discoveryVersion + " is newer than or equal to 22.05.00");
			return (
				<ScrollView>
					<Box safeArea={5}>
						{browseCategories.map((category) => {
							return (
								<DisplayBrowseCategory
									categoryLabel={category.title}
									categoryKey={category.key}
									records={category.records}
									isHidden={category.isHidden}
									renderRecords={this._renderRecords}
									header={this._renderHeader}
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
									viewAll={this._renderViewAll}
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
	const {onPressSettings, onRefreshCategories, libraryUrl, patronId} = props;
	const [isLoading, setLoading] = React.useState(true);

	React.useEffect(()=>{
		const timeout = setTimeout(() => {
			setLoading (false);
		}, 5000);

		return () => {
			clearTimeout(timeout);
		};
	},[]);

	if(isLoading) {
		return null
	}

	return <Box>
		<Button size="md" colorScheme="primary" onPress={() => {
			onPressSettings(libraryUrl, patronId)
		}} startIcon={<Icon as={MaterialIcons} name="settings" size="sm"/>}>Manage Categories</Button>
		<Button size="md" mt="3" colorScheme="primary" onPress={() => {
			onRefreshCategories(libraryUrl, patronId)
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