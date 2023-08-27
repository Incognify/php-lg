<?php
// Setting content type to plain text for better display of command outputs.
header('Content-Type: text/plain; charset=utf-8');

// Setting the execution time to 60 seconds as some commands may take longer.
set_time_limit(60);

/**
 * Function to check if an IP address is within a specified subnet.
 * @param string $ip - The IP address to check.
 * @param string $range - The subnet range.
 * @return bool - Returns true if IP is in the range, false otherwise.
 */
function ip_in_range($ip, $range) {
    // If range does not contain a CIDR, it's assumed to be a /32.
    if (strpos($range, '/') === false) {
        $range .= '/32';
    }

    list($range, $netmask) = explode('/', $range, 2);

    // Checking for IPv6 addresses.
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // Convert IP and range to big integers for comparison.
        $ip_dec = gmp_strval(gmp_init($ip, 16));
        $range_dec = gmp_strval(gmp_init($range, 16));

        // Calculating wildcards and netmask for IPv6.
        $wildcard_dec = gmp_sub(gmp_pow(2, 128), 1);
        $netmask_dec = gmp_sub(gmp_pow(2, 128), gmp_pow(2, (128 - $netmask)));

        $ip_net = gmp_and($ip_dec, $netmask_dec);
        $range_net = gmp_and($range_dec, $netmask_dec);

        return gmp_cmp($ip_net, $range_net) === 0;
    }

    // Checking for IPv4 addresses.
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        // Convert IP and range to integer values for comparison.
        $ip_dec = ip2long($ip);
        $range_dec = ip2long($range);

        // Calculating wildcards and netmask for IPv4.
        $wildcard_dec = pow(2, 32) - 1;
        $netmask_dec = ~($wildcard_dec >> $netmask);

        return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
    }

    // Default case if IP does not match IPv4 or IPv6.
    return false;
}

// List of forbidden subnets to check against.
$forbiddenSubnets = [ ... ];

// Get IP and command from POST request.
$ip = filter_input(INPUT_POST, 'ip', FILTER_VALIDATE_IP);
$command = $_POST['command'] ?? null;

// Basic validation for provided IP.
if (!isset($_POST['ip']) || empty($_POST['ip'])) {
    die("IP/Hostname is required.");
}

// Check if provided IP is in any forbidden subnets.
$forbidden = false;
$matchedSubnet = '';  // for debugging purposes
foreach ($forbiddenSubnets as $subnet) {
    if (ip_in_range($ip, $subnet)) {
        $forbidden = true;
        $matchedSubnet = $subnet;  // store the matched subnet for debug.
        break;
    }
}
if ($forbidden) {
    die('The IP address or subnet you have entered is not allowed. Matched subnet: ' . $matchedSubnet);
}

// Check for other forbidden values that could exploit SSRF vulnerabilities.
$forbiddenValues = ['localhost'];
foreach ($forbiddenValues as $value) {
    if (strpos($ip, $value) !== false) {
        die("Invalid IP/Hostname.");
    }
}

// List of allowed commands and their shell equivalents.
$allowedCommands = [ ... ];

// Check if provided command is in allowed list.
if (!isset($allowedCommands[$command])) {
    die('Invalid command.');
}

// Construct the final shell command, escaping the IP for security.
$finalCommand = $allowedCommands[$command] . ' ' . escapeshellarg($ip) . ' 2>&1';

// Execute the command and display the output.
$output = shell_exec($finalCommand);
echo "<pre><code>" . htmlspecialchars($output) . "</code></pre>";
?>
