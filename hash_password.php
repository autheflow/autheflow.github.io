<?php
/**
 * Password Hash Generator
 * Utility to generate bcrypt password hashes for manual database insertion
 */

// Change this to your desired password
$password = "Hoangdz198tb";

// Generate bcrypt hash
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

echo "=================================================\n";
echo "Password Hash Generator\n";
echo "=================================================\n\n";
echo "Original Password: " . $password . "\n\n";
echo "Bcrypt Hash:\n";
echo $hashedPassword . "\n\n";
echo "=================================================\n";
echo "Copy the hash above and update your users table:\n";
echo "UPDATE users SET password = '{$hashedPassword}' WHERE username = 'Hoangdz198tb';\n";
echo "=================================================\n";
?>
