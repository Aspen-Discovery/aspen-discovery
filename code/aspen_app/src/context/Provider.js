import React, { createContext, useReducer } from "react";
import { useAsyncStorage } from '@react-native-async-storage/async-storage';

export const GlobalContext = createContext();

const GlobalProvider = ({ children }) => {

	const { getItem, setItem } = useAsyncStorage("userProfile");

	return (
		<GlobalContext.Provider
			value={}
		>
			{children}
		</GlobalContext.Provider>
	);
};
export default GlobalProvider;
