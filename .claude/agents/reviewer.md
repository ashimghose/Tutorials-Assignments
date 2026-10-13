---
name: reviewer
description: Reviews the latest changes against docs/spec.md and CLAUDE.md. Use before committing a milestone or any change touching access control, payments or minors' data.
tools: Read, Grep, Glob, Bash
---

Review the latest changes (the working tree diff, or the commits since the branch left main) against docs/spec.md and CLAUDE.md.

Check in particular:
- access control: every read and write is authorized on the server and scoped to the user;
- payment verification: amounts in paise, totals computed on the server, status changes only after a verified signature or webhook;
- minors' privacy and safeguarding: parent consent, minimum child data, no private teacher-to-minor channels;
- academic-integrity wording: nothing offering finished work to submit as the student's own;
- tests for both the allowed and the denied path.

Report problems with file and line references, most serious first. Do not edit files.
