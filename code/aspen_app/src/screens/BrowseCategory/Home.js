import React, {Component} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {Box, Button, Icon, Pressable, ScrollView, Skeleton} from "native-base";
import * as SecureStore from 'expo-secure-store';
import {MaterialIcons} from "@expo/vector-icons";
import ExpoFastImage from 'expo-fast-image'
import {create} from 'apisauce';

// custom components and helper files
import BrowseCategory from './BrowseCategory';
import {getBrowseCategories, getLibraryInfo, getLocationInfo} from '../../util/loadLibrary';
import {dismissBrowseCategory} from "../../util/accountActions";
import {loadingSpinner} from "../../components/loadingSpinner";
import {loadError} from "../../components/loadError";
import {GLOBALS} from "../../util/globals";


export default class BrowseCategoryHome extends Component {
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
			browseCategories: [{
				title: "",
				key: "",
			}],
			user: [],
			libraryUrl: global.libraryUrl,
		};
		getBrowseCategories();
		getLibraryInfo(global.libraryId, global.libraryUrl, GLOBALS.timeoutAverage);
		getLocationInfo();
	}

	loadBrowseCategories = async () => {
		const tmp = await AsyncStorage.getItem('@browseCategories');
		const items = JSON.parse(tmp);
		this.setState({
			browseCategories: items,
			isLoading: false,
		})
	}

	loadUser = async () => {
		const tmp = await AsyncStorage.getItem('@patronProfile');
		const profile = JSON.parse(tmp);
		this.setState({
			user: profile,
			isLoading: false,
		})
	}

	componentDidMount = async () => {
		this.setState({
			isLoading: true,
		});

		await this.loadBrowseCategories();
		await this.loadUser();

		this.interval = setInterval(() => {
			this.loadBrowseCategories();
		}, 5000)

		return () => clearInterval(this.interval)

	}

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	onHideCategory = async (item) => {
		await dismissBrowseCategory(item, this.state.user.id);
	};

	onPressItem = (item) => {
		this.props.navigation.navigate("GroupedWorkScreen", {item});
	};

	onLoadMore = (item) => {
		this.props.navigation.navigate("GroupedWorkScreen", {item});
	};

	onPressSettings = () => {
		this.props.navigation.navigate("AccountStack", {screen: "SettingsHomeScreen"});
	};

	_renderNativeItem = (data) => {
		const imageUrl = global.libraryUrl + "/bookcover.php?id=" + data.key + "&size=medium&type=grouped_work";
		return (
				<Pressable mr={1.5} onPress={() => this.onPressItem(data.key)} width={{base: 100, lg: 200}}
				           height={{base: 150, lg: 250}}>
					<ExpoFastImage cacheKey={data.key} uri={imageUrl} alt={data.title} resizeMode="cover"
					               style={{width: '100%', height: '100%', borderRadius: 8}} />
				</Pressable>
		);
	};

	render() {
		const {isLoading} = this.state;

		if (this.state.isLoading === true || this.state.browseCategories === null) {
			return (loadingSpinner());
		}

		if (this.state.hasError) {
			return (loadError(this.state.error));
		}

		if (typeof this.state.browseCategories === 'undefined') {
			return (loadError("No categories"));
		}

		return (
			<ScrollView>
				<Box safeArea={5}>
					{this.state.browseCategories.map((category) => {
						return (
							<BrowseCategory
								isLoading={isLoading}
								categoryLabel={category.title}
								categoryKey={category.key}
								renderItem={this._renderNativeItem}
								loadMore={this.onLoadMore}
								hideCategory={this.onHideCategory}
							/>
						);
					})}
					<Button colorScheme="primary" mt={5} onPress={() => {
						this.onPressSettings()
					}} startIcon={<Icon as={MaterialIcons} name="settings" size="sm"/>}>Manage Browse
						Categories</Button>
				</Box>
			</ScrollView>
		);

	}

}
