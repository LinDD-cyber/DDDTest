FROM mariadb:11.8

ADD my.cnf /etc/mysql/my.cnf

RUN apt-get update && \
    apt-get -y install tzdata cron openssh-client expect

RUN cp /usr/share/zoneinfo/Asia/Taipei /etc/localtime && \
    echo "Asia/Taipei" > /etc/timezone

EXPOSE 3306
