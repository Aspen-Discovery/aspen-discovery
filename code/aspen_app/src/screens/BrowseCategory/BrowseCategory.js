import React, {useCallback, useEffect} from 'react'
import {Button, FlatList, HStack, Icon, Text, View} from 'native-base';
import {create} from 'apisauce';
import {MaterialIcons} from "@expo/vector-icons";
import _ from "lodash";

import {createAuthTokens, getHeaders, postData, problemCodeMap} from "../../util/apiAuth";
import {popToast} from "../../components/loadError";

const BrowseCategory = (props) => {
	const {categoryLabel, categoryKey, renderItem, hideCategory} = props
	const [page, setPage] = React.useState(1);
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
		setTimeout(
			function () {
				fetch();
			}
				.bind(this),
			2000);
		setInitialLoad(true);
	}, [fetchMore]);

	if(typeof items !== "undefined") {
		if (items.length !== 0) {
			return (
				<View pb={5} height="225">
					<HStack space={3} alignItems="center" justifyContent="space-between" pb={2}>
						<Text maxWidth="80%" bold mb={1} fontSize={{base: "lg", lg: "2xl"}}>{categoryLabel}</Text>
						<Button size="xs" colorScheme="trueGray" variant="ghost" onPress={() => hideCategory(categoryKey)}
						        startIcon={<Icon as={MaterialIcons} name="close" size="xs" mr={-1.5}/>}>Hide</Button>
					</HStack>
					<FlatList
						horizontal
						data={items}
						renderItem={({item}) => renderItem(item)}
						keyExtractor={item => categoryKey.concat("_", item.key)}
						initialNumToRender={25}
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
	const postBody = await postData();
	const api = create({
		baseURL: global.libraryUrl + '/API',
		timeout: 60000,
		headers: getHeaders(true),
		auth: createAuthTokens(),
		params: {limit: limit, id: categoryKey, page: page}
	});
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
		const problem = problemCodeMap(response.problem);
		popToast(problem.title, problem.message, "warning");
	}
}

export default BrowseCategory;