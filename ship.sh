#!/bin/bash
#
# リリース作業自動化コマンド
# 引数でバージョンを指定すると、下記のことをしてくれます。
#
# * ETHNA_VERSIONの数字を変更してコミット
# * git tag vx.y.z

set -e

prog=$(basename $0)
version_file=bootstrap.php

if [[ $# -eq 0 ]] || [[ $1 = "--help" ]] ; then
    echo "Usage: $prog <version_number>"
    echo ""
    echo "Example: $prog x.y.z"
    echo -n "Current Version: "
    grep ETHNA_VERSION $version_file
    exit 1
fi

ver=$1
set -x

echo shipping version $ver ...

perl -pi -e "s#v\d*\.\d*\.\d*#v$ver#" $version_file

git add $version_file
git commit -m "bump version to $ver"
git tag v${ver}
git push
git push --tags

