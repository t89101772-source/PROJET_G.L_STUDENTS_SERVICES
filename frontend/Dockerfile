# Dockerfile pour le frontend React + Vite
FROM node:20-alpine

WORKDIR /app

# Copier les fichiers package
COPY package*.json ./

# Installer les dépendances
RUN npm install

# Copier le reste du code
COPY . .

# Exposer le port Vite
EXPOSE 5173

# Démarrer le serveur de développement avec host 0.0.0.0
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]