<?php
/**
 * This script mostly just replaces a bunch of placeholders
 * inside of the project to kick-start a VISU project.
 */
 
/**
 * Helpers
 * ----------------------------------------------------------------------------
 */
enum CLIColor : string {
    case RED = "\033[31m";
    case GREEN = "\033[32m";
    case YELLOW = "\033[33m";
    case BLUE = "\033[34m";
    case MAGENTA = "\033[35m";
    case CYAN = "\033[36m";
    case WHITE = "\033[37m";
    case RESET = "\033[0m";
}

function printLine($line, $indent = 0, ?CLIColor $color = null) {
    $indent = str_repeat(' ', $indent);
    // split by lines
    $lines = explode(PHP_EOL, $line);
    foreach ($lines as $line) {
        echo $indent . ($color ? $color->value : '') . $line . CLIColor::RESET->value . PHP_EOL;
    }
}

function printSeperator(string $char = '-', int $length = 80) {
    printLine(str_repeat($char, $length));
}

/**
 * Install Setup
 * ----------------------------------------------------------------------------
 */

printSeperator();
printLine('VISU Project Setup', 2, CLIColor::CYAN);
printSeperator();

/**
 * Check for requirements
 * ----------------------------------------------------------------------------
 */
// Check for PHP version > 8.1
if (version_compare(PHP_VERSION, '8.1.0') < 0) {
    printLine('Your PHP version is ' . PHP_VERSION . ' but VISU requires at least PHP 8.1.0', 0, CLIColor::RED);
    exit(1);
}

// check if the "glfw" extension is installed
if (!extension_loaded('glfw')) {
    printLine('The "glfw" extension is not installed.', 0, CLIColor::RED);
    printLine('Please goto https://phpgl.net and follow the installation instructions.', 0, CLIColor::RED);
    exit(1);
}

/**
 * Ask for setup values
 * ----------------------------------------------------------------------------
 */
$projectName = '';

printLine('# Please enter the project name:', 0, CLIColor::YELLOW);
 
while (empty($projectName)) {
    $projectName = readline('Project Name: ');
}

printLine('Great! Your project will be named "' . $projectName . '".', 0, CLIColor::GREEN);
