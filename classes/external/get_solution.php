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
 * Model solution of an assignment: files and release settings.
 *
 * @package    assignfeedback_modelsolution
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_solution extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID of the assignment'),
        ]);
    }

    /**
     * Returns the model solution.
     *
     * @param int $cmid
     * @return array
     */
    public static function execute(int $cmid): array {
        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);
        [$assign, $plugin] = self::plugin($params['cmid']);
        require_capability('assignfeedback/modelsolution:viewanytime', $assign->get_context());
        return self::describe($assign, $plugin);
    }

    /**
     * Loads the assignment and the plugin, checks the context.
     *
     * @param int $cmid
     * @return array [assign, assign_feedback_modelsolution]
     */
    public static function plugin(int $cmid): array {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'assign');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        $assign = new \assign($context, $cm, $course);
        $plugin = $assign->get_feedback_plugin_by_type('modelsolution');
        if (!$plugin || !$plugin->is_visible()) {
            throw new \moodle_exception('pluginnotavailable', 'assignfeedback_modelsolution');
        }
        return [$assign, $plugin];
    }

    /**
     * Model solution as returned by the web service.
     *
     * @param \assign $assign
     * @param \assign_feedback_modelsolution $plugin
     * @return array
     */
    public static function describe(\assign $assign, \assign_feedback_modelsolution $plugin): array {
        $files = array_values(array_map(
            fn($f) => ['filename' => $f->get_filename(), 'filesize' => (int) $f->get_filesize()],
            $plugin->get_solution_files()
        ));
        return array_merge($plugin->get_release(), [
            'cmid' => (int) $assign->get_course_module()->id,
            'enabled' => (int) $plugin->is_enabled(),
            'files' => $files,
            'duedate' => $plugin->due_date(),
            'releasefrom' => $plugin->is_enabled() ? $plugin->release_time() : -1,
        ]);
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'enabled' => new external_value(PARAM_INT, '1 if the model solution is turned on for this assignment'),
            'files' => new external_multiple_structure(new external_single_structure([
                'filename' => new external_value(PARAM_FILE, 'File name'),
                'filesize' => new external_value(PARAM_INT, 'Size in bytes'),
            ])),
            'releasetype' => new external_value(PARAM_INT, '0 by hand (hidden), 1 released, 2 after the due date, 3 at a date'),
            'releaseoffset' => new external_value(PARAM_INT, 'Seconds after the due date (releasetype 2)'),
            'releasetime' => new external_value(PARAM_INT, 'Release date (releasetype 3), never before the due date'),
            'requiresubmission' => new external_value(PARAM_INT, '1: only students who submitted see it'),
            'hideafter' => new external_value(PARAM_INT, 'Hidden again from this date, 0 = never'),
            'duedate' => new external_value(PARAM_INT, 'Due date of the assignment, 0 = none'),
            'releasefrom' => new external_value(
                PARAM_INT,
                'When students without an individual due date see it: 0 now, -1 not released, else a timestamp'
            ),
        ]);
    }
}
