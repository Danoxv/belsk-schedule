<?php

$input = getopt('s:', []);

$scriptName = $input['s'];

require_once ROOT . '/src/scripts/' . $scriptName;