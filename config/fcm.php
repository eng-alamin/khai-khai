<?php

return [
    /*
    | Firebase Cloud Messaging (HTTP v1) for the mobile app's push notifications.
    |
    | FCM_CREDENTIALS = absolute path of the Firebase *service account* JSON
    | (Firebase console > Project settings > Service accounts > Generate new
    | private key). Keep that file OUTSIDE the public folder and out of git.
    | While it is empty or unreadable, push is simply switched off; the in-app
    | notification list keeps working.
    */
    'credentials' => env('FCM_CREDENTIALS'),

    // Optional: overrides the project_id found inside the JSON file.
    'project_id' => env('FCM_PROJECT_ID'),
];
