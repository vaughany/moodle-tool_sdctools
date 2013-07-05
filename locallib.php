<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Link to SDC Tools admin page
 *
 * @package    tool_sdctools
 * @copyright  2013 Paul Vaughan {@link http://commoodle.southdevon.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Draw a common table of contents.
 */
function sdctools_tableofcontents() {
    global $OUTPUT, $pictures;
    
    $out = '';

    $out .= '<ul>';
    $out .= '<li><a href="index.php">'.get_string('pluginname', 'tool_sdctools').'</a></li>';
    $out .= '<li><a href="emails.php">'.get_string('emailchecks', 'tool_sdctools').'</a></li>';
    //$out .= '<li><a href="coursereports.php">'.get_string('coursereports', 'tool_sdctools').'</a>';
    //$out .= ' (<a href="coursereports.php?pictures=1">'.get_string('coursereportspictures', 'tool_sdctools').'</a>)</li>';

    $out .= '<li>'.html_writer::link(new moodle_url('coursereports.php'), get_string('coursereports', 'tool_sdctools'));

    $out .= ' ('.html_writer::link(new moodle_url('coursereports.php', array('pictures' => 1)), get_string('coursereportspictures', 'tool_sdctools')).')</li>';

    $out .= '<ul>';

    return $out;
}

/**
 * Pit the course's ID at the start of the name.
 */
function sdctools_idprefix($in) {
    $out = '';
    foreach ($in as $key => $value) {
        $out[$key] = $key.': '.$value;
    }

    return $out;
}

/**
 * A 'time ago' script.
 */
function sdctools_timeago($int, $ago = true) {

    $in = ($ago) ? (time() - $int) : $int;
    
    $secsyear = 60*60*24*365.25;
    $secsday  = 60*60*24;
    $secshour = 60*60;
    $secsmin  = 60;

    $years = intval($in / $secsyear);
    $remainder = $in % $secsyear;
    $days = intval($remainder / $secsday);
    $remainder = $remainder % $secsday;
    $hours = intval($remainder / $secshour);
    $remainder = $remainder % $secshour;
    $minutes = intval($remainder / $secsmin);
    $remainder = $remainder % $secsmin;
    $seconds = intval($remainder);
    
    $out = '';
    // Omit any period of time with no data.
    if ($years) {
        $out .= $years;
        $out .= ($years == 1) ? ' year, ' : ' years, ';
    }
    if ($days) {
        $out .= $days;
        $out .= ($days == 1) ? ' day, ' : ' days, ';
    }
    if ($hours) {
        $out .= $hours;
        $out .= ($hours == 1) ? ' hour, ' : ' hours, ';
    }
    if ($minutes) {
        $out .= $minutes;
        $out .= ($minutes == 1) ? ' minute, ' : ' minutes, ';
    }
    if ($seconds) {
        $out .= $seconds;
        $out .= ($seconds == 1) ? ' second' : ' seconds';
    }

    return $out;
}

/**
 * Trimming the ', ' off the end of a string.
 */
function sdctools_trimcomma($in) {
    $in = trim($in);
    if (substr($in, -1) == ',') {
        return  substr($in, 0, strlen($in)-1);
    } else {
        return $in;
    }
}