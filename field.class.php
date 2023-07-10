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
 * Class profile_field_namecoach
 *
 * @package    profilefield_namecoach
 * @copyright  2023 Erwin Veugelers - MacEwan University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_namecoach extends profile_field_base {

    /**
     * Add elements for editing the profile field value.
     * @param moodleform $mform
     */
    public function edit_field_add($mform) {
        // Check for NameCoach recording(s).
        $has_recording = true;
        $user = $this->get_profile_user();
        $nmdata = $this->get_namecoach_data($user);
        if (!$nmdata) {
            $has_recording = false;
        }

        // Create the form field.
        $label = format_string($this->field->name);
        if (!$has_recording) {
            $label .= ' (You must use the Hear my name activity to record your name at least once before you can enable this field)';
        }
        $widget = $this->get_namecoach_recording_widget($user);
        $label .= $widget;
        $checkbox = $mform->addElement('advcheckbox', $this->inputname, $label);
        if ($this->data == '1') {
            $checkbox->setChecked(true);
        }
        // Cannot enbale if no recording is present.
        if (!$has_recording) {
            $checkbox->setChecked(false);
            $checkbox->freeze();
        }
        $mform->setType($this->inputname, PARAM_BOOL);
        if ($this->is_required() and !has_capability('moodle/user:update', context_system::instance())) {
            $mform->addRule($this->inputname, get_string('required'), 'nonzero', null, 'client');
        }
    }

    /**
     * Display the data for this field
     *
     * @return string HTML.
     */
    public function display_data() {
        $options = new stdClass();
        $options->para = false;
        $user = $this->get_profile_user();
        if (!$user) return '';
        $nmdata = $this->get_namecoach_data($user);
        $playback = $this->get_namecoach_playback($nmdata);
        $widget = $this->get_namecoach_recording_widget($user);
        if (!$playback) {
            $msg = get_string('msg_unavailable', 'profilefield_namecoach');
            return "<em>{$msg}</em>".$widget;
        }
        $displayname = $this->get_namecoach_displayname($nmdata);
        if (empty($displayname)) {
            $displayname = fullname($this->get_profile_user());
        }
        return $playback.'&nbsp;'.$displayname.$widget;
    }

    /**
     * Check if the field data is considered empty
     *
     * @return boolean
     */
    public function is_empty() {
        return empty($this->data);
    }

    /**
    * Get the user object for this profile's user
    *
    * @return stdClass user;
    */
    protected function get_profile_user() {
        global $DB;
    
        $user = $DB->get_record('user', array('id' => $this->userid));
        return $user;    
    }
    
    /**
    * Retrieve the NameCoach data from NameCoach
    *
    * @return object namecoach data
    */
    protected function get_namecoach_data($user) {
        $location = "https://www.name-coach.com/api/private/v5/participants?email_list={$user->email}&include=embeddables";
        $header = [
            'Accept: application/json',
            "Authorization: {$this->field->param1}",
        ];
        $curl = new \curl();
        $curl->setHeader($header);
        $result = $curl->get($location);
        $nmdata = json_decode($result, true);
        if (!$nmdata['Response']['participants'][0]) return false;
        
        return $nmdata['Response']['participants'][0];
    }    
    
    /**
    * Retrieve the name playback widget from NameCoach data
    *
    * @return string namecoach html
    */
    protected function get_namecoach_playback($nmdata) {
        if (!$nmdata['embed_image']) return false;
        
        return $nmdata['embed_image'];
    }

    /**
    * Retrieve the display name (with phonetics if available) from NameCoach data
    *
    * @return string namecoach html
    */
    protected function get_namecoach_displayname($nmdata) {
        $displayname = "{$nmdata['first_name']} {$nmdata['last_name']}";
    
        if (!empty($nmdata['phonetic_spelling'])) {
            $displayname .= " ({$nmdata['phonetic_spelling']})";
        }
        
        return $displayname;
    }

    /**
    * Retrieve the display name (with phonetics if available) from NameCoach data
    *
    * @return string namecoach recording widget html
    */
    protected function get_namecoach_recording_widget($user) {
        $apitoken = $this->field->param1;
        $accesscode = '19B215';
        $widgethtml =
            "
            <script type=\"text/javascript\" src=\"https://s3.us-east-2.amazonaws.com/nc-widget-v3/bundle.js\"> </script>
            <button id=\"nc-button\" type=\"button\"
                data-toggle=\"nc-widget\"
                data-attributes-email-value=\"{$user->email}\"
                data-attributes-email-presentation=\"hidden\"
                data-attributes-first-name-value=\"{$user->firstname}\"
                data-attributes-first-name-presentation=\"readonly\"
                data-attributes-last-name-value=\"{$user->lastname}\"
                data-attributes-last-name-presentation=\"readonly\"
                class=\"btn btn-link\">
                Record your name
            </button>
            <script>
                ncE(function() {
                    ncE.configure(function(config) {
                        config.eventCode = \"{$accesscode}\";
                        config.accessToken = \"{$apitoken}\";
                        config.dictionary = {
                            audio_recording_desc: 'Record your name as you would like to have it pronounced.',
                            modal_header: 'MacEwan University NameCoach Recording'
                        };
                    });
                });
                window.onload = (event) => {
                    let cards = document.querySelectorAll('section.card');
                    cards.forEach((el) => { el.style.setProperty('position', 'unset') });
                };
            </script>
            ";
        return $widgethtml;
    }

    /**
     * Return the field type and null properties.
     * This will be used for validating the data submitted by a user.
     *
     * @return array the param type and null property
     * @since Moodle 3.2
     */
    public function get_field_properties() {
        return array(PARAM_BOOL, NULL_NOT_ALLOWED);
    }
}


