#!/bin/bash

set -ev
MODSECURITY_PATH=/etc/nginx/modsecurity
OWASP_PATH=/etc/nginx/modsecurity/owasp-modsecurity-crs

git clone https://github.com/SpiderLabs/owasp-modsecurity-crs.git $OWASP_PATH

cp $OWASP_PATH/crs-setup.conf.example $OWASP_PATH/crs-setup.conf
cp $OWASP_PATH/rules/REQUEST-900-EXCLUSION-RULES-BEFORE-CRS.conf.example $OWASP_PATH/rules/REQUEST-900-EXCLUSION-RULES-BEFORE-CRS.conf && \
cp $OWASP_PATH/rules/RESPONSE-999-EXCLUSION-RULES-AFTER-CRS.conf.example $OWASP_PATH/rules/RESPONSE-999-EXCLUSION-RULES-AFTER-CRS.conf

echo $'include modsecurity.conf\ninclude owasp-modsecurity-crs/crs-setup.conf' > $MODSECURITY_PATH/modsecurity_includes.conf

for f in $(ls -1 $OWASP_PATH/rules/ | grep -E "^(RESPONSE|REQUEST)-.*\.conf$"); do
  echo "include owasp-modsecurity-crs/rules/${f}" >> modsecurity_includes.conf;
done

cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.new
sed -i 's/\/etc\/nginx\/modsecurity\/modsecurity.conf/\/etc\/nginx\/modsecurity\/modsecurity_includes.conf/g' /etc/nginx/nginx.conf.new
cp -f /etc/nginx/nginx.conf.new /etc/nginx/nginx.conf

## Allowed methods of Restful api PUT DELETE
METHOD_START=$(grep -n "id:900200" $OWASP_PATH/crs-setup.conf |  cut -d : -f 1)
METHOD_START="$(($METHOD_START-1))"
METHOD_END="$(($METHOD_START+6))"
sed -i "$METHOD_START,$METHOD_END s/^#//" $OWASP_PATH/crs-setup.conf
sed -i "$METHOD_END,$METHOD_END s/'tx.allowed_methods=GET HEAD POST OPTIONS'/'tx.allowed_methods=GET HEAD POST OPTIONS PUT PATCH DELETE'/" $OWASP_PATH/crs-setup.conf

## This version of ModSecurity was not compiled with GeoIP support
GEO_START=$(grep -n "SecRule TX:HIGH_RISK_COUNTRY_CODES" $OWASP_PATH/rules/REQUEST-910-IP-REPUTATION.conf | cut -d : -f 1)
GEO_END="$(($GEO_START+21))"
sed -i "$GEO_START,$GEO_END s/^/#/" $OWASP_PATH/rules/REQUEST-910-IP-REPUTATION.conf

nginx -s reload