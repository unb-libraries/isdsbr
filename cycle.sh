#!/usr/bin/env bash
sudo rm -rf /tmp/exporttst
mkdir -p /tmp/exporttst
sudo rm -rf /tmp/exporttst2
mkdir -p /tmp/exporttst2
./isdsbr isdsbr:export /tmp/exporttst --max-items-per-collection=25
./isdsbr isdsbr:crosswalk /tmp/exporttst /tmp/exporttst2
