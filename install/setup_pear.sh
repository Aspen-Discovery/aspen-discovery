#!/bin/bash
## Begin Script

## Install PEAR Packages
pear upgrade pear
pear channel-discover pear.horde.org
pear channel-update pear.horde.org
pear install Horde/Horde_Yaml-beta
