<?php

namespace App\Jobs;

use App\Functions\FileFunctions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteFileAndRecordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $model;
    protected $recordId;
    protected $fileColumn;

    /**
     * Create a new job instance.
     */
    public function __construct($model, $recordId, $fileColumn)
    {
        $this->model = $model;
        $this->recordId = $recordId;
        $this->fileColumn = $fileColumn;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $record = $this->model::find($this->recordId);

        if (!$record) {
            Log::warning("🟡 Suppression annulée : Enregistrement introuvable (ID: {$this->recordId})");
            return;
        }

        // 🔥 Supprimer le fichier en utilisant FileFunctions
        if (FileFunctions::deleteFile($record->{$this->fileColumn})) {
            Log::info("✅ Fichier supprimé : {$record->{$this->fileColumn}}");
        } else {
            Log::warning("⚠️ Impossible de supprimer le fichier : {$record->{$this->fileColumn}}");
        }

        // 🔥 Supprimer l'enregistrement de la base de données
        $record->delete();
        Log::info("✅ Enregistrement supprimé (ID: {$this->recordId})");
    }
}
