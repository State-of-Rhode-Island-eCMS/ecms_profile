# ECMS Languages

Provides configuration and logic to customize the language switcher.

## Configuration

A configuration form is provided to select which active languages
should be removed from the switcher drop down.


## Alterations

The session negotiator was not respecting the session, making it impossible
to edit/delete a translation without first using the language switcher. This
extends the language session negotiator to add the ?language=XX to the
edit/delete node operation links.
