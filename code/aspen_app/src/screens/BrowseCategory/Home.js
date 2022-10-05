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
import {translate} from "../../translations/translations";

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
		this._isMounted = false;
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
		this._isMounted = true;

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

		this._isMounted && this.setState({
			categoriesLoaded: false,
		})

		let libraryUrl = "";
		try {
			libraryUrl = await AsyncStorage.getItem('@pathUrl');
		} catch (e) {
			console.log(e);
		}

		//console.log(libraryUrl);

		if (this.context.library.baseUrl) {
			await getCheckedOutItems(this.context.library.baseUrl);
			await getHolds(this.context.library.baseUrl);
			await getPickupLocations(this.context.library.baseUrl);
			await getLists(this.context.library.baseUrl);
			await getILSMessages(this.context.library.baseUrl);
			//await getPatronBrowseCategories(libraryUrl);
		}

	}

	componentWillUnmount() {
		this._isMounted = false;
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

	onRefreshCategories = async () => {
		this.setState({isLoading: true});

		await getBrowseCategories(this.context.library.baseUrl, this.state.discoveryVersion).then(response => {
			this.context.browseCategories = response;
			//console.log(response);
			this.setState({
				isLoading: false,
			})
		})
	}

	onLoadAllCategories = async () => {
		this.setState({isLoading: true});

		await getBrowseCategories(this.context.library.baseUrl, this.state.discoveryVersion).then(response => {
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

	onPressItem = (key, type, title, discoveryVersion) => {
		const libraryUrl = this.context.library.baseUrl;
		if(discoveryVersion >= "22.07.00") {
			if(type === "List") {
				this.props.navigation.navigate("SearchByList", {id: key, libraryUrl: libraryUrl, title: title});
			} else if (type === "SavedSearch") {
				this.props.navigation.navigate("SearchBySavedSearch", {id: key, libraryUrl: libraryUrl, title: title});
			} else {
				this.props.navigation.navigate("GroupedWorkScreen", {id: key, libraryUrl: libraryUrl, title: title});
			}
		} else {
			this.props.navigation.navigate("GroupedWorkScreen", {id: key, libraryUrl: libraryUrl, title: title})
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
				//console.log(data);
				return (
					<Pressable mr={1.5} onPress={() => this.onPressItem(data.key)}
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
					        startIcon={<Icon as={MaterialIcons} name="close" size="xs" mr={-1.5}/>}>{translate('general.hide')}</Button>
				</HStack>
			</Box>
		)
	};

	_renderRecords = (data, user, libraryUrl, discoveryVersion) => {
		let type = "grouped_work";
		if(data.source) {
			type = data.source;
		}
		const imageUrl = libraryUrl + "/bookcover.php?id=" + data.id + "&size=medium&type=" + type.toLowerCase();

		let isNew = false;
		if(typeof data.isNew !== "undefined") {
			isNew = data.isNew;
		}

		//console.log(data);

		return (
			<Pressable ml={1} mr={3} onPress={() => this.onPressItem(data.id, type, data.title_display, discoveryVersion)}
			           width={{base: 100, lg: 200}}
			           height={{base: 125, lg: 250}}>
				{discoveryVersion >= "22.08.00" && isNew ? (<Container zIndex={1}><Badge colorScheme="warning" shadow={1} mb={-2} ml={-1} _text={{fontSize: 9}}>{translate('general.new')}</Badge></Container>) : null}
				<CachedImage
					cacheKey={data.id}
					alt={data.title_display}
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
								           title: categoryLabel,
								           id: categoryKey,
								           libraryUrl: libraryUrl,
							           })}
					           }>
						<Box width={{base: 100, lg: 200}} height={{base: 150, lg: 250}} bg="secondary.200" borderRadius="4" p="5" alignItems="center" justifyContent="center">
							<Center><Text bold fontSize="md">{translate('general.load_more')}</Text></Center>
						</Box>
					</Pressable>

				</Box>
			)
		}
	}

	_handleOnPressCategory = (categoryLabel, categoryKey, categorySource) => {
		let searchBy = "SearchByCategory";
		if(categorySource === "List") {
			searchBy = "SearchByList";
		} else if (categorySource === "SavedSearch") {
			searchBy = "SearchBySavedSearch";
		} else {
			searchBy = "SearchByCategory";
		}
		this.props.navigation.navigate(searchBy, {
			title: categoryLabel,
			id: categoryKey,
			libraryUrl: this.context.library.baseUrl,
		})
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

		setTimeout(() => {
			this.setState({
				isLoading: false,
			})
		}, 1);


		if (this.state.isLoading === true) {
			return (loadingSpinner());
		}

		if (this.state.hasError) {
			return (loadError(this.state.error));
		}

		if (typeof browseCategories === 'undefined') {
			//return (loadingSpinner());
			return (loadError("No categories found", this.onRefreshCategories()));
		}

		if(discoveryVersion >= "22.05.00" && browseCategories) {
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
									onPressCategory={this._handleOnPressCategory}
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

	if(discoveryVersion >= "22.07.00") {
		if(loadAll) {
			return (
				<Box>
					<Button size="md" colorScheme="primary" onPress={() => {
						onPressSettings(libraryUrl, patronId)
					}} startIcon={<Icon as={MaterialIcons} name="settings" size="sm"/>}>{translate('browse_category.manage_categories')}</Button>
					<Button size="md" mt="3" colorScheme="primary" onPress={() => {
						onRefreshCategories()
					}} startIcon={<Icon as={MaterialIcons} name="refresh" size="sm"/>}>{translate('browse_category.refresh_categories')}</Button>
				</Box>
			)
		} else {
			return (
				<Box>
					<Button size="md" colorScheme="primary" onPress={() => {
						onLoadAllCategories(libraryUrl, patronId)
					}} startIcon={<Icon as={MaterialIcons} name="schedule" size="sm"/>}>{translate('browse_category.load_all_categories')}</Button>
				</Box>
			)
		}
	}

	return <Box>
		<Button size="md" colorScheme="primary" onPress={() => {
			onPressSettings(libraryUrl, patronId)
		}} startIcon={<Icon as={MaterialIcons} name="settings" size="sm"/>}>{translate('browse_category.manage_categories')}</Button>
		<Button size="md" mt="3" colorScheme="primary" onPress={() => {
			onRefreshCategories(libraryUrl)
		}} startIcon={<Icon as={MaterialIcons} name="refresh" size="sm"/>}>{translate('browse_category.refresh_categories')}</Button>
	</Box>
}

function sleep(milliseconds) {
	const date = Date.now();
	let currentDate = null;
	do {
		currentDate = Date.now();
	} while (currentDate - date < milliseconds);
}
