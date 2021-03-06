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
 * Admin plugin to do whatever we want!
 *
 * @package    tool_sdctools
 * @copyright  2013-2015 Paul Vaughan {@link http://commoodle.southdevon.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define( 'NO_OUTPUT_BUFFERING', true );

require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/config.php' );
require_once( $CFG->dirroot . '/course/lib.php' );
require_once( $CFG->libdir . '/adminlib.php' );

require_once( 'locallib.php' );

$choices        = array(5, 10, 25, 50, 100, 200);
$defaultchoice  = 10;

$numusers       = optional_param('numusers', $defaultchoice, PARAM_INT);
$numlogs        = optional_param('numlogs', $defaultchoice, PARAM_INT);

$numusers       = ( $numusers <= 0 || $numusers > 200 ) ? $defaultchoice : $numusers;
$numlogs        = ( $numlogs <= 0 || $numlogs > 200 ) ? $defaultchoice : $numlogs;

admin_externalpage_setup( 'toolsdctools' );

$securewwwroot = ( empty( $CFG->loginhttps ) ) ? $CFG->wwwroot : str_replace( 'http:', 'https:', $CFG->wwwroot );

echo $OUTPUT->header();
echo $OUTPUT->heading( get_string( 'pageheader', 'tool_sdctools' ) );

echo sdctools_tableofcontents( 'index' );


/* Some server details. */
echo $OUTPUT->box_start();
echo $OUTPUT->heading( get_string( 'serverdetailsheader', 'tool_sdctools' ) );

echo '<ul>';
if (is_readable('/proc/version') && $osver = @file('/proc/version')) {
    echo '<li><strong>'.get_string('operatingsystem', 'tool_sdctools').':</strong> '.$osver[0].'</li>';
}
/* For some reason, I can't use two file functions in the same if statement... */
if (is_readable('/proc/loadavg') && $loadavg = @file('/proc/loadavg')) {
    if (is_readable('/proc/cpuinfo') && $cpuinfo = @file('/proc/cpuinfo')) {
        $loads = explode(' ', $loadavg[0]);
        $cpucount = 0;
        foreach ($cpuinfo as $cpu) {
            if (preg_match('/processor/', $cpu)) {
                $cpucount++;
            }
        }
        echo '<li><strong>'.get_string('averageload', 'tool_sdctools').':</strong> '.
            get_string('averageloadexample', 'tool_sdctools').' '.$loads[0].' / '.$loads[1].' / '.$loads[2].' ['.$cpucount.
            '.0] (<a href="http://blog.scoutapp.com/articles/2009/07/31/understanding-load-averages">'.
            get_string('linuxcpuload', 'tool_sdctools').'</a>)</li>';
        echo '<li><strong>'.get_string('processes', 'tool_sdctools').':</strong> '.
            get_string('processesexample', 'tool_sdctools').' '.$loads[3].'</li>';
    }
}
echo '<li><strong>'.get_string('webserver', 'tool_sdctools').':</strong> '.$_SERVER["SERVER_SOFTWARE"].'</li>';
echo '<li><strong>'.get_string('phpversion', 'tool_sdctools').':</strong> '.phpversion().'</li>';
if (is_readable('/proc/uptime') && $uptime = @file('/proc/uptime')) {
    $utime = explode(' ', $uptime[0]);
    $out = sdctools_timeago($utime[0], false);
    echo '<li><strong>'.get_string('serveruptime', 'tool_sdctools').':</strong> '.$out.'</li>';
}

echo '<li><strong>php.ini:</strong><ul>';

$maxsize = ini_get('post_max_size');
$maxfilesize = ini_get('upload_max_filesize');
$sameornot = ($maxsize == $maxfilesize) ? ' (Same)' : ' (<span class="error">Different!</span>)' ;
echo '<li><strong>post_max_size:</strong> '.$maxsize.'</li>';
echo '<li><strong>upload_max_filesize:</strong> '.$maxfilesize.$sameornot.'</li>';

$maxinput     = ini_get('max_input_time');
$maxexec      = ini_get('max_execution_time');
$sameornot = ($maxinput == $maxexec) ? ' (Same)' : ' (<span class="error">Different!</span>)' ;
echo '<li><strong>max_input_time:</strong> '.$maxinput.'</li>';
echo '<li><strong>max_execution_time:</strong> '.$maxexec.$sameornot.'</li>';

echo '</ul></li>';

echo '</ul>';
echo '<p>'.html_writer::link(new moodle_url($securewwwroot.'/admin/phpinfo.php',
    array()), get_string('phpinfo')).'.</p>';

// End drawing 'Server details' table.
echo $OUTPUT->box_end();



echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string('moodledetailsheader', 'tool_sdctools'));

