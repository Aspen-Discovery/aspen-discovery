#!/bin/bash
echo "Preparing Selenium IDE test file..."
node prepare-selenium-tests.js
echo ""
echo "If no errors were reported, the test file has been prepared successfully."
echo "Please open AspenDiscovery.processed.side in Selenium IDE."
echo ""
read -p "Press Enter to continue..."
