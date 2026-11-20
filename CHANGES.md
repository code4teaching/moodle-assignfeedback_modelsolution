# Changes

## 1.0.1 (2026-11-20)

* Site defaults for new assignments: release after the due date and only for students who submitted.
* "Only for students who submitted" needs "Require students to click the submit button"; the assignment form and
  the web service say so. Otherwise a student could submit anything, look at the model solution and change the
  submission afterwards.

## 1.0.0 (2026-11-20)

First release, based on assignfeedback_solutionsheet 4.0.0 by Henning Bostelmann.

* Moodle 5.0 and later.
* Students see the model solution after their own due date: extensions and user or group overrides count.
* "At a date" never releases before a student's own due date.
* One capability for releasing and hiding (`assignfeedback/modelsolution:release`), checked everywhere.
* Web services to read and set the model solution.
* German translation.
