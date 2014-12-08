#!/bin/bash

set -e
cd "`dirname "$0"`/../vendor/svt/votr"

if ! [ -d venv ]; then
  ${VOTR_VIRTUALENV:-virtualenv -p python3} venv
fi

venv/bin/pip install -r requirements.txt

