<?php
  session_start();
  if(!isset($_SESSION["users"])){
     header("Location:login.php");
  }
  
  $user=$_SESSION["users"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel = "stylesheet" href = "./Stylesheets/dashboardStyle.css"> -->
    <title>Dashboard | Disaster Surveillance System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
            color: #e5e7eb;
            min-height: 100vh;
            padding: 20px;
        }

        #upbar {
            background: rgba(15, 20, 40, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 140, 0, 0.2);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #ff8c00;
            object-fit: cover;
        }

        .user-info h2 {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }

        .status-badge {
            text-align: center;
            flex: 1;
        }

        .status-badge h2 {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(135deg, #00bfff 0%, #ff8c00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .card {
            background: rgba(30, 40, 70, 0.6);
            border: 2px solid #ff8c00;
            border-radius: 16px;
            padding: 24px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .card:hover {
            border-color: #ffa520;
            box-shadow: 0 12px 40px rgba(255, 140, 0, 0.15);
            transform: translateY(-2px);
        }

        .card.alert {
            border: 2px solid #dc2626;
            background: rgba(40, 20, 20, 0.6);
            padding: 32px;
        }

        .card.alert h1 {
            font-size: 28px;
            margin-bottom: 12px;
            color: #ff8c00;
            font-weight: 700;
        }

        .card.alert p {
            font-size: 15px;
            color: #d1d5db;
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .card.small {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 180px;
            position: relative;
            overflow: hidden;
        }

        .card.small::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff8c00, #ffa520);
        }

        .card.small h3 {
            font-size: 18px;
            font-weight: 700;
            color: #ff8c00;
            margin-bottom: 16px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .card.small button,
        .card.small a {
            background: linear-gradient(135deg, #ff8c00 0%, #ffa520 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(255, 140, 0, 0.25);
        }

        .card.small button:hover,
        .card.small a:hover {
            background: linear-gradient(135deg, #ffa520 0%, #ffb540 100%);
            box-shadow: 0 6px 16px rgba(255, 140, 0, 0.35);
            transform: translateY(-1px);
        }

        .card.wide {
            grid-column: 1 / -1;
        }

        .card.wide h2 {
            font-size: 22px;
            font-weight: 700;
            color: #ff8c00;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card.wide p {
            color: #d1d5db;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .card.wide a {
            color: #00bfff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .card.wide a:hover {
            color: #00e5ff;
            text-decoration: underline;
        }

        .card.wide ul {
            list-style: none;
            margin-left: 0;
        }

        .card.wide ul li {
            color: #d1d5db;
            font-size: 15px;
            padding: 10px 0;
            padding-left: 28px;
            position: relative;
            line-height: 1.5;
        }

        .card.wide ul li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #ff8c00;
            font-weight: 700;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            #upbar {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }

            .status-badge h2 {
                font-size: 18px;
            }

            .card.alert {
                padding: 24px;
            }

            .card.alert h1 {
                font-size: 24px;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }
    </style>
</head>
<body>

<!-- TOP BAR -->
<div id="upbar">
    <div class="user-info">
        <img src="<?php echo $user['picture'] ?>" alt="Profile">
        <h2><?php echo $user["name"] ?></h2>
    </div>

    <div class="status-badge">
       <h2>Disaster Surveillance System</h2>
    </div>
</div>

<div class="container">

    <div class="card alert">
        <h1>🚨 Are you even prepared?</h1>
        <p>Disasters are unpredictable and can strike at any moment.</p>
        <p>Stay informed. Stay safe. Stay ready.</p>
    </div>

    <div class="grid">

        <div class="card small">
            <h3>🌧 Weather Status</h3>
            <button id = "btn">Monitor Weather</button>
        </div>

        <div class="card small">
            <h3>📍 Nearest Evacuation</h3>
            <button id = "btn2">Scan Location</button>
        </div>

        <div class="card small">
            <h3>🚨 Active Alerts</h3>
            <button id = "btn3">Scan for threats</button>
        </div>

        <div class="card small">
            <h3>☎ Emergency Hotlines</h3>
            <a href="./extras/Hotlines.html">Check Hotlines</a>
        </div>

    </div>

    <div class="card wide">
        <h2>📢 Disaster Readiness</h2>
        <p>Being prepared for a disaster can save lives and reduce property damage. Learn essential steps to protect yourself and your family.</p>
        <a href = "./extras/DisasterBlog.html">Learn about Disasters →</a>
    </div>

    <div class="card wide">
        <h2>🧠 Safety Tips</h2>
        <ul>
            <li>Prepare an emergency kit with water, food, first aid supplies</li>
            <li>Know your evacuation routes and have a meeting plan</li>
            <li>Stay updated with weather alerts and emergency notifications</li>
            <li>Keep communication devices and power banks charged</li>
        </ul>
    </div>

</div>
<script src = "script.js"></script>
<script>
       navigator.geolocation.getCurrentPosition(async(position) => {
        const latitude = position.coords.latitude;
        const longitude = position.coords.longitude;
        const response = await fetch(
            `weather.php?lat=${latitude}&lon=${longitude}`
        );

          const data = await response.json();
          document.getElementById("weather").innerHTML = `${data.current.temperature_2m}°C`;}, (error) => {
             console.log(error);
           }
        );
</script>
</body>
</html> 