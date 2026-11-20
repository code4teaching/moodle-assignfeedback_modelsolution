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

namespace assignfeedback_modelsolution;

use assignfeedback_modelsolution\external\get_solution;
use assignfeedback_modelsolution\external\set_solution;
use core_external\external_api;
use mod_assign_test_generator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

/**
 * Web service functions.
 *
 * @package    assignfeedback_modelsolution
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_modelsolution\external\set_solution
 * @covers     \assignfeedback_modelsolution\external\get_solution
 */
final class external_test extends \advanced_testcase {
    use mod_assign_test_generator;

    /**
     * Teacher sets files and release; students cannot.
     */
    public function test_set_and_get(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $due = time() + DAYSECS;
        $assign = $this->create_instance($course, ['duedate' => $due]);
        $cmid = (int) $assign->get_course_module()->id;

        $this->setUser($teacher);
        $r = set_solution::execute($cmid, [['filename' => 'Lösung.pdf', 'content' => base64_encode('%PDF-1')]], 2, HOURSECS);
        $r = external_api::clean_returnvalue(set_solution::execute_returns(), $r);
        $this->assertSame(1, $r['enabled']);
        $this->assertSame([['filename' => 'Lösung.pdf', 'filesize' => 6]], $r['files']);
        $this->assertSame($due + HOURSECS, $r['releasefrom']);
        $this->assertSame(1, $r['requiresubmission']);   // Site default.

        // Only the release changes, the files stay.
        $r = external_api::clean_returnvalue(set_solution::execute_returns(), set_solution::execute($cmid, [], -1, 2 * HOURSECS));
        $this->assertCount(1, $r['files']);
        $this->assertSame($due + 2 * HOURSECS, $r['releasefrom']);
        $r = external_api::clean_returnvalue(get_solution::execute_returns(), get_solution::execute($cmid));
        $this->assertSame(2, $r['releasetype']);

        $this->setUser($student);
        $this->expectException(\required_capability_exception::class);
        get_solution::execute($cmid);
    }

    /**
     * "Only with a submission" needs submission drafts.
     */
    public function test_needs_drafts(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $assign = $this->create_instance($course, ['duedate' => time(), 'submissiondrafts' => 0]);
        $this->setUser($teacher);
        $cmid = (int) $assign->get_course_module()->id;
        $r = set_solution::execute($cmid, [], 2, 0, -1, 0);
        $this->assertSame(0, $r['requiresubmission']);
        $this->expectException(\moodle_exception::class);
        set_solution::execute($cmid, [], -1, -1, -1, 1);
    }

    /**
     * "After the due date" needs a due date.
     */
    public function test_needs_due_date(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $assign = $this->create_instance($course, ['duedate' => 0]);
        $this->setUser($teacher);
        $this->expectException(\moodle_exception::class);
        set_solution::execute((int) $assign->get_course_module()->id, [], 2);
    }
}
