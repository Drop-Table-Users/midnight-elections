// resources/js/alpine/index.js
// Alpine.js components for Midnight wallet integration

import walletConnect from './wallet-connect.js';
import voteForm from './vote-form.js';
import transactionStatus from './transaction-status.js';

export { walletConnect, voteForm, transactionStatus };

/**
 * Register all Alpine components at once
 * @param {object} Alpine - Alpine.js instance
 */
export function registerAlpineComponents(Alpine) {
  if (!Alpine) {
    throw new Error('Alpine instance is required');
  }

  Alpine.data('walletConnect', walletConnect);
  Alpine.data('voteForm', voteForm);
  Alpine.data('transactionStatus', transactionStatus);
}

// Auto-register if Alpine is available on window
if (typeof window !== 'undefined' && window.Alpine) {
  registerAlpineComponents(window.Alpine);
}
