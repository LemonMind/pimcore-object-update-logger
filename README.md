# ObjectUpdateLoggerBundle for Pimcore by LemonMind

This is the Pimcore Bundle to record changes to your objects and see who is responsible for the changes. It also
can monitor your class definition changes.

## Installation

```
composer require lemonmind/pimcore-object-update-logger
bin/console pimcore:bundle:enable LemonmindObjectUpdateLoggerBundle
```

You can specify which object/class would you like to log

```yaml
lemonmind_object_update_logger:
    classes_to_log: TestClass1, TestClass2 # if removed all classes will be logged
    objects_to_log: TestClass1, TestClass2 # if removed all objects will be logged
#    disable_class_log: true               # if you want to disable class logging
#    disable_object_log: true              # if you want to disable object logging
```

Log will be saved in var/log/updateLogger.log

