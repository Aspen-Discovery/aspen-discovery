import React, { Component, useState, useCallback, useEffect, useRef } from 'react'
import { Box, Text, FlatList, Spinner, ScrollView, View, TouchableWithoutFeedback, Button, Divider, HStack, Icon } from 'native-base';
import { create, CancelToken } from 'apisauce';
import { MaterialIcons, Entypo } from "@expo/vector-icons";
import _ from "lodash";

import { createAuthTokens, postData, getHeaders } from "../../util/apiAuth";

const BrowseCategory = (props) => {
    const { isLoading, categoryLabel, categoryKey, renderItem, loadMore, hideCategory } = props
    const [page, setPage] = React.useState(1);
    const [items, setItems] = React.useState([]);
    const [shouldFetch, setShouldFetch] = React.useState(true);
    const [initialLoad, setInitialLoad] = React.useState(false);

    const fetchMore = useCallback(() => setShouldFetch(true), []);
        useEffect(() => {
            if(!fetchMore) { return;  }
            const fetch = async () => {
                const newItems = await getBrowseCategoryResults(categoryKey, 25, 1);
                setShouldFetch(false);
                setItems(newItems);

            };
           setTimeout(
             function() {
                fetch();
             }
             .bind(this),
             1000);
            setInitialLoad(true);
        }, [fetchMore]);

    if(items.length != 0) {
        return (
            <View pb={5}>
                <HStack space={3} alignItems="center" justifyContent="space-between" pb={2}>
                    <Text bold mb={1} fontSize={{ base: "lg", lg: "2xl" }}>{categoryLabel}</Text>
                    <Button size="xs" colorScheme="trueGray" variant="outline" onPress={() => hideCategory(categoryKey)} startIcon={<Icon as={MaterialIcons} name="close" size="xs" mr={-1.5} />}>Hide</Button>
                </HStack>
                <FlatList
                    horizontal
                    data={items}
                    renderItem={({ item }) => renderItem(item)}
                    keyExtractor={item => categoryKey.concat("_",item.key)}
                    initialNumToRender={25}
                />
            </View>
        )
    }

    return null
}

async function getBrowseCategoryResults(categoryKey, limit = 25, page) {
    const postBody = await postData();
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: global.timeoutSlow, headers: getHeaders(true), auth: createAuthTokens(), params: { limit: limit, id: categoryKey, page: page } });
    const response = await api.post('/SearchAPI?method=getAppBrowseCategoryResults', postBody);
    if(response.ok) {
        const result = response.data;
        const itemResult = result.result;
        const records = itemResult.records;

        if(_.isArray(records) == false) {
            let array = _.values(records);
            const items = array.map(({ id, title_display }) => ({
                key: id,
                title: title_display,
            }));
            return items;
        }
        if(_.isArray(records) == true) {
            const items = records.map(({ id, title_display }) => ({
                key: id,
                title: title_display,
            }));
            return items;
        }
    } else {
        console.log(response);
    }
}

export default BrowseCategory;