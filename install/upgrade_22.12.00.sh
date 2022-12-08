# mod evasive is causing issues with sites that have lots of book covers on one page. Not installing.
# configure mod evasive
#yum install mod_evasive -y
#cp mod_evasive.conf /etc/httpd/conf.d/mod_evasive.conf
yum remove mod_evasive -y

# mod security is causing issues with file uploads.  Not installing.
#configure mod security
#yum install mod_security -y
yum remove mod_security -y
