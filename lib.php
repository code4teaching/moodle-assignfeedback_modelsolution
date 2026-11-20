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
 * Library functions of the model solution feedback plugin.
 *
 * @package    assignfeedback_modelsolution
 * @copyright  2026 Holger Sebastião, based on assignfeedback_solutionsheet (2016 Henning Bostelmann)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Serves the files of the model solution, only to users who may see it now.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool false if the file is not found or not allowed
 */
function assignfeedback_modelsolution_pluginfile($course, $cm, context $context, $filearea, $args, $forcedownload,
        array $options = []) {
    global $CFG;
    if ($context->contextlevel != CONTEXT_MODULE || $filearea !== 'modelsolution') {
        return false;
    }
    require_login($course, false, $cm);
    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    $assign = new assign($context, $cm, $course);
    $plugin = $assign->get_feedback_plugin_by_type('modelsolution');
    if (!$plugin || !$plugin->is_enabled() || !$plugin->can_view()) {
        return false;
    }
    $itemid = (int) array_shift($args);
    if ($itemid !== 0) {
        return false;
    }
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
    $file = get_file_storage()->get_file($context->id, 'assignfeedback_modelsolution', $filearea, 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, true, $options);
}
