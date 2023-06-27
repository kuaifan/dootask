#!/bin/bash

# 用于删除历史纪录中指定文件的脚本

# 确认用户输入参数
if [ $# -ne 1 ]
then
  echo "Usage: rewrite-history.sh <file-to-remove>"
  exit 1
fi

# 获取文件名和目录路径
file_to_remove=$1
dir_path=$(dirname "${file_to_remove}")

# 移动到 Git 根目录
cd $(git rev-parse --show-toplevel)

# 移除指定文件
git filter-branch --force --index-filter "git rm --cached --ignore-unmatch ${file_to_remove}" --prune-empty --tag-name-filter cat -- --all

# 删除备份文件
rm -Rf .git/refs/original/

# 清理垃圾文件
git reflog expire --expire=now --all && git gc --prune=now --aggressive

# 重置 Ignored 文件
echo "**/${dir_path}/${file_to_remove}" >> .gitignore

# 提交更改并强制推送到远程仓库
git add .
git commit -m "Remove file '${file_to_remove}' from history"
git push origin --force --all


