# Resources - JeeTizen

Documentation et ressources techniques pour le plugin JeeTizen.

## Protocole WebSocket Samsung Tizen

### Configuration

- **Protocol**: WSS (WebSocket Secure) ou WS (non sécurisé)
- **Port**: 8002 (défaut)
- **Endpoint**: `/api/v2/channels/samsung.remote.control`
- **Timeout initial**: 20 secondes (première connexion)
- **Timeout avec token**: 5 secondes (connexions ultérieures)

### URL de connexion

```
wss://[IP_TV]:8002/api/v2/channels/samsung.remote.control?name=[APP_NAME]&token=[TOKEN]
```

Exemple:
```
wss://192.168.1.100:8002/api/v2/channels/samsung.remote.control?name=amVlZG9tLmplZXRpemVu&token=xxxxx
```

### Format des messages

#### Message de contrôle de touche

```json
{
    "method": "ms.remote.control",
    "params": {
        "Cmd": "Click",
        "DataOfCmd": "KEY_POWER",
        "Option": "false",
        "TypeOfRemote": "SendRemoteKey"
    }
}
```

#### Réponse de connexion

```json
{
    "event": "ms.channel.connect",
    "data": {
        "token": "xxxxxxxxxxxxx",
        "clients": []
    }
}
```

### Codes des touches

#### Contrôle d'alimentation
- `KEY_POWER` - Allumer/Éteindre

#### Contrôle du son
- `KEY_MUTE` - Couper/Rétablir le son
- `KEY_VOLUP` - Augmenter le volume
- `KEY_VOLDOWN` - Réduire le volume

#### Navigation des chaînes
- `KEY_CHUP` - Chaîne suivante
- `KEY_CHDOWN` - Chaîne précédente
- `KEY_PRECH` - Chaîne précédente (accès rapide)

#### Navigation générale
- `KEY_HOME` - Écran d'accueil
- `KEY_MENU` - Menu principal
- `KEY_UP` - Flèche haut
- `KEY_DOWN` - Flèche bas
- `KEY_LEFT` - Flèche gauche
- `KEY_RIGHT` - Flèche droite
- `KEY_ENTER` - Entrée/Validation
- `KEY_BACK` - Retour/Annuler
- `KEY_EXIT` - Quitter

#### Numéros et sources
- `KEY_0` à `KEY_9` - Numéros
- `KEY_SOURCE` - Menu des sources
- `KEY_TV` - Source TV analogique

#### Touches colorées
- `KEY_RED` - Bouton rouge
- `KEY_GREEN` - Bouton vert
- `KEY_YELLOW` - Bouton jaune
- `KEY_BLUE` - Bouton bleu

#### Autres
- `KEY_INFO` - Information/Détails
- `KEY_TOOLS` - Outils
- `KEY_GUIDE` - Guide des programmes
- `KEY_RECORD` - Enregistrement

### Authentification

#### Première connexion (sans token)

1. Connexion à l'endpoint WSS
2. Réception du message `ms.channel.connect` avec nouveau token
3. Stockage du token pour les connexions futures

#### Connexions suivantes (avec token)

1. Utiliser le token stocké dans la requête
2. Connexion plus rapide (timeout réduit)
3. Reconnexion si token invalide

### Gestion des erreurs

- **Timeout dépassé**: Vérifier l'IP et le port
- **Connexion refusée**: TV éteinte ou hors réseau
- **Token invalide**: Supprimer le token stocké et reconnecter
- **Séquence invalide**: Vérifier la syntaxe des touches

### Exemples de séquences

#### Changer de chaîne

```
KEY_TV | KEY_1 | KEY_2 | KEY_ENTER
```

#### Accéder au menu Netflix

```
KEY_HOME | KEY_DOWN | KEY_DOWN | KEY_ENTER
```

#### Couper le son et augmenter le volume

```
KEY_MUTE | KEY_MUTE | KEY_VOLUP | KEY_VOLUP
```

### Notes d'implémentation

- Délai recommandé entre les touches: 100-500ms
- Maximum de touches par séquence: illimité (recommandé < 10)
- Fermer la connexion après envoi complet
- Gérer les exceptions WebSocket
- Logger les erreurs pour débogage

### Ressources externes

- [Samsung SmartTV WebSocket Protocol](https://github.com/search?q=samsung+tizen+websocket)
- [RFC 6455 - WebSocket Protocol](https://tools.ietf.org/html/rfc6455)
- [Documentation Textalk WebSocket](https://github.com/Textalk/websocket-php)