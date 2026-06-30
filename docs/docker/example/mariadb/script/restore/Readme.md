# 還原腳本

## 第一步 建立mariadb容器

- cd 到與 docker-compose.yml 同等位之處

```shell
docker-compose up -d mariadb
```

## 第二步 放置欲還原的資料

- 還原資料由1全量備份與n個連續的增量備份構成
    - full_20211030.tar.gz
    - incr_20211031.tar.gz
    - incr_20211101.tar.gz
    - incr_20211102.tar.gz
    - incr_20211103.tar.gz

- 將此些檔案放置在`./mariadb/backup`下

```shell
markq@certificationmark-7791-0:/var/www/html/CertificationMark/docker/markq/mariadb/backup$ ll
-rw-r--r-- 1 root  root      994 Nov  2 19:00 bak.log
drwxrwxr-x 7 markq markq    4096 Nov  2 19:00 data/
-rw-rw-r-- 1 markq markq 9153204 Nov  1 10:51 full_20211030.tar.gz
-rw-rw-r-- 1 markq markq 1995383 Nov  1 10:51 incr_20211031.tar.gz
-rw-rw-r-- 1 markq markq 1995364 Nov  1 10:51 incr_20211101.tar.gz
-rw-r--r-- 1 markq markq 2041691 Nov  2 00:43 incr_20211102.tar.gz
-rw-r--r-- 1 markq markq 1990808 Nov  2 19:00 incr_20211103.tar.gz
```

- 並在`./mariadb/restore/restore_list.txt`中填入對應的檔案名稱

```shell
markq@certificationmark-7791-0:/var/www/html/CertificationMark/docker/markq/mariadb/script/restore$ cat restore_list.txt
full_20211030
incr_20211031
incr_20211101
incr_20211102
incr_20211103
```

## 第三步 還原資料庫

- 進入mariadb container中

```shell
markq@certificationmark-7791-0:~$ docker exec -it markq_mariadb bash
```

**注意!!!**

**請在mariadb container中執行以下指令**

**以下指令會清除所有原本在docker-compose.yml中mariadb所定義的空間資料!!(/var/lib/markq_mysql)**

```yaml
    ~~(略)~~
    ports:
      - "3306:3306"
    volumes:
      - /var/lib/markq_mysql:/var/lib/mysql
    ~~(略)~~
```

**請思考是否需事先備份原位置資料**

```shell
root@c956819062fb:/# bash /script/restore/restore.sh
```

若出現以下情況，是還原腳本中含有windows的換行結尾`\r\n`，將其改為`\n`就可以正常運行。

```shell
/script/restore/restore.sh: line 2: $'\r': command not found
: invalid optionrestore.sh: line 3: set: -
set: usage: set [-abefhkmnptuvxBCHP] [-o option-name] [--] [arg ...]
/script/restore/restore.sh: line 4: $'\r': command not found
: numeric argument required line 7: exit: 0
```

若出現以下文字才是**正確還原成功**

```text
Restore completed.
exit and restart mysql container......
```

否則很有可能是增量備份與全量備份無法對應，**增量備份無法使用**，並出現以下訊息

```shell
mariabackup based on MariaDB server 10.4.11-Mar````iaDB debian-linux-gnu (x86_64)
[00] 2021-12-06 11:46:20 incremental backup from 183217893 is enabled.
[00] 2021-12-06 11:46:20 cd to /backup/data/full_20211204/
[00] 2021-12-06 11:46:20 This target seems to be already prepared.
[00] 2021-12-06 11:46:20 error: This incremental backup seems not to be proper for the target. Check 'to_lsn' of the target and 'from_lsn' of the incremental.
```

## 第四步 重啟資料庫

```shell
root@c956819062fb:/# exit
markq@certificationmark-7791-0:~$ docker restart markq_mariadb
```