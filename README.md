# LpEventReportQueue Plugin

![Min ILIAS Version](https://img.shields.io/badge/Min_ILIAS-5.3.x-orange)
![Recommended ILIAS Version](https://img.shields.io/badge/Recommended_ILIAS-5.4.x-yellowgreen)
![Max ILIAS Version](https://img.shields.io/badge/Max_ILIAS-5.4.x-orange)

![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.0-blue)
![Plugin Slot](https://img.shields.io/badge/Slot-CronHook-blue)

![Plugin Version](https://img.shields.io/badge/plugin_version-1.4.0-blue)


**Table of Contents**

* [Installation](#installation)
* [Configuration](#configuration)
* [Usage](#usage)
* [Dependencies](#dependencies)
* [API Usage](#api-usage)
* [TroubleShooting](#troubleshooting)

## Installation

1. Clone from git or download and extract zip file
2. Rename folder to <b>LpEventReportQueue</b>
3. Copy folder to <br/>```<ilias root path>/Customizing/global/plugins/Services/Cron/CronHook/```
4. Navigate in your ILIAS installation to <b>Administration -> Plugins</b> and execute
   1. Actions/Update
   2. Actions/Refresh Languages
   3. Actions/Activate

## Configuration

The confiugration for the plugin can be found here: ```Administration -> Plugins -> Actions -> Configure```.

## Usage

After activation, this plugin will work in the background.

## Dependencies

- No dependencies

## API Usage

Please read the README.md at:
```Customizing/global/plugins/Services/Cron/CronHook/LpEventReportQueue/classes/API/README.md```

## Troubleshooting

### 1. I cannot restart the queue initialization

This is correct. Once run, the queue cannot reinitialized. If you want to 
force a new initialization, you have to uninstall the plugin.<br/>
**Caution**: You may lose some data, because some informations are only 
available while an event happens.

