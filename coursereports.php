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
 * @copyright  2013 Paul Vaughan {@link http://commoodle.southdevon.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/adminlib.php');

require_once('locallib.php');

admin_externalpage_setup('toolsdctoolscourse');

$cid = optional_param('id', false, PARAM_INT);

if (empty($CFG->loginhttps)) {
    $securewwwroot = $CFG->wwwroot;
} else {
    $securewwwroot = str_replace('http:', 'https:', $CFG->wwwroot);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pageheader', 'tool_sdctools'));

echo sdctools_tableofcontents();


/* If we have a course ID, print a nice report about that course. */
if ($cid) {
    //print ('got a cid: '.$cid);

    $course = $DB->get_record('course', array('id' => $cid), '*');

    echo $OUTPUT->box_start();
    echo $OUTPUT->heading(get_string('coursereportfor', 'tool_sdctools').$course->fullname);

    echo '<dl>';
    echo '<dt>Course Name/s</dt>';
    $buttons = array();
    $buttons[] = html_writer::link(new moodle_url('/course/view.php',
                    array('id' => $cid)),
                    html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/hide'),
                    'alt' => get_string('view'), 'class' => 'iconsmall')), array('title' => get_string('view')));
    $buttons[] = html_writer::link(new moodle_url('/course/edit.php',
                    array('id' => $cid)),
                    html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/edit'),
                    'alt' => get_string('edit'), 'class' => 'iconsmall')), array('title' => get_string('edit')));

    echo '  <dd><b>Full:</b> '.$course->fullname.' '.implode(' ', $buttons).'</dd>';
    echo '  <dd><b>Short:</b> '.$course->shortname.'</dd>';
    
    $coursecategory = $DB->get_record('course_categories', array('id' => $course->category), 'name');
    echo '  <dd><b>Category:</b> '.$coursecategory->name.'</dd>';
    echo '  <dd><b>ID:</b> '.$course->id.'</dd>';
    $idnumber = ($course->idnumber == '') ? '<i>(blank)</i>' : $course->idnumber; 
    echo '  <dd><b>ID number:</b> '.$idnumber.'</dd>';
    $visible = ($course->visible == 1) ? get_string('yes') : get_string('no');
    echo '  <dd><b>Visible:</b> '.$visible.'</dd>';
    echo '</dl>';

    echo '<dl>';
    echo '<dt>Created and Modified</dt>';
    echo '  <dd><b>Creation date:</b> '.strftime(get_string('strftimedaydate'), $course->timecreated).' ('.sdctools_timeago($course->timecreated).' ago)</dd>';
    echo '  <dd><b>Last modified date:</b> '.strftime(get_string('strftimedaydate'), $course->timemodified).' ('.sdctools_timeago($course->timemodified).' ago)</dd>';
    echo '</dl>';


    echo '<dl>';
    echo '<dt>Details</dt>';
    echo '  <dd><b>Format:</b> '.ucfirst($course->format).'</dd>';
    if ($course->format == 'weeks') {
        echo '  <dd><b>Start Date:</b> '.strftime(get_string('strftimedaydate'), $course->startdate).'</dd>';
    }
    $courseformat = $DB->get_record('course_format_options', array('courseid' => $cid, 'name' => 'numsections'), 'value');
    echo '  <dd><b>Sections:</b> '.$courseformat->value.'</dd>';
    $mods = unserialize($course->modinfo);
    $cmods = count($mods);
    $cmods =42;
    echo '  <dd><b>Modules:</b> '.$cmods.'</dd>';
    echo '  <dd><b>Average Modules per Section:</b> '.number_format($cmods/$courseformat->value, 1).'</dd>';
    
    echo '</dl>';

    echo $OUTPUT->box_end();

print_object($course);
//print_object(unserialize($course->modinfo));

} else { // If we don't have the $cid, print a nice form.

    if ($ccc = $DB->get_records('course', null, 'fullname', 'id, shortname, fullname, category')) {
        foreach ($ccc as $cc) {
            if ($cc->category) {
                $courses[$cc->id] = format_string(get_course_display_name_for_list($cc));
            } else {
                $courses[$cc->id] = format_string($cc->fullname) . ' (Site)';
            }
        }
    }

    // Courses in alphanumeric order by name.
    $courses_alpha = $courses;
    asort($courses_alpha);
    $courses_alpha = sdctools_idprefix($courses_alpha);

    // Courses in numeric order by course ID.
    $courses_numeric = sdctools_idprefix($courses);
    ksort($courses_numeric);

    unset($courses);

    // Choose a course from a list in course name order.
    echo $OUTPUT->box_start();
    echo $OUTPUT->heading(get_string('coursesby', 'tool_sdctools'));
    echo '<p>'.get_string('byname', 'tool_sdctools').'</p>';
    echo '<form class="courseselectalphaform" action="coursereports.php" method="get">'."\n";
    echo "  <div>\n";
    echo html_writer::label(get_string('selectacourse'), 'menuid', false, array('class' => 'accesshide'));
    echo html_writer::select($courses_alpha, 'id', $cid, false);
    echo '  <input type="submit" value="'.get_string('getcoursereport', 'tool_sdctools').'" />';
    echo '  </div>';
    echo '</form>';

    // Choose a course from a list in course name order.
    echo '<p>&nbsp;</p><p>'.get_string('byid', 'tool_sdctools').'</p>';
    echo '<form class="courseselectnumericform" action="coursereports.php" method="get">'."\n";
    echo "  <div>\n";
    echo html_writer::label(get_string('selectacourse'), 'menuid', false, array('class' => 'accesshide'));
    echo html_writer::select($courses_numeric, 'id', $cid, false);
    echo '  <input type="submit" value="'.get_string('getcoursereport', 'tool_sdctools').'" />';
    echo '  </div>';
    echo '</form>';
    echo $OUTPUT->box_end();

} // end if $cid

echo $OUTPUT->footer();
