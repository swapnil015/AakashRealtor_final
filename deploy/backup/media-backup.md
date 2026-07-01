# Media backup notes

Property listing images are the only large mutable asset. Strategy depends on
`MEDIA_DISK`:

## MEDIA_DISK=cloudinary  (recommended, default in production)
Cloudinary stores originals durably and replicates them — **no host-side backup
needed**. Belt-and-braces options:
- Enable **Cloudinary auto-backup** (Settings → Backup) to mirror assets into
  your own S3/GCS bucket. This is the recommended durability add-on.
- Periodically export an asset inventory for disaster audit:
  `cld admin resources max_results=500` (Cloudinary CLI), or call the Admin API.

## MEDIA_DISK=s3
S3 is durable (11 nines). Add protection against accidental deletion / app bugs:
- Turn on **bucket Versioning** + an **MFA-delete** or lifecycle policy.
- Optionally enable **Cross-Region Replication** to a second bucket.
- No cron job required.

## MEDIA_DISK=public  (local disk — least durable, avoid in prod)
Images live in the `storage` named volume (`storage/app/public`). This is the
ONLY case needing an explicit media backup. Add to the nightly cron:

```bash
# Nightly media archive (host), rotates with the DB backup retention.
docker run --rm \
  -v deploy_storage:/data:ro \
  -v /var/backups/aakash/media:/backup \
  alpine sh -c 'tar czf /backup/media_$(date +%F).tgz -C /data app/public'
find /var/backups/aakash/media -name 'media_*.tgz' -mtime +14 -delete
```

Then ship `/var/backups/aakash/media` offsite (S3/Backblaze via rclone), same as
the DB dumps. Better: migrate to `cloudinary` or `s3` so media is durable by
default and the host stays stateless.
```
```

## Restore checklist (any disk)
1. Restore the Postgres dump first (it holds the image *records* / paths).
2. Ensure the media store has the matching files (Cloudinary/S3: already there;
   local: untar into the `storage` volume).
3. `php artisan storage:link` if using the public disk.
