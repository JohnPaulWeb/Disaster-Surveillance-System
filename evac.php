<?php
  // This script handles the Overpass API request
  // It receives lat, lon, and radius from AJAX calls
  
  if (isset($_GET['action']) && $_GET['action'] == 'fetch') {
      $lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
      $lon = isset($_GET['lon']) ? floatval($_GET['lon']) : null;
      $radius = isset($_GET['radius']) ? intval($_GET['radius']) : 1000; // Changed default to 1km
      
      // If no coordinates provided, return error
      if (!$lat || !$lon) {
          echo json_encode(["error" => "No coordinates provided", "elements" => []]);
          exit;
      }
      
      $overpass_query = '[out:json];
      (
        node(around:' . $radius . ',' . $lat . ',' . $lon . ')[amenity=shelter];
        way(around:' . $radius . ',' . $lat . ',' . $lon . ')[amenity=shelter];
        node(around:' . $radius . ',' . $lat . ',' . $lon . ')[amenity=school];
        way(around:' . $radius . ',' . $lat . ',' . $lon . ')[amenity=school];
        node(around:' . $radius . ',' . $lat . ',' . $lon . ')[building=school];
        way(around:' . $radius . ',' . $lat . ',' . $lon . ')[building=school];
        node(around:' . $radius . ',' . $lat . ',' . $lon . ')[amenity=community_centre];
        way(around:' . $radius . ',' . $lat . ',' . $lon . ')[amenity=community_centre];
        node(around:' . $radius . ',' . $lat . ',' . $lon . ')[amenity=townhall];
        way(around:' . $radius . ',' . $lat . ',' . $lon . ')[amenity=townhall];
        node(around:' . $radius . ',' . $lat . ',' . $lon . ')[leisure=sports_centre];
        way(around:' . $radius . ',' . $lat . ',' . $lon . ')[leisure=sports_centre];
      );
      out center;';
      
      $encoded_query = urlencode($overpass_query);
      $url = "https://overpass-api.de/api/interpreter?data=" . $encoded_query;
      
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-Overpass-API-Request/1.0');
      
      $response = curl_exec($ch);
      
      if ($response === false) {
          echo json_encode(["error" => curl_error($ch), "elements" => []]);
      } else {
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          if ($httpCode != 200) {
              echo json_encode(["error" => "HTTP $httpCode", "elements" => []]);
          } else {
              echo $response;
          }
      }
      curl_close($ch);
      exit;
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disaster Surveillance System • Nearby Facilities Finder</title>
    
    <!-- Leaflet CSS & JS for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <link rel="stylesheet" href="./Stylesheets/evacStyle.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📍 Nearby Facilities Finder</h1>
            
            <div class="location-panel">
                <div class="location-controls">
                    <div class="location-status">
                        <div class="status-dot" id="statusDot"></div>
                        <span id="statusText">Getting your location...</span>
                        <button id="retryBtn" class="btn btn-secondary" style="display:none;">Retry</button>
                    </div>
                    
                    <div class="location-input">
                        <input type="text" id="latInput" placeholder="Latitude" step="any">
                        <input type="text" id="lonInput" placeholder="Longitude" step="any">
                        <button id="manualLocBtn" class="btn btn-primary">Go</button>
                    </div>
                    
                    <div class="radius-select">
                        <label>Radius:</label>
                        <select id="radiusSelect">
                            <option value="1000" selected>1 km</option>
                            <option value="2000">2 km</option>
                            <option value="5000">5 km</option>
                            <option value="10000">10 km</option>
                        </select>
                    </div>
                </div>
                
                <div class="coords-display">
                    <span id="coordsDisplay">Waiting for location...</span>
                    <button id="copyCoordsBtn" class="btn-icon" title="Copy coordinates">📋</button>
                </div>
            </div>
        </div>
        
        <!-- MAP CONTAINER -->
        <div class="map-container" id="mapContainer" style="display:none;">
            <div id="map"></div>
        </div>
        
        <div class="stats" id="stats" style="display:none;">
            <div class="stat-card">
                <div class="stat-number" id="totalCount">0</div>
                <div class="stat-label">Total Facilities</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="shelterCount">0</div>
                <div class="stat-label">🏠 Shelters</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="schoolCount">0</div>
                <div class="stat-label">🏫 Schools</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="communityCount">0</div>
                <div class="stat-label">🏘️ Community</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="townhallCount">0</div>
                <div class="stat-label">🏛️ Town Halls</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="sportsCount">0</div>
                <div class="stat-label">⚽ Sports</div>
            </div>
        </div>
        
        <div class="results" id="results" style="display:none;">
            <div class="tabs" id="tabs">
                <button class="tab active" data-tab="all">All</button>
                <button class="tab" data-tab="shelter">Shelters</button>
                <button class="tab" data-tab="school">Schools</button>
                <button class="tab" data-tab="community_centre">Community</button>
                <button class="tab" data-tab="townhall">Town Halls</button>
                <button class="tab" data-tab="sports_centre">Sports</button>
            </div>
            <div id="tabContents"></div>
        </div>
    </div>
    
    <script src="finder.js"></script>
</body>
</html>