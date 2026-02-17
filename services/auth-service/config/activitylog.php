<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Activity Logging
    |--------------------------------------------------------------------------
    |
    | This option enables activity logging for the application.
    | When disabled, no activities will be logged to the database.
    |
    */

    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Activity Model
    |--------------------------------------------------------------------------
    |
    | This is the model used to store activity logs.
    | You can customize this to use your own model.
    |
    */

    'activity_model' => \Spatie\Activitylog\Models\Activity::class,

    /*
    |--------------------------------------------------------------------------
    | Activity Table Name
    |--------------------------------------------------------------------------
    |
    | This is the table name used to store activity logs.
    |
    */

    'table_name' => env('ACTIVITY_LOGGER_TABLE_NAME', 'activity_logs'),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | This is the database connection used to store activity logs.
    | When set to null, the default connection will be used.
    |
    */

    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This option determines if activity logs should be soft deleted.
    |
    */

    'soft_delete' => env('ACTIVITY_LOGGER_SOFT_DELETE', false),

    /*
    |--------------------------------------------------------------------------
    | Log Events
    |--------------------------------------------------------------------------
    |
    | These are the events that will be logged automatically.
    |
    */

    'log_events' => [
        'created',
        'updated',
        'deleted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subject Reset
    |--------------------------------------------------------------------------
    |
    | This option determines if the subject should be reset after logging.
    |
    */

    'subject_returns_soft_deleted_models' => false,

];
