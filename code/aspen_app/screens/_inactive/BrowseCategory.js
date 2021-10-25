import React, { Component, useState, useCallback, useEffect, useRef } from 'react'
import { Box, Text, FlatList, Spinner, ScrollView, View, TouchableWithoutFeedback } from 'native-base';
import { create, CancelToken } from 'apisauce';

const BrowseCategory = (props) => {

    const { isLoading, categoryLabel, categoryKey, renderItem, emptyComponent } = props

    //const [page, setPage] = React.useState(1);
    const [items, setItems] = React.useState([]);
    const [shouldFetch, setShouldFetch] = React.useState(true);
    const [initialLoad, setInitialLoad] = React.useState(false);




    // return this function for Flatlist to call onEndReached
    const fetchMore = useCallback(() => setShouldFetch(true), []);

        useEffect(() => {
            if(!fetchMore) {
                return;
            }

            const fetch = async () => {

                const newItems = await getBrowseCategoryResults(categoryKey, 25, 1);

                // set the load more call to false to prevent fetching on page number update
                setShouldFetch(false);
                setItems(oldItems => [...oldItems, ...newItems]);


            };

            fetch();
            setInitialLoad(true);

        }, [fetchMore],);


    return (
        <View>
            <Text bold mb={0.5} fontSize="lg">{categoryLabel}</Text>
            <FlatList
                horizontal
                data={items}
                renderItem={({ item }) => renderItem(item)}
                keyExtractor={item => categoryKey.concat("_",item.key)}
                onEndReachedThreshold={0.5}
                onEndReached={fetchMore}
                initialNumToRender={15}
            />
        </View>
    )
}

async function getBrowseCategoryResults(categoryKey, limit = 25, page) {
    const api = create({ baseURL: '' });
    const url = "http://demo.localhost:8888/API/SearchAPI?method=getAppBrowseCategoryResults&limit=" + limit + "&id=" + categoryKey + "&page=" + page;
    const response = await api.get(url);

    if(response.ok) {
        const result = response.data;
        const itemResult = result.result;

        const items = itemResult.records.map(({ id, title_display }) => ({
            key: id,
            title: title_display,
        }));

        return items;
    }
}

function useInterval(callback, delay) {
    const savedCallback = useRef();

    useEffect(() => {
        savedCallback.current = callback;
    }, [callback]);

    useEffect(() => {
        let id = setInterval(() => {
            savedCallback.current();
        }, [delay]);
    })
}

export default BrowseCategory;