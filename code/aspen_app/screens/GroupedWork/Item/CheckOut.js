async function checkoutItem(id, source) {
    const api = create({ baseURL: 'https://aspen-test.bywatersolutions.com/API', timeout: 10000 });
    const response = await api.get('/UserAPI?method=checkoutItem', { username: global.userKey, password: global.secretKey, sessionId: global.sessionId, itemSource: source, itemId: id, patronId: patronId });

    if(response.ok) {
        const result = response.data;
        const fetchedData = result.result;

        if(fetchedData.success == true) {
            Toast.show({
                title: "Item checked out",
                description: fetchedData.message,
                status: "success",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top",

            });



        } else {
            Toast.show({
                title: "Unable to checkout item",
                description: fetchedData.message,
                status: "error",
                isClosable: true,
                duration: 8000,
                accessibilityAnnouncement: fetchedData.message,
                zIndex: 9999,
                placement: "top"
            });
        }

    } else {
        const fetchedData = response.problem;
        console.log(fetchedData);
        return fetchedData;
    }
}