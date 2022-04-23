import React, {Component} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {Box, Button, Icon, Pressable, ScrollView} from "native-base";
import {MaterialIcons} from "@expo/vector-icons";
import * as Progress from 'react-native-progress';
import ExpoFastImage from 'expo-fast-image';
import { createImageProgress } from 'react-native-image-progress';
import Constants from 'expo-constants';
import { useNavigation } from '@react-navigation/native';

// custom components and helper files
import BrowseCategory from './BrowseCategory';
import {getBrowseCategories, getPickupLocations} from '../../util/loadLibrary';
import {dismissBrowseCategory} from "../../util/accountActions";
import {loadingSpinner} from "../../components/loadingSpinner";
import {loadError} from "../../components/loadError";
import {userContext} from "../../context/user";
import {getCheckedOutItems, getHolds, getLists, getPatronBrowseCategories} from "../../util/loadPatron";
import {AuthContext} from "../../components/navigation";
import {isNull} from "lodash";
import {removeData} from "../../util/logout";

export default class BrowseCategoryHome extends Component {
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
		};
		//getBrowseCategories();
	}



	loadBrowseCategories = async (libraryUrl) => {
		if(patron && this.state.categoriesLoaded === false) {
			await getBrowseCategories(libraryUrl).then(response => {
				this.setState({
					browseCategories: response,
					categoriesLoaded: true,
					isLoading: false,
				});
			});
		}
	}

	componentDidMount = async () => {

		this.setState({
			categoriesLoaded: false,
			isLoading: true,
		})

		let libraryUrl = "";
		try {
			libraryUrl = await AsyncStorage.getItem('@pathUrl');
		} catch (e) {
			console.log(e);
		}

		this.interval = setInterval(() => {
			this.loadBrowseCategories(libraryUrl);
		}, 1000)

		return () => clearInterval(this.interval);

		if(libraryUrl) {
			await getCheckedOutItems(libraryUrl);
			await getHolds(libraryUrl);
			await getPickupLocations(libraryUrl);
			await getLists(libraryUrl);
			//await getPatronBrowseCategories(libraryUrl);
		}
	}

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	onHideCategory = async (libraryUrl, categoryId, patronId) => {
		console.log("Trying to hide category...");
		this.setState({isLoading: true });
		await dismissBrowseCategory(libraryUrl, categoryId, patronId).then(async r => {
			await getBrowseCategories(libraryUrl).then(response => {
				this.setState({
					browseCategories: response,
					isLoading: false
				});
			})
		});

		//await getBrowseCategories();
	};

	onRefreshCategories = async (libraryUrl, patronId) => {
		this.setState({isLoading: true});
		await getBrowseCategories(libraryUrl).then(response => {
			this.setState({
				browseCategories: response,
				isLoading: false,
			})
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
		const Image = createImageProgress(ExpoFastImage);

		if(libraryUrl) {
			const imageUrl = libraryUrl + "/bookcover.php?id=" + data.key + "&size=medium&type=grouped_work";
			return (
				<Pressable mr={1.5} onPress={() => this.onPressItem(data.key, libraryUrl)} width={{base: 100, lg: 200}}
				           height={{base: 150, lg: 250}}>
					<Image
						source={{ uri: imageUrl }}
						style={{ width: '100%', height: '100%'}}
						imageStyle={{ borderRadius: 8 }}
						indicator={Progress.CircleSnail}
						indicatorProps={{
							size: 40,
							color: ['#F44336', '#2196F3', '#009688'],
							hidesWhenStopped: true,
							spinDuration: 10000,
							fill: '#00000000'
						}}
					/>
				</Pressable>
			);
		} else {
			return null
		}
	};

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

		//console.log(this.state.browseCategories);

		if (this.state.isLoading === true || this.state.browseCategories === "null") {
			return (loadingSpinner());
		}

		if (this.state.hasError) {
			return (loadError(this.state.error));
		}

		if (typeof this.state.browseCategories === 'undefined') {
			//return (loadingSpinner());
			return (loadError("No categories found"));
		}

		return (
			<ScrollView style={{ marginBottom: 80 }}>
				<Box safeArea={5}>
					{this.state.browseCategories.map((category) => {
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
					<Button size="md" colorScheme="primary" onPress={() => {
						this.onPressSettings(library.baseUrl, user.id)
					}} startIcon={<Icon as={MaterialIcons} name="settings" size="sm"/>}>Manage Categories</Button>
					<Button size="md" mt="3" colorScheme="primary" onPress={() => {
						this.onRefreshCategories(library.baseUrl, user.id)
					}} startIcon={<Icon as={MaterialIcons} name="refresh" size="sm"/>}>Refresh Categories</Button>
				</Box>
			</ScrollView>
		);

	}

}