echo '<ul>';
echo '<li><strong>'.get_string('moodleversion', 'tool_sdctools').':</strong> '.$CFG->release.' ['.get_string('internal', 'tool_sdctools').': '.$CFG->version.']</li>';
$usersactive    = $DB->get_record_sql('SELECT COUNT(*) AS users FROM mdl_user WHERE deleted = 0;');
$usersdeleted   = $DB->get_record_sql('SELECT COUNT(*) AS users FROM mdl_user WHERE deleted = 1;');
$userstotal     = $usersactive->users + $usersdeleted->users; 
echo '<li><strong>'.get_string('usersactivedeletedtotal', 'tool_sdctools').':</strong> '.number_format($usersactive->users).
    ' / '.number_format($usersdeleted->users).' / '.number_format($userstotal).'</li>';

// Courses.
$coursesvisible = $DB->get_record_sql('SELECT COUNT(*) AS courses FROM mdl_course WHERE visible = 1;');
$courseshidden  = $DB->get_record_sql('SELECT COUNT(*) AS courses FROM mdl_course WHERE visible = 0;');
$coursestotal   = $coursesvisible->courses + $courseshidden->courses;
echo '<li><strong>'.get_string('coursesvisiblehiddentotal', 'tool_sdctools').':</strong> '.number_format($coursesvisible->courses).
    ' / '.number_format($courseshidden->courses).' / '.number_format($coursestotal).'</li>';

// Modules.
$modulesvisible = $DB->get_record_sql('SELECT COUNT(*) AS modules FROM mdl_course_modules WHERE visible = 1;');
$moduleshidden  = $DB->get_record_sql('SELECT COUNT(*) AS modules FROM mdl_course_modules WHERE visible = 0;');
$modulestotal   = $modulesvisible->modules + $moduleshidden->modules;
echo '<li><strong>'.get_string('modulesvisiblehiddentotalavg', 'tool_sdctools').':</strong> '.number_format($modulesvisible->modules).
    ' / '.number_format($moduleshidden->modules).' / '.number_format($modulestotal).' / '.number_format($modulestotal/$coursestotal).'</li>';

// Blocks.
//$blocksvisible  = $DB->get_record_sql('SELECT COUNT(*) AS blocks FROM mdl_block_instances WHERE visible = 1;');
//$blockshidden   = $DB->get_record_sql('SELECT COUNT(*) AS blocks FROM mdl_block_instances WHERE visible = 0;');
//$blockstotal    = $modulesvisible->modules + $moduleshidden->modules;
//echo '<li><strong>'.get_string('blocksvisiblehiddentotalavg', 'tool_sdctools').':</strong> '.number_format($blocksvisible->blocks).
//    ' / '.number_format($blockshidden->blocks).' / '.number_format($blockstotal).' / '.number_format($blockstotal/$coursestotal).'</li>';
$blocksvisible  = $DB->get_record_sql('SELECT COUNT(*) AS blocks FROM mdl_block_instances;');
echo '<li><strong>'.get_string('blockstotalavg', 'tool_sdctools').':</strong> '.number_format($blocksvisible->blocks).
    ' / '.number_format($blocksvisible->blocks/$coursestotal).'</li>';

