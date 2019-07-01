<?php
//////////////////////////////////////////////
// script for fetching the funny pics from
// phun.org to a local dir for my desktop
// background and screen saver
//////////////////////////////////////////////

// set the error handler to print nice things
set_error_handler(function ($code, $message, $file, $line) {
    bla("$file:$line\t$message");
}, E_ALL | E_STRICT | E_NOTICE);

// configs
const URL   = 'http://www.phun.org/newspics/funny_friday_10/'; //<< url root
const DIR   = '/Users/chase/Pictures/phun.org/'; //<< local dir root
const MAX   = 100000; //<< max pic number to exhaust
const CODES = 10; //<< max response codes to allow before keeling
const SLEEP = [1, 10]; //<< rand bounds for sleepy time

// get the current pic numbers we already have
$files = array_filter(scandir(DIR), function ($file) {
    return $file[0] !== '.';
});

$numbers = array_map(function ($file) {
    return str_replace('.jpg', '', $file);
}, $files);

// define the upper/lower bounds to ping
$lower = min($numbers);
$upper = max($numbers);

bla("lower: $lower");
bla("upper: $upper");

// keep track of the last x number of response codes
$lowers = [];
$uppers = [];

// begin the forever loop
do {
    // get rid of oldest code
    shift($lowers);
    shift($uppers);

    // shift the bounds out
    $lower--;
    $upper++;

    // check if s'ok
    $do_low = sok($lower, $lowers);
    $do_up  = sok($upper, $uppers);

    // if not ok for either
    if (!$do_low && !$do_up) {
        // kill switch
        bla("no more pics to find");

        break;
    }

    // is ok for lower?
    if ($do_low) {
        $lowers[$lower] = curl($lower);

        snooze();
    }

    // is ok for upper?
    if ($do_up) {
        $uppers[$upper] = curl($upper);

        snooze();
    }
} while (true);

bla('done');

// check if the pic is in range and we didnt fail too many times
// trying to get it already
function sok($number, $codes) {
    if (!in($number, 1, MAX)) {
        return false;
    }

    if (count($codes) >= CODES && !in_array(200, $codes)) {
        return false;
    }

    return true;
}

// check if a number is in a given range
function in($number, $min, $max) {
    return $number >= $min && $number <= $max;
}

// i probably should have just called it println()
function bla($message) {
    print("$message\n");
}

// shifts the first entry off the response codes array
function shift(&$codes) {
    if (count($codes) > CODES) {
        array_shift($codes);
    }
}

// wrapper for sleep(rand())
function snooze() {
    $sleep = rand(SLEEP[0], SLEEP[1]);

    bla("sleep $sleep");

    sleep($sleep);
}

// builds the pic file name
function pic($number) {
    return "$number.jpg";
}

// builds the pic url
function url($number) {
    return URL . pic($number);
}

// builds the pic local image path
function path($number) {
    return DIR . pic($number);
}

// performs the curl, returns the response code
function curl($number) {
    bla("curl $number");

    $url  = url($number);
    $path = path($number);

    bla("url  $url");
    bla("path $path");

    $curl = curl_init($url);
    $file = fopen($path, 'wb');

    curl_setopt($curl, CURLOPT_FILE, $file);
    curl_setopt($curl, CURLOPT_HEADER, 0);

    curl_exec($curl);

    $code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

    bla("code $code");

    curl_close($curl);
    fclose($file);

    if ($code !== 200 && !unlink($path)) {
        bla("unlink fail $path");
    }

    return $code;
}