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
 * Web service functions of the model solution feedback plugin.
 *
 * @package    assignfeedback_modelsolution
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'assignfeedback_modelsolution_get_solution' => [
        'classname'    => 'assignfeedback_modelsolution\external\get_solution',
        'description'  => 'Returns the model solution of an assignment: files and release settings.',
        'type'         => 'read',
        'capabilities' => 'assignfeedback/modelsolution:viewanytime',
    ],
    'assignfeedback_modelsolution_set_solution' => [
        'classname'    => 'assignfeedback_modelsolution\external\set_solution',
        'description'  => 'Sets the model solution of an assignment (files base64 encoded, release settings) and turns it on.',
        'type'         => 'write',
        'capabilities' => 'moodle/course:manageactivities, assignfeedback/modelsolution:release',
    ],
];
