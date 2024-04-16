#!/usr/bin/env bash
set -eu
declare -a directories=(
  "html/libraries/billboard/dist-esm"
  "html/libraries/billboard/src"
  "html/libraries/billboard/types"
  "html/libraries/billboard/dist/plugin"
  "html/libraries/billboard/dist/theme"
)
counter=0
for directory in "${directories[@]}"
  do
    if [ -d $directory ]; then
      echo "Deleting $directory"
      rm -rf $directory
      counter=$((counter+1))
    fi
  done
declare -a files=(
  "html/libraries/billboard/CONTRIBUTING.md"
  "html/libraries/billboard/README.md"
  "html/libraries/billboard/LICENSE"
  "html/libraries/billboard/package.json"
  "html/libraries/billboard/dist/package.json"
)
counter=0
for file in "${files[@]}"
  do
    if [[ -f $file ]]; then
      echo "Deleting $file"
      rm $file
      counter=$((counter+1))
    fi
  done
