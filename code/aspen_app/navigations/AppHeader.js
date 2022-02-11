import React from "react";
import { IconButton } from 'native-base';
import {MaterialCommunityIcons} from "@expo/vector-icons";
import { useNavigation } from '@react-navigation/native';

const OpenAccountDrawer = ({ props }) => {
	const navigation = useNavigation();
	return (
		<IconButton
			onPress={() => navigation.openDrawer()}
			size={8}
			variant="ghost"
			_icon={{
				as: MaterialCommunityIcons,
				name: "account"
			}}
		/>
	)
}

export default OpenAccountDrawer;
