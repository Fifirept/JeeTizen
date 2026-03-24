// Utility functions for IP validation, port validation, sequence formatting, and JSON parsing

/**
 * Validates an IP address.
 * @param {string} ip - The IP address to validate.
 * @returns {boolean} - Returns true if valid, false otherwise.
 */
function validateIP(ip) {
    const ipPattern = /^(\d{1,3}\.){3}\d{1,3}$/;
    if (!ipPattern.test(ip)) return false;
    const segments = ip.split('.');
    return segments.every(segment => parseInt(segment) >= 0 && parseInt(segment) <= 255);
}

/**
 * Validates a port number.
 * @param {number} port - The port number to validate.
 * @returns {boolean} - Returns true if valid, false otherwise.
 */
function validatePort(port) {
    return Number.isInteger(port) && port >= 0 && port <= 65535;
}

/**
 * Formats a sequence of numbers into a string with a specific format.
 * @param {Array<number>} sequence - The array of numbers to format.
 * @returns {string} - Returns formatted sequence string.
 */
function formatSequence(sequence) {
    return sequence.join(', ');
}

/**
 * Safely parses a JSON string.
 * @param {string} jsonString - The JSON string to parse.
 * @returns {Object|null} - Returns the parsed object or null if error occurs.
 */
function parseJSON(jsonString) {
    try {
        return JSON.parse(jsonString);
    } catch (e) {
        console.error('Invalid JSON:', e);
        return null;
    }
}