# Construire et démarrer tous les services
docker-compose up --build

# Démarrer en arrière-plan
docker-compose up -d

# Arrêter les services
docker-compose down

# Voir les logs
docker-compose logs -f

# Accès aux services

- Frontend React : http://localhost:5173
- Backend PHP : http://localhost:8080
- MySQL : localhost:3306