// Backups.
$out = '';
$backupstatus = $DB->get_record('config_plugins', array('plugin' => 'backup', 'name' => 'backup_auto_active'), 'value');
if ($backupstatus->value == 0) {
    // Disabled.
    $out = '<span class="error">'.get_string('autoactivedisabled', 'backup').'</span> ('.
        html_writer::link(new moodle_url('/admin/settings.php', array('section' => 'automated')), get_string('automatedsetup', 'backup')).')';
} else if ($backupstatus->value == 1) {
    // Enabled.
    $out = get_string('autoactiveenabled', 'backup');
} else if ($backupstatus->value == 2) {
    // Manual.
    $out = get_string('autoactivemanual', 'backup');
}
$backuprunning = $DB->get_record('config_plugins', array('plugin' => 'backup', 'name' => 'backup_auto_running'), 'value');
if ($backuprunning && $backuprunning->value == 1) {
    $out .= get_string('running', 'tool_sdctools');
}
echo '<li><strong>'.get_string('backupstatus', 'tool_sdctools').'</strong> '.$out.'</li>';

echo '</ul>';

//echo '<p>'.html_writer::link(new moodle_url($securewwwroot.'/admin/phpinfo.php', array()), get_string('phpinfo')).'.</p>';

// End drawing 'Moodle details' table.
echo $OUTPUT->box_end();


// Leap bits.
echo $OUTPUT->box_start();
echo $OUTPUT->heading( get_string( 'leapdetailsheader', 'tool_sdctools' ) );
echo '<p>Check for:</p>';
echo '<ul><li>Leap enrolment plugin (present and enabled)</li><li>Leap block (present and enabled)</li><li>Leap webservices (present and enabled and configured)</li>
<li>Any courses not having the block enabled</li><li>Missing course code config</li><li>Missing grade tracking config</li></ul>';
echo $OUTPUT->box_end();


// Recent new user stats.
echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string('newuserstatsheading', 'tool_sdctools'));
echo '<p>'.get_string('newuserstatsstrapline', 'tool_sdctools').'</p>';
$now = time();
$userstats = $DB->get_records_sql("SELECT
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - 60) ) AS one,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*5)) ) AS five,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*15)) ) AS fifteen,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60)) ) AS onehour,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*2)) ) AS twohours,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*6)) ) AS sixhours,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*12)) ) AS twelvehours,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*24)) ) AS twentyfour,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*24*2)) ) AS fortyeight,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*24*7)) ) AS oneweek,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*24*14)) ) AS twoweeks,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*24*30)) ) AS onemonth,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*24*30*2)) ) AS twomonths,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*24*30*3)) ) AS threemonths,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*24*30*6)) ) AS sixmonths,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*24*365)) ) AS oneyear,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*24*365*2)) ) AS twoyears,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*24*365*3)) ) AS threeyears,
    ( SELECT COUNT(*) FROM mdl_user WHERE firstaccess > (".$now." - (60*60*24*365*4)) ) AS fouryears
;");

$table = new html_table();
$table->head = array ();
$table->head[] = get_string('one', 'tool_sdctools');
$table->head[] = get_string('five', 'tool_sdctools');
$table->head[] = get_string('fifteen', 'tool_sdctools');
$table->head[] = get_string('onehour', 'tool_sdctools');
$table->head[] = get_string('twohours', 'tool_sdctools');
$table->head[] = get_string('sixhours', 'tool_sdctools');
$table->head[] = get_string('twelvehours', 'tool_sdctools');
$table->head[] = get_string('twentyfour', 'tool_sdctools');
$table->head[] = get_string('fortyeight', 'tool_sdctools');
$table->head[] = get_string('oneweek', 'tool_sdctools');
$table->head[] = get_string('twoweeks', 'tool_sdctools');
$table->head[] = get_string('onemonth', 'tool_sdctools');
$table->head[] = get_string('twomonths', 'tool_sdctools');
$table->head[] = get_string('threemonths', 'tool_sdctools');
$table->head[] = get_string('sixmonths', 'tool_sdctools');
$table->head[] = get_string('oneyear', 'tool_sdctools');
$table->head[] = get_string('twoyears', 'tool_sdctools');
$table->head[] = get_string('threeyears', 'tool_sdctools');
$table->head[] = get_string('fouryears', 'tool_sdctools');
$table->width = "100%";
$row = array ();
$row[] = number_format($userstats[0]->one);
$row[] = number_format($userstats[0]->five);
$row[] = number_format($userstats[0]->fifteen);
$row[] = number_format($userstats[0]->onehour);
$row[] = number_format($userstats[0]->twohours);
$row[] = number_format($userstats[0]->sixhours);
$row[] = number_format($userstats[0]->twelvehours);
$row[] = number_format($userstats[0]->twentyfour);
$row[] = number_format($userstats[0]->fortyeight);
$row[] = number_format($userstats[0]->oneweek);
$row[] = number_format($userstats[0]->twoweeks);
$row[] = number_format($userstats[0]->onemonth);
$row[] = number_format($userstats[0]->twomonths);
$row[] = number_format($userstats[0]->threemonths);
$row[] = number_format($userstats[0]->sixmonths);
$row[] = number_format($userstats[0]->oneyear);
$row[] = number_format($userstats[0]->twoyears);
$row[] = number_format($userstats[0]->threeyears);
$row[] = number_format($userstats[0]->fouryears);
$table->data[] = $row;

