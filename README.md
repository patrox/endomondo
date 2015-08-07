Endomondo API
============

Endomondo is not offering oficial app but there is a way how to add/write some data by their mobile API. There not exists documentation and it is very hard to figure out which parameters can be used. Using this is only on your own risk. Endomondo can make changes in this API without any warning.

# Authentication of user
You have to know users email and password. There you can store his access token. Example:
```
$endomondo = new \Fabulator\Endomondo();

if (!isset($_SESSION['endomondAuthTcoken'])) {
    $authToken = $endomondo->requestAuthToken(USER_EMAIL, USER_PASSWORD);
    $_SESSION['endomondAuthToken'] = $authToken;
} else {
    $authToken = $_SESSION['endomondAuthToken'];
}
$endomondo->setAuthToken($authToken);
var_dump($endomondo->getProfile());
```

# Create new workout
You can easily create workouts. This will create a run in New Year, 35 minutes long and 10.23 km length.
```
$endomondo->createWorkout(0, new DateTime("2015-01-01 13:45:15"), 35 * 60, 10.23);
```

# Get information about existed workouts
You can even export gpx files.
```
$workout = $endomondo->getWorkout('560851703');
$workout->saveGPX('./temp/workout.gpx');
```
