#!/usr/bin/env bash
# ---------------------------------------------------------------
# FamilyFlow Backup: Datenbank + Uploads + Einstellungen
# -> verschluesselt nach Cloudflare R2 (ueber rclone, Remote "ffbackup")
#
# Laeuft jede Nacht automatisch (systemd-Timer familyflow-backup.timer).
# Von Hand:  sudo bash /var/www/familyflow/tools/backup.sh
#
# Aufbewahrung in R2:  daily/ 7 Tage, weekly/ (Sonntag) 5 Wochen, monthly/ (1. des Monats) 6 Monate
# Lokal bleiben die letzten 3 Backups in /var/backups/familyflow
# Schlaegt etwas fehl, geht eine Mail an admin_email (mailconfig.php).
# ---------------------------------------------------------------
set -euo pipefail

# rclone-Einstellungen (R2-Zugang + Verschluesselung) liegen beim root-Benutzer.
# Fest angeben, weil der naechtliche Systemdienst sonst evtl. kein HOME kennt.
export RCLONE_CONFIG="${RCLONE_CONFIG:-/root/.config/rclone/rclone.conf}"

APP_DIR="/var/www/familyflow"
LOCAL_DIR="/var/backups/familyflow"
REMOTE="ffbackup:"
STAMP="$(date +%Y-%m-%d_%H%M)"
NAME="familyflow_${STAMP}.tar.gz"
WORK="$(mktemp -d)"

# Bei jedem Fehler: Info-Mail schicken und Arbeitsordner aufraeumen
on_error() {
    local line="$1"
    php "$APP_DIR/tools/backup_failed_mail.php" "Backup abgebrochen in Zeile $line ($(date '+%d.%m.%Y %H:%M'))" || true
    rm -rf "$WORK"
}
trap 'on_error $LINENO' ERR

mkdir -p "$LOCAL_DIR"
chmod 700 "$LOCAL_DIR"

echo "[$(date '+%F %T')] Backup startet: $NAME"

# 1. Datenbank (als root ueber den Unix-Socket, wie bei "sudo mysqldump")
mysqldump --single-transaction --routines familyflow > "$WORK/familyflow.sql"

# 2. Uploads + Einstellungen, die nicht im Git sind
mkdir -p "$WORK/files"
cp -a "$APP_DIR/uploads" "$WORK/files/"
mkdir -p "$WORK/files/config" "$WORK/files/server"
cp -a "$APP_DIR"/src/config/dbconfig.php "$APP_DIR"/src/config/mailconfig.php "$WORK/files/config/"
cp -a /etc/apache2/conf-available/familyflow-security.conf "$WORK/files/server/" 2>/dev/null || true
cp -a /etc/php/*/apache2/conf.d/99-familyflow.ini "$WORK/files/server/" 2>/dev/null || true

# 3. Alles in ein Archiv packen
tar -czf "$LOCAL_DIR/$NAME" -C "$WORK" familyflow.sql files
chmod 600 "$LOCAL_DIR/$NAME"
rm -rf "$WORK"

# 4. Verschluesselt nach R2 (rclone "crypt" verschluesselt vor dem Hochladen)
rclone copyto "$LOCAL_DIR/$NAME" "${REMOTE}daily/$NAME"
if [ "$(date +%u)" = "7" ]; then
    rclone copyto "$LOCAL_DIR/$NAME" "${REMOTE}weekly/$NAME"
fi
if [ "$(date +%d)" = "01" ]; then
    rclone copyto "$LOCAL_DIR/$NAME" "${REMOTE}monthly/$NAME"
fi

# 5. Alte Staende entfernen
rclone delete --min-age 8d  "${REMOTE}daily/"
rclone delete --min-age 36d "${REMOTE}weekly/"
rclone delete --min-age 190d "${REMOTE}monthly/"
ls -1t "$LOCAL_DIR"/familyflow_*.tar.gz | tail -n +4 | xargs -r rm -f

SIZE="$(du -h "$LOCAL_DIR/$NAME" | cut -f1)"
echo "[$(date '+%F %T')] Backup fertig: $NAME ($SIZE)"
