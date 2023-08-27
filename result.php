<?php
header('Content-Type: text/plain; charset=utf-8');
// Ensure this script can run for a while, some commands might take time.
set_time_limit(60);
/**
 * Function to check if an IP address is within a specified subnet.
 * @param string $ip - The IP address to check.
 * @param string $range - The subnet range.
 * @return bool - Returns true if IP is in the range, false otherwise.
 */
// Check to see if an IP is within a specified subnet (Bogons)
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
        // Convert IP and range to integer values for compariso
        $ip_dec = ip2long($ip);
        $range_dec = ip2long($range);

        // Calculating wildcards and netmask for IPv4.
        $wildcard_dec = pow(2, 32) - 1;
        $netmask_dec = ~($wildcard_dec >> $netmask);

        return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
    }

    return false;
}

// List of forbidden subnets to check against.
$forbiddenSubnets = [
    '0.0.0.0/8',
    '10.0.0.0/8',
    '100.64.0.0/10',
    '127.0.0.0/8',
    '169.254.0.0/16',
    '172.16.0.0/12',
    '192.0.0.0/24',
    '192.0.2.0/24',
    '192.168.0.0/16',
    '198.18.0.0/15',
    '198.51.100.0/24',
    '203.0.113.0/24',
    '::1/128',
    '::/128',
    '::ffff:0:0/96',
    '100::/64',
    '2001::/23',
    '2001:db8::/32',
    'fe80::/10'
];


// Get the raw IP or hostname from the POST request
$input = $_POST['ip'] ?? null;

// Basic validation for provided IP/hostname.
if (!isset($input) || empty($input)) {
    die("IP/Hostname is required.");
}

// Check for other forbidden values that could exploit SSRF vulnerabilities.
$forbiddenValues = ['localhost'];
foreach ($forbiddenValues as $value) {
    if (strpos($input, $value) !== false) {
        die("Invalid IP/Hostname.");
    }
}

// If the input is a valid IP, check if it's in any forbidden subnets.
if (filter_var($input, FILTER_VALIDATE_IP)) {
    $forbidden = false;
    $matchedSubnet = '';  // for debugging purposes
    foreach ($forbiddenSubnets as $subnet) {
        if (ip_in_range($input, $subnet)) {
            $forbidden = true;
            $matchedSubnet = $subnet;  // store the matched subnet for debug.
            break;
        }
    }
    if ($forbidden) {
        die('The IP address or subnet you have entered is not allowed. Matched subnet: ' . $matchedSubnet);
    }
}

// Then, continue with the rest of your logic to get the command and execute it.

$command = $_POST['command'] ?? null;


if ($forbidden) {
    die('The IP address or subnet you have entered is not allowed. Matched subnet: ' . $matchedSubnet);
}

// Check for other forbidden values that could exploit SSRF vulnerabilities.
$forbiddenValues = ['localhost'];
foreach ($forbiddenValues as $value) {
    if (strpos($input, $value) !== false) {
        die("Invalid IP/Hostname.");
    }
}

// List of allowed commands and their shell equivalents.
$allowedCommands = [
    'ping' => 'ping -c 20',
    'ping6' => 'ping6 -c 20',
    'traceroute' => 'traceroute',
    'traceroute6' => 'traceroute6',
    'mtr' => 'mtr --report --report-cycles 10',
    'mtr6' => 'mtr --report --report-cycles 10 -6'
];

// Check if provided command is in allowed list.
if (!isset($allowedCommands[$command])) {
    die('Invalid command.');
}

// Construct the final shell command, escaping the IP for security.
$finalCommand = $allowedCommands[$command] . ' ' . escapeshellarg($input) . ' 2>&1';

// Execute the command and display the output.
$output = shell_exec($finalCommand);
echo "<pre><code>" . htmlspecialchars($output) . "</code></pre>";
?>
