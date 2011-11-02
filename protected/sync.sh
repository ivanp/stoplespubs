#!/bin/sh
rsync -av --progress -e "ssh -vC" --relative controllers models commands admin views vendors/EZend components stop@stoplespubs.com:public_html/protected