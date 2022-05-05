import React from 'react';
import {Button, FlatList, HStack, Icon, Text, View, Center, Heading, SectionList} from 'native-base';
import {MaterialIcons} from "@expo/vector-icons";
import _ from "lodash";
import * as Random from 'expo-random';

const DisplayBrowseCategory = (props) => {
	const {user, renderRecords, header, libraryUrl, records, categoryLabel, categoryKey, hideCategory} = props;

	if(typeof records !== "undefined") {
		const newArr = Object.values(records)

		return (
			<View pb={5} height="225">
				<HStack space={3} alignItems="center" justifyContent="space-between" pb={2}>
					<Text maxWidth="80%" bold mb={1} fontSize={{base: "lg", lg: "2xl"}}>{categoryLabel}</Text>
					<Button size="xs" colorScheme="trueGray" variant="ghost" onPress={() => hideCategory(libraryUrl, categoryKey, user)}
					        startIcon={<Icon as={MaterialIcons} name="close" size="xs" mr={-1.5}/>}>Hide</Button>
				</HStack>
				<FlatList
					horizontal
					data={newArr}
					renderItem={({item}) => renderRecords(item, user, libraryUrl)}
					initialNumToRender={5}
				/>
			</View>
		)
	}

	return null;

}

export default DisplayBrowseCategory;