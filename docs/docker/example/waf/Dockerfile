FROM nginx:1.21.4

COPY nginx.conf /etc/nginx/nginx.conf
COPY modsecurity/modsecurity.conf /etc/nginx/modsecurity/modsecurity.conf
COPY modsecurity/owasp-install.sh /etc/nginx/modsecurity/owasp-install.sh

RUN apt-get update && \
    apt-get -y install tzdata wget git build-essential libpcre3 libpcre3-dev libssl-dev libtool autoconf apache2-dev libxml2-dev libcurl4-openssl-dev automake pkgconf zlib1g-dev

RUN cp /usr/share/zoneinfo/Asia/Taipei /etc/localtime && \
    echo "Asia/Taipei" > /etc/timezone

RUN rm -rf /var/cache/apk/*

WORKDIR /opt
RUN git clone --depth 1 -b v3/master https://github.com/SpiderLabs/ModSecurity

WORKDIR /opt/ModSecurity
RUN git submodule init && git submodule update && \
    ./build.sh && \
    ./configure && \
    make && \
    make install

WORKDIR /opt
RUN git clone --depth 1 https://github.com/SpiderLabs/ModSecurity-nginx.git && \
    wget http://nginx.org/download/nginx-1.21.4.tar.gz && \
    tar zxvf nginx-1.21.4.tar.gz

WORKDIR /opt/nginx-1.21.4
RUN ./configure --with-compat --add-dynamic-module=../ModSecurity-nginx && \
    make modules && \
    cp objs/ngx_http_modsecurity_module.so /etc/nginx/modules/

RUN cp /opt/ModSecurity/unicode.mapping /etc/nginx/modsecurity/unicode.mapping

WORKDIR /etc/nginx/modsecurity

EXPOSE 80