#!/usr/bin/env bash
sudo rm -rf /tmp/exporttst
mkdir -p /tmp/exporttst
sudo rm -rf /tmp/exporttst2
mkdir -p /tmp/exporttst2
./isdsbr isdsbr:backup:local /tmp/exporttst
./isdsbr isdsbr:convert /tmp/exporttst /tmp/exporttst2
