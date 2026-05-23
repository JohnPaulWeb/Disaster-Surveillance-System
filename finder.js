// Global variables
let currentLat = null;
let currentLon = null;
let currentRadius = 1000;  // Default to 1km
let isLoading = false;

// Map-related variables
let currentMap = null;
let currentMarkers = [];

// DOM Elements
const statusDot = document.getElementById('statusDot');
const statusText = document.getElementById('statusText');
const retryBtn = document.getElementById('retryBtn');
const coordsDisplay = document.getElementById('coordsDisplay');
const latInput = document.getElementById('latInput');
const lonInput = document.getElementById('lonInput');
const manualLocBtn = document.getElementById('manualLocBtn');
const radiusSelect = document.getElementById('radiusSelect');
const copyCoordsBtn = document.getElementById('copyCoordsBtn');

// Facility type mappings
const facilityNames = {
    shelter: 'Shelters',
    school: 'Schools',
    community_centre: 'Community Centres',
    townhall: 'Town Halls',
    sports_centre: 'Sports Centres'
};

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // Set default radius select to 1km (value 1000)
    if (radiusSelect) {
        radiusSelect.value = '1000';
    }
    fetchLocation();
    setupEventListeners();
});

function setupEventListeners() {
    if (retryBtn) retryBtn.addEventListener('click', fetchLocation);
    if (manualLocBtn) manualLocBtn.addEventListener('click', useManualLocation);
    if (radiusSelect) {
        radiusSelect.addEventListener('change', () => {
            currentRadius = parseInt(radiusSelect.value);
            if (currentLat && currentLon && !isLoading) fetchFacilities();
        });
    }
    if (copyCoordsBtn) copyCoordsBtn.addEventListener('click', copyCoordinates);
    
    // Tab switching (event delegation)
    const tabsContainer = document.getElementById('tabs');
    if (tabsContainer) {
        tabsContainer.addEventListener('click', (e) => {
            const tab = e.target.closest('.tab');
            if (tab) {
                const tabName = tab.dataset.tab;
                switchTab(tabName);
            }
        });
    }
}

