FROM mariadb:11.8

ADD my.cnf /etc/mysql/my.cnf
# 只會新增不會執行故註解
#ADD init-user.sh /docker-entrypoint-initdb.d/init-user.sh

RUN apt-get update && \
    apt-get -y install tzdata cron openssh-client expect

RUN cp /usr/share/zoneinfo/Asia/Taipei /etc/localtime && \
    echo "Asia/Taipei" > /etc/timezone

EXPOSE 3306
