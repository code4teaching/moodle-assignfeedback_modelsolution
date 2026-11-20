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

/**
 * Release the model solution now or hide it again.
 *
 * @package    assignfeedback_modelsolution
 * @copyright  2026 Holger Sebastião, based on assignfeedback_solutionsheet (2016 Henning Bostelmann)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

$cmid = required_param('cmid', PARAM_INT);
$release = required_param('release', PARAM_BOOL);

[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'assign');
require_login($course, false, $cm);
require_sesskey();
$context = context_module::instance($cm->id);
require_capability('assignfeedback/modelsolution:release', $context);

$assign = new assign($context, $cm, $course);
$plugin = $assign->get_feedback_plugin_by_type('modelsolution');
$plugin->set_release_by_hand($release);

redirect(
    new moodle_url('/mod/assign/view.php', ['id' => $cm->id]),
    get_string($release ? 'releaseddone' : 'hiddendone', 'assignfeedback_modelsolution')
);
