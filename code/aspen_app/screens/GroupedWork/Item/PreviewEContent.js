import React, { Component, useState, useReducer } from "react";
import { Dimensions, Animated } from "react-native";
import { Center, Stack, HStack, VStack, Spinner, Toast, Button, Divider, Flex, Box, Text, Icon, Image, IconButton, FlatList, Badge, Avatar, Actionsheet, useDisclose, Pressable } from "native-base";
import { create, CancelToken } from 'apisauce';
import * as WebBrowser from 'expo-web-browser';

export default async function overDriveSample(formatId, itemId, sampleNumber) {
    const api = create({ baseURL: 'http://demo.localhost:8888/API', timeout: 3000 });
    const response = await api.get('/UserAPI?method=viewOnlineItem', { username: global.userKey, password: global.secretKey, overDriveId: itemId, formatId: formatId, sampleNumber: sampleNumber, itemSource: "overdrive", isPreview: "true" });

    if(response.ok) {
        const result = response.data;
        const accessUrl = result.result.url;

        await WebBrowser.openBrowserAsync(accessUrl)
          .then(res => {
            console.log(res);
          })
          .catch(async err => {
            if (err.message === "Another WebBrowser is already being presented.") {

             try {
                  WebBrowser.dismissBrowser();
                  await WebBrowser.openBrowserAsync(accessUrl)
                    .then(response => {
                      console.log(response);
                    })
                    .catch(async error => {
                      console.log("Unable to close previous browser session.");
                    });
                } catch(error) {
                    console.log ("Really borked.");
                }
            } else {
              console.log("Unable to open browser window.");
            }
          });


    } else {
        const result = response.problem;
        return result;
    }
}