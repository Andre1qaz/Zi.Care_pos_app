#!/usr/bin/env php
<?php

/**
 * Generate bcrypt password hashes for seed data
 * Usage: php database/scripts/generate_password_hash.php YourPassword
 */

echo password_hash($argv[1] ?? 'Admin@123', PASSWORD_BCRYPT, ['cost' => 12]) . PHP_EOL;
