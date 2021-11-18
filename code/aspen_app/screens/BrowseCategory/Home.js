import React, { Component, useEffect, setState, useState } from "react";
import { SectionList, View, TouchableWithoutFeedback } from "react-native";
import { Image, Button, Icon, Center, Box, Spinner, HStack, Select, Heading, Toast, CheckIcon, FormControl, Text, Flex, Container, Pressable, ScrollView, FlatList } from "native-base";
import { FlatGrid } from "react-native-super-grid";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import Constants from "expo-constants";
import { MaterialIcons, Entypo } from "@expo/vector-icons";
import ExpoFastImage from 'expo-fast-image'
import NavigationService from '../../components/NavigationService';
import BrowseCategory from './BrowseCategory';
import * as Random from 'expo-random';
import { create, CancelToken } from 'apisauce';
import moment from "moment";
import base64 from 'react-native-base64';

import { setGlobalVariables, setSession } from '../../util/setVariables';
import { getProfile, getCheckedOutItems, getHolds } from '../../util/loadPatron';
import { getLocationInfo } from '../../util/loadLibrary';

import { loadingSpinner } from "../../components/loadingSpinner";

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

       console.log("Ready to work.")
       console.log("Preparing...")
       console.log("Creating session...")
       await setSession();
       await setGlobalVariables();
       setTimeout(
         function() {
            getCheckedOutItems();
            getHolds();
            getProfile();
            getLocationInfo();
         }
         .bind(this),
         1000
       );

       console.log("Jobs done.")

       await this.getActiveBrowseCategories();
	}

	getActiveBrowseCategories = () => {
        const api = create({ baseURL: global.libraryUrl + '/API/' , timeout: 5000});
        api.get("SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true")
            .then(response => {
                if(response.ok) {
                    const items = response.data;
                    const results = items.result;

                    var allCategories = [];
                    const categoriesArray = results.map(function (category, index, array) {
                        const subCategories = category['subCategories'];

                        if(subCategories.length != 0) {
                            subCategories.forEach(item => allCategories.push({'key':item.key, 'title':item.title}))
                        } else {
                            allCategories.push({'key':category.key, 'title':category.title});
                        }

                        return allCategories;
                    });

                        this.setState({
                            isLoading: false,
                            categories: categoriesArray[0],
                        })
                } else {
                    console.log(response);
                }
            })
    }

    onPressItem = (item) => {
        this.props.navigation.navigate("GroupedWork", { item });
    };

    onLoadMore = (item) => {
        this.props.navigation.navigate("GroupedWork", { item });
    };

    _renderNativeItem = (data) => {
	    const imageUrl = global.libraryUrl + "/bookcover.php?id=" + data.key + "&size=medium&type=grouped_work";
		return (
        <Pressable mr={1.5} onPress={() => this.onPressItem(data.key)} width={{ base: 100, lg: 200 }} height={{ base: 125, lg: 275 }}>
            <ExpoFastImage cacheKey={data.key} uri={imageUrl} alt={data.title} resizeMode="cover" style={{ width: '100%', height: '100%', borderRadius:8 }} />
        </Pressable>
		);
	};

    _listFooterComponent = () => {
		return (
        <Pressable onPress={() => this.onLoadMore(data.key)} width={{ base: 100, lg: 200 }} height={{ base: 125, lg: 275 }} bgColor="secondary.400" rounded={8}>
            <Center flex={1}><Text fontSize={{ base: "lg", lg: "2xl" }} bold>Load More</Text></Center>
        </Pressable>
		);
	};

	_listEmptyComponent = () => {
        if(this.state.hasError) {
            return (
                <Center mt={5} mb={5}>
                    <Text bold fontSize="lg">
                        Error loading items. Please try again later.
                    </Text>
                </Center>
            )
        }
		return (
			<Center mt={5} mb={5}>
				<Text bold fontSize="lg">
					No items to load
				</Text>
				<Text>Try a different category</Text>
			</Center>
		);
	};

	render() {
	    const { isLoading, categories } = this.state;

        if(this.state.isLoading == true) {
           return ( loadingSpinner() );
        } else {
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
                            emptyComponent={this._listEmptyComponent}
                            footerComponent={this._listFooterComponent}
                            loadMore={this.onLoadMore}
                            />
                        );
                    })}
                </Box>
            </ScrollView>
            );
        }
    }

}
