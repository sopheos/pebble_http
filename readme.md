# Pebble/Http

Couche d'abstraction pour PHP pour analyser des requetes HTTP et créer des réponse HTTP.

## Request

`Pebble\Http\Request` Récupération de données d'une requète HTTP.

### Données du serveur

- `servers(): array` Retourne un tableau des paramètres serveur (`$_SERVER`).
- `server(string $name)` Retourne un paramètre serveur.
- `time(): int` Retourne l'horodatage en secondes de la requète.
- `mtime(): float` Retourne l'horodatage en secondes de la requète avec une précision à la microseconde.

### Méthode HTTP

`method(): string` Retourne la méthode HTTP de la requète (CLI, GET, POST, DELETE, ...).
`isGet(): bool` Retourne vrai pour une requète HTTP GET.
`isPost(): bool` Retourne vrai pour une requète HTTP POST.
`isPut(): bool` Retourne vrai pour une requète HTTP PUT.
`isPatch(): bool` Retourne vrai pour une requète HTTP PATCH.
`isDelete(): bool` Retourne vrai pour une requète HTTP DELETE.
`isClient(): bool` Retourne vrai pour une requète depuis la ligne de commande.
`isAjax(): bool` Retourne vrai pour une requète AJAX.
`isSecure(): bool` Returne vrai pour une requète HTTPS.
` uri(): string` Retourne l'URI demandée par la requète.

### Cookies

- `cookies()` Returne un tableau des cookies.
- `cookie(string $name)` Returne la valeur d'un cookie.

### Données de la requète

- `queryParams(): array` Returne un tableau des paramètres de la requête.
- `queryParam(string $name)` Returne la valeur d'un paramètre de la donnée.
- `bodyParams(): array` Returne un tableau des données du corps de la requète d'un formulaire ou JSON.
- `bodyParam(string $name)` Returne la valeur d'une donnée du corps de la requète d'un formulaire ou JSON.
- `attachements(): array` Retourne un tableau des données des fichiers envoyés. Indexé par le nom du champ.
- `attachement(string $name): array` Retourne les données d'un fichier envoyé.

### Client

- `userAgent(): string` Returns the user agent.
- `ip(): string` Returns the IP Address.

## Response

`Pebble\Http\Response` Créer une réponse HTTP.

- `reset(): mixed` Réinitialise la réponse.

### HTTP status

- `setProtocolVersion(): string` Définit la version du protocole HTTP. Par défaut utilise celui de la requète.
- `setStatusCode(int $code, ?string $reason = null): static` Définit le status HTTP de la réponse. Si la raison est nulle, une valeur par défaut correspondant au code HTTP sera utilisée.

### HTTP headers

- `addHeader(string $name, string $value): static` Ajoute une entête HTTP.
- `removeHeader(string $name): static` Supprimer une entête HTTP.
- `setContentType(string $mime, string $charset = "UTF-8"): static` Définit le type.
    ````php
    $res->setContentType('jpg'); // shortcurt
    $res->setContentType('image/jpeg'); // verbose
    ````
- `addCookie(string $name, $value, int $expire = 0, $settings = []): static` Ajoute un cookie.
- `removeCookie(string $name): static` Supprime un cookie.
- `redirect(string $url = "/", bool $temporary = true): static` Redirection HTTP.
- `cache(int $age = 86400): static` Raccourcis pour configurer le cache HTTP.
- `noCache(): static` Désactive le cache HTTP.
- `cors(?string $origin = null, ?string $method = null): static` Active les CORS.

### Http body

- `setBody(Pebble\Http\Stream|string $body = ""): static` Ajoute un contenu a la réponse.
- `setText(string $data = ''): static` Convertit une chaîne en text/plain.
- `setJson(mixed $data = null): static` Convertit une donnée en application/json.
- `setJsonException(ResponseException $ex): static` Convertit une ResponseException en application/json.

### Rendering

`emitHeaders()` Envoi les entêtes au navigateur.
`emitBody(int $bufferLength = 0)` Envoi le contenu au navigateur.
`emit(int $bufferLength = 0)` Envoi entête et contenu au navigateur.

## Session

`Pebble\Http\Session` Couche d'abstraction pour manipuler les sessions.

- `__construct(\SessionHandlerInterface $handler = NULL)` Le constructeur accepte un gestionnaire de session optionnel (memcache, db, etc).
- `start(): static` Démarre une session.
- `close(): static` Ecrit et ferme la session.
- `destroy(): static` Détruit une session.
- `reset(): static` Supprime toutes les variables sessions.
- `all()` Retourne toutes les données.
- `has(string $name): bool` Retourne si une donnée existe en session.
- `get(string $name, $default = null): mixed` Récupère la valeur d'une session.
- `set(string $name, mixed $value): static` Ajoute une donnée à la session.

### Flash data

Une donnée flash existe jusqu'a la prochaine requete (a moins de la re-marqué en flash).

- `setFlash(string $name, $value): static` 
- `markFlash(string $name): static`
- `unmarkFlash(string $name): static`

### Temp data

Une donnée temporaire existe une certaine durée.

- `setTemp($name, $value, $time = 300)`
- `markTemp(string $name, int $time = 300)`
- `unmarkTemp(string $name)`

## Exceptions

Utilisés pour gérer globalement les erreurs des réponses HTTP.

## ResponseException

Hérite de `Exception` et implémente `JsonSerializable`.

`setErrors(array $errors): static` : Configure les erreurs 
`addError(string $key, mixed $value): static` : Ajoute une erreur.
`setExtra(array $extra): static` : Configure des données additionnelles.
`addExtra(string $key, mixed $value): static` : Ajoute une donnée additionnelle.

## Liste des exceptions

Héritent de `ResponseException`

- `AccessException` : Informations d'authentification non valides (Statut 401).
- `ForbiddenException` : Accès interdit (Statut 403).
- `EmptyException` : Ressource introuvable (Statut 404).
- `LockException` : Ressource verrouillée. (Statut 423).
- `UserException` : Erreur provenant de l'utilisateur. Erreur de validation par exemple (Statut 400).
- `SystemException` : Erreur d'execution. Telle une panne. (Statut 500).
