#!/bin/bash

/usr/bin/mysqladmin -u root password '12345678' && \
/usr/bin/mysql -u root -p12345678 -e "GRANT ALL ON *.* TO ai4dt@$(%) identified by 'ai4dt@eis';" && \
/usr/bin/mysql -u root -p12345678 -e "FLUSH PRIVILEGES;"
