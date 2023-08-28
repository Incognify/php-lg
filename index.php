<?php
// 08/27/2023 - Looking Glass script.
// Still work to do, but this works as it is now.
// Will refine later, probably.
//
// Set the below variables:
$lg_location = "🇺🇸 Liberty Lake, WA"; // Where is this looking glass install located?
$lg_ipv4 = "23.184.48.25"; // IPv4 address of your looking glass server
$lg_ipv6 = "2602:fc24:18:a5d8::1"; // IPv6 address of your looking glass server
$logo = "logo.svg"; //Logo or image to display above your other locations

// Get the IP / hostname of the website visitor
function getUserInfo() {
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $hostName = gethostbyaddr($ipAddress);
    return [
        'ip' => $ipAddress,
        'hostname' => $hostName
    ];
}
$userInfo = getUserInfo();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo ("$description");?>">
    <meta name="keywords" content="<?php echo ("$keywords");?>">
    <meta name="author" content="IncogNET">
    <meta name="robots" content="index, follow">
    <link rel="preload" as="style" href="style.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title><?php echo($lg_location)?> - Looking Glass</title>
<body>
<nav id="mainNav">
    <h1>Network Looking Glass</h1>
</nav>
<div class="forms-container">
    <div class="left-form">
        <form id="lookupForm">
            <div id="userInfo">
                <p>This is a <b>beta</b> PHP driven looking glass tool. From the Looking Glass location shown below, you may test network connectivity to any publicly accessible IPv4 or IPv6 address or hostname that you enter.</p>
                <hr>
                <b>Looking Glass Location:</b> <span><?php echo($lg_location)?></span><br>
                <b>Looking Glass IPv4:</b> <span><?php echo($lg_ipv4)?></span><br>
                <b>Looking Glass IPv6:</b> <span><?php echo($lg_ipv6)?></span><br>
                <hr>
                <b>Your IP Address:</b> <span><?= $userInfo['ip'] ?></span><br>
                <b>Your Hostname:</b> <span><?= $userInfo['hostname'] ?></span>
                <hr>
            </div>
        IPv4 / IPv6 Address or Hostname: <input type="text" name="ip" required>
        Run Command:
        <select name="command">
            <option value="ping">Ping</option>
            <option value="ping6">Ping6</option>
            <option value="traceroute">Traceroute</option>
            <option value="traceroute6">Traceroute6</option>
            <option value="mtr">MTR</option>
            <option value="mtr6">MTR6</option>
        </select>
        <input type="submit" value="Proceed">
    </form>
    </div>
        <div class="center-results">
            <div id="loadingMessage" style="display: none;"><h2>Running test, please wait...<br>(This may take a moment)</h2></div>
            <div id="results"></div>
        </div>
    <div class="right-form">
        <form id="lookupForm">
                    <div id="userInfo">
                        <center>
                        <img src="<?php echo($logo)?>" width="313" height="63"><br>
                        <h4>Want to test from one of our other locations?</h4>
                        <hr>
                        <span>🇺🇸 Liberty Lake, WA</span> | <span>🇺🇸 Kansas City, MO</span><br>
                        <span>🇺🇸 Allentown, PA</span> | <span>🇺🇸 Miami, FL</span><br>
                        <span>🇺🇸 Las Vegas, NV</span> | <span>🇺🇸 New York, NY</span><br>
                        <span>🇳🇱 Naaldwijk, NL</span> | <span>🇱🇺 Roost, Bissen, LU</span>
                        <hr>
                        <small>The above links are for demonstration purposes only.<br><br>© <?php echo date("Y"); ?> <a href="https://incognet.io" target="_blank">IncogNET LLC</a> | <a href="https://github.com/Incognify/php-lg/" target="_blank">GitHub</a> | <a href="https://twitter.com/IncogNETLLC" target="_blank">Twitter</a> | <a href="https://bgp.tools/as/210630" target="_blank">BGP.TOOLS</a> |  v1.1</small></center>
                    </div>
        </form>
    </div>
</div>
<script>
    $('#lookupForm').submit(function(e) {
        e.preventDefault();

        // Show the loading message
        $('#loadingMessage').show();
        
        $.post('result.php', $(this).serialize(), function(data) {
            $('#results').html(data);

            // Hide the loading message
            $('#loadingMessage').hide();
        }).fail(function() {
            $('#results').html('<pre><code>Error fetching the results. Please try again later.</code></pre>');
            
            // Hide the loading message
            $('#loadingMessage').hide();
        });
    });
</script>
</body>
</html>