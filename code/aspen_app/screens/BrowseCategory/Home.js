import React, {Component} from "react";
import {Box, Button, Icon, Pressable, ScrollView} from "native-base";
import * as SecureStore from 'expo-secure-store';
import {MaterialIcons} from "@expo/vector-icons";
import ExpoFastImage from 'expo-fast-image'
import {create} from 'apisauce';

// custom components and helper files
import {createAuthTokens, getHeaders, postData, problemCodeMap} from "../../util/apiAuth";
import BrowseCategory from './BrowseCategory';
import {setGlobalVariables, setSession} from '../../util/setVariables';
import {getCheckedOutItems, getHolds, getProfile} from '../../util/loadPatron';
import {getLibraryInfo, getLocationInfo} from '../../util/loadLibrary';
import {dismissBrowseCategory} from "../../util/accountActions";
import {loadingSpinner} from "../../components/loadingSpinner";
import {loadError} from "../../components/loadError";
import {removeData} from "../../util/logout";

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
			categories: null,
		};
	}

	componentDidMount = async () => {
		this.setState({
			isLoading: true,
		});

		await setSession();
		await setGlobalVariables();

		setTimeout(
			function () {
				getCheckedOutItems(true);
				getHolds(true);
				getProfile(true);
				getLocationInfo();
				getLibraryInfo(global.libraryId, global.libraryUrl, global.timeoutAverage);
			}
				.bind(this),
			1000
		);

		await this.getActiveBrowseCategories();
	}

	getActiveBrowseCategories = async () => {
		this.setState({
			isLoading: true,
		})
		const postBody = await postData();
		const apiUrl = await SecureStore.getItemAsync("pathUrl");
		const api = create({
			baseURL: apiUrl + '/API',
			headers: getHeaders(),
			timeout: 60000,
			auth: createAuthTokens(),
		});
		api.post("/SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true", postBody)
			.then(async response => {
				if (response.status === 403) {
					await removeData().then(res => {
						console.log("Session ended.")
					});
				}
				if (response.ok) {
					const items = response.data;
					const results = items.result;
					var allCategories = [];
					const categoriesArray = results.map(function (category, index, array) {
						const subCategories = category['subCategories'];

						if (subCategories.length !== 0) {
							subCategories.forEach(item => allCategories.push({'key': item.key, 'title': item.title}))
						} else {
							allCategories.push({'key': category.key, 'title': category.title});
						}

						return allCategories;
					});

					this.setState({
						isLoading: false,
						categories: categoriesArray[0],
					});
				} else {
					const problem = problemCodeMap(response.problem);
					this.setState({
						hasError: true,
						error: problem,
						isLoading: false,
					})
				}
			})
	}

	onHideCategory = async (item) => {
		await dismissBrowseCategory(item).then(res => {
			this.setState({
				isLoading: false,
			});
		})
		await this.getActiveBrowseCategories();
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
				               style={{width: '100%', height: '100%', borderRadius: 8}}/>
			</Pressable>
		);
	};

	render() {
		const {isLoading, categories} = this.state;

		if (this.state.isLoading === true) {
			return (loadingSpinner());
		}

		if (this.state.hasError) {
			return (loadError(this.state.error));
		}

		if (typeof categories === 'undefined') {
			return (loadError("No categories"));
		}

		return (
			<ScrollView>
				<Box safeArea={5}>
					{categories.map((category) => {
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
