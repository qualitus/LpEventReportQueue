# LpEventReportQueue API

**Table of Contents**

* [Init API](#init-api)
* [Usage](#usage)
  * [Register Provider](#register-provider)
  * [Create Filter](#create-filter)
  * [Query Data](#query-data)
  * [updateProvider](#update-provider)
  * [unregisterProvider](#unregister-provider)
  * [Override Routines](#override-routines)
* [Troubleshooting](#trouble-shooting)

## Init API
To init the API you should first check if the plugin autoloader is available:
```php
if (isset($DIC['autoload.lc.lcautoloader']))
```
If the autoloader is not available, you should try to init the plugin via 
ilPluginAdmin like this:
```php
\ilPluginAdmin::getPluginObject(
    "Services", 
    "Cron", 
    "crnhk", 
    "LpEventReportQueue"
);
```
At the plugins init method, the autoloader will be initialized and propagated 
to the DIC.

When the autoloader is available, you should check if the API is available:
```php
if(isset($DIC['qu.lerq.api']))
```
If the API is not available and you have not already tried to init the plugin 
(like in the last step), then you should try to init the plugin. If you already 
tried this, take a look at the [Troubleshooting](#trouble-shooting).

If the API is available, you may use it. Therefor, the API has three methods.
You will see more information below. You also may click on a method to get
to its description part of the readme.
* [registerProvider](#register-provider)
* [createFilterObject](#create-filter)
* [getCollection](#query-data)
* [updateProvider](#update-provider)
* [unregisterProvider](#unregister-provider)

## Usage

### Register Provider
Once the [API is initialized](#init-api) you may register your provider. To do
this, you can simply call
```php
$DIC['qu.lerq.api']->registerProvider('MyProvider', '\My\Provider\Namespace', realpath(dirname('MyProviderPlugin.php')), True|False)
```
*For the third parameter (path) you should propagate the realpath*

The method returns a boolean value. It is True if the provider was 
successful registered or if it is already registered. It returns False if there
was a problem registering the provider. 

For more information you should consult the Faceade Interface at: 
```Customizing/global/plugins/Services/Cron/CronHook/LpEventReportQueue/classes/API/Facade.php```

### Create Filter 
To use the filter, you have to create an FilterObject. To do this, the 
[API must be initialized](#init-api).

Now you can create the filter in two steps. First create a new object:
```php
$filter = $DIC['qu.lerq.api']->createFilterObject()
```
This method does not take any parameters and returns a FilterObject instance.

At this object you have a bunch of methods to set your filters. All of these
methods are chainable, so you may call it like:
```php
$filter->setPageStart(5)
    ->setPageLength(5)
    ->setEventType('lp_event');
```
So your second step is to set the filters at the FilterObject. Afterwards it
is ready to be used to get the [queue data](#query-data).

For more information you should consult the Faceade Interface at: 
```Customizing/global/plugins/Services/Cron/CronHook/LpEventReportQueue/classes/API/Facade.php```

### Query Data
To query the queue, you need to [initialize the api](#init-api) and create an [FilterObject](#create-filter).
If you have done these steps, you can query the queue data by passing your 
FilterObject variable to the API's method "getCollection":
```php
$data = $DIC['qu.lerq.api']->getCollection($filter, True|False)
``` 
Within the FilterObject, every filter values are declared to get the collection.

This method returns an iteratable QueueCollection object, that holds a an
array of arrays (if the second parameter is True) or QueueModel objects. 

The advantage of the QueueModels is the you may use it methods to get the
data or you may call its __toString method, to get a JSON string of the data.
If you use the __toString method, the sub objects (UserModel, ObjectModel, 
MemberModel) will also be converted into json string.

So you will get a JSON string like:
```json
{
  "id": 0,
  "timestamp": "2019-03-25T12:32:52+02:00",
  "event": "progress_changed",
  "event_type": "lp_event",
  "progress": "in_progress",
  "assignment": null,
  "course_start": "2019-03-20T06:00:00+02:00",
  "course_end": "2019-04-05T19:00:00+02:00",
  "user_data": {
    "usr_id": 15,
    "username": "maxmuster",
    "firstname": "max",
    "lastname": "muster",
    "title": "",
    "gender": "m",
    "email": "max@muster.de",
    "institution": "",
    "street": "",
    "city": "",
    "country": "",
    "phone_office": "",
    "hobby": "",
    "department": "",
    "phone_home": "",
    "phone_mobile": "",
    "phone_fax": "",
    "referral_comment": "",
    "matriculation": "",
    "active": true,
    "approval_date": "2019-01-01 15:45:52",
    "agree_date": null,
    "auth_mode": "default",
    "ext_account": null,
    "birthday": null,
    "udf_data": { "...": "..." } 
  },
  "obj_data": {
    "id": 12, 
    "title": "excersice abc", 
    "ref_id": 123, 
    "link": "", 
    "type": "exc", 
    "course_title": "course abc", 
    "course_id": 11, 
    "course_ref_id": 120
  },
  "mem_data": { 
    "role": null, 
    "course_title": null, 
    "course_id": null, 
    "course_ref_id":  null
  }
}
```

If you prefer to use the method calls of the QueueModel objects, you may 
get the sub objects either as json string, like above, or as objects.

For more information you should consult the Faceade Interface at:</br> 
```Customizing/global/plugins/Services/Cron/CronHook/LpEventReportQueue/classes/API/Facade.php```
<br/>or the models at:</br>
```Customizing/global/plugins/Services/Cron/CronHook/LpEventReportQueue/classes/Model```

### Update Provider

Once your provider gets an update, you **should** call the updateProvider method.<br/>
If this method gets called, the api checks for new overrides from the provider.
```php
$DIC['qu.lerq.api']->updateProvider('MyProvider', '\My\Provider\Namespace', realpath(dirname('MyProviderPlugin.php')), True|False)
```
*For the third parameter (path) you should propagate the realpath*

You should keep in mind, that only the path (third parameter) and the hasOverrides (fourth parameter) can be updated.

The method returns a boolean value. It is True if the provider was 
successful updated. It returns False if there was a problem updating 
the provider. 

For more information you should consult the Faceade Interface at: 
```Customizing/global/plugins/Services/Cron/CronHook/LpEventReportQueue/classes/API/Facade.php```

### Unregister Provider

At the deinstallation of your provider, you **should** call the unregister method, 
to prevent failures while the collection process.
```php
$DIC['qu.lerq.api']->unregisterProvider('MyProvider', '\My\Provider\Namespace')
```

The method returns a boolean value. It is True if the provider was 
successful unregistered or if it is not already registered. It returns False if there
was a problem unregistering the provider. 

For more information you should consult the Faceade Interface at: 
```Customizing/global/plugins/Services/Cron/CronHook/LpEventReportQueue/classes/API/Facade.php```


## Override Routines

To override collection routines, your provider **must** get a class *Routines* at the path<br/>
```[Plugin Root]/classes/CaptureRoutines/Routines.php```

This class **must** implement the interface: **\QU\LERQ\API\DataCaptureRoutinesInterface**.

Inside this class, you may use the predefined methods to override the collection process. 
Be careful, because you are not extending the routines. Instead you literally override them.

For more information you should consult the DataCaptureRoutinesInterface Interface at: 
```Customizing/global/plugins/Services/Cron/CronHook/LpEventReportQueue/classes/API/DataCaptureRoutinesInterface.php```

## Troubleshooting

-
