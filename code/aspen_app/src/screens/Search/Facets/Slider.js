import _ from "lodash";
import { Box, FormControl, HStack, Input } from "native-base";
import React, { Component } from "react";
import { ScrollView } from "react-native";

// custom components and helper files
import { loadingSpinner } from "../../../components/loadingSpinner";
import { userContext } from "../../../context/user";
import { translate } from "../../../translations/translations";
import { addAppliedFilter } from "../../../util/search";

export default class Facet_Slider extends Component {
  static contextType = userContext;

  constructor(props, context) {
    super(props, context);
    this.state = {
      isLoading: true,
      startValue: "*",
      endValue: "*",
      item: this.props.data,
      category: this.props.category,
      updater: this.props.updater,
    };
    this._isMounted = false;
  }

  componentDidMount = async () => {
    this._isMounted = true;

    this.appliedStartValue();
    this.appliedEndValue();

    this.setState({
      isLoading: false,
    });
  };

  componentWillUnmount() {
    this._isMounted = false;
  }

  updateValue = (type, value) => {
    const { category, updater } = this.state;

    this.setState(
      {
        [type]: value,
      },
      () => {
        this.updateFacet();
      }
    );
  };

  updateFacet = () => {
    const { startValue, endValue } = this.state;

    let value =
      "[" + this.state.startValue + "+TO+" + this.state.endValue + "]";
    if (!startValue && endValue) {
      value = "[*+TO+" + this.state.endValue + "]";
    } else if (startValue && !endValue) {
      value = "[" + this.state.startValue + "+TO+*]";
    } else if (!startValue && !endValue) {
      value = "[*+TO+*]";
    }
    addAppliedFilter(this.state.category, value, false);
    this.props.updater(this.state.category, value);
  };

  appliedStartValue = () => {
    const { item, category } = this.state;
    let value = 0.0;

    if (_.find(item, ["isApplied", true])) {
      const appliedFilterObj = _.find(item, ["isApplied", true]);
      value = appliedFilterObj["value"];
    }

    this.setState({
      startValue: value,
    });
  };

  appliedEndValue = () => {
    const { item, category } = this.state;
    let value = 5.0;

    if (_.find(item, ["isApplied", true])) {
      const appliedFilterObj = _.find(item, ["isApplied", true]);
      value = appliedFilterObj["value"];
    }

    this.setState({
      endValue: value,
    });
  };

  render() {
    if (this.state.isLoading) {
      return loadingSpinner();
    }

    return (
      <ScrollView>
        <Box safeArea={5}>
          <FormControl mb={2}>
            <HStack space={3} justifyContent="center">
              <Input
                size="lg"
                placeholder={translate("filters.from")}
                accessibilityLabel={translate("filters.from")}
                defaultValue={this.state.startValue}
                value={this.state.startValue}
                onChangeText={(value) => {
                  this.updateValue("startValue", value);
                }}
                w="50%"
              />
              <Input
                size="lg"
                placeholder={translate("filters.to")}
                accessibilityLabel={translate("filters.to")}
                defaultValue={this.state.endValue}
                value={this.state.endValue}
                onChangeText={(value) => {
                  this.updateValue("endValue", value);
                }}
                w="50%"
              />
            </HStack>
          </FormControl>
        </Box>
      </ScrollView>
    );
  }
}