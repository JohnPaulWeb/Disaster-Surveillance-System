<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SAFEPATH Login</title>

  <link rel="stylesheet" href="./Stylesheets/loginStyle.css">
  <script src="https://accounts.google.com/gsi/client" async></script>

  <script>
    function decodeJWT(token) {
      let base64Url = token.split(".")[1];
      let base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
      let jsonPayload = decodeURIComponent(
        atob(base64)
          .split("")
          .map(c => "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2))
          .join("")
      );
      return JSON.parse(jsonPayload);
    }

    function handleCredentialResponse(response) {
      const responsePayload = decodeJWT(response.credential);

      fetch("save.php", {
        method: "POST",
        body: new URLSearchParams({
          name: responsePayload.name,
          email: responsePayload.email,
          picture: responsePayload.picture
        })
      }).then(() => {
        window.location.href = "dashboard.php";
      });
    }
  </script>
</head>

<body>

  <!-- 🔴 Emergency Background Layer -->
  <div class="background-overlay"></div>

  <!-- 🚨 Main Login Container -->
  <div class="login-wrapper">

    <!-- Alert Header -->
    <div class="alert-header">
      🚨 DISASTER MONITORING ACCESS PORTAL
    </div>

    <!-- System Name -->
    <h1 class="system-title">Disaster Surveillance System</h1>

    <p class="system-subtitle">
      Disaster Information & Evacuation Management System
    </p>

    <!-- Warning Message -->
    <div class="warning-box">
      ⚠ Advance Disaster Monitoring Enabled
    </div>

    <!-- Login Box -->
    <div id="login-box">

      <p class="login-text">Sign in using your Google Account</p>

      <div
        id="g_id_onload"
        data-auto_prompt="false"
        data-callback="handleCredentialResponse"
        data-client_id="925391134111-to5hnqkmu6itho0q62qie84ivf5t0t2s.apps.googleusercontent.com"
      ></div>

      <div class="g_id_signin"></div>

    </div>

    <!-- Footer Warning -->
    <div class="footer-note">
      System monitored by Disaster Risk Response Unit
    </div>

  </div>

</body>
</html>