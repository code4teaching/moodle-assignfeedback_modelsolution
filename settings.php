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
 * Site settings for the model solution feedback plugin.
 *
 * @package    assignfeedback_modelsolution
 * @copyright  2026 Holger Sebastião, based on assignfeedback_solutionsheet (2016 Henning Bostelmann)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_configcheckbox(
    'assignfeedback_modelsolution/default',
    new lang_string('default', 'assignfeedback_modelsolution'),
    new lang_string('default_help', 'assignfeedback_modelsolution'),
    0
));

$settings->add(new admin_setting_configselect(
    'assignfeedback_modelsolution/defaultreleasetype',
    new lang_string('defaultreleasetype', 'assignfeedback_modelsolution'),
    new lang_string('defaultreleasetype_help', 'assignfeedback_modelsolution'),
    2,
    [
        2 => new lang_string('releaseafterdue', 'assignfeedback_modelsolution'),
        3 => new lang_string('releasedate', 'assignfeedback_modelsolution'),
        0 => new lang_string('releasemanual', 'assignfeedback_modelsolution'),
        1 => new lang_string('releasenow', 'assignfeedback_modelsolution'),
    ]
));

$settings->add(new admin_setting_configduration(
    'assignfeedback_modelsolution/defaultreleaseoffset',
    new lang_string('releaseoffset', 'assignfeedback_modelsolution'),
    '',
    0,
    HOURSECS
));

$settings->add(new admin_setting_configcheckbox(
    'assignfeedback_modelsolution/defaultrequiresubmission',
    new lang_string('requiresubmission', 'assignfeedback_modelsolution'),
    new lang_string('defaultrequiresubmission_help', 'assignfeedback_modelsolution'),
    1
));
