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
 * NameCoach profile field
 *
 * @package   profilefield_namecoach
 * @copyright  2023 Erwin Veugelers - MacEwan University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class profile_define_checkbox
 * @copyright  2023 Erwin Veugelers - MacEwan University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_define_namecoach extends profile_define_base {
    const DEFAULT_NAMECOACH_API_ENDPOINT = 'https://www.name-coach.com';

    /**
     * Add elements for creating/editing a checkbox profile field.
     *
     * @param moodleform $form
     */
    public function define_form_specific($form) {
        // Select whether or not this should be checked by default.
        $form->addElement('selectyesno', 'defaultdata', get_string('profiledefaultchecked', 'admin'));
        $form->setDefault('defaultdata', 0); // Defaults to 'no'.
        $form->setType('defaultdata', PARAM_BOOL);
        
        // Param 1 for text type contains a the NameCoach API token.
        $form->addElement('text', 'param1', get_string('api_token', 'profilefield_namecoach'));
        $form->setType('param1', PARAM_TEXT);

        // Param 2 for text type contains a the NameCoach name page access code.
        $form->addElement('text', 'param2', get_string('access_code', 'profilefield_namecoach'));
        $form->setType('param2', PARAM_TEXT);

        // Param 3 for text type contains a the NameCoach API endpoint URL.
        $form->addElement('text', 'param3', get_string('api_endpoint', 'profilefield_namecoach'));
        $form->setType('param3', PARAM_TEXT);
        $form->setDefault('param3', self::DEFAULT_NAMECOACH_API_ENDPOINT);
    }
}


