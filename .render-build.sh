#!/usr/bin/env bash

# Télécharger FFmpeg
curl -sL https://johnvansickle.com/ffmpeg/releases/ffmpeg-release-amd64-static.tar.xz | tar -xJ 

# Définir le chemin vers FFmpeg (sans déplacement)
export PATH="$PWD/ffmpeg-*-static:$PATH"

# Vérifier si FFmpeg fonctionne
ffmpeg -version
