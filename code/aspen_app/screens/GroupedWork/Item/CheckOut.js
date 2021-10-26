import React, { Component, useState, useReducer } from "react";
import { Dimensions, Animated } from "react-native";
import { Center, Stack, HStack, VStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Image, IconButton, FlatList, Badge, Avatar, Actionsheet, useDisclose, Pressable } from "native-base";
import { create, CancelToken } from 'apisauce';

export default async function checkoutItem(itemId, source, patronId) {
    const api = create({ baseURL: 'http://demo.localhost:8888/API', timeout: 5000 });
    const response = await api.get('/UserAPI?method=checkoutItem', { username: global.userKey, password: global.secretKey, itemId: itemId, source: source, patronId: patronId });


    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;

        console.log(fetchedData);

        return fetchedData
    } else {
        console.log("Unable to connect.");
        fetchedData = response.problem;
        return fetchedData
    }
}