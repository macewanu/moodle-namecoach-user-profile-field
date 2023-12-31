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
        // Create the form field.
        $label = 'Enable "'.format_string($this->field->name).'"';
        $checkbox = $mform->addElement('advcheckbox', $this->inputname, $label);
        if ($this->data == '1') {
            $checkbox->setChecked(true);
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
        // Get the recording dasta from NameCoach
        $nmdata = $this->get_namecoach_data($user);
        // Actual API errors, report unavailable
        if (!$nmdata) {
            $msg = get_string('msg_unavailable', 'profilefield_namecoach');
            return "<em>{$msg}</em>";
        }
        // Widget is blank if profile user is not the current user
        $widget = $this->get_namecoach_recording_widget($user);
        // No errors, but no recording exists
        if (isset($nmdata['message']) && $nmdata['message'] == 'Not Found') {
            $msg = get_string('msg_norecording', 'profilefield_namecoach');
            return "<em>{$msg}</em>".$widget;
        }
        // Display playback widget
        $nmdata = $nmdata['participant'];
        $playback = $this->get_namecoach_playback($nmdata);
        if (!$playback) {
            $msg = get_string('msg_norecording', 'profilefield_namecoach');
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
        // More precise data gathering based on name page identifier in (where?))
        $apitoken = $this->field->param1;
        $namepageid = $this->field->param4;
        $endpoint = $this->field->param3;
        $location = "{$endpoint}/api/private/v4/name_pages/{$namepageid}/participants/{$user->email}?include=embeddables";

        $header = [
            'Accept: application/json',
            "Authorization: {$this->field->param1}",
        ];
        $curl = new \curl();
        $curl->setHeader($header);
        $result = $curl->get($location);
        $nmdata = json_decode($result, true);
        if (!isset($nmdata['participant']) &&
                (!isset($nmdata['message']) ||
                ($nmdata['message'] != 'Not Found'))) {
            return false;
        };
        
        return $nmdata;
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
        global $USER;

        // Only allow recording when the current user is the profile user.
        if (!($USER->id == $user->id)) {
            return '';
        }

        $apitoken = $this->field->param1;
        $accesscode = $this->field->param2;
        // The window.onload CSS kludge (near bottom) messes with the default Moodle
        // layout to allow the iFrame to run full size. But I don't like it.
        $widgethtml =
            "
            <div class=\"modal\" id=\"nc-modal\" role=\"dialog\" tabindex=\"-1\">
                <div class=\"modal-dialog\" role=\"document\">
                    <div class=\"modal-content\">
                        <div class=\"modal-header\">
                            <h5 class=\"modal-title\">Record your name</h5>
                            <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                <span aria-hidden=\"true\">&times;</span>
                            </button>
                        </div>
                        <div class=\"modal-body\" style=\"padding: .7em;\">
                            <div id=\"nc-embed\"
                                style=\"position: relative; min-height: 480px;\"
                                data-mode=\"embedded\" data-attributes-email-value=\"{$user->email}\"
                                data-attributes-email-presentation=\"hidden\"
                                data-attributes-first-name-value=\"{$user->firstname}\"
                                data-attributes-first-name-presentation=\"readonly\"
                                data-attributes-last-name-value=\"{$user->lastname}\"
                                data-attributes-last-name-presentation=\"readonly\">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script type=\"text/javascript\" src=\"https://s3.us-east-2.amazonaws.com/nc-widget-v3/bundle.js\"> </script>
            <button id=\"nc-button\" class=\"btn btn-link\" data-toggle=\"modal\" data-target=\"#nc-modal\">
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
                    $('#nc-modal').on('shown.bs.modal', function (event) {
                        document.querySelector('#nc-embed').style.setProperty('height', document.querySelector('#nc-widget').contentDocument.body.getBoundingClientRect().height + 'px');
                    });
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


