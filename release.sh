#!/bin/bash
# Tag a commit and push it to origin

read -p "Enter version number (without v): "  version
[ -n "$version" ] || exit 1

echo $version
git commit --allow-empty -a -m "Release $version"
git tag "v$version"
git push origin master
git push origin "v$version"
