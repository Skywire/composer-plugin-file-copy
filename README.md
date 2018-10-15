# File Copy Composer Plugin

Allows files to be copied on dependency installation, default composer behaviour only runs scripts for the root package.

Will only work on modules with `skywire` in the package name, will only work with paths relative to the working directory, e.g. `dev`  instead of `./dev` or `../../dev`

Uses https://github.com/slowprog/CopyFile to copy files, see their documentation for configuration, only the `extras.copy-files` section is rquired

# Installation

From your module repository run 

`composer require skywire/file-copy-plugin` 