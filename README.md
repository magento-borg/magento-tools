### How to use

 1. Load source code:
    - `php deprecation-tool.php load-source-code --edition=ce --tags=2.1.0,2.1.1,2.1.2 --latest-2.3-rc1 --branch=2.3-develop`
    - To download the Magento source code from the Git repositories. This job may take some time (up to 1 hour)
 
 2. Generate metadata:
    - `php deprecation-tool.php generate-metadata --edition=ce --tags=2.1.0,2.1.1,2.1.2 --latest-2.3-rc1 --branch=2.3-develop`
    - To pre-generate metadata about each release This job may take some time (up to 1 hours)

 3. Generate changelog:
    - `php deprecation-tool.php generate-changelog --edition=ce --tags=2.1.0,2.1.1,2.1.2 --latest-2.3-rc1 --branch=2.3-develop`
    - To generate the information about what classes/methods and properties must be updated. This job may take some time (up to 3 minutes)

 4. Update source code:
    - `php deprecation-tool.php update-code --edition=ce --tags=2.1.0,2.1.1,2.1.2 --latest-2.3-rc1 --branch=2.3-develop`
    - To update source code of latest release

 5. Commit and push all changes to the required branch/repository.
    - All modifications will be done in var/releases/{latest_release}/{magento2ce,...} folders


#### Options:
 --edition : The Magento edition to run the tool for (e.g. ce, ee, b2b)
 
 --tags : Previous releases to compare against. comma separated (e.g. 2.2.0,2.2.1,2.2.2)

 --latest : Version for new release. (e.g. 2.3-rc1)
 
 --branch : Branch with updated code for the new release (e.g. 2.3-develop)

