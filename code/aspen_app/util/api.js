import React, { Component } from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from 'expo-secure-store';
import { create, CancelToken } from 'apisauce';

import { libraryUrl } from "./variables";

const api = create({ baseURL: libraryUrl + '/API/', timeout: 10000 });

export async function getBasicItemInfo(id) {
    const response = await api.get('/ItemAPI?method=getAppBasicItemInfo', { id: id });

    if(response.ok) {
        const result = response.data;
        const data = result.result;
        return data;
    } else {
        const data = response.problem;
        return data;
    }
}