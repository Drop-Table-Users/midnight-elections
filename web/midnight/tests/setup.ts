/**
 * Vitest setup file
 */

import { vi } from 'vitest';

// Mock window object
global.window = global.window || {};

// Mock document object
global.document = global.document || {
    readyState: 'complete',
    addEventListener: vi.fn()
};

// Mock navigator
global.navigator = global.navigator || {
    clipboard: {
        writeText: vi.fn().mockResolvedValue(undefined)
    }
};

// Mock fetch globally
global.fetch = vi.fn().mockResolvedValue({
    ok: true,
    json: vi.fn().mockResolvedValue({}),
    text: vi.fn().mockResolvedValue(''),
}) as any;

// Mock setTimeout and setInterval to use real timers by default
// Tests can override this with vi.useFakeTimers() if needed
global.setTimeout = setTimeout;
global.setInterval = setInterval;
global.clearTimeout = clearTimeout;
global.clearInterval = clearInterval;

// Mock structuredClone if not available (for older environments)
if (typeof global.structuredClone === 'undefined') {
    global.structuredClone = <T>(obj: T): T => JSON.parse(JSON.stringify(obj));
}

// Setup global test utilities
beforeEach(() => {
    // Clear all mocks before each test
    vi.clearAllMocks();
});

afterEach(() => {
    // Cleanup after each test
    vi.restoreAllMocks();
});
