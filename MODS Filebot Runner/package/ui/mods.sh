#!/bin/sh

stdbuf -oL echo "Executing FileBot Task 1 as" $(whoami)
stdbuf -oL echo "___________________________________________________________________"
stdbuf -oL echo
stdbuf -oL echo "Please Wait. FileBot needs some time to start."
stdbuf -oL echo
stdbuf -oL echo

sleep 1

stdbuf -oL /usr/local/filebot-node/task 1

stdbuf -oL echo
stdbuf -oL echo "___________________________________________________________________"