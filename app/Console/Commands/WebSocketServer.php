<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Workerman\Worker;

class WebSocketServer extends Command {
    /**
    * Le nom et la signature de la commande.
    *
    * @var string
    */
    protected $signature = 'ws:serve';

    /**
    * La description de la commande.
    *
    * @var string
    */
    protected $description = 'Démarre le serveur WebSocket avec Workerman';

    /**
    * Exécuter la commande.
    *
    * @return void
    */

    public function handle() {
        require_once base_path( 'app/Websockets/server.php' );
    }
}