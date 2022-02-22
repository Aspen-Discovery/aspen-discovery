## Remove a site from the server

#### Remove the site files
To remove site files, run `sudo rm -rf /usr/local/aspen-discovery/sites/{sitename}`

#### Remove the data directory
To remove the data directory run `sudo rm -rf /data/aspen-discovery/{sitename}`

#### Delete the MySQL database
To delete the MySQL database:
```
> sudo su
> mysql -u root
> drop database {name_of_site_database}
> exit
```

#### Delete site specific logs
To remove logs run `sudo rm -rf /var/log/aspen-discovery/{sitename}`

#### Delete solr indexes
First stop the relevant solr server: `sudo /usr/local/aspen-discovery/sites/default/solr-7.6.0/bin/solr stop -p {portnumber}`

To delete the solr cores run:
```
sudo /usr/local/aspen-discovery/sites/default/solr-7.6.0/bin/solr delete -c course_reserves
sudo /usr/local/aspen-discovery/sites/default/solr-7.6.0/bin/solr delete -c events
sudo /usr/local/aspen-discovery/sites/default/solr-7.6.0/bin/solr delete -c genealogy
sudo /usr/local/aspen-discovery/sites/default/solr-7.6.0/bin/solr delete -c grouped_works
sudo /usr/local/aspen-discovery/sites/default/solr-7.6.0/bin/solr delete -c lists
sudo /usr/local/aspen-discovery/sites/default/solr-7.6.0/bin/solr delete -c open_archives
sudo /usr/local/aspen-discovery/sites/default/solr-7.6.0/bin/solr delete -c website_pages
```

#### Disable site in Apache
To list active sites in Apache run `a2query -s`

When the Apache sitename has been identified run `a2dissite {apache_sitename}`

#### Remove cron.d symbolic link
To remove the symbolic link run `sudo rm -rf /etc/cron.d/{sitename}`
