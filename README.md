# LpEventReportQueue Plugin

![Min ILIAS Version](https://img.shields.io/badge/Min_ILIAS-6.x-orange)
![Recommended ILIAS Version](https://img.shields.io/badge/Recommended_ILIAS-7.x-yellowgreen)
![Max ILIAS Version](https://img.shields.io/badge/Max_ILIAS-7.x-orange)

![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.2-blue)
![Plugin Slot](https://img.shields.io/badge/Slot-CronHook-blue)

![Plugin Version](https://img.shields.io/badge/plugin_version-6.0.0-blue)

The LpEventReportQueue plugin tracks certain events and historizes them in a database queue. Entries in the queue can later be accessed via API by other Plugins (see below).
Those Plugins are called _Provider_-Plugins, because they can transfer (=provide) the events to another system.

This approach decouples the collection and distribution of events into separate plugins (Queue + Provider). The Queue-Plugin can therefore be used in different contexts and even with multiple Providers at once.

Tracked events:

* LearningProgress (`no_attempted` | `in_progress` | `completed` | `failed`)
  * `updateStatus`
* Member (`Administrator` | `Tutor` | `Member`)
  * `addParticipant`
  * `deleteParticipant`
  * `addToWaitingList`
  * `removeFromWaitingList`
  * `createAssignment`
  * `deleteAssignment`
  * `updateAssignment`
* Object
  * `create`
  * `delete`
  * `toTrash`
  * `undelete`
  * `update`
  * `putObjectInTree`

**Table of Contents**

* [Installation](#installation)
* [Configuration](#configuration)
* [Initialization](#initialization)
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

  * Select which user data should be persisted (e.g. `firstname`, `lastname`, or custom fields)
  * Select if events should be gathered for all repository objects, or courses only

## Initialization

After installation of the plugin, the queue will be empty - even if there has already been learning activity.
This means, the queue starts in an incomplete state which can be "repaired" by initialization of the queue.
The initialization can only be started once and will approximate a sequence of events to match the current state of the system (learning progress, course memberships, etc.).

> _"better than nothing"_

Please bear in mind, that this reconstruction will *NOT* match the actual history: event dates can be incorrect and intermediate steps can be missing.

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