if (!empty($table)) {
    echo html_writer::table($table);
} else {
    echo '<ul><li>'.get_string('nonefound', 'tool_sdctools').'</li></ul>';
}

echo '<p>'.get_string('firstaccessstrapline', 'tool_sdctools').'</p>';
$firstaccess = $DB->get_records_sql("SELECT 0,
    ( SELECT COUNT(*) FROM mdl_user WHERE deleted = 0 AND firstaccess = 0 ) AS no,
    ( SELECT COUNT(*) FROM mdl_user WHERE deleted = 0 AND firstaccess > 0 ) AS yes,
    ( SELECT COUNT(*) FROM mdl_user WHERE deleted = 0 ) AS total
;");

$table = new html_table();
$table->head = array ();
$table->head[] = get_string('no');
$table->head[] = get_string('yes');
$table->head[] = get_string('total');
$table->width = "25%";
$row = array ();
$row[] = number_format($firstaccess[0]->no).' ('.number_format(($firstaccess[0]->no/$firstaccess[0]->total)*100, 1).'%)';
$row[] = number_format($firstaccess[0]->yes).' ('.number_format(($firstaccess[0]->yes/$firstaccess[0]->total)*100, 1).'%)';
$row[] = number_format($firstaccess[0]->total);
$table->data[] = $row;

if (!empty($table)) {
    echo html_writer::table($table);
} else {
    echo '<ul><li>'.get_string('nonefound', 'tool_sdctools').'</li></ul>';
}

// End Recent new user stats.
echo $OUTPUT->box_end();


// Some of the most recent users.
echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string('recentusersheader', 'tool_sdctools').'<a id="recentusers"></a>');
echo '<p>'.get_string('recentusersstrapline', 'tool_sdctools')."</p>\n";

$out = '[ ';
foreach ($choices as $choice) {
    $out .= '<a href="'.$securewwwroot.'/admin/tool/sdctools/index.php?numusers='.$choice.'#recentusers">'.$choice.'</a> ';
}
$out .= '/ <a href="'.$securewwwroot.'/admin/tool/sdctools/index.php#recentusers">'.get_string('reset').'</a> ]';

$recentusers = $DB->get_records('user', null, 'id DESC', '*', 0, $numusers);

