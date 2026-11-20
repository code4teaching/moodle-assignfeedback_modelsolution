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
 * Version information for the model solution feedback plugin.
 *
 * @package    assignfeedback_modelsolution
 * @copyright  2026 Holger Sebastião, based on assignfeedback_solutionsheet (2016 Henning Bostelmann)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'assignfeedback_modelsolution';
$plugin->version   = 2026112001;
$plugin->requires  = 2025041400; // Moodle 5.0.
$plugin->maturity  = MATURITY_BETA;
$plugin->release   = '1.0.1';
