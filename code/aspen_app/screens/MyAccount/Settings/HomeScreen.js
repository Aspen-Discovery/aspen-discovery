import React, { Component } from "react";
import { View } from "react-native";
import { Center, Spinner, HStack, Text, Image, Flex, Box, FlatList, Icon, Button, Switch } from "native-base";
import { MaterialIcons, Entypo, Ionicons } from "@expo/vector-icons";
import { ListItem } from "react-native-elements";
import * as SecureStore from 'expo-secure-store';
import AsyncStorage from "@react-native-async-storage/async-storage";
import Barcode from "react-native-barcode-expo";

// custom components and helper files
import { translate } from '../../../util/translations';
import { loadingSpinner } from "../../../components/loadingSpinner";
import { loadError } from "../../../components/loadError";
import { getActiveBrowseCategories } from "../../../util/loadLibrary";
import { getHiddenBrowseCategories } from "../../../util/loadPatron";
import { showBrowseCategory, dismissBrowseCategory } from "../../../util/accountActions";

export default class Settings_HomeScreen extends Component {
	static navigationOptions = { title: translate('user_profile.home_screen_settings') };

	constructor(props) {
		super(props);
		this.state = {
            isLoading: true,
            hasError: false,
            error: null,
            hasUpdated: false,
            isRefreshing: false,
            browseCategories: [],
		};
	}

	// store the values into the state
	componentDidMount = async () => {
		this.setState({
			isLoading: false,
		});

		await this._fetchBrowseCategoryList();
		await this._fetchHiddenBrowseCategories();

	};

    _fetchBrowseCategoryList = async () => {

        this.setState({
            isLoading: true,
        });

        await getActiveBrowseCategories().then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: translate('error.timeout'),
                    isLoading: false,
                });
            } else {
                this.setState({
                    browseCategories: response,
                    hasError: false,
                    error: null,
                    isLoading: false,
                });
            }
        });
    }

    _fetchHiddenBrowseCategories = async () => {
        this.setState({
            isLoading: true,
        });

        await getHiddenBrowseCategories().then(response => {
            if(response == "TIMEOUT_ERROR") {
                this.setState({
                    hasError: true,
                    error: translate('error.timeout'),
                    isLoading: false,
                });
            } else {
                const hiddenCategories = response;
                const browseCategories = this.state.browseCategories;
                const allCategories = hiddenCategories.concat(browseCategories);
                this.setState({
                    hiddenCategories: response,
                    allCategories: allCategories,
                    hasError: false,
                    error: null,
                    isLoading: false,
                });
            }
        });
    }

    // handles the on-press action
    onPressItem = async (item) => {
        console.log(item);
        if(item.isHidden == true) {
            await showBrowseCategory(item.key);
        } else {
            await dismissBrowseCategory(item.key);
        }
            await this._fetchBrowseCategoryList();
            await this._fetchHiddenBrowseCategories();
    };


	renderNativeItem = (item) => {
        return (
            <Box safeArea={5} bgColor="white">
                <HStack space={3} alignItems="center" justifyContent="space-between" pb={1}>
                    <Text isTruncated bold maxW="80%">{item.title}</Text>
                    { item.isHidden ? <Switch size="md" onToggle={() => this.onPressItem(item)} /> : <Switch size="md" onToggle={() => this.onPressItem(item)} defaultIsChecked /> }
                </HStack>
            </Box>
        );
	};

	render() {
		if (this.state.isLoading) {
			return ( loadingSpinner() );
		}

		if (this.state.hasError) {
            return ( loadError(this.state.error) );
		}

		return (
            <Box>
				<FlatList
					data={this.state.allCategories}
					renderItem={({ item }) => this.renderNativeItem(item)}
				/>
			</Box>
		);
	}
}
