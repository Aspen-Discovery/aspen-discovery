import React from 'react';
import {Button, FlatList, HStack, Icon, Text, View, Pressable} from 'native-base';
import {MaterialIcons} from "@expo/vector-icons";
import _ from "lodash";
import {translate} from "../../translations/translations";

const DisplayBrowseCategory = (props) => {
	const {id, user, renderRecords, libraryUrl, records, categoryLabel, categoryKey, hideCategory, loadMore, categorySource, discoveryVersion, onPressCategory} = props;

	let key = categoryKey;
	if(id) {
		key = id;
	}

	if(typeof records !== "undefined" || typeof records !== "subCategories") {
		let newArr = [];
		if(typeof records !== "undefined" && !_.isNull(records)) {
			newArr = Object.values(records);
		}
		const recordCount = newArr.length;
		if(newArr.length > 0){
			return (
				<View pb={5} height="225">
					<HStack space={3} alignItems="center" justifyContent="space-between" pb={2}>
						{discoveryVersion >= "22.10.00" ? (
							<Pressable onPress={() => onPressCategory(categoryLabel, categoryKey, categorySource)} maxWidth="80%" mb={1}><Text bold fontSize={{base: "lg", lg: "2xl"}}>{categoryLabel}</Text></Pressable>
						) : (
							<Text maxWidth="80%" mb={1} bold fontSize={{base: "lg", lg: "2xl"}}>{categoryLabel}</Text>
						)}

						<Button size="xs" colorScheme="trueGray" variant="ghost"
						        onPress={() => hideCategory(libraryUrl, key, user)}
						        startIcon={<Icon as={MaterialIcons} name="close" size="xs" mr={-1.5}/>}>{translate('general.hide')}</Button>
					</HStack>
					<FlatList
						horizontal
						data={newArr}
						renderItem={({item}) => renderRecords(item, user, libraryUrl, discoveryVersion)}
						initialNumToRender={5}
						ListFooterComponent={loadMore(categoryLabel, categoryKey, libraryUrl, categorySource, recordCount, discoveryVersion)}
					/>
				</View>
			)
		}
	}

	return null;

}

export default DisplayBrowseCategory;