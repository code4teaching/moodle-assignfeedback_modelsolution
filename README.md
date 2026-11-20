# Model solution (assignfeedback_modelsolution)

A feedback plugin for the Moodle assignment activity: teachers attach a model solution, and students see it on the
assignment page – but never before their own due date.

* **When:** after the due date (plus an optional delay), at a date, now, or by hand. "After the due date" and
  "at a date" use each student's own due date, so **extensions and user or group overrides are respected**.
* **Only for students who submitted** (optional).
* **Hide again** from a date, e.g. before a test.
* Teachers and non-editing teachers see the model solution at any time, greyed out while students cannot see it.
  "Release now" and "Hide from students" buttons on the assignment page.
* Files are served only to users who may see the model solution right now.
* Backup, restore and course copy keep the model solution with the assignment.
* Web services `assignfeedback_modelsolution_get_solution` and `assignfeedback_modelsolution_set_solution`
  (used by [VisuDesk](https://visudesk.visucode.de)).

Requires Moodle 5.0 or later.

## Installation

Copy the folder to `mod/assign/feedback/modelsolution` (Moodle 5.1+: `public/mod/assign/feedback/modelsolution`)
or install the ZIP under *Site administration → Plugins → Install plugins*. Turn it on for new assignments by default
under *Site administration → Plugins → Activity modules → Assignment → Feedback plugins → Model solution*.

## Credits

Based on the idea and code of [Solution sheet](https://moodle.org/plugins/assignfeedback_solutionsheet) by
Henning Bostelmann, which is no longer maintained. Rewritten for current Moodle versions by Holger Sebastião
and Daniel Trageser.

## License

GNU GPL v3 or later.
