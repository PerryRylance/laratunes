<?php

header('Content-type: text/plain');

echo shell_exec('/usr/local/bin/olaf stats');
