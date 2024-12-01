#!/usr/local/bin/bash

DRY_RUN=
MODE=sync
PROG=$0


function usage() {
    echo "usage: $PROG -u user -g game -s src-dir [-mode mirror|sync] [-dry-run]"
    exit 1
}

while getopts 'u:g:s:m:d' OPT
do
    case "$OPT" in
        d)
            DRY_RUN=--dry-run
            ;;
        u)
            BGAUSER="$OPTARG"
            ;;
        g)
            BGAPROJ="$OPTARG"
            ;;
        m)
            MODE="$OPTARG"
            ;;
        s)
            SRC="$OPTARG"
            ;;
        *)
            usage
            ;;
    esac
done

if [ "$BGAUSER" == "" -o "$SRC" == "" -o "$BGAPROJ" == "" ]; then
    usage
fi

# SRC="$HOME/projects/bga/babylonia"

DEST=${BGAGAME}
cd "${SRC}"

LAST_SYNC=.last_sync
HOST=sftp://${BGAUSER}:@1.studio.boardgamearena.com:2022

# exclude "local" dir, any dot-dir (like .git), emacs temp files
# and BGA-controlled files.
EXCLUDES='(((work/|local/|\.|.*#).*)|LICENSE_BGA|Makefile|_ide_helper.php|psalm.xml)'

function lftp_via_sync() {
    COPY="lftp ${HOST}"
    TOUCH="touch ${LAST_SYNC}"
    if [ "${DRY_RUN}" == --dry-run ];
    then
        COPY=cat
        TOUCH=true
    fi
    declare -a files
    readarray -t files < \
              <(find -E -X . -newer ${LAST_SYNC} -not -regex ^./"${EXCLUDES}" -type f)
    echo mput -O "${DEST}" -d "${files[@]}" | ${COPY} && ${TOUCH}
}

function lftp_mirror() {
    REPEAT=$1
    lftp "${HOST}" <<EOF
cd ${DEST}
${REPEAT} mirror ${DRY_RUN} -R -vvv -x ^'${EXCLUDES}'
EOF
}

function lftp_via_periodic_mirror() {
    lftp_mirror "repeat -d 1m -c 60"
}

function lftp_via_mirror() {
    lftp_mirror ""
}

case "$MODE" in
    mirror)
        lftp_via_mirror
        ;;
    sync)
        lftp_via_sync
        ;;
    *)
        usage
esac
