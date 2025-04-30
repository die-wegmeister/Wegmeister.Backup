Wegmeister.Backup
=================

Neos CMS package that provides import & export functionality for content.

# Installation

Go to your site package and add this package to your composer dependencies:

```bash
cd DistributionPackages/Vendor.Site
composer require wegmeister/backup --no-update
```

Then go back to your project root and run the actual update:

```bash
composer update
```

# Commands

## Export parts of the content tree

To export parts of the content tree, run the following command:

```bash
./flow content:export siteNodeName sourceNodeIdentifier filename
```

where
- `siteNodeName` is the name of the site node you want to export from,
- `sourceNodeIdentifier` is the node identifier of the node you want to use as starting point; this node and all its descendants will be exported,
- `filename` is the name of the path and (xml) file to which the content will be exported (fe. `./Backup/export.xml`). Next to the file, a folder `Resources` with all the exported resources will be created. The folder will be created in the same directory as the file.

Further options:
- `--tidy`: will create a tidy XML file (no line breaks, no indentation)
- `--nodeTypeFilter=FILTER`: will only export nodes matching the filter (e.g. "Neos.Neos:Page", "!Neos.Neos:Page,Neos.Neos:Text").
- `--workspace=WORKSPACE_NAME`: can be used to export another workspace. If not set, the `live` workspace will be exported.

## Import parts of the content tree

To import previously exported data, run the following command:

```bash
./flow content:import siteNodeName filename
```

where
- `siteNodeName` is the name of the site node you want to import to,
- `filename` is the name of the path and (xml) file containing the previously exported data (fe. `./Backup/export.xml`).

# Special thanks

Special thanks to [Sebastian Helzle](https://github.com/sebobo), who shared this code via Slack. Maybe some more commands will follow if needed (some of which are already mentioned in [Flowpack.ContentTransfer](https://github.com/Flowpack/Flowpack.ContentTransfer/)).
