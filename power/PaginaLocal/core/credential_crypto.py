import logging
import os

from cryptography.fernet import Fernet, InvalidToken

logger = logging.getLogger(__name__)

# Prefix marking a value as encrypted with this module -- lets decrypt_secret
# tell a freshly-encrypted value apart from a legacy plaintext one (profiles
# saved before this existed, or saved while GARMIN_CREDENTIALS_KEY wasn't
# set) without needing a separate migration pass: old profiles keep working
# as plaintext and get encrypted automatically the next time they're saved.
_ENC_PREFIX = "enc:v1:"

_ENV_VAR = "GARMIN_CREDENTIALS_KEY"


def _get_fernet() -> Fernet | None:
    key = os.getenv(_ENV_VAR)
    if not key:
        return None
    try:
        return Fernet(key.encode())
    except (ValueError, TypeError) as exc:
        logger.error("%s is set but is not a valid Fernet key: %s", _ENV_VAR, exc)
        return None


def encrypt_secret(plaintext: str | None) -> str | None:
    """Encrypts a secret (Garmin password) for storage at rest in
    user_secure_data/profiles.json. Idempotent -- an already-encrypted value
    is returned unchanged, so re-saving a profile loaded via decrypt_secret
    can never double-wrap it. Falls back to returning the plaintext unchanged
    (profiles.json keeps today's behavior) when GARMIN_CREDENTIALS_KEY isn't
    configured, rather than losing the credential or crashing profile save."""
    if not plaintext or plaintext.startswith(_ENC_PREFIX):
        return plaintext
    fernet = _get_fernet()
    if not fernet:
        logger.warning(
            "%s is not set -- Garmin credentials will be stored in plaintext. "
            "Generate one with Fernet.generate_key() and add it to .env.",
            _ENV_VAR,
        )
        return plaintext
    return _ENC_PREFIX + fernet.encrypt(plaintext.encode()).decode()


def decrypt_secret(value: str | None) -> str | None:
    """Reverses encrypt_secret. A value without the enc:v1: prefix is
    returned as-is (legacy plaintext, or never encrypted because no key was
    configured when it was saved) -- this is what makes the migration to
    encrypted storage lazy/automatic rather than needing a one-time script."""
    if not value or not value.startswith(_ENC_PREFIX):
        return value
    fernet = _get_fernet()
    if not fernet:
        raise RuntimeError(
            f"This credential is encrypted but {_ENV_VAR} is not set -- cannot "
            "decrypt it. Set the same key that was used to encrypt it."
        )
    try:
        return fernet.decrypt(value[len(_ENC_PREFIX):].encode()).decode()
    except InvalidToken as exc:
        raise RuntimeError(
            f"Failed to decrypt a stored credential -- wrong {_ENV_VAR}?"
        ) from exc
