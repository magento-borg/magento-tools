### How to use
1. run `php source-code-loader.php` to download the Magento source code from the Git repositories. This job may take some time (up to 1 hour)
2. run `php source-code-metadata-generator.php` to pre-generate metadata about each release This job may take some time (up to 1 hours)
3. run `php changelog-generator.php` to generate the information about what classes/methods and properties must be updated. This job may take some time (up to 3 minutes)
4. run `php code-updater.php` to update source code of latest release
5. Commit and push all changes to the required branch/repository. All modifications will be done in var/releases/{latest_release}/{magento2ce,...} folders