if (!$recentusers) {
    echo '<p>'.get_string('norecentusers', 'tool_sdctools').'</p>';
} else {
    echo '<p>'.get_string('recentusers', 'tool_sdctools', number_format(count($recentusers))).' '.$out.'</p>';

    $table = new html_table();
    $table->head = array ();
    $table->align = array();
    $table->head[] = '#';
    $table->align[] = '';
    $table->head[] = get_string('id', 'tool_sdctools');
    $table->align[] = 'left';
    $table->head[] = get_string('firstaccess');
    $table->align[] = 'left';
    $table->head[] = get_string('fullnameuser');
    $table->align[] = 'left';
    $table->head[] = get_string('username');
    $table->align[] = 'left';
    $table->head[] = get_string('email');
    $table->align[] = 'left';
    $table->head[] = get_string('ip_address');
    $table->align[] = 'left';
    $table->head[] = get_string('actions');
    $table->align[] = 'left';
    $table->width = "100%";

    $items = 0;
    foreach ($recentusers as $user) {

        $buttons = array();
        $row = array ();

        if (is_siteadmin($USER) or !is_siteadmin($user)) {
            // Edit.
            $buttons[] = html_writer::link(new moodle_url($securewwwroot.'/user/editadvanced.php',
                array('id' => $user->id)),
                html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/edit'),
                'alt' => get_string('edit'))), array('title' => get_string('edit')));
            // Delete.
            $buttons[] = html_writer::link(new moodle_url($securewwwroot.'/admin/user.php',
                array('delete' => $user->id)),
                html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'),
                'alt' => get_string('delete'))), array('title' => get_string('delete')));
            // Log.
            $buttons[] = html_writer::link(new moodle_url($securewwwroot.'/report/log/index.php',
                array('chooselog' => 1, 'showusers' => 1, 'user' => $user->id, 'date' => '')),
                html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/log'),
                'alt' => get_string('logs'))), array('title' => get_string('logs')));

        }
        if ($user->firstaccess) {
            $strfirstaccess = sdctools_timeago($user->firstaccess);
        } else {
            $strfirstaccess = get_string('never');
        }
        $fullname = fullname($user, true);
        $row[] = number_format(++$items);
        $row[] = $user->id;
        $row[] = $strfirstaccess;
        $row[] = html_writer::link(new moodle_url($securewwwroot.'/user/view.php',
            array('user' => $user->id, 'sesskey' => sesskey())), $fullname);
        $row[] = $user->username;
        $row[] = $user->email;
        $row[] = $user->lastip;
        $row[] = implode(' ', $buttons);
        $table->data[] = $row;
    }

    if (!empty($table)) {
        echo html_writer::table($table);
    }

    echo '<p>'.html_writer::link(new moodle_url($securewwwroot.'/admin/user.php',
        array()), get_string('userlist', 'admin')).'.</p>';

}

 // Some of the most recent users.
echo $OUTPUT->box_end();


// Some of the most recent log entries.
// TODO: The format of the log table has changed significantly. 
echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string('recentlogsheader', 'tool_sdctools').'<a id="recentlogs"></a>');
echo '<p>'.get_string('recentlogsstrapline', 'tool_sdctools')."</p>\n";

$out = '[ ';
foreach ($choices as $choice) {
    $out .= '<a href="'.$securewwwroot.'/admin/tool/sdctools/index.php?numlogs='.$choice.'#recentlogs">'.$choice.'</a> ';
}
$out .= '/ <a href="'.$securewwwroot.'/admin/tool/sdctools/index.php#recentlogs">'.get_string('reset').'</a> ]';

$recentlog = $DB->get_records('logstore_standard_log', null, 'id DESC', '*', 0, $numlogs);

