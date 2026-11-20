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
 * The model solution feedback plugin.
 *
 * Teachers attach a model solution to an assignment. Students see it on the assignment page once it is released:
 * by hand, right away, a set time after their own due date or at a fixed date, but never before their own due date.
 * Their own due date takes user and group overrides and extensions into account.
 *
 * @package    assignfeedback_modelsolution
 * @copyright  2026 Holger Sebastião, based on assignfeedback_solutionsheet (2016 Henning Bostelmann)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Model solution feedback plugin.
 *
 * @package    assignfeedback_modelsolution
 * @copyright  2026 Holger Sebastião, based on assignfeedback_solutionsheet (2016 Henning Bostelmann)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_feedback_modelsolution extends assign_feedback_plugin {

    /** File area of the model solution. */
    const FILEAREA = 'modelsolution';
    /** Hidden until a teacher releases it. */
    const RELEASE_MANUAL = 0;
    /** Released. */
    const RELEASE_NOW = 1;
    /** Released a set time after the student's own due date. */
    const RELEASE_AFTERDUE = 2;
    /** Released at a fixed date, but not before the student's own due date. */
    const RELEASE_DATE = 3;
    /** Release time meaning "not released". */
    const NEVER = -1;

    /**
     * Name of the plugin.
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'assignfeedback_modelsolution');
    }

    /**
     * File areas of the plugin configuration, used for backup and restore.
     *
     * @return array
     */
    public function get_config_file_areas() {
        return [self::FILEAREA];
    }

    /**
     * File areas of the plugin.
     *
     * @return array
     */
    public function get_file_areas() {
        return [self::FILEAREA => $this->get_name()];
    }

    /**
     * The files of the model solution.
     *
     * @return stored_file[]
     */
    public function get_files(): array {
        return get_file_storage()->get_area_files($this->assignment->get_context()->id, 'assignfeedback_modelsolution',
            self::FILEAREA, 0, 'filename', false);
    }

    /**
     * Release options for the settings form.
     *
     * @return array
     */
    public static function release_options(): array {
        return [
            self::RELEASE_AFTERDUE => get_string('releaseafterdue', 'assignfeedback_modelsolution'),
            self::RELEASE_DATE => get_string('releasedate', 'assignfeedback_modelsolution'),
            self::RELEASE_MANUAL => get_string('releasemanual', 'assignfeedback_modelsolution'),
            self::RELEASE_NOW => get_string('releasenow', 'assignfeedback_modelsolution'),
        ];
    }

    /**
     * Settings in the assignment form.
     *
     * @param MoodleQuickForm $mform
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        $p = 'assignfeedback_modelsolution_';
        $mform->addElement('filemanager', $p . 'upload', get_string('upload', 'assignfeedback_modelsolution'), null,
            ['subdirs' => 0]);
        $mform->addElement('select', $p . 'releasetype', get_string('release', 'assignfeedback_modelsolution'),
            self::release_options());
        $mform->addHelpButton($p . 'releasetype', 'release', 'assignfeedback_modelsolution');
        $mform->setDefault($p . 'releasetype', $this->get_config('releasetype') === false ? self::RELEASE_AFTERDUE
            : (int) $this->get_config('releasetype'));
        $mform->addElement('duration', $p . 'releaseoffset', get_string('releaseoffset', 'assignfeedback_modelsolution'),
            ['optional' => false, 'defaultunit' => HOURSECS]);
        $mform->setDefault($p . 'releaseoffset', (int) $this->get_config('releaseoffset'));
        $mform->hideIf($p . 'releaseoffset', $p . 'releasetype', 'neq', self::RELEASE_AFTERDUE);
        $mform->addElement('date_time_selector', $p . 'releasetime', get_string('releasetime', 'assignfeedback_modelsolution'));
        $mform->setDefault($p . 'releasetime', (int) $this->get_config('releasetime') ?: time() + WEEKSECS);
        $mform->hideIf($p . 'releasetime', $p . 'releasetype', 'neq', self::RELEASE_DATE);
        $mform->addElement('advcheckbox', $p . 'requiresubmission',
            get_string('requiresubmission', 'assignfeedback_modelsolution'));
        $mform->addHelpButton($p . 'requiresubmission', 'requiresubmission', 'assignfeedback_modelsolution');
        $mform->setDefault($p . 'requiresubmission', (int) $this->get_config('requiresubmission'));
        $mform->addElement('date_time_selector', $p . 'hideafter', get_string('hideafter', 'assignfeedback_modelsolution'),
            ['optional' => true]);
        $mform->addHelpButton($p . 'hideafter', 'hideafter', 'assignfeedback_modelsolution');
        $mform->setDefault($p . 'hideafter', (int) $this->get_config('hideafter'));
        foreach (['upload', 'releasetype', 'releaseoffset', 'releasetime', 'requiresubmission', 'hideafter'] as $name) {
            $mform->hideIf($p . $name, $p . 'enabled', 'notchecked');
        }
    }

    /**
     * Checks the settings: a release after the due date needs a due date.
     *
     * @param array $data
     * @param array $files
     * @return array errors
     */
    public function settings_validation(array $data, array $files): array {
        $p = 'assignfeedback_modelsolution_';
        if (empty($data[$p . 'enabled'])) {
            return [];
        }
        $errors = [];
        $type = (int) ($data[$p . 'releasetype'] ?? self::RELEASE_AFTERDUE);
        if ($type === self::RELEASE_AFTERDUE && empty($data['duedate'])) {
            $errors[$p . 'releasetype'] = get_string('errorneedsduedate', 'assignfeedback_modelsolution');
        }
        $hide = (int) ($data[$p . 'hideafter'] ?? 0);
        if ($hide && $type === self::RELEASE_DATE && $hide <= (int) ($data[$p . 'releasetime'] ?? 0)) {
            $errors[$p . 'hideafter'] = get_string('errorhidebeforerelease', 'assignfeedback_modelsolution');
        }
        return $errors;
    }

    /**
     * Prepares the file manager of the settings form.
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        $context = $this->assignment->get_context();
        $draftitemid = file_get_submitted_draft_itemid('assignfeedback_modelsolution_upload');
        file_prepare_draft_area($draftitemid, $context ? $context->id : null, 'assignfeedback_modelsolution', self::FILEAREA, 0,
            ['subdirs' => 0]);
        $defaultvalues['assignfeedback_modelsolution_upload'] = $draftitemid;
    }

    /**
     * Saves the settings from the assignment form.
     *
     * @param stdClass $formdata
     * @return bool
     */
    public function save_settings(stdClass $formdata) {
        $p = 'assignfeedback_modelsolution_';
        if (isset($formdata->{$p . 'upload'})) {
            file_save_draft_area_files($formdata->{$p . 'upload'}, $this->assignment->get_context()->id,
                'assignfeedback_modelsolution', self::FILEAREA, 0, ['subdirs' => 0]);
        }
        $this->save_release([
            'releasetype' => $formdata->{$p . 'releasetype'} ?? self::RELEASE_AFTERDUE,
            'releaseoffset' => $formdata->{$p . 'releaseoffset'} ?? 0,
            'releasetime' => $formdata->{$p . 'releasetime'} ?? 0,
            'requiresubmission' => $formdata->{$p . 'requiresubmission'} ?? 0,
            'hideafter' => $formdata->{$p . 'hideafter'} ?? 0,
        ]);
        return true;
    }

    /**
     * Saves release settings. Keys that are missing stay as they are.
     *
     * @param array $settings releasetype, releaseoffset, releasetime, requiresubmission, hideafter
     */
    public function save_release(array $settings): void {
        foreach (['releasetype', 'releaseoffset', 'releasetime', 'requiresubmission', 'hideafter'] as $key) {
            if (array_key_exists($key, $settings)) {
                $value = (int) $settings[$key];
                if ($key === 'releasetype' && !array_key_exists($value, self::release_options())) {
                    throw new invalid_parameter_exception('releasetype');
                }
                $this->set_config($key, $key === 'requiresubmission' ? (int) !empty($value) : max(0, $value));
            }
        }
    }

    /**
     * Release by hand (now) or hide by hand. Releasing also removes a "hide after" date that has passed and turns
     * the plugin on for this assignment.
     *
     * @param bool $release
     */
    public function set_release_by_hand(bool $release): void {
        if ($release) {
            $this->enable();
            $settings = ['releasetype' => self::RELEASE_NOW];
            if ($this->is_hidden_again()) {
                $settings['hideafter'] = 0;
            }
            $this->save_release($settings);
        } else {
            $this->save_release(['releasetype' => self::RELEASE_MANUAL]);
        }
    }

    /**
     * Release settings of this assignment.
     *
     * @return array
     */
    public function get_release(): array {
        return [
            'releasetype' => $this->get_config('releasetype') === false ? self::RELEASE_MANUAL
                : (int) $this->get_config('releasetype'),
            'releaseoffset' => (int) $this->get_config('releaseoffset'),
            'releasetime' => (int) $this->get_config('releasetime'),
            'requiresubmission' => (int) $this->get_config('requiresubmission'),
            'hideafter' => (int) $this->get_config('hideafter'),
        ];
    }

    /**
     * Due date for a user: relative dates, user or group override and an extension, whichever is latest.
     * Without a userid: the due date of the assignment itself.
     *
     * @param int $userid
     * @return int timestamp, 0 = no due date
     */
    public function due_date(int $userid = 0): int {
        if (!$userid) {
            return (int) $this->assignment->get_default_instance()->duedate;
        }
        $due = (int) $this->assignment->get_instance($userid)->duedate;
        $override = $this->assignment->override_exists($userid);
        if (isset($override->duedate)) {
            $due = (int) $override->duedate;
        }
        $flags = $this->assignment->get_user_flags($userid, false);
        if ($flags && !empty($flags->extensionduedate) && $flags->extensionduedate > $due) {
            $due = (int) $flags->extensionduedate;
        }
        return $due;
    }

    /**
     * When the model solution is released for a user (or, without userid, for students without an individual due date).
     *
     * @param int $userid
     * @return int timestamp, 0 = released, self::NEVER = not released
     */
    public function release_time(int $userid = 0): int {
        $release = $this->get_release();
        switch ($release['releasetype']) {
            case self::RELEASE_NOW:
                return 0;
            case self::RELEASE_AFTERDUE:
                $due = $this->due_date($userid);
                return $due ? $due + $release['releaseoffset'] : self::NEVER;
            case self::RELEASE_DATE:
                return max($release['releasetime'], $this->due_date($userid));
            default:
                return self::NEVER;
        }
    }

    /**
     * Whether the "hide after" date has passed.
     *
     * @return bool
     */
    public function is_hidden_again(): bool {
        $hide = $this->get_release()['hideafter'];
        return $hide > 0 && $hide <= time();
    }

    /**
     * Whether the model solution is released for a user now (not counting "require submission").
     *
     * @param int $userid
     * @return bool
     */
    public function is_released(int $userid = 0): bool {
        $time = $this->release_time($userid);
        return $time !== self::NEVER && $time <= time() && !$this->is_hidden_again();
    }

    /**
     * Whether the user has submitted (for a team submission: the team).
     *
     * @param int $userid
     * @return bool
     */
    protected function has_submitted(int $userid): bool {
        $participant = $this->assignment->get_participant($userid);
        return $participant && !empty($participant->submitted);
    }

    /**
     * Whether a user may see the model solution now.
     *
     * @param int $userid default: the current user
     * @return bool
     */
    public function can_view(int $userid = 0): bool {
        global $USER;
        $userid = $userid ?: (int) $USER->id;
        $context = $this->assignment->get_context();
        if (has_capability('assignfeedback/modelsolution:viewanytime', $context, $userid)) {
            return true;
        }
        if (!has_capability('assignfeedback/modelsolution:view', $context, $userid) || !$this->is_released($userid)) {
            return false;
        }
        return !$this->get_release()['requiresubmission'] || $this->has_submitted($userid);
    }

    /**
     * The model solution on the assignment page, below the description.
     *
     * @return string
     */
    public function view_header() {
        global $USER, $OUTPUT;
        if (!$this->get_files()) {
            return '';
        }
        $context = $this->assignment->get_context();
        $release = $this->get_release();
        $teacher = has_capability('assignfeedback/modelsolution:viewanytime', $context);
        $o = $OUTPUT->heading(get_string('modelsolution', 'assignfeedback_modelsolution'), 3);
        $o .= $OUTPUT->box_start('generalbox assignfeedback_modelsolution');
        if ($this->can_view()) {
            $files = $this->assignment->render_area_files('assignfeedback_modelsolution', self::FILEAREA, 0);
            $o .= html_writer::div($files, $teacher && !$this->is_released() ? 'dimmed_text' : '');
        }
        $notes = [];
        if ($teacher) {
            $notes[] = $this->status_for_teachers();
        } else if ($this->is_hidden_again()) {
            $notes[] = get_string('nolonger', 'assignfeedback_modelsolution');
        } else if (!$this->can_view()) {
            $time = $this->release_time((int) $USER->id);
            $notes[] = $time > 0 ? get_string('availablefrom', 'assignfeedback_modelsolution', userdate($time))
                : get_string('notyet', 'assignfeedback_modelsolution');
            if ($release['requiresubmission']) {
                $notes[] = get_string('requiresubmissionnote', 'assignfeedback_modelsolution');
            }
        }
        $notes = array_filter($notes);
        if ($notes) {
            $o .= html_writer::tag('p', implode(' ', $notes));
        }
        if (has_capability('assignfeedback/modelsolution:release', $context)) {
            $o .= $this->release_button(!$this->is_released());
        }
        $o .= $OUTPUT->box_end();
        return $o;
    }

    /**
     * Status line for teachers: when students see the model solution.
     *
     * @return string
     */
    protected function status_for_teachers(): string {
        $release = $this->get_release();
        if ($this->is_hidden_again()) {
            return get_string('statushiddenagain', 'assignfeedback_modelsolution', userdate($release['hideafter']));
        }
        $time = $this->release_time();
        if ($time === self::NEVER) {
            $status = $release['releasetype'] === self::RELEASE_AFTERDUE
                ? get_string('statusnoduedate', 'assignfeedback_modelsolution')
                : get_string('statushidden', 'assignfeedback_modelsolution');
        } else if ($time <= time()) {
            $status = get_string('statusreleased', 'assignfeedback_modelsolution');
        } else {
            $status = get_string('statusfrom', 'assignfeedback_modelsolution', userdate($time));
        }
        if (in_array($release['releasetype'], [self::RELEASE_AFTERDUE, self::RELEASE_DATE])) {
            $status .= ' ' . get_string('statusindividual', 'assignfeedback_modelsolution');
        }
        if ($release['requiresubmission']) {
            $status .= ' ' . get_string('requiresubmissionnote', 'assignfeedback_modelsolution');
        }
        if ($release['hideafter'] && $time !== self::NEVER) {
            $status .= ' ' . get_string('statushideafter', 'assignfeedback_modelsolution', userdate($release['hideafter']));
        }
        return $status;
    }

    /**
     * Button to release the model solution now or to hide it.
     *
     * @param bool $release true: release now, false: hide
     * @return string
     */
    protected function release_button(bool $release): string {
        global $OUTPUT;
        $url = new moodle_url('/mod/assign/feedback/modelsolution/release.php',
            ['cmid' => $this->assignment->get_course_module()->id, 'release' => (int) $release, 'sesskey' => sesskey()]);
        $text = get_string($release ? 'dorelease' : 'dohide', 'assignfeedback_modelsolution');
        $confirm = new confirm_action(get_string($release ? 'confirmrelease' : 'confirmhide', 'assignfeedback_modelsolution'));
        return html_writer::div($OUTPUT->render(new action_link($url, $text, $confirm, ['class' => 'btn btn-secondary'])),
            'mt-2');
    }

    /**
     * Not shown in the grading table.
     *
     * @return bool
     */
    public function has_user_summary() {
        return false;
    }

    /**
     * Removes the files when the plugin data of the assignment is deleted.
     *
     * @return bool
     */
    public function delete_instance() {
        get_file_storage()->delete_area_files($this->assignment->get_context()->id, 'assignfeedback_modelsolution', self::FILEAREA);
        return true;
    }
}
