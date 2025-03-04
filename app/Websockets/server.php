<?php

use Workerman\Worker;
use Illuminate\Database\Capsule\Manager as Capsule;

# Inclure les dépendances de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

# Charger le fichier d'application Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
# Initialiser Eloquent avec Laravel
$capsule = new Capsule;
$capsule->addConnection(config('database.connections.mysql'));
$capsule->setAsGlobal();
$capsule->bootEloquent();

# Configurer la longueur des chaînes de caractères pour la base de données
Capsule::schema()->defaultStringLength(191);

# Créer un serveur WebSocket avec Workerman
$port = getenv('PORT') ?: 10000;
$ws_worker = new Worker("websocket://0.0.0.0:$port");

# Définir le nombre de processus de travail
$ws_worker->count = 1;

# Stocker les connexions des clients
$clients = [];

# Lorsqu'un client se connecte
$ws_worker->onConnect = function ( $connection ) use ( &$clients ) {
    $clients[ $connection->id ] = $connection;
}
;

# Lorsqu'un message est reçu d'un client
$ws_worker->onMessage = function ( $connection, $data ) use ( &$clients ) {
    $data = json_decode( $data, true );
    if ( !isset( $data[ 'type' ] ) ) {
        echo 'Requête invalide (type manquant)\n';
        return;
    }

    switch ( $data[ 'type' ] ) {
        case 'new_story':
        $response = [
            'type' => 'new_story',
            'story' => $data[ 'story' ]
        ];
        break;
        case 'delete_story':
        $response = [
            'type' => 'delete_story',
            'story_id' => $data[ 'story' ][ 'id' ]
        ];
        break;
        case 'story_viewed':
            $response = [
                'type' => 'story_viewed',
                'user_id' => $data['user_id'],
                'story_id' => $data['story_id']
            ];
            break;
        case 'messages_sent':
            $response = [
                'type' => 'messages_sent',
                'messages' => $data['messages'],
                'unread_count' => $data['unread_count'],
            ];
            break;
        case 'messages_read':
            $response = [
                'type' => 'messages_read',
                'receiver_id' => $data['receiver_id'],
                'sender_id' => $data['sender_id'],
                'unread_count' => $data['unread_count'],
            ];
            break;

        default:
        echo 'Type de requête non reconnu : ' . $data[ 'type' ] . '\n';
        return;
    }

    if ( $response ) {
        foreach ( $clients as $client ) {
            $client->send( json_encode( $response ) );
        }
    }
}
;

# Lorsqu'un client se déconnecte
$ws_worker->onClose = function ( $connection ) use ( &$clients ) {
    unset( $clients[ $connection->id ] );
}
;

# Démarrer le serveur Workerman
Worker::runAll();