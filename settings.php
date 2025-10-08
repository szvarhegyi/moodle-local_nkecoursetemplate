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

use core\lang_string;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    //END manage

    $ADMIN->add('localplugins', new admin_category('local_nkecoursetemplate', new lang_string('pluginname', 'local_nkecoursetemplate')));
    $settingspage = new admin_settingpage('managelocalnkecoursetemplate', new lang_string('pluginname', 'local_nkecoursetemplate'));

    if ($ADMIN->fulltree) {

        $options = [];
        foreach($DB->get_records('course_categories') as $cat) {
            $options[$cat->id] = "$cat->name - ID: " . $cat->id;
        }


        $settingspage->add(
            new admin_setting_configselect(
                'local_nkecoursetemplate/category',
                new lang_string('category'),
                new lang_string('category_desc', 'local_nkecoursetemplate'),
                null,
                $options
            )
        );

        $settingspage->add(
            new admin_setting_configtext(
                'local_nkecoursetemplate/path',
                new lang_string('path', 'local_nkecoursetemplate'),
                new lang_string('path_desc', 'local_nkecoursetemplate'),
                '',
            )
        );

        $settingspage->add(
            new admin_setting_configcheckbox(
                 'local_nkecoursetemplate/remove_participants',
                new lang_string('remove_participants', 'local_nkecoursetemplate'),
                new lang_string('remove_participants_desc', 'local_nkecoursetemplate'),
                '1'
            )
        );

    }

    $ADMIN->add('localplugins', $settingspage);
}