if (!$recentlog) {
    echo '<p>'.get_string('norecentlogs', 'tool_sdctools').'</p>';
} else {
    echo '<p>'.get_string('recentlogs', 'tool_sdctools', number_format(count($recentlog))).' '.$out.'</p>';

    $table = new html_table();
    $table->head = array ();
    $table->align = array();
    $table->head[] = '#';
    $table->align[] = '';
    $table->head[] = get_string('id', 'tool_sdctools');
    $table->align[] = 'left';
    $table->head[] = get_string('time');
    $table->align[] = 'left';
    $table->head[] = get_string('fullnameuser');
    $table->align[] = 'left';
    $table->head[] = get_string('course');
    $table->align[] = 'left';
    $table->head[] = get_string('module', 'tool_sdctools').' / '.get_string('action');
    $table->align[] = 'left';
    $table->head[] = get_string('url');
    $table->align[] = 'left';
    $table->width = "100%";

    $items = 0;
    foreach ($recentlog as $entry) {

        $userbuttons = array();
        $coursebuttons = array();
        $row = array();

        if ( $entry->timecreated ) {
            $strtime = sdctools_timeago( $entry->timecreated );
        } else {
            $strtime = get_string('never');
        }

        $userdetails    = $DB->get_record('user', array('id' => $entry->userid), 'firstname, lastname');
        $coursedetails  = $DB->get_record( 'course', array( 'id' => $entry->courseid ), 'fullname' );

        $row[] = number_format(++$items);
        $row[] = $entry->id;
        $row[] = $strtime;

        // Create the user-action buttons.
        $out = '';
        if ($userdetails) {
            $out = html_writer::link(new moodle_url($securewwwroot.'/user/view.php',
                array('user' => $entry->userid, 'sesskey' => sesskey())), $userdetails->firstname.' '.$userdetails->lastname);
            if (is_siteadmin($USER) or !is_siteadmin($user)) {
                $out .= ' ' . html_writer::link(new moodle_url($securewwwroot.'/user/editadvanced.php',
                    array('id' => $entry->userid)),
                    html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/edit'),
                    'alt' => get_string('edit'))), array('title' => get_string('edit'))) . ' ' .
                html_writer::link(new moodle_url($securewwwroot.'/admin/user.php',
                    array('delete' => $entry->userid, 'sesskey' => sesskey())),
                    html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'),
                    'alt' => get_string('delete'))), array('title' => get_string('delete'))) . ' ' .
                html_writer::link(new moodle_url($securewwwroot.'/report/log/index.php',
                    array('chooselog' => 1, 'showusers' => 1, 'user' => $entry->userid, 'date' => '')),
                    html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/log'),
                    'alt' => get_string('logs'))), array('title' => get_string('logs')));

            }
        } else {
            $out = get_string('none', 'tool_sdctools');
        }
        $row[] = $out;

        // Create the course-action buttons.
        $out = '';
        if ($coursedetails) {
            $out = html_writer::link( new moodle_url( $securewwwroot . '/course/view.php',
                array( 'id' => $entry->courseid ) ), $coursedetails->fullname );
            if ( $entry->courseid == 1 ) {
                $out .= ' (' . get_string( 'site' ) . ')';
            }
            if (is_siteadmin($USER) or !is_siteadmin($user)) {
                $out .= ' ' . html_writer::link( new moodle_url( $securewwwroot . '/course/edit.php',
                    array( 'id' => $entry->courseid ) ),
                    html_writer::empty_tag( 'img', array( 'src' => $OUTPUT->pix_url( 't/edit' ),
                    'alt' => get_string( 'edit' ) ) ) , array( 'title' => get_string( 'edit' ) ) ) . ' ' .
                html_writer::link( new moodle_url( $securewwwroot . '/course/delete.php',
                    array( 'id' => $entry->courseid ) ),
                    html_writer::empty_tag( 'img', array( 'src' => $OUTPUT->pix_url( 't/delete' ),
                    'alt' => get_string( 'delete' ) ) ), array( 'title' => get_string( 'delete' ) ) ) . ' ' .
                html_writer::link( new moodle_url( $securewwwroot . '/report/log/index.php',
                    array( 'chooselog' => 1, 'date' => '', 'showusers' => 1, 'course' => $entry->courseid ) ),
                    html_writer::empty_tag( 'img', array( 'src' => $OUTPUT->pix_url( 't/log' ),
                    'alt' => get_string( 'logs' ) ) ), array( 'title' => get_string( 'logs' ) ) );
            }
        } else {
            $out = get_string('none', 'tool_sdctools');
        }
        $row[] = $out;

        $row[] = $entry->component . ' / ' . $entry->action;
        //$row[] = html_writer::link( new moodle_url( $securewwwroot . '/' . $entry->url, array() ), $entry->url );
        $row[] = '[redacted]';
        $table->data[] = $row;
    }

    if (!empty($table)) {
        echo html_writer::table($table);
    }

    echo '<p>'.html_writer::link(new moodle_url($securewwwroot.'/report/log/index.php',
        array()), get_string('sitelogs')).'.</p>';

}

 // Some of the most recent log entries.
echo $OUTPUT->box_end();


// End the page.
echo $OUTPUT->footer();

