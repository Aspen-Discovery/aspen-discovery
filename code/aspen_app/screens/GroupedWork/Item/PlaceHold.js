import React, { Component, useState, useReducer } from "react";
import { Dimensions, Animated } from "react-native";
import { Center, Stack, HStack, VStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Image, IconButton, FlatList, Badge, Avatar, Actionsheet, useDisclose, Pressable } from "native-base";
import { create, CancelToken } from 'apisauce';

export default async function placeHold(itemId, source, patronId) {
    const api = create({ baseURL: global.libraryUrl + '/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=placeHold', { username: global.userKey, password: global.secretKey, itemId: itemId, itemSource: source, patronId: patronId });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;
        console.log(fetchedData);

        if (fetchedData.success == true) {
           console.log(fetchedData);

        } else {
            Toast.show({
                title: "Unable to place hold",
                description: fetchedData.message,
                status: "error",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top"
            });
        }
    } else {
        console.log("Unable to connect.");
        fetchedData = response.problem;
        return fetchedData
    }
}