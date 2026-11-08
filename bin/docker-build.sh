#!/usr/bin/env bash
set -euo pipefail

image="${SINTERKLAAS_IMAGE:-registry.opsum.nl/sinterklaas}"
tag="${SINTERKLAAS_TAG:-$(git rev-parse HEAD)}"
platform="${SINTERKLAAS_PLATFORM:-linux/amd64}"
push=false

for argument in "$@"; do
    case "$argument" in
        --push) push=true ;;
        --help|-h)
            echo "Usage: $0 [--push]"
            echo "SINTERKLAAS_IMAGE, SINTERKLAAS_TAG, SINTERKLAAS_PLATFORM, and COMPOSER_AUTH_FILE are supported."
            exit 0
            ;;
        *) echo "Unknown argument: $argument" >&2; exit 2 ;;
    esac
done

build_args=(buildx build --file Dockerfile.release --platform "$platform" --tag "$image:$tag")
if [[ -n "${COMPOSER_AUTH_FILE:-}" ]]; then
    [[ -f "$COMPOSER_AUTH_FILE" ]] || { echo "Composer auth file does not exist: $COMPOSER_AUTH_FILE" >&2; exit 1; }
    build_args+=(--secret "id=composer_auth,src=$COMPOSER_AUTH_FILE")
fi
if [[ "$push" == true ]]; then
    build_args+=(--push)
else
    build_args+=(--load)
fi

docker "${build_args[@]}" .
echo "Built $image:$tag"
if [[ "$push" == true ]]; then echo "Pushed $image:$tag"; else echo "Use --push to publish this image."; fi
