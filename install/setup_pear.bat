;Install PEAR Packages
pear upgrade pear
pear install --onlyreqdeps Log
pear channel-discover pear.horde.org
pear channel-update pear.horde.org
pear install Horde/Horde_Yaml-beta
