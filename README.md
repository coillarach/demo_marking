# Deployment

To deploy a module, run from the repo root:

`./deploy.sh <module code> /path/to/webroot`                                                                                         

e.g.

`./deploy.sh SET08101 /path/to/set08101/webroot`

The script copies everything from src/ into the target directory, then copies the relevant 
configs/SETXXXXX.php in as config.php. It also creates empty data/ and submissions/ 
directories if they don't exist.

The resulting target directory is a flat, self-contained web application.
Point your web server's document root at it and it runs.

First-time setup for a new module instance:

1. Edit configs/SETXXXXX.php — replace the placeholder password_hash('changemeN', ...) values with real passwords for each marker
2. Create students.txt in the target directory (one matric per line) if you want autocomplete pre-populated
3. Ensure the web server has write permission on data/ (needed for saving mark records)

After any code change:                                                                                                                 
Re-run the deploy command — it overwrites all shared files. The data/ and submissions/ 
directories are untouched (the script only creates them if absent, never deletes them).

After a config-only change (e.g. updating passwords or adding a feature):                                                              
Re-run deploy for that module only; the other module's live directory is unaffected.

# Server requirements for uploading submissions

Bulk submission zips (Moodle's "Download all submissions") can easily exceed PHP's default
upload limits for a full-size cohort — e.g. ~120 students with PDF reports can be over 1GB.
On the server hosting the admin tool (`admin/submissions.php`), set in `php.ini`:

- `upload_max_filesize` — at least as large as the biggest zip you'll upload (e.g. `2000M`)
- `post_max_size` — must be *larger* than `upload_max_filesize` (e.g. `2048M`)
- `memory_limit` — comfortably above `upload_max_filesize` (e.g. `512M`)

If the upload is rejected before PHP even runs (a raw web-server error page rather than one from
this app), also check the web server's own request body limit (e.g. Apache's `LimitRequestBody`,
nginx's `client_max_body_size`) and raise or remove it. With mod_php, changing `php.ini` requires
restarting Apache to take effect.
