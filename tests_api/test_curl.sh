#!/usr/bin/env bash
# Script de test rapide de l'API en ligne de commande (curl).
# Usage : ./test_curl.sh http://localhost:8000/api

BASE_URL="${1:-http://localhost:8000/api}"

echo "== 1. Connexion =="
TOKEN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@moto-api.com","password":"password123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['token'])")

echo "Token obtenu : $TOKEN"

echo "== 2. Création d'une moto =="
curl -s -X POST "$BASE_URL/motos" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"immatriculation":"1234 TBA","marque":"Yamaha","modele":"YBR 125","type_vehicule":"moto"}' | python3 -m json.tool

echo "== 3. Liste des motos =="
curl -s -X GET "$BASE_URL/motos" -H "Authorization: Bearer $TOKEN" | python3 -m json.tool

echo "== 4. Tableau de bord =="
curl -s -X GET "$BASE_URL/dashboard" -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
