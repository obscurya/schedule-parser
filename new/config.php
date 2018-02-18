<?php

function debug($array) {
    echo '<pre>' . print_r($array, true) . '</pre>';
}

function pretty_print($json) {
    debug(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}