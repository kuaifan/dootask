#!/bin/sh

new_password=$1

GreenBG="\033[42;37m"
Font="\033[0m"

new_encrypt=$(date +%s%N | md5sum | awk '{print $1}' | cut -c 1-6)
if [ -z "$new_password" ]; then
    new_password=$(date +%s%N | md5sum | awk '{print $1}' | cut -c 1-16)
fi
md5_password=$(echo -n `echo -n $new_password | md5sum | awk '{print $1}'`$new_encrypt | md5sum | awk '{print $1}')

content=$(echo "select \`email\` from ${MYSQL_PREFIX}users where \`userid\`=1;" | mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE)
account=$(echo "$content" | sed -n '2p')

if [ -z "$account" ]; then
    echo "错误：账号不存在！"
    exit 1
fi

mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE <<EOF
update ${MYSQL_PREFIX}users set \`encrypt\`='${new_encrypt}',\`password\`='${md5_password}' where \`userid\`=1;
EOF

echo "账号: ${GreenBG}${account}${Font}"
echo "密码: ${GreenBG}${new_password}${Font}"
