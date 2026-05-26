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
            case 0:  $skyCondition = "Clear Sky"; break;
            case 1:
            case 2:
            case 3:  $skyCondition = "Cloudy"; break;
            case 45:
            case 48: $skyCondition = "Foggy"; break;
            case 51:
            case 53:
            case 55: $skyCondition = "Drizzle"; break;
            case 61:
            case 63:
            case 65: $skyCondition = "Rainy"; break;
            case 71:
            case 73:
            case 75: $skyCondition = "Snow"; break;
            case 95: $skyCondition = "Thunderstorm"; break;
            default: $skyCondition = "Unknown";
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
    <link rel="stylesheet" href="./Stylesheets/weatherStyle.css">
    <style>
        /* Force nav bar to always be full-width at the top */
        html, body {
            margin: 0;
            padding: 0;
        }

        .nav-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 24px;
            background: rgba(15, 20, 40, 0.95);
            border-bottom: 1px solid rgba(255, 140, 0, 0.25);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            width: 100%;
            box-sizing: border-box;
        }

        .nav-bar a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .nav-bar .back-btn {
            background: rgba(255, 140, 0, 0.15);
            border: 1px solid rgba(255, 140, 0, 0.4);
        }
        .nav-bar .back-btn:hover { background: rgba(255, 140, 0, 0.3); }

        .nav-bar .logout-btn {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.4);
        }
        .nav-bar .logout-btn:hover { background: rgba(220, 38, 38, 0.3); }

        /* Push content below the fixed nav */
        .page-content {
            padding-top: 70px;
        }
    </style>
    <title>Weather Monitor | Disaster Surveillance System</title>
</head>
<body>

<!-- FIXED TOP NAV BAR -->
<div class="nav-bar">
    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    <a href="logout.php" class="logout-btn">🚪 Logout</a>
</div>

<!-- PAGE CONTENT (pushed below fixed nav) -->
<div class="page-content">
    <div class="container">

    <?php if ($error): ?>
        <p><?php echo htmlspecialchars($error); ?></p>

    <?php elseif ($temperature !== null): ?>

        <div class="location">
            📍 <?php echo htmlspecialchars($locationName); ?>
        </div>

        <div class="card">
            <h2>🌡 Temperature: <?php echo $temperature; ?>°C</h2>
            <div class="status <?php
                if ($temperature <= 10) echo "cold";
                elseif ($temperature <= 20) echo "cool";
                elseif ($temperature <= 32) echo "warm";
                else echo "hot";
            ?>">
                <?php
                    if ($temperature <= 10) echo "Cold";
                    elseif ($temperature <= 20) echo "Cool";
                    elseif ($temperature <= 32) echo "Warm";
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
            <h2>🌤 Sky Condition: <?php echo htmlspecialchars($skyCondition); ?></h2>
        </div>

    <?php else: ?>
        <p>📡 Waiting for coordinates...</p>
    <?php endif; ?>

    </div>
</div>

<script>
const params = new URLSearchParams(window.location.search);

if (!params.has("lat") || !params.has("lon")) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                window.location.href = `?lat=${position.coords.latitude}&lon=${position.coords.longitude}`;
            },
            (error) => {
                window.location.href = `?lat=15.9010&lon=120.5987`;
            },
            { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
        );
    } else {
        window.location.href = `?lat=15.9010&lon=120.5987`;
    }
}
</script>

</body>
</html>