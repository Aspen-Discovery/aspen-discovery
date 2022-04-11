import React from 'react';

const userContext = React.createContext({
	user: {},
	library: {},
	location: {},
	updateUser: () => {},
});

export { userContext };