set shell := ["bash", "-euo", "pipefail", "-c"]

image := env_var_or_default("SINTERKLAAS_IMAGE", "registry.opsum.nl/sinterklaas")
tag := env_var_or_default("SINTERKLAAS_TAG", "manual")
platform := env_var_or_default("SINTERKLAAS_PLATFORM", "linux/amd64")
local_image := env_var_or_default("SINTERKLAAS_LOCAL_IMAGE", "sinterklaas-release")
local_tag := env_var_or_default("SINTERKLAAS_LOCAL_TAG", "local")

default:
    @just --list

# Build the local release image used by docker-compose.dev.yml.
build:
    SINTERKLAAS_IMAGE={{local_image}} SINTERKLAAS_TAG={{local_tag}} SINTERKLAAS_PLATFORM={{platform}} ./bin/docker-build.sh

# Build and push the image to the registry. Authenticate with `docker login` first.
push:
    SINTERKLAAS_IMAGE={{image}} SINTERKLAAS_TAG={{tag}} SINTERKLAAS_PLATFORM={{platform}} ./bin/docker-build.sh --push

# Print the image reference used by the other recipes.
image-reference:
    @echo "{{image}}:{{tag}}"

# Inspect the published image manifest and digest.
inspect:
    docker buildx imagetools inspect {{image}}:{{tag}}
