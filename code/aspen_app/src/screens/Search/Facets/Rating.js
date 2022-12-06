import { MaterialIcons } from "@expo/vector-icons";
import _ from "lodash";
import { HStack, Icon, Pressable, Text, VStack } from "native-base";
import React, { Component } from "react";
import { ScrollView } from "react-native";
import { Rating } from "react-native-elements";

// custom components and helper files
import { loadingSpinner } from "../../../components/loadingSpinner";
import { userContext } from "../../../context/user";
import { addAppliedFilter, removeAppliedFilter } from "../../../util/search";

export default class Facet_Rating extends Component {
  static contextType = userContext;

  constructor(props, context) {
    super(props, context);
    this.state = {
      isLoading: true,
      appliedRating: "",
      value: "",
      item: this.props.data,
      category: this.props.category,
      updater: this.props.updater,
      stars: [
        {
          label: "fiveStar",
          value: "5",
        },
        {
          label: "fourStar",
          value: "4",
        },
        {
          label: "threeStar",
          value: "3",
        },
        {
          label: "twoStar",
          value: "2",
        },
        {
          label: "oneStar",
          value: "1",
        },
        {
          label: "Unrated",
          value: "0",
        },
      ],
    };
    this._isMounted = false;
  }

  componentDidMount = async () => {
    this._isMounted = true;
    this.setState({
      isLoading: false,
    });

    const { item } = this.state;
    let value = "";
    if (_.find(item, ["isApplied", true])) {
      const appliedFilterObj = _.find(item, ["isApplied", true]);
      value = appliedFilterObj["value"];
    }
    this.setState({
      value,
    });
  };

  componentWillUnmount() {
    this._isMounted = false;
  }

  getRatingCount(star) {
    const { item } = this.state;
    let results = 0;
    if (_.find(item, ["value", star])) {
      results = _.find(item, ["value", star]);
      results = results["count"];
    }
    return results;
  }

  updateSearch = (star) => {
    const { category, value } = this.state;
    if (star === value) {
      removeAppliedFilter(category, star);
      this.setState({
        value: "",
      });
    } else {
      addAppliedFilter(category, star, false);
      this.setState({
        value: star,
      });
    }
    this.props.updater(this.state.category, star);
  };

  render() {
    const stars = this.state.stars;

    if (this.state.isLoading) {
      return loadingSpinner();
    }

    return (
      <ScrollView>
        <VStack space={2}>
          {stars.map((star, index) => (
            <Pressable
              onPress={() => this.updateSearch(star.label)}
              p={0.5}
              py={2}
            >
              <HStack space={3} justifyContent="flex-start" alignItems="center">
                {this.state.value === star.label ? (
                  <Icon
                    as={MaterialIcons}
                    name="radio-button-checked"
                    size="lg"
                    color="primary.600"
                  />
                ) : (
                  <Icon
                    as={MaterialIcons}
                    name="radio-button-unchecked"
                    size="lg"
                    color="muted.400"
                  />
                )}
                <Rating imageSize={20} readonly startingValue={star.value} />
                <Text color="darkText" ml={2}>
                  ({this.getRatingCount(star.label)})
                </Text>
              </HStack>
            </Pressable>
          ))}
        </VStack>
      </ScrollView>
    );
  }
}