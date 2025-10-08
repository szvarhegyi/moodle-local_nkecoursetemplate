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


namespace local_nkecoursetemplate;
defined('MOODLE_INTERNAL') || die();

class course_observer {

    public static function course_updated(\core\event\course_updated $event) {
        global $DB, $CFG;
        $remove_participants = (int)get_config('local_nkecoursetemplate', 'remove_participants');
        if($remove_participants) {
            $snapshot = $event->get_record_snapshot('course', $event->objectid);
            if($snapshot->category == (int)get_config('local_nkecoursetemplate', 'category')) {

                require_once($CFG->dirroot . '/lib/enrollib.php');

                // Betöltjük a kurzushoz tartozó összes beiratkozási módszert
                $instances = $DB->get_records('enrol', ['courseid' => $event->objectid]);

                foreach ($instances as $instance) {
                    if(in_array($instance->enrol, ['cohort', 'self'])) {
                        // Lekérjük az enrol plugin objektumot (pl. manual, self, cohort, stb.)
                        $enrol = enrol_get_plugin($instance->enrol);

                        if ($enrol) {
                            // Ez törli az adott beiratkozási módszerhez tartozó összes felhasználót is
                            $enrol->delete_instance($instance);
                        }
                    } else if($instance->enrol == 'manual') {
                        //Manual enrolment
                        $enrol = enrol_get_plugin($instance->enrol);
                        foreach($DB->get_records('user_enrolments', ['enrolid' => $instance->id]) as $u) {
                            $enrol->unenrol_user($instance, $u->userid);
                        }
                    }
                }

            }
        }
    }

}