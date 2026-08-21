# Automated SSH deployment

The deployment replaces the existing `tg-instantview` directory without
changing its WordPress plugin path, so existing settings and activation state
are preserved. It validates every PHP file, creates a timestamped backup,
activates the plugin when necessary, flushes W3 Total Cache and the WordPress
object cache, and restores the backup if WordPress cannot load the new version.

## Deploy to 2050.su

Run from the repository checkout on your Mac:

```bash
cd /path/to/wp-tg-instantview
chmod +x deploy/*.sh
SSH_TARGET=viktorbush@77.222.61.69 \
WP_ROOT=/home/v/viktorbush/2050su/domains/2050.su/public_html \
REF=master \
./deploy/deploy-over-ssh.sh
```

If the SSH host is saved in `~/.ssh/config`, use its alias as `SSH_TARGET`.
For a key registered in the Sweb control panel, add for example
`SSH_IDENTITY=~/.ssh/2050su`. Without a registered key, SSH can ask for the
account password interactively.

`REF` may be a branch, tag, or commit SHA. For a reproducible production
deployment, prefer a release tag:

```bash
REF=v1.9.0 ./deploy/deploy-over-ssh.sh
```

## Verify on the server

```bash
cd /home/v/viktorbush/2050su/domains/2050.su/public_html
/usr/bin/php8.5 -r '
require "wp-load.php";
require_once ABSPATH."wp-admin/includes/plugin.php";
$plugin="tg-instantview/tg-instantview.php";
echo "active=".(is_plugin_active($plugin)?"YES":"NO").PHP_EOL;
echo "version=".get_plugin_data(WP_PLUGIN_DIR."/".$plugin,false,false)["Version"].PHP_EOL;
$options=tgiv_options();
echo "media=".json_encode([
    "enabled"=>$options["tgiv_process_media"],
    "action"=>$options["tgiv_media_action"],
],JSON_UNESCAPED_SLASHES).PHP_EOL;
'
```

Then open **Settings → TG InstantView**, enable **Process media?**, choose the
action, save, and test a new cache-busting article URL:

```text
https://2050.su/example-post/?tg-instantview=1&test=media-1
```

After rollout, purge the Cloudflare Pages cache or change the Worker's
`CACHE_VERSION`; otherwise an already cached article can retain its previous
HTML until the edge TTL expires.

## Rollback

Backups are stored in:

```text
wp-content/upgrade/tg-instantview-backup-YYYYMMDDTHHMMSSZ
```

The installer automatically restores the latest backup when activation or
WordPress loading fails. To roll back manually, move the current plugin aside
and move the required backup back to `wp-content/plugins/tg-instantview`.
