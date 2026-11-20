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

use mod_assign_test_generator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

/**
 * When students see the model solution: own due date, overrides, extensions, submission, hide again.
 *
 * @package    assignfeedback_modelsolution
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assign_feedback_modelsolution
 */
final class release_test extends \advanced_testcase {
    use mod_assign_test_generator;

    /**
     * Assignment with the model solution turned on.
     *
     * @param \stdClass $course
     * @param array $params
     * @return array [assign, plugin]
     */
    private function assignment(\stdClass $course, array $params): array {
        $assign = $this->create_instance($course, array_merge([
            'assignsubmission_onlinetext_enabled' => 1,
            'assignfeedback_modelsolution_enabled' => 1,
            'assignfeedback_modelsolution_requiresubmission' => 0,
        ], $params));
        $plugin = $assign->get_feedback_plugin_by_type('modelsolution');
        get_file_storage()->create_file_from_string(['contextid' => $assign->get_context()->id,
            'component' => 'assignfeedback_modelsolution', 'filearea' => 'modelsolution', 'itemid' => 0, 'filepath' => '/',
            'filename' => 'solution.pdf'], '%PDF');
        return [$assign, $plugin];
    }

    /**
     * After the due date: every student after their own due date, extensions and overrides included.
     */
    public function test_after_own_due_date(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $anna = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $ben = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $cem = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $due = time() - HOURSECS;
        [$assign, $plugin] = $this->assignment($course, [
            'duedate' => $due,
            'assignfeedback_modelsolution_releasetype' => \assign_feedback_modelsolution::RELEASE_AFTERDUE,
            'assignfeedback_modelsolution_releaseoffset' => 0,
        ]);
        $this->assertTrue((bool) $plugin->is_enabled());

        // Ben has an extension, Cem a user override: both later.
        $flags = $assign->get_user_flags($ben->id, true);
        $flags->extensionduedate = time() + DAYSECS;
        $assign->update_user_flags($flags);
        $DB->insert_record('assign_overrides', ['assignid' => $assign->get_instance()->id, 'userid' => $cem->id,
            'duedate' => time() + 2 * DAYSECS]);

        $this->assertSame($due, $plugin->release_time($anna->id));
        $this->assertTrue($plugin->can_view($anna->id));
        $this->assertSame($flags->extensionduedate, $plugin->release_time($ben->id));
        $this->assertFalse($plugin->can_view($ben->id));
        $this->assertFalse($plugin->can_view($cem->id));
        $this->assertTrue($plugin->can_view($teacher->id));

        // Offset: one day after the due date.
        $plugin->save_release(['releaseoffset' => DAYSECS]);
        $this->assertFalse($plugin->can_view($anna->id));
        $this->assertSame($due + DAYSECS, $plugin->release_time());
    }

    /**
     * At a date: never before the student's own due date; by hand; hide again.
     */
    public function test_date_by_hand_and_hide_again(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $anna = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $ben = $this->getDataGenerator()->create_and_enrol($course, 'student');
        [$assign, $plugin] = $this->assignment($course, [
            'duedate' => time() - DAYSECS,
            'assignfeedback_modelsolution_releasetype' => \assign_feedback_modelsolution::RELEASE_DATE,
            'assignfeedback_modelsolution_releasetime' => time() - HOURSECS,
        ]);
        $flags = $assign->get_user_flags($ben->id, true);
        $flags->extensionduedate = time() + DAYSECS;
        $assign->update_user_flags($flags);
        $this->assertTrue($plugin->can_view($anna->id));
        $this->assertFalse($plugin->can_view($ben->id));

        $plugin->set_release_by_hand(false);
        $this->assertSame(\assign_feedback_modelsolution::NEVER, $plugin->release_time($anna->id));
        $this->assertFalse($plugin->can_view($anna->id));

        $plugin->save_release(['hideafter' => time() - 60]);
        $plugin->set_release_by_hand(true);   // Releasing by hand removes a hide date that has passed.
        $this->assertTrue($plugin->can_view($ben->id));
        $this->assertSame(0, $plugin->get_release()['hideafter']);

        $plugin->save_release(['hideafter' => time() - 60]);
        $this->assertFalse($plugin->can_view($anna->id));
    }

    /**
     * Only students who submitted.
     */
    public function test_require_submission(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $anna = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $ben = $this->getDataGenerator()->create_and_enrol($course, 'student');
        [$assign, $plugin] = $this->assignment($course, [
            'duedate' => time() - DAYSECS,
            'assignfeedback_modelsolution_releasetype' => \assign_feedback_modelsolution::RELEASE_AFTERDUE,
            'assignfeedback_modelsolution_requiresubmission' => 1,
        ]);
        // Ben has only a draft: that does not count.
        $this->add_submission($anna, $assign);
        $this->submit_for_grading($anna, $assign);
        $this->add_submission($ben, $assign);
        $this->setAdminUser();
        $this->assertTrue($plugin->can_view($anna->id));
        $this->assertFalse($plugin->can_view($ben->id));
    }

    /**
     * Site defaults for new assignments: after the due date, only with a submission, which needs submission drafts.
     */
    public function test_site_defaults(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->create_instance($course, ['duedate' => time() - DAYSECS, 'assignfeedback_modelsolution_enabled' => 1]);
        $release = $assign->get_feedback_plugin_by_type('modelsolution')->get_release();
        $this->assertSame(
            [\assign_feedback_modelsolution::RELEASE_AFTERDUE, 1],
            [$release['releasetype'], $release['requiresubmission']]
        );
        $plugin = $assign->get_feedback_plugin_by_type('modelsolution');
        $data = ['assignfeedback_modelsolution_enabled' => 1, 'duedate' => time(), 'submissiondrafts' => 0,
            'assignfeedback_modelsolution_releasetype' => \assign_feedback_modelsolution::RELEASE_AFTERDUE,
            'assignfeedback_modelsolution_requiresubmission' => 1];
        $this->assertArrayHasKey('assignfeedback_modelsolution_requiresubmission', $plugin->settings_validation($data, []));
        $data['submissiondrafts'] = 1;
        $this->assertSame([], $plugin->settings_validation($data, []));
    }

    /**
     * Without a due date, "after the due date" never releases; the form says so.
     */
    public function test_no_due_date(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $anna = $this->getDataGenerator()->create_and_enrol($course, 'student');
        [$assign, $plugin] = $this->assignment($course, [
            'duedate' => 0,
            'assignfeedback_modelsolution_releasetype' => \assign_feedback_modelsolution::RELEASE_DATE,
            'assignfeedback_modelsolution_releasetime' => time() - 60,
        ]);
        $this->assertTrue($plugin->can_view($anna->id));
        $plugin->save_release(['releasetype' => \assign_feedback_modelsolution::RELEASE_AFTERDUE]);
        $this->assertFalse($plugin->can_view($anna->id));
        $errors = $plugin->settings_validation(['assignfeedback_modelsolution_enabled' => 1,
            'assignfeedback_modelsolution_releasetype' => \assign_feedback_modelsolution::RELEASE_AFTERDUE, 'duedate' => 0], []);
        $this->assertArrayHasKey('assignfeedback_modelsolution_releasetype', $errors);
    }
}
