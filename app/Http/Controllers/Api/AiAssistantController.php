<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiInteraction;
use App\Models\Alerte;
use App\Models\Conducteur;
use App\Models\Moto;
use App\Models\Versement;
use App\Services\LocalAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiAssistantController extends Controller
{
    public function ask(Request $request, LocalAiService $ai)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $context = [
            'motos_actives' => Moto::where('actif', true)->count(),
            'conducteurs_actifs' => Conducteur::where('statut', 'actif')->count(),
            'versements_en_retard' => Versement::where('en_retard', true)->count(),
            'montant_restant_ar' => (float) Versement::where('en_retard', true)->sum('reste_a_payer'),
            'alertes_ouvertes' => Alerte::where('status', 'open')->count(),
        ];

        try {
            $result = $ai->answer($data['question'], $context);
            $interaction = AiInteraction::create([
                'user_id' => $request->user()->id,
                'kind' => 'assistant',
                'question' => $data['question'],
                'answer' => $result['answer'],
                'context_summary' => $context,
                'provider' => $result['provider'],
                'model' => $result['model'],
                'status' => 'completed',
            ]);

            return $this->ok([
                'interaction_id' => $interaction->id,
                'answer' => $result['answer'],
                'context' => $context,
            ], 'Réponse générée');
        } catch (Throwable $e) {
            Log::warning('AI assistant unavailable', ['message' => $e->getMessage()]);
            return $this->error('Assistant IA temporairement indisponible.', 503);
        }
    }
}
