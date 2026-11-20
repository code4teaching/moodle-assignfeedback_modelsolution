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

namespace assignfeedback_modelsolution\privacy;

/**
 * Privacy provider: the plugin stores no personal data.
 *
 * The model solution and its release settings belong to the assignment, like the files of mod_resource.
 * Whether a student may see it is computed from data mod_assign already holds (due date, overrides,
 * extensions, submission status).
 *
 * @package    assignfeedback_modelsolution
 * @copyright  2026 Holger Sebastião, based on assignfeedback_solutionsheet (2016 Henning Bostelmann)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Reason why no personal data is stored.
     *
     * @return string language string identifier
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
