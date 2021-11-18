import React, { Component, useState, useCallback, useEffect, useRef } from 'react'
import { Box, Text, FlatList, Spinner, ScrollView, View, TouchableWithoutFeedback, Button, Divider } from 'native-base';
import { create, CancelToken } from 'apisauce';

const BrowseCategory = (props) => {
    const { isLoading, categoryLabel, categoryKey, renderItem, emptyComponent, footerComponent, loadMore } = props
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
            fetch();
            setInitialLoad(true);
        }, [fetchMore]);

    if(items.length != 0) {
        return (
            <View pb={5}>
                <Text bold mb={1} fontSize={{ base: "lg", lg: "2xl" }}>{categoryLabel}</Text>
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
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 10000 });
    const url = "/SearchAPI?method=getAppBrowseCategoryResults&limit=" + limit + "&id=" + categoryKey + "&page=" + page;
    const response = await api.get(url);

    if(response.ok) {
        const result = response.data;
        const itemResult = result.result;
        const records = itemResult.records;
        if(records) {
            const items = records.map(({ id, title_display }) => ({
                key: id,
                title: title_display,
            }));
            return items;
        }
    }
}

export default BrowseCategory;