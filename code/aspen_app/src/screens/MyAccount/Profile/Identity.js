import { Box, Text } from "native-base";
import React from "react";

// custom components and helper files

const Profile_Identity = (props) => {
  return (
    <Box pb={5}>
      <Text bold>First Name</Text>
      <Text>{props.firstName}</Text>
      <Text bold mt={2}>
        Last Name
      </Text>
      <Text>{props.lastName}</Text>
    </Box>
  );
};

export default Profile_Identity;