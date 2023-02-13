#!/usr/bin/env bash
# sudo rm -rf /home/jsanford/fedora_export
# mkdir -p /home/jsanford/fedora_export
sudo rm -rf /home/jsanford/dspace_import
mkdir -p /home/jsanford/dspace_import
# ./isdsbr isdsbr:export /home/jsanford/fedora_export
./isdsbr isdsbr:crosswalk /home/jsanford/fedora_export /home/jsanford/dspace_import
