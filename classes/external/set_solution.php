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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace assignfeedback_modelsolution\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Sets the model solution of an assignment: files (base64) and release settings. Turns the plugin on for the assignment.
 *
 * @package    assignfeedback_modelsolution
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_solution extends external_api {
    /**
     * Parameters. Release settings with -1 stay as they are.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID of the assignment'),
            'files' => new external_multiple_structure(new external_single_structure([
                'filename' => new external_value(PARAM_FILE, 'File name'),
                'content' => new external_value(PARAM_RAW, 'File content, base64 encoded'),
            ]), 'Files of the model solution; they replace the current ones. Empty: files stay as they are.', VALUE_DEFAULT, []),
            'releasetype' => new external_value(
                PARAM_INT,
                '0 by hand (hidden), 1 released, 2 after the due date, 3 at a date',
                VALUE_DEFAULT,
                -1
            ),
            'releaseoffset' => new external_value(PARAM_INT, 'Seconds after the due date (releasetype 2)', VALUE_DEFAULT, -1),
            'releasetime' => new external_value(PARAM_INT, 'Release date (releasetype 3)', VALUE_DEFAULT, -1),
            'requiresubmission' => new external_value(
                PARAM_INT,
                '1: only students who submitted see it (needs submission drafts)',
                VALUE_DEFAULT,
                -1
            ),
            'hideafter' => new external_value(PARAM_INT, 'Hidden again from this date, 0 = never', VALUE_DEFAULT, -1),
            'enabled' => new external_value(PARAM_INT, '1 turn on (default), 0 turn off for this assignment', VALUE_DEFAULT, 1),
        ]);
    }

    /**
     * Sets the model solution.
     *
     * @param int $cmid
     * @param array $files
     * @param int $releasetype
     * @param int $releaseoffset
     * @param int $releasetime
     * @param int $requiresubmission
     * @param int $hideafter
     * @param int $enabled
     * @return array as get_solution
     */
    public static function execute(
        int $cmid,
        array $files = [],
        int $releasetype = -1,
        int $releaseoffset = -1,
        int $releasetime = -1,
        int $requiresubmission = -1,
        int $hideafter = -1,
        int $enabled = 1
    ): array {
        global $CFG, $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact(
            'cmid',
            'files',
            'releasetype',
            'releaseoffset',
            'releasetime',
            'requiresubmission',
            'hideafter',
            'enabled'
        ));
        [$assign, $plugin] = get_solution::plugin($params['cmid']);
        $context = $assign->get_context();
        require_capability('moodle/course:manageactivities', $context);
        require_capability('assignfeedback/modelsolution:release', $context);

        $settings = $plugin->is_configured() ? [] : \assign_feedback_modelsolution::site_defaults();
        foreach (['releasetype', 'releaseoffset', 'releasetime', 'requiresubmission', 'hideafter'] as $key) {
            if ($params[$key] >= 0) {
                $settings[$key] = $params[$key];
            }
        }
        $type = $settings['releasetype'] ?? $plugin->get_release()['releasetype'];
        $require = $settings['requiresubmission'] ?? $plugin->get_release()['requiresubmission'];
        if ($require && !$assign->get_instance()->submissiondrafts) {
            throw new \moodle_exception('errorneedsdrafts', 'assignfeedback_modelsolution');
        }
        if ($type === \assign_feedback_modelsolution::RELEASE_AFTERDUE && !$plugin->due_date()) {
            throw new \moodle_exception('errorneedsduedate', 'assignfeedback_modelsolution');
        }
        if ($params['files']) {
            $maxbytes = get_user_max_upload_file_size($context, $CFG->maxbytes, $assign->get_course()->maxbytes);
            $decoded = [];
            foreach ($params['files'] as $f) {
                $name = clean_param($f['filename'], PARAM_FILE);
                $content = base64_decode($f['content'], true);
                if ($name === '' || $content === false) {
                    throw new \invalid_parameter_exception('files');
                }
                if ($maxbytes > 0 && strlen($content) > $maxbytes) {
                    throw new \moodle_exception('errorfiletoobig', 'assignfeedback_modelsolution', '', $name);
                }
                $decoded[$name] = $content;
            }
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, 'assignfeedback_modelsolution', \assign_feedback_modelsolution::FILEAREA, 0);
            foreach ($decoded as $name => $content) {
                $fs->create_file_from_string(['contextid' => $context->id, 'component' => 'assignfeedback_modelsolution',
                    'filearea' => \assign_feedback_modelsolution::FILEAREA, 'itemid' => 0, 'filepath' => '/', 'filename' => $name,
                    'userid' => $USER->id], $content);
            }
        }
        $plugin->save_release($settings);
        if ($params['enabled']) {
            $plugin->enable();
        } else {
            $plugin->disable();
        }
        \core\event\course_module_updated::create_from_cm($assign->get_course_module(), $context)->trigger();
        return get_solution::describe($assign, $plugin);
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_solution::execute_returns();
    }
}
