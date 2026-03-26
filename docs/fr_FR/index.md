# Plugin JeeTizen

## Description

Plugin Jeedom pour le contrôle des téléviseurs Samsung via WebSocket. Supporte les modèles Tizen (2016+) et Legacy (pré-2016).

Basé sur les librairies de communication Samsung de TvDomSamsung, avec une interface conforme au template officiel Jeedom.

## Configuration

### Ajout d'un équipement

1. Aller dans **Plugins → Multimédia → JeeTizen**
2. Cliquer sur **Ajouter**
3. Renseigner le nom et configurer les paramètres TV

### Paramètres de connexion

- **Modèle TV** : Tizen (2016+) ou Legacy (pré-2016)
- **Série** (Tizen uniquement) : Standard, Modèle K (2016), Modèle J (encrypted), Défaut (auto)
- **Adresse IP** : IP de la TV Samsung sur le réseau local
- **Port** : 8002 (WSS, défaut Tizen), 8001 (WS), 55000 (Legacy)
- **SSL** : activé par défaut pour le port 8002
- **Token** : rempli automatiquement après la première connexion (accepter l'appairage sur la TV)
- **Application TV** : identifiant de l'application (défaut : jeedom.jeetizen.samsung)

### Modèle K (série 2016)

Les TV Samsung série K nécessitent un délai supplémentaire entre 500 et 1000 ms.

### Wake On LAN

- **Activer WOL** : permet d'allumer la TV via la commande Marche/Arrêt
- **Adresse MAC** : obligatoire si WOL activé
- **Mode Direct** : envoie le paquet WOL à une IP de broadcast spécifique
- **Mode Broadcast** : envoie le paquet WOL via un masque subnet

### Latences

- **Latence touches** : délai entre les touches non numériques (0-2000 ms)
- **Latence NUM** : délai entre les touches numériques lors d'un zap (1-2000 ms)

## Commandes disponibles

| Commande | Type | Description |
|----------|------|-------------|
| Marche/Arrêt | Action | Allumer (WOL) / éteindre la TV |
| Extinction | Action | Éteindre seulement (sans WOL) |
| Mute | Action | Couper/remettre le son |
| Volume +/- | Action | Ajuster le volume |
| Chaîne +/- | Action | Chaîne suivante/précédente |
| Source | Action | Changer de source |
| Zap | Action (slider) | Aller directement sur une chaîne |
| Touche | Action (slider) | Envoyer une séquence de touches |
| Authentifier | Action | Lancer l'authentification TV |
| Etat | Info (binaire) | État on/off de la TV |

### Commande Touche (sendkey)

Séquences possibles avec le séparateur `|` :

```
KEY_HOME
KEY_UP | KEY_UP | KEY_ENTER
KEY_TV | KEY_1 | KEY_5 | KEY_ENTER
```

## Librairies intégrées

Le plugin embarque les librairies suivantes (aucune dépendance à installer) :

- **textalk/websocket** : client WebSocket PHP (modèle Standard)
- **ratchet/pawl** + ReactPHP : client WebSocket asynchrone (modèle K)
- **phpwol** : Wake On LAN
- **Connecteurs Legacy** : protocole socket Samsung pré-Tizen
