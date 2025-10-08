<?php
// This file is part of Moodle - https://moodle.org/
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

namespace local_nkecoursetemplate\task;

use backup as GlobalBackup;
use backup_controller;

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
class backup extends \core\task\scheduled_task
{

    public function get_name()
    {
        return get_string('pluginname', 'local_nkecoursetemplate');
    }

    /**
     * Execute the task.
     */
    public function execute()
    {
        global $DB;

        $coursecategory = get_config('local_nkecoursetemplate', 'category');
        $lastruntime = $this->get_last_run_time(); //Ez mindig az előző feladat befejezését jelenti!
        if($lastruntime && $lastruntime > 0) {
            $lastrun = $DB->get_record_sql("SELECT * FROM {task_log} WHERE classname = ? and pid != ? ORDER BY id DESC", ['local_nkecoursetemplate\task\backup', $this->get_pid()], IGNORE_MULTIPLE);
            $lastruntime = (int)$lastrun->timestart;
        } else {
            $lastruntime = 0;
        }

        $courses = $DB->get_records_sql("SELECT * FROM {course} WHERE category = ? AND (timecreated > ? or timemodified > ?)", [$coursecategory, $lastruntime, $lastruntime]);
        foreach($courses as $course) {
            if($course->idnumber == "") {
                mtrace("A kurzus azonositoja ures, ezert kihagyasra kerul. Kurzus ID: $course->id");
                continue;
            }
            if(!$this->is_valid_code($course->idnumber)) {
                mtrace("A kurzus azonositoja nem megfelelo ($course->idnumber), ezert kihagyasra kerul. Kurzus ID: $course->id");
                continue;
            }        
            
            mtrace("Kurzus feldolgozasa $course->idnumber");
            $this->backupCourse($course);

        }

        return true;

    }

    function is_valid_code(string $value): bool {
        // töröljük a környező whitespace-eket
        $value = trim($value);

        // regex: 1 betű, /, szám+, /, szám+
        return (bool) preg_match('/^([A-Za-z])\/(\d+)\/(\d+)$/', $value);
    }

    function backupCourse($course) {
        global $USER;
        
        $bc = new backup_controller(GlobalBackup::TYPE_1COURSE, $course->id, GlobalBackup::FORMAT_MOODLE,
            GlobalBackup::INTERACTIVE_YES, GlobalBackup::MODE_GENERAL, $USER->id);

        $tasks = $bc->get_plan()->get_tasks();
        foreach ($tasks as &$task) {
            if ($task instanceof \backup_root_task) {
                $setting = $task->get_setting('grade_histories');
                $setting->set_value('0');
                $setting = $task->get_setting('users');
                $setting->set_value('0');
                $setting = $task->get_setting('filters');
                $setting->set_value('0');
                $setting = $task->get_setting('comments');
                $setting->set_value('0');
                $setting = $task->get_setting('logs');
                $setting->set_value('0');
                $setting = $task->get_setting('grade_histories');
                $setting->set_value('0');
                $setting = $task->get_setting('customfield');
                $setting->set_value('0');
                //$setting = $task->get_setting('root_customfield');
                //$setting->set_value('0');
            } 
        }
        
        $bc->set_status(GlobalBackup::STATUS_AWAITING);
        $bc->execute_plan();
        $result = $bc->get_results();

        if(isset($result['backup_destination']) && $result['backup_destination']) {
            /** @var $file stored_file */
            $file = $result['backup_destination'];
            
            $destination = get_config('local_nkecoursetemplate', 'path') . str_replace('/', '', $course->idnumber) . '.mbz';
            if(file_exists($destination)) {
                @unlink($destination);
            }
            if(!$file->copy_content_to($destination)) {
                cli_error("Problems copying final backup to '". $destination . "'");
            } else {
                mtrace($destination);
            }
        } else {
	        mtrace($bc->get_backupid());
        }

    }
}
