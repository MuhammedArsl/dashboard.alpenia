# Security Hardening Notes

## Environment variables

```bash
# Force TLS redirect for frontend requests
ALPENIA_FORCE_HTTPS=1

# 32-byte key encoded as base64 (required for at-rest field encryption)
# Example generation: openssl rand -base64 32
ALPENIA_FIELD_ENCRYPTION_KEY=

# Optional HMAC key for signed document download URLs
ALPENIA_DOWNLOAD_SIGNING_KEY=

# Optional password pepper for WordPress password hashing/checking pipeline
ALPENIA_PASSWORD_PEPPER=
```

## Migration notes

1. Existing participant/trip sensitive fields remain readable.
2. Sensitive fields are now encrypted on next write using AES-256-GCM.
3. For immediate migration, run a one-time script to re-save sensitive meta fields.
4. If `ALPENIA_PASSWORD_PEPPER` is enabled on an existing system, existing hashes will not match until users reset passwords.
5. Uploaded files are now written under `wp-content/uploads/alpenia-private/*` and served via signed, short-lived URLs.
6. On NGINX, add a deny location rule for `/wp-content/uploads/alpenia-private/` because `.htaccess` is Apache-only.

## Operational controls

- Encrypted backups required for `wp_posts`, `wp_postmeta`, and upload storage.
- Keep encryption key and backup key material in separate secret stores.
- Enable centralized log shipping for lines prefixed with `[alpenia_security]`.
- Keep MFA metadata (`alpenia_mfa_enabled`) enforced for privileged accounts.
