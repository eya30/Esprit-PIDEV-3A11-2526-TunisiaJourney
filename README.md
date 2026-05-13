# 🌍 TunisiaJourney — Plateforme Web


> Une plateforme de voyage complète développée avec Symfony 6.4, permettant aux utilisateurs de découvrir, réserver et gérer leurs expériences de voyage en Tunisie.

---

## 📌 Table des matières

- [À propos du projet](#à-propos-du-projet)
- [Fonctionnalités](#fonctionnalités)
- [Technologies utilisées](#technologies-utilisées)
- [Installation](#installation)
- [Variables d'environnement](#variables-denvironnement)
- [Base de données](#base-de-données)
- [Lancement](#lancement)
- [Structure du projet](#structure-du-projet)
- [Équipe](#équipe)

---

## 🧭 À propos du projet

**TunisiaJourney** est une plateforme intégrée de gestion de voyages développée dans le cadre du projet intégré de 3ème année ingénierie à l'**École Supérieure Privée d'Ingénierie et de Technologies — Esprit**.

Le projet combine une **application web Symfony 6.4** et une **application desktop JavaFX**, toutes deux connectées à une base de données **MySQL** partagée.

🔗 **Repository :** [github.com/eya30/Esprit-PIDEV-3A11-2526-TunisiaJourney](https://github.com/eya30/Esprit-PIDEV-3A11-2526-TunisiaJourney.git)

---

## ✨ Fonctionnalités

### 👤 Gestion des utilisateurs
- Inscription et connexion sécurisée avec hachage BCrypt
- Authentification Google OAuth 2.0
- Authentification à deux facteurs (2FA) avec TOTP
- Connexion par reconnaissance faciale (DeepFace & ArcFace)
- Gestion du profil avec upload de photo (ImgBB)
- Réinitialisation du mot de passe par email (Brevo API)
- Vérification de sécurité par IP & reCAPTCHA

### 🏨 Voyages & Hôtels
- Consultation et réservation de forfaits de voyage
- Listing et gestion des hôtels
- Vérification de disponibilité en temps réel

### 📅 Événements
- Découverte et inscription aux événements
- Réservation d'activités

### 🛒 Marketplace
- Listing et commande de produits
- Gestion des commandes

### 💬 Forum
- Publications et discussions communautaires
- Système de likes et commentaires

### 🔧 Back-office Admin
- Gestion des utilisateurs (CRUD, blocage/déblocage)
- Tableau de bord avec statistiques
- Journaux d'administration et surveillance

---

## 🛠 Technologies utilisées

| Couche | Technologie |
|--------|------------|
| Backend | PHP 8.2, Symfony 6.4 |
| Frontend | Twig, Bootstrap, JavaScript |
| Base de données | MySQL 8.0 / MariaDB |
| ORM | Doctrine ORM |
| Authentification | KnpU OAuth2, Google OAuth |
| Email | Brevo API |
| Reconnaissance faciale | DeepFace, ArcFace (Flask Python) |
| Upload d'images | ImgBB API |
| Sécurité | reCAPTCHA, BCrypt, TOTP |
| Tunneling | ngrok |

---

## 🚀 Installation

### Prérequis

- PHP >= 8.2
- Composer
- MySQL / MariaDB
- Symfony CLI
- Python 3.x + Flask (pour le Face ID)

### Étapes

```bash
# Cloner le repository
git clone https://github.com/eya30/Esprit-PIDEV-3A11-2526-TunisiaJourney.git
cd Esprit-PIDEV-3A11-2526-TunisiaJourney

# Installer les dépendances PHP
composer install

# Copier le fichier d'environnement
cp .env .env.local
```

---

## 🔐 Variables d'environnement

Modifiez `.env.local` avec vos propres credentials :

```env
# Base de données
DATABASE_URL="mysql://root:@127.0.0.1:3306/pidev"

# Google OAuth
GOOGLE_CLIENT_ID=votre_client_id
GOOGLE_CLIENT_SECRET=votre_client_secret

# Brevo Email
BREVO_API_KEY=votre_cle_api_brevo
BREVO_SENDER_EMAIL=votre_email@gmail.com

# ImgBB
IMGBB_API_KEY=votre_cle_imgbb

# reCAPTCHA
RECAPTCHA_SITE_KEY=votre_site_key
RECAPTCHA_SECRET_KEY=votre_secret_key
```

---

## 🗄 Base de données

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Mettre à jour le schéma
php bin/console doctrine:schema:update --force

# Vider le cache
php bin/console cache:clear
```

---

## ▶️ Lancement

```bash
# Démarrer le serveur Symfony
symfony server:start

# Ou avec le serveur PHP intégré
php -S localhost:8000 -t public

# Démarrer le serveur Flask (pour la reconnaissance faciale)
cd flask_server
python app.py
```

Accédez à l'application sur : `http://localhost:8000`

---

## 📁 Structure du projet

```
Esprit-PIDEV-3A11-2526-TunisiaJourney/
├── src/
│   ├── Controller/          # Contrôleurs Symfony
│   ├── Entity/              # Entités Doctrine
│   ├── Repository/          # Repositories base de données
│   ├── Security/            # Authentification et sécurité
│   └── Service/             # Services métier
├── templates/               # Templates Twig
├── public/                  # Assets publics
├── config/                  # Configuration Symfony
├── migrations/              # Migrations base de données
└── flask_server/            # Serveur Python Face ID
```

---

## 👥 Équipe

| Nom | Module |
|-----|--------|
| Meriem Bennour | Gestion des utilisateurs & Sécurité |
| Eya Boughdiri | Gestion des produits et commandes  |
| Maram Balti | Hôtels & Réservations |
| Souha Mzoughi | Gestion des voyages |
| Chaima Bjeoui | Événements & Activités |
| Omaima Bouzekri | Gestion du forum |

---

## 🏫 Contexte académique

- **Établissement :** Esprit — École Supérieure Privée d'Ingénierie et de Technologies
- **Niveau :** 3ème année ingénierie
- **Année universitaire :** 2025–2026
- **Type de projet :** Projet Intégré (PI)

---

## 🏷 Mots-clés / Topics

`symfony` `php` `javafx` `mysql` `voyage` `tunisie` `oauth2` `2fa` `reconnaissance-faciale` `brevo` `doctrine` `twig` `projet-integre` `esprit` `plateforme-web` `deepface` `bcrypt` `totp`

---

