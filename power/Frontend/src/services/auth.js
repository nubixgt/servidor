import api from './api';

/**
 * Exchanges a Google ID token (the `credential` from Google Identity Services)
 * for our own session. Backend responds with one of:
 *   - { status: 'success', token, user }
 *   - { status: 'pending' | 'rejected', user }
 *   - { status: 'error', message }
 */
export async function loginWithGoogle(credential, referralCode = null) {
  // El código de invitación sólo cuenta si este login CREA la cuenta (lo decide el servidor).
  const { data } = await api.post('/auth/google', referralCode ? { credential, referral_code: referralCode } : { credential });
  return data;
}

export async function fetchCurrentUser() {
  const { data } = await api.get('/auth/me');
  return data;
}
