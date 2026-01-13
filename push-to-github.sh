#!/bin/bash
# Script para subir el proyecto a GitHub
# Ejecuta este script después de crear el repositorio en GitHub

# Reemplaza 'NOMBRE-DEL-REPO' con el nombre real de tu repositorio
REPO_NAME="sistema-bancario-web"

echo "🚀 Subiendo proyecto a GitHub..."

# Agregar el remote
git remote add origin https://github.com/gaxl-1/$REPO_NAME.git

# Subir a GitHub
git push -u origin main

echo "✅ ¡Proyecto subido exitosamente!"
echo "📍 URL: https://github.com/gaxl-1/$REPO_NAME"
