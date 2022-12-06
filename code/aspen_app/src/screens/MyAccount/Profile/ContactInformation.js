import { Box, Text } from "native-base";
import React from "react";

// custom components and helper files

const Profile_ContactInformation = (props) => {
  return (
    <Box py={5}>
      <Text bold>Primary Phone</Text>
      <Text>{props.phone}</Text>
      <Text bold mt={2}>
        Primary Email
      </Text>
      <Text>{props.email}</Text>
    </Box>
  );
};

export default Profile_ContactInformation;