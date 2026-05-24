<?php
$temperature = null;
$weatherCode = null;
$error = null;

if (isset($_GET["lat"]) && isset($_GET["lon"])) {

    $latitude = $_GET["lat"];
    $longitude = $_GET["lon"];

    $url = "https://api.open-meteo.com/v1/forecast?"
    . "latitude=$latitude"
    . "&longitude=$longitude"
    . "&current=temperature_2m,weather_code,relative_humidity_2m"
    . "&daily=precipitation_probability_max"
    . "&timezone=auto";

    $geoUrl =
    "https://nominatim.openstreetmap.org/reverse?"
    . "format=json"
    . "&lat=$latitude"
    . "&lon=$longitude";

    $options = [
       "http" => [
          "header" => "User-Agent: MyWeatherApp/1.0\r\n"
        ]
    ];

    $context = stream_context_create($options);

    $geoResponse = file_get_contents($geoUrl, true, $context);

    $geoData = json_decode($geoResponse, true);
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        exit();
    } else {
       $data = json_decode($response, true);
       $temperature = $data["current"]["temperature_2m"];
       $weatherCode = $data["current"]["weather_code"];
       $humidity = $data["current"]["relative_humidity_2m"];
       $rainChance = $data["daily"]["precipitation_probability_max"][0];
       $skyCondition = "";
       switch ($weatherCode) {
            case 0:
               $skyCondition = "Clear Sky";
               break;
            case 1:
            case 2:
            case 3:
              $skyCondition = "Cloudy";
              break;
            case 45:
            case 48:
              $skyCondition = "Foggy";
              break;
            case 51:
            case 53:
            case 55:
              $skyCondition = "Drizzle";
              break;
            case 61:
            case 63:
            case 65:
              $skyCondition = "Rainy";
              break;
            case 71:
            case 73:
            case 75:
              $skyCondition = "Snow";
              break;
            case 95:
              $skyCondition = "Thunderstorm";
              break;
            default:
             $skyCondition = "Unknown";
        }

        $locationName =
          $geoData["address"]["city"]
          ?? $geoData["address"]["town"]
          ?? $geoData["address"]["village"]
          ?? "Unknown Location";
    }

    curl_close($ch);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel = "stylesheet" href = "./Stylesheets/weatherStyle.css">
    <title>Weather Monitor</title>
</head>
<body>
<div class="container">

<?php if ($error): ?>

    <p><?php echo $error; ?></p>

<?php elseif ($temperature !== null): ?>

    <div class="location">
        📍 <?php echo $locationName; ?>
    </div>

    <div class="card">
        <h2>🌡 Temperature: <?php echo $temperature; ?>°C</h2>

        <div class="status 
            <?php 
                if ($temperature <= 10) echo "cold";
                else if ($temperature <= 20) echo "cool";
                else if ($temperature <= 32) echo "warm";
                else echo "hot";
            ?>">
            <?php
                if ($temperature <= 10) echo "Cold";
                else if ($temperature <= 20) echo "Cool";
                else if ($temperature <= 32) echo "Warm";
                else echo "Hot";
            ?>
        </div>
    </div>

    <div class="card">
        <h2>💧 Humidity: <?php echo $humidity; ?>%</h2>
    </div>

    <div class="card">
        <h2>🌧 Rain Chance: <?php echo $rainChance; ?>%</h2>
    </div>

    <div class="card alert">
        <!-- ganito talago ito, wag nyo na lang galawin hahaha-->
        <h2>🌤 Sky Condition: <?php echo $skyCondition; ?></h2>
    </div>

<?php else: ?>

    <p>📡 Waiting for coordinates...</p>

<?php endif; ?>

</div>

<script>
const params = new URLSearchParams(window.location.search);

if (!params.has("lat") || !params.has("lon")) {

    navigator.geolocation.getCurrentPosition((position) => {

        const latitude = position.coords.latitude;
        const longitude = position.coords.longitude;

        window.location.href =
            `?lat=${latitude}&lon=${longitude}`;
    });

}

</script>

</body>
</html>