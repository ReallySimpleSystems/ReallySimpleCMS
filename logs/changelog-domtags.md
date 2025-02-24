# DOMtags Changelog

**Legend**
- N = new file
- D = deprecated file
- R = renamed file
- X = removed file
- M = minor change

**Versions**
- X.x.x (major release)
- x.X.x (feature update release)
- x.x.X (standard/minor release)
- x.x.x.X (bug fix/emergency patch release)

## Version 1.1.4.2 (2024-10-15)

- Incremented version

**Modified files:**
- n/a

## Version 1.1.4.1 (2024-10-15)

- Untracked a file in `.gitignore`

**Modified files:**
- .gitignore

## Version 1.1.4 (2024-10-15)

- Renamed `DomTag` class to `DomTags`
- Restructured internal directories

**Modified files:**
- class-dom-tags.php (M)
- dom-tags/(.\*) (M)

## Version 1.1.3.1 (2024-10-15)

- Fixed `.gitignore` not removing untracked files

**Modified files:**
- n/a

## Version 1.1.3 (2024-10-15)

- Moved `dom-tags.php` to root directory and renamed it to `domtags.php`
- Added some files to the `.gitignore` file

**Modified files:**
- .gitignore
- README.md (M)
- domtags.php (R)

## Version 1.1.2.1 (2024-10-14)

- Added version constant to the core software and incremented version

**Modified files:**
- includes/dom-tags.php

## Version 1.1.2 (2024-08-05)

- Tweaked README to include information about PHP requirements
- Created a .gitignore file
- Minor code cleanup
- Added support for the following tags and their properties:
  - `table`, `td`, `th`, `tr`

**Modified files:**
- .gitignore (N)
- README.md
- includes/dom-tags.php
- includes/dom-tags/class-div-tag.php (M)
- includes/dom-tags/class-span-tag.php (M)
- includes/dom-tags/class-table-cell-tag.php (N)
- includes/dom-tags/class-table-row-tag.php (N)
- includes/dom-tags/class-table-tag.php (N)
- includes/dom-tags/interface-dom-tag.php (M)

## Version 1.1.1 (2024-07-31)

- Added support for the `code` tag and its properties
- Tweaked internal documentation

**Modified files:**
- includes/dom-tags.php (M)
- includes/dom-tags/class-code-tag.php (N)
- includes/functions.php (M)

## Version 1.1.0 (2024-06-19)

- Whitelisted the `placeholder` parameter for the `textarea` tag
- Rewrote internal logic of the `domTag` function
- Tweaked internal documentation
- Added an index page for testing
- Added a functions file and moved several files to a new `/includes` directory
- Code cleanup

**Bug fixes:**
- The `input` tag's default `type` parameter (`type="text"`) isn't properly being generated

**Modified files:**
- includes/class-dom-tag.php
- includes/dom-tags.php
- includes/dom-tags/class-textarea-tag.php (M)
- includes/functions.php (N)
- index.php (N)

## Version 1.0.6 (2024-04-05)

- Updated the copyright year in the README
- Rearranged the order of `button` params

**Modified files:**
- README.md (M)
- dom-tags/class-button-tag.php (M)

## Version 1.0.5 (2024-01-02)

- Whitelisted the `type` parameter for the `button` tag

**Bug fixes:**
- The `props` method of the `ButtonTag` class references a property of itself instead of the parent class

**Modified files:**
- dom-tags/class-button-tag.php (M)

## Version 1.0.4.1 (2023-12-13)

- Removed a reference to ReallySimpleCMS, a separate project, in the README

**Modified files:**
- README.md (M)

## Version 1.0.4 (2023-12-13)

- Tweaked a previous entry in the changelog
- Whitelisted the global `style` property
- Whitelisted the `autofocus` property for the `input` tag
- Whitelisted the `alt` and `height` properties for the `img` tag

**Modified files:**
- class-dom-tag.php (M)
- dom-tags/class-image-tag.php (M)
- dom-tags/class-input-tag.php (M)

## Version 1.0.3 (2023-11-06)

**Bug fixes:**
- The label arg for the central `constructTag` isn't properly constructed into a wrapping tag

**Modified files:**
- class-dom-tag.php

## Version 1.0.2.1 (2023-09-30)

**Bug fixes:**
- The new `strong` tag can't be used by the `domTag` function

**Modified files:**
- dom-tags.php (M)

## Version 1.0.2 (2023-09-30)

- Added author data to core files
- Added support for the following tags and their properties:
  - `b`, `strong`

**Bug fixes:**
- The return statement of the `DivTag::props` method ends with a comma instead of a semicolon

**Modified files:**
- dom-tags/class-div-tag.php (M)
- dom-tags/class-strong-tag.php (N)

## Version 1.0.1 (2023-09-21)

- Added support for the following tags and their properties:
  - `abbr`, `h1-h6`, `section`
- Whitelisted the `pattern` and `required` properties for the `input` tag
- Dom tags can now be created without directly invoking the individual classes
- New functions:
  - `dom-tags.php` (`domTag`)

**Modified files:**
- class-dom-tag.php (M)
- dom-tags.php
- dom-tags/class-abbr-tag.php (N)
- dom-tags/class-heading-tag.php (N)
- dom-tags/class-input-tag.php (M)
- dom-tags/class-section-tag.php (N)

## Version 1.0.0 (2023-02-15)

- Set up core files and initial tags
- The following tags and their properties are supported:
  - `a`, `br`, `button`, `div`, `em`, `fieldset`, `form`, `hr`, `i`, `img`, `input`, `label`, `li`, `ol`, `option`, `p`, `select`, `span`, `textarea`, `ul`

**Modified files:**
- LICENSE.md (N)
- README.md (N)
- changelog.md (N)
- class-dom-tag.php (N)
- dom-tags.php (N)
- dom-tags/class-anchor-tag.php (N)
- dom-tags/class-button-tag.php (N)
- dom-tags/class-div-tag.php (N)
- dom-tags/class-em-tag.php (N)
- dom-tags/class-fieldset-tag.php (N)
- dom-tags/class-form-tag.php (N)
- dom-tags/class-image-tag.php (N)
- dom-tags/class-input-tag.php (N)
- dom-tags/class-label-tag.php (N)
- dom-tags/class-list-item-tag.php (N)
- dom-tags/class-list-tag.php (N)
- dom-tags/class-option-tag.php (N)
- dom-tags/class-paragraph-tag.php (N)
- dom-tags/class-select-tag.php (N)
- dom-tags/class-separator-tag.php (N)
- dom-tags/class-span-tag.php (N)
- dom-tags/class-textarea-tag.php (N)
- dom-tags/interface-dom-tag.php (N)