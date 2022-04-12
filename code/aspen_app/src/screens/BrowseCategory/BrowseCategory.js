import React, {useCallback, useEffect} from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {Button, FlatList, HStack, Icon, Text, View} from 'native-base';
import {create} from 'apisauce';
import {MaterialIcons} from "@expo/vector-icons";
import _ from "lodash";
import * as Random from 'expo-random';

import {createAuthTokens, getHeaders, postData} from "../../util/apiAuth";

const BrowseCategory = (props) => {
	const {categoryLabel, categoryKey, renderItem, hideCategory, user, libraryUrl, viewAll, isHidden} = props
	const [items, setItems] = React.useState([]);
	const [shouldFetch, setShouldFetch] = React.useState(true);
	const [initialLoad, setInitialLoad] = React.useState(false);

	const fetchMore = useCallback(() => setShouldFetch(true), []);
	useEffect(() => {
		if (!fetchMore) {
			return;
		}
		const fetch = async () => {
			const newItems = await getBrowseCategoryResults(categoryKey, 25, 1);
			setShouldFetch(false);
			setItems(newItems);
		};
		fetch();
		setInitialLoad(true);
	}, [fetchMore]);

	if(typeof items !== 'undefined') {
		if (items.length !== 0 && !isHidden) {
			return (
				<View pb={5} height="225">
					<HStack space={3} alignItems="center" justifyContent="space-between" pb={2}>
						<Text maxWidth="80%" bold mb={1} fontSize={{base: "lg", lg: "2xl"}}>{categoryLabel}</Text>
						<Button size="xs" colorScheme="trueGray" variant="ghost" onPress={() => hideCategory(libraryUrl, categoryKey, user.id)}
						        startIcon={<Icon as={MaterialIcons} name="close" size="xs" mr={-1.5}/>}>Hide</Button>
					</HStack>
					<FlatList
						horizontal
						data={items}
						renderItem={({item}) => renderItem(item, libraryUrl)}
						keyExtractor={({item}) => categoryKey.concat("_", Random.getRandomBytes(32))}
						initialNumToRender={5}
					/>
				</View>
			)
		} else {
			return null
		}
	}

	return null
}

async function getBrowseCategoryResults(categoryKey, limit = 25, page) {
	let libraryUrl;
	try {
		libraryUrl = await AsyncStorage.getItem('@pathUrl');
	} catch (e) {
		console.log(e);
	}

	const postBody = await postData();
	const api = create({
		baseURL: libraryUrl + '/API',
		timeout: 60000,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {limit: limit, id: categoryKey, page: page}
	});
	if(libraryUrl) {
		const response = await api.post('/SearchAPI?method=getAppBrowseCategoryResults', postBody);
		if (response.ok) {
			const result = response.data;
			const itemResult = result.result;
			//console.log(result);
			const records = itemResult.records;

			if (_.isArray(records) === false) {
				let array = _.values(records);
				return array.map(({id, title_display}) => ({
					key: id,
					title: title_display,
				}));
			}
			if (_.isArray(records) === true) {
				return records.map(({id, title_display}) => ({
					key: id,
					title: title_display,
				}));
			}
		} else {
			console.log(response);
		}
	}

}

export default BrowseCategory;