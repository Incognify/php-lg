<?php
header('Content-Type: text/plain; charset=utf-8');

$error_general = "<pre><code>⚠️ ERROR: The IP address you have entered (or the resolved IP) is not allowed.</pre></code>";
$error_limit = "<pre><code>⚠️ ERROR: Rate limit exceeded. Please wait a moment and then try again.</pre></code>";
$error_host = "<pre><code>⚠️ ERROR: Hostname did not resolve to an IP. Check that the spelling is correct.</pre></code>";

// Rate limiting stuff. You can also do this with nginx (see readme)
session_start();
// Set a time limit for session, for example 60 seconds
$rate_limit_time = 60;
// Maximum number of requests within the time limit
$max_requests = 5;

if (!isset($_SESSION['first_request'])) {
    $_SESSION['first_request'] = time();
    $_SESSION['request_count'] = 0;
}

if (time() - $_SESSION['first_request'] > $rate_limit_time) {
    // Reset the counter if the time limit is exceeded
    $_SESSION['first_request'] = time();
    $_SESSION['request_count'] = 0;
}

if ($_SESSION['request_count'] > $max_requests) {
    die("$error_limit");
}

$_SESSION['request_count']++;

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


        $packedIp = inet_pton($ip);
        if ($packedIp === false) {
            die("$error_general");
        }
        $hex = bin2hex($packedIp);
        $ip_dec = gmp_strval(gmp_init($hex, 16));


        $packedRange = inet_pton($range);
        if ($packedRange === false) {
            die("$error_general");
        }
        $hexRange = bin2hex($packedRange);
        $range_dec = gmp_strval(gmp_init($hexRange, 16));


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

function get_resolved_ip($hostname) {
    $ip = gethostbyname($hostname);
    return ($ip === $hostname) ? false : $ip;  // If IP is same as hostname, it didn't resolve.
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

$input = $_POST['ip'] ?? null;
if (!isset($input) || empty($input)) {
    die("$error_general");
}

$forbiddenValues = ['localhost','2130706433'];
foreach ($forbiddenValues as $value) {
    if (strpos($input, $value) !== false) {
        die("$error_general");
    }
}

$forbidden = false;
$matchedSubnet = '';

if (filter_var($input, FILTER_VALIDATE_IP)) {
    // Check IP against forbidden subnets
    foreach ($forbiddenSubnets as $subnet) {
        if (ip_in_range($input, $subnet)) {
            $forbidden = true;
            $matchedSubnet = $subnet;
            break;
        }
    }
} elseif (filter_var($input, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
    // It's a valid hostname, resolve to IP.
    $resolvedIP = get_resolved_ip($input);
    if (!$resolvedIP) {
        die("$error_host");
    }
    foreach ($forbiddenSubnets as $subnet) {
        if (ip_in_range($resolvedIP, $subnet)) {
            $forbidden = true;
            $matchedSubnet = $subnet;
            break;
        }
    }
}

if ($forbidden) {
    die("$error_general");
}

$command = $_POST['command'] ?? null;
$allowedCommands = [
    'ping' => 'ping -c 5',
    'ping6' => 'ping6 -c 5',
    'traceroute' => 'traceroute',
    'traceroute6' => 'traceroute6',
    'mtr' => 'mtr --report --report-cycles 2',
    'mtr6' => 'mtr --report --report-cycles 2 -6'
];

if (!isset($allowedCommands[$command])) {
    die('Invalid command.');
}

$finalCommand = $allowedCommands[$command] . ' ' . escapeshellarg($input) . ' 2>&1';

$output = shell_exec($finalCommand);
echo "<pre><code>" . htmlspecialchars($output) . "</code></pre>";
?>