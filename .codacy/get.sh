#!/usr/bin/env bash

set -e +o pipefail

# Log levels
i="\033[0;36m" # info
g="\033[0;32m" # green
e="\033[0;31m" # error
l="\033[0;90m" # log test
r="\033[0m" # reset


# Logger
# This function log messages
# Usage: log <LEVEL> <MESSAGE>
log() {
    echo -e " $1--> $l$2$r"
}


# Fatal logger
# This function log fatal messages
# Usage: fatal <MESSAGE> <EXIT_CODE>
fatal() {
    log "$e" "$1"
    exit "$([ $# -eq 2 ] && echo "$2" || echo 1)"
}


# Greetings
# This function print the greetings message for this coverage reporter
# Usage: greetings
greetings() {
cat << EOF
     ______          __
    / ____/___  ____/ /___ ________  __
   / /   / __ \/ __  / __ \`/ ___/ / / /
  / /___/ /_/ / /_/ / /_/ / /__/ /_/ /
  \____/\____/\__,_/\__,_/\___/\__, /
                              /____/

  Codacy Coverage Reporter

EOF
}

greetings

# Error trap
# This function prints succeeded or failed message depending on last exit status
# Usage: exit_trap
exit_trap() {
    EXIT_NUM=$?

    echo

    if [ $EXIT_NUM -eq 0 ];
    then
        log "$g" "Succeeded!"
    else
        fatal "Failed!"
    fi
}

trap exit_trap EXIT
trap 'fatal Interrupted' INT

download_stdout() {
    local url="$1"

    if command -v curl > /dev/null 2>&1; then
        curl -# -LS "$url" -o-
    elif command -v wget > /dev/null 2>&1; then
        wget "$url" -O-
    else
        fatal "Could not find curl or wget, please install one."
    fi
}

download_file() {
    local url="$1"

    if command -v curl > /dev/null 2>&1; then
        curl -# -LS "$url" -O
    elif command -v wget > /dev/null 2>&1; then
        wget "$url"
    else
        fatal "Could not find curl or wget, please install one."
    fi
}

checksum() {
  local file_name="$1"
  local checksum_url="$2"
  local major_version="$(echo "$CODACY_REPORTER_VERSION" | cut -d '.' -f 1)"

  if [ "$CODACY_REPORTER_SKIP_CHECKSUM" = true ]; then
    log "$i" "Force skipping checksum on the binary."
  elif [ "$major_version" -ge 13 ]; then
    log "$i" "Checking checksum..."
    download_file "$checksum_url"
    if command -v sha512sum > /dev/null 2>&1; then
        sha_check_command="sha512sum"
    elif command -v shasum > /dev/null 2>&1; then
        sha_check_command="shasum -a 512"
    else
        fatal "Error: can't validate checksum, please see https://docs.codacy.com/coverage-reporter/troubleshooting-common-issues/#checksum"
    fi

    log "$i" "Expected checksum"
    cat "$file_name.SHA512SUM"
    log "$i" "Actual checksum"
    $sha_check_command "$file_name"
    $sha_check_command -c "$file_name.SHA512SUM"
  else
    log "$i" "Checksum not available for versions prior to 13.0.0, consider updating your CODACY_REPORTER_VERSION"
  fi
}

download() {
    local url="$1"
    local file_name="$2"
    local output_folder="$3"
    local output_filename="$4"
    local checksum_url="$5"
    local original_folder="$(pwd)"

    cd "$output_folder"

    download_file "$url"
    checksum "$file_name" "$checksum_url"
    if [ "$os_name_arch" = "Linux x86_64" ] || [ "$os_name_arch" = "Darwin arm64" ]; then
        mv "$file_name" "$output_filename"
    fi

    cd "$original_folder"
}

download_reporter() {
    if [ "$os_name_arch" = "Linux x86_64" ] || [ "$os_name_arch" = "Darwin arm64" ]; then
        # OS name lower case
        suffix=$(echo "$os_name" | tr '[:upper:]' '[:lower:]')
    else
        suffix="assembly.jar"
    fi
    local binary_name="codacy-coverage-reporter-$suffix"
    local reporter_path=$1
    local reporter_folder=$2
    local reporter_filename=$3

    if [ ! -f "$reporter_path" ]
    then
        log "$i" "Downloading the codacy reporter $binary_name... ($CODACY_REPORTER_VERSION)"

        binary_url="https://artifacts.codacy.com/bin/codacy-coverage-reporter/$CODACY_REPORTER_VERSION/$binary_name"
        checksum_url="https://github.com/codacy/codacy-coverage-reporter/releases/download/$CODACY_REPORTER_VERSION/$binary_name.SHA512SUM"

        download "$binary_url" "$binary_name" "$reporter_folder" "$reporter_filename" "$checksum_url"
    else
        log "$i" "Codacy reporter $binary_name already in cache"
    fi
}

is_self_hosted_instance() {
  if [[ "$CODACY_API_BASE_URL" == "https://api.codacy.com"* ]] || \
     [[ "$CODACY_API_BASE_URL" == "https://app.codacy.com"* ]] || \
     [[ "$CODACY_API_BASE_URL" == "https://app.staging.codacy.org"* ]] || \
     [[ "$CODACY_API_BASE_URL" == "https://api.staging.codacy.org"* ]] || \
     [[ "$CODACY_API_BASE_URL" == "https://app.dev.codacy.org"* ]] || \
     [[ "$CODACY_API_BASE_URL" == "https://api.dev.codacy.org"* ]]; then
    false
  else
    true
  fi
}

os_name=$(uname)
os_name_arch=$(uname -sm)

# This version should be one that matches the latest self hosted release.
SELF_HOSTED_CODACY_REPORTER_VERSION="13.13.14"

# Find the latest version in case is not specified
if [ -z "$CODACY_REPORTER_VERSION" ] || [ "$CODACY_REPORTER_VERSION" = "latest" ]; then
    # In case of a self hosted installation, pin a version to the latest released self hosted version working coverage reporter
    if [ -n "$CODACY_API_BASE_URL" ] && is_self_hosted_instance; then
      log "Self hosted instance detected, setting codacy coverage reporter version to $SELF_HOSTED_CODACY_REPORTER_VERSION"
      CODACY_REPORTER_VERSION="$SELF_HOSTED_CODACY_REPORTER_VERSION"
    else
      CODACY_REPORTER_VERSION=$(download_stdout "https://artifacts.codacy.com/bin/codacy-coverage-reporter/latest")
    fi
fi

# Temporary folder for downloaded files
if [ -z "$CODACY_REPORTER_TMP_FOLDER" ]; then
    if [ "$os_name" = "Linux" ]; then
        CODACY_REPORTER_TMP_FOLDER="$HOME/.cache/codacy/coverage-reporter"
    elif [ "$os_name" = "Darwin" ]; then
        CODACY_REPORTER_TMP_FOLDER="$HOME/Library/Caches/Codacy/coverage-reporter"
    else
        CODACY_REPORTER_TMP_FOLDER=".codacy-coverage"
    fi
fi

# Set binary name
if [ "$os_name_arch" = "Linux x86_64" ] || [ "$os_name_arch" = "Darwin arm64" ]; then
    reporter_filename="codacy-coverage-reporter"
else
    reporter_filename="codacy-coverage-reporter-assembly.jar"
fi

# Folder containing the binary
reporter_folder="$CODACY_REPORTER_TMP_FOLDER"/"$CODACY_REPORTER_VERSION"

# Create the reporter folder if not exists
mkdir -p "$reporter_folder"

# Set binary path
reporter_path="$reporter_folder"/"$reporter_filename"

download_reporter "$reporter_path" "$reporter_folder" "$reporter_filename"

if [ "$os_name_arch" = "Linux x86_64" ] || [ "$os_name_arch" = "Darwin arm64" ]; then
    chmod +x "$reporter_path"
    run_command="$reporter_path"
else
    run_command="java -jar \"$reporter_path\""
fi

if [ -z "$run_command" ]
then
    fatal "Codacy coverage reporter binary could not be found."
fi
if [ -z "$CODACY_REPORTER_OPTIONS" ] || [ -n "$CODACY_REPORTER_OPTIONS" ]; then
    EXTRA_ARGUMENTS="$CODACY_REPORTER_OPTIONS"
else
    EXTRA_ARGUMENTS=""
fi


if [ "$#" -eq 1 ] && [ "$1" = "download" ];
then
    log "$g" "Codacy reporter download succeeded";
elif [ "$#" -gt 0 ];
then
    log "Running command: $run_command $* $EXTRA_ARGUMENTS"
    eval "$run_command $* $EXTRA_ARGUMENTS"
else
    log "Running command: $run_command \"report\" $EXTRA_ARGUMENTS"
    eval "$run_command \"report\" $EXTRA_ARGUMENTS"
fi

