insert into account_profiles (id, name, driver, loginConfiguration, authenticationMethod, vendorOpacUrl, patronApiUrl, recordSource, weight, ils) VALUES (2, 'ils', 'MockILS', 'barcode_pin', 'ils', 'http://unit_test.localhost', 'http://unit_test.localhost', 'ils', 2, 'mock');
update library set accountProfileId = 2 where libraryId = 1;
