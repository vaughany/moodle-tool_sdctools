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
 * A page to give full cross-Moodle details for a given course.
 *
 * @package    tool_sdctools
 * @copyright  2013-2015 Paul Vaughan {@link http://commoodle.southdevon.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define( 'NO_OUTPUT_BUFFERING', true );

require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/config.php' );
require_once( $CFG->libdir . '/adminlib.php' );
require_once( $CFG->libdir . '/blocklib.php' );
require_once( '../../../blocks/leap/locallib.php' );

require_once( 'locallib.php' );

admin_externalpage_setup( 'toolsdctools' );


echo $OUTPUT->header();

echo $OUTPUT->heading( get_string( 'pageheader', 'tool_sdctools' ) );

echo sdctools_tableofcontents( 'leapchecker' );

echo $OUTPUT->box_start();
echo $OUTPUT->heading( get_string( 'leapchecks', 'tool_sdctools' ) );



// Enrolment plugin.
// TODO: lots of options for checking the configuration (e.g. course code/s) and addition to courses.
echo '<h4>Leap Enrolment Plugin</h4>';

$enrols = enrol_get_plugins( false );
$leapenrolinstalled = false;
foreach ( $enrols as $enrol => $instance ) {
    if ( $enrol == 'leap' ) {
        $leapenrolinstalled = true;
        continue;
    }
}

$out = '';
if ( $leapenrolinstalled ) {
    $out .= '<p>The Leap enrolment plugin is installed ';

    // Check to see if it's enabled or not.
    if ( enrol_is_enabled( 'leap' ) ) {
        $out .= 'and enabled.</p>';
    } else {
        $out .= '<strong>but not enabled</strong>.</p>';
    }

} else {
    $out .= '<p><strong>Problem:</strong> The Leap enrolment plugin is not installed.</p>';
}
echo $out;



// Block.
echo '<h4>Leap Block</h4>';

$blocks = $PAGE->blocks->get_installed_blocks();
$leapblockinstalled = false;
foreach ( $blocks as $block ) {
    if ( $block->name == 'leap' ) {
        $leapblockinstalled = true;
        continue;
    }
}

$out = '';
if ( $leapblockinstalled ) {
    $out .= '<p>The Leap block is installed, ';

    $leap_url = get_config( 'block_leap', 'leap_url' );
    if ( empty( $leap_url ) ) {
        $out .= 'but the <strong>\'Leap URL\' config setting is not set</strong>, ';
    } else {
        $out .= 'and the \'Leap URL\' config setting is set, ';
    }

    $auth_username = get_config( 'block_leap', 'auth_username' );
    if ( empty( $auth_username ) ) {
        $out .= 'and the <strong>\'Leap user name\' config setting is not set</strong>, ';
    } else {
        $out .= 'and the \'Leap user name\' config setting is set, ';
    }

// auth token, requires leap block locallib.
    $auth_token = get_auth_token();




    $out .= '</p>';
} else {
    $out .= '<p><strong>Problem:</strong> The Leap block is not installed.</p>';
}
echo $out;

$out = '<ul>';
//if ( $leapblockinstalled ) {
    $courses = $DB->get_records( 'course' );
    foreach ( $courses as $course ) {
        $out .= '<li>' . html_writer::link( new moodle_url( '/course/view.php', array( 'id' => $course->id ) ), $course->fullname . ' (' . number_format( $course->id ) . ')' ) . '</li>';

    }
//}
$out .= '</ul>';
echo $out;



// Web services.
echo '<h4>Leap Web Services</h4>';
$out = '';
if ( $leapwebservicesinstalled = $DB->get_record( 'config_plugins', array( 'plugin' => 'local_leapwebservices', 'name' => 'version' ) ) ) {
    $out .= '<p>The Leap Webservices plugin is installed.</p>';
} else {
    $out .= '<p><strong>Problem:</strong> The Leap Webservices plugin is not installed.</p>';
}
echo $out;


echo $OUTPUT->box_end();
echo $OUTPUT->footer();