function fetchLocation() {
    if (!navigator.geolocation) {
        showError('Geolocation not supported');
        return;
    }
    
    updateStatus('Getting your location...', true);
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            currentLat = position.coords.latitude;
            currentLon = position.coords.longitude;
            updateStatus('Location ready', false);
            updateCoordinatesDisplay(currentLat, currentLon);
            fetchFacilities();
        },
        (error) => {
            console.error(error);
            handleLocationError(error);
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

function handleLocationError(error) {
    let message = '';
    switch(error.code) {
        case error.PERMISSION_DENIED:
            message = 'Location permission denied. Enter coordinates manually.';
            break;
        case error.POSITION_UNAVAILABLE:
            message = 'Location unavailable. Enter coordinates manually.';
            break;
        case error.TIMEOUT:
            message = 'Location timeout. Enter coordinates manually.';
            break;
        default:
            message = 'Location error. Enter coordinates manually.';
    }
    
    updateStatus(message, false);
    if (retryBtn) retryBtn.style.display = 'inline-block';
    
    // Set default coordinates (Manila as example)
    currentLat = 14.5995;
    currentLon = 120.9842;
    updateCoordinatesDisplay(currentLat, currentLon);
    fetchFacilities();
}

function useManualLocation() {
    if (isLoading) return;
    
    const lat = parseFloat(latInput.value);
    const lon = parseFloat(lonInput.value);
    
    if (isNaN(lat) || isNaN(lon)) {
        alert('Please enter valid latitude and longitude');
        return;
    }
    
    if (lat < -90 || lat > 90) {
        alert('Latitude must be between -90 and 90');
        return;
    }
    
    if (lon < -180 || lon > 180) {
        alert('Longitude must be between -180 and 180');
        return;
    }
    
    currentLat = lat;
    currentLon = lon;
    updateStatus('Manual location set', false);
    updateCoordinatesDisplay(currentLat, currentLon);
    fetchFacilities();
}

function updateStatus(message, isActive) {
    if (statusText) statusText.innerText = message;
    if (statusDot) {
        if (isActive) {
            statusDot.classList.add('active');
        } else {
            statusDot.classList.remove('active');
        }
    }
}

function updateCoordinatesDisplay(lat, lon) {
    if (coordsDisplay) coordsDisplay.innerHTML = `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
    if (latInput) latInput.value = lat.toFixed(6);
    if (lonInput) lonInput.value = lon.toFixed(6);
}

function copyCoordinates() {
    const coords = coordsDisplay ? coordsDisplay.innerText : '';
    navigator.clipboard.writeText(coords).then(() => {
        if (copyCoordsBtn) {
            const originalText = copyCoordsBtn.textContent;
            copyCoordsBtn.textContent = '✓';
            setTimeout(() => {
                copyCoordsBtn.textContent = originalText;
            }, 2000);
        }
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

function showError(message) {
    const tabContents = document.getElementById('tabContents');
    if (tabContents) {
        tabContents.innerHTML = `<div class="error">⚠️ ${escapeHtml(message)}</div>`;
    }
    const resultsDiv = document.getElementById('results');
    if (resultsDiv) resultsDiv.style.display = 'block';
    const statsDiv = document.getElementById('stats');
    if (statsDiv) statsDiv.style.display = 'none';
    isLoading = false;
}

async function fetchFacilities() {
    if (!currentLat || !currentLon) return;
    if (isLoading) return;
    
    isLoading = true;
    const radius = parseInt(radiusSelect.value);
    currentRadius = radius;
    
    // Hide results and show loading
    const resultsDiv = document.getElementById('results');
    const statsDiv = document.getElementById('stats');
    const mapContainer = document.getElementById('mapContainer');
    
    if (resultsDiv) resultsDiv.style.display = 'none';
    if (statsDiv) statsDiv.style.display = 'none';
    if (mapContainer) mapContainer.style.display = 'none';
    
    const tabContents = document.getElementById('tabContents');
    
    // Create loading animation
    if (tabContents) {
        tabContents.innerHTML = `
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <div class="loading-text">🌍 Searching for facilities within ${radius/1000}km...</div>
                <div class="loading-subtext">This may take 10-30 seconds depending on the area</div>
                <div class="loading-progress">
                    <div class="progress-bar"></div>
                </div>
                <div class="loading-tip">💡 Tip: Smaller radius = faster results</div>
            </div>
        `;
    }
    if (resultsDiv) resultsDiv.style.display = 'block';
    
    // Animate progress bar
    let progress = 0;
    const progressBar = document.querySelector('.progress-bar');
    const progressInterval = setInterval(() => {
        if (progress < 90) {
            progress += Math.random() * 10;
            if (progress > 90) progress = 90;
            if (progressBar) progressBar.style.width = progress + '%';
        }
    }, 500);
    
    const startTime = Date.now();
    
    try {
        const url = `?action=fetch&lat=${currentLat}&lon=${currentLon}&radius=${radius}`;
        const response = await fetch(url);
        const data = await response.json();
        
        clearInterval(progressInterval);
        if (progressBar) progressBar.style.width = '100%';
        
        const elapsedTime = ((Date.now() - startTime) / 1000).toFixed(1);
        
        if (data.error) {
            showError(`API Error: ${data.error}`);
            return;
        }
        
        processResults(data.elements || [], elapsedTime);
    } catch (err) {
        clearInterval(progressInterval);
        showError(`Failed to fetch data: ${err.message}`);
    } finally {
        isLoading = false;
    }
}

function processResults(elements, elapsedTime) {
    const facilities = {
        shelter: [],
        school: [],
        community_centre: [],
        townhall: [],
        sports_centre: []
    };
    
    const allFacilities = []; // For map markers
    
    for (const el of elements) {
        const tags = el.tags || {};
        let type = null;
        
        // Determine facility type
        if (tags.amenity === 'shelter') type = 'shelter';
        else if (tags.amenity === 'school') type = 'school';
        else if (tags.building === 'school') type = 'school';
        else if (tags.amenity === 'community_centre') type = 'community_centre';
        else if (tags.amenity === 'townhall') type = 'townhall';
        else if (tags.leisure === 'sports_centre') type = 'sports_centre';
        
        if (type) {
            // Get facility name
            let name = tags.name;
            if (!name) {
                if (type === 'school') name = 'Unnamed School';
                else if (type === 'shelter') name = 'Unnamed Shelter';
                else if (type === 'community_centre') name = 'Community Centre';
                else if (type === 'townhall') name = 'Town Hall';
                else name = 'Sports Centre';
            }
            
            // Get coordinates
            let lat, lon;
            if (el.type === 'node') {
                lat = el.lat;
                lon = el.lon;
            } else if (el.type === 'way' && el.center) {
                lat = el.center.lat;
                lon = el.center.lon;
            } else {
                continue;
            }
            
            // Calculate distance from user
            const distance = calculateDistance(currentLat, currentLon, lat, lon);
            
            const facility = { 
                name, 
                lat, 
                lon, 
                tags,
                type: type,
                typeLabel: getTypeLabel(type),
                distance_km: distance.toFixed(2)
            };
            
            facilities[type].push(facility);
            allFacilities.push(facility);
        }
    }
    
    // Sort each category by distance
    for (const type of Object.keys(facilities)) {
        facilities[type].sort((a, b) => parseFloat(a.distance_km) - parseFloat(b.distance_km));
    }
    
    // Update statistics
    updateStats(facilities, elapsedTime);
    
    // Render facilities
    renderFacilities(facilities);
    
    // Initialize map
    initMap(currentLat, currentLon, allFacilities, currentRadius);
    
    // Show results
    const statsDiv = document.getElementById('stats');
    if (statsDiv) statsDiv.style.display = 'flex';
}

function getTypeLabel(type) {
    const labels = {
        shelter: '🏠 Shelter',
        school: '🏫 School',
        community_centre: '🏘️ Community Centre',
        townhall: '🏛️ Town Hall',
        sports_centre: '⚽ Sports Centre'
    };
    return labels[type] || type;
}

function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function updateStats(facilities, elapsedTime) {
    const total = Object.values(facilities).reduce((sum, arr) => sum + arr.length, 0);
    
    const totalCountEl = document.getElementById('totalCount');
    const shelterCountEl = document.getElementById('shelterCount');
    const schoolCountEl = document.getElementById('schoolCount');
    const communityCountEl = document.getElementById('communityCount');
    const townhallCountEl = document.getElementById('townhallCount');
    const sportsCountEl = document.getElementById('sportsCount');
    
    if (totalCountEl) totalCountEl.innerHTML = total;
    if (shelterCountEl) shelterCountEl.innerHTML = facilities.shelter.length;
    if (schoolCountEl) schoolCountEl.innerHTML = facilities.school.length;
    if (communityCountEl) communityCountEl.innerHTML = facilities.community_centre.length;
    if (townhallCountEl) townhallCountEl.innerHTML = facilities.townhall.length;
    if (sportsCountEl) sportsCountEl.innerHTML = facilities.sports_centre.length;
}

function renderFacilities(facilities) {
    const tabContents = document.getElementById('tabContents');
    const tabs = ['all', 'shelter', 'school', 'community_centre', 'townhall', 'sports_centre'];
    
    if (!tabContents) return;
    
    let html = {};
    
    for (const tab of tabs) {
        let items = [];
        
        if (tab === 'all') {
            for (const type of Object.keys(facilities)) {
                items = items.concat(facilities[type].map(f => ({ ...f, typeDisplay: type })));
            }
            items.sort((a, b) => parseFloat(a.distance_km) - parseFloat(b.distance_km));
        } else {
            items = facilities[tab];
        }
        
        let content = `<div class="tab-content" data-tab="${tab}">`;
        
        if (items.length === 0) {
            content += '<div class="empty">✨ No facilities found in this category</div>';
        } else {
            content += '<div class="facilities-grid">';
            for (const item of items) {
                const typeLabel = item.typeLabel || facilityNames[item.type] || item.type;
                content += `
                    <div class="facility-card" onclick="centerOnFacility(${item.lat}, ${item.lon}, '${escapeHtml(item.name)}')">
                        <div class="facility-name">
                            ${escapeHtml(item.name)}
                            <span class="facility-type">${typeLabel}</span>
                        </div>
                        <div class="facility-distance">📍 ${item.distance_km} km away</div>
                        <div class="facility-coord">${item.lat.toFixed(6)}, ${item.lon.toFixed(6)}</div>
                    </div>
                `;
            }
            content += '</div>';
        }
        
        content += '</div>';
        html[tab] = content;
    }
    
    // Build the entire tab contents
    let allContent = '';
    for (const tab of tabs) {
        allContent += html[tab];
    }
    tabContents.innerHTML = allContent;
    
    // Activate first tab
    switchTab('all');
}

function switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab').forEach(tab => {
        if (tab.dataset.tab === tabName) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });
    
    // Update content
    document.querySelectorAll('.tab-content').forEach(content => {
        if (content.dataset.tab === tabName) {
            content.classList.add('active');
        } else {
            content.classList.remove('active');
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ========== MAP FUNCTIONS ==========

function initMap(lat, lon, facilities, radius) {
    const mapContainer = document.getElementById('mapContainer');
    const mapDiv = document.getElementById('map');
    
    if (!mapDiv) {
        console.error('Map div not found');
        return;
    }
    
    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.error('Leaflet not loaded');
        return;
    }
    
    // Show map container
    if (mapContainer) mapContainer.style.display = 'block';
    
    // Clear existing markers
    if (currentMarkers) {
        currentMarkers.forEach(marker => {
            if (marker && typeof marker.remove === 'function') marker.remove();
        });
        currentMarkers = [];
    }
    
    // Create new map if doesn't exist
    if (currentMap && typeof currentMap.remove === 'function') {
        currentMap.remove();
    }
    
    currentMap = L.map(mapDiv).setView([lat, lon], 13);
    
    // Add OpenStreetMap tiles with dark theme
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; CartoDB',
        subdomains: 'abcd',
        maxZoom: 19,
        minZoom: 8
    }).addTo(currentMap);
    
    // Add marker for user location
    const userIcon = L.divIcon({
        className: 'user-location-marker',
        html: '<div style="background-color: #ff8c00; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 10px #ff8c00;"></div>',
        iconSize: [16, 16],
        iconAnchor: [8, 8]
    });
    
    L.marker([lat, lon], { icon: userIcon }).addTo(currentMap)
        .bindPopup('<b>📍 Your Location</b><br>Emergency Response Center')
        .openPopup();
    
    // Draw circle for search radius
    L.circle([lat, lon], {
        color: '#ff8c00',
        fillColor: '#ff8c00',
        fillOpacity: 0.1,
        radius: radius
    }).addTo(currentMap);
    
    // Add markers for facilities
    if (facilities && facilities.length > 0) {
        facilities.forEach(facility => {
            if (facility.lat && facility.lon) {
                let markerColor = '#ff8c00';
                
                switch(facility.type) {
                    case 'shelter':
                        markerColor = '#ff6b6b';
                        break;
                    case 'school':
                        markerColor = '#4ecdc4';
                        break;
                    case 'community_centre':
                        markerColor = '#ffe66d';
                        break;
                    case 'townhall':
                        markerColor = '#a8e6cf';
                        break;
                    case 'sports_centre':
                        markerColor = '#ff8c00';
                        break;
                }
                
                const customIcon = L.divIcon({
                    className: 'facility-marker',
                    html: `<div style="background-color: ${markerColor}; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 5px ${markerColor};"></div>`,
                    iconSize: [12, 12],
                    iconAnchor: [6, 6]
                });
                
                const marker = L.marker([facility.lat, facility.lon], { icon: customIcon })
                    .bindPopup(`
                        <b>${escapeHtml(facility.name)}</b><br>
                        <span style="color: ${markerColor}">${escapeHtml(facility.typeLabel || facility.type)}</span><br>
                        📍 ${facility.distance_km} km away
                    `);
                
                marker.addTo(currentMap);
                currentMarkers.push(marker);
            }
        });
        
        // Fit bounds to show all markers if there are any
        if (currentMarkers.length > 0) {
            const group = L.featureGroup(currentMarkers);
            const bounds = group.getBounds();
            // Also include user location in bounds
            bounds.extend([lat, lon]);
            currentMap.fitBounds(bounds.pad(0.1));
        }
    }
}

// Global function to center on a facility (called from onclick)
window.centerOnFacility = function(lat, lon, name) {
    if (currentMap && typeof currentMap.setView === 'function') {
        currentMap.setView([lat, lon], 16);
        // Find and open popup for this facility
        if (currentMarkers) {
            currentMarkers.forEach(marker => {
                const markerLatLng = marker.getLatLng();
                if (Math.abs(markerLatLng.lat - lat) < 0.0001 && Math.abs(markerLatLng.lng - lon) < 0.0001) {
                    marker.openPopup();
                }
            });
        }
    }
};

// Auto-refresh when page becomes visible again
document.addEventListener('visibilitychange', () => {
    if (!document.hidden && currentLat && currentLon && !isLoading) {
        fetchFacilities();
    }
});