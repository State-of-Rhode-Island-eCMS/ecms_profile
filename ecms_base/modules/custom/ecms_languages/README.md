# ECMS Languages

Provides :

- Configuration and logic to customize the language switcher.
  - i.e. to pick the language the whole site is translated into.
- Form alter function to remove unwanted langcodes from search filter selector.
  - i.e. the language of content to appear in search results.

## Configuration

A configuration form is provided to select which active languages
should be removed from the switcher drop down.


## Alterations

The session negotiator was not respecting the session, making it impossible
to edit/delete a translation without first using the language switcher. This
extends the language session negotiator to add the ?language=XX to the
edit/delete node operation links.
