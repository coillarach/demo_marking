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
