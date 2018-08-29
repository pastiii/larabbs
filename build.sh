#!/bin/bash

function build_docker {
    TAG=$1
    IMAGE_NAME="$DOCKER_REP/platformphp/user"
    IMAGE_FULL_NAME="$IMAGE_NAME:$TAG"
    IMAGE_LATEST_NAME="$IMAGE_NAME:latest"

    HAS_OLD_IMAGES=$(docker images|grep $IMAGE_NAME|grep $TAG|wc -l)
    echo $HAS_OLD_IMAGES
    if [ $HAS_OLD_IMAGES -ne "0" ]; then
        echo "Remove docker image..."
        docker rmi $IMAGE_FULL_NAME
    fi
    echo "Building docker image..."
    docker build -t $IMAGE_FULL_NAME .
    docker tag $IMAGE_FULL_NAME $IMAGE_LATEST_NAME

    echo "Push image to reigstry $IMAGE_FULL_NAME"
    docker push $IMAGE_FULL_NAME
    echo "Push image to reigstry $IMAGE_LATEST_NAME"
    docker push $IMAGE_LATEST_NAME
}

set -e
if [ -z "DOCKER_REP" ]
then
    export DOCKER_REP="registry.bitboolean.com"
fi

DATETAG=$(date +"%y%m%d_%H%M%S")

build_docker $DATETAG

echo "Done"

