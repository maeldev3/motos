<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class LocalAiService
{
    public function answer(string $question, array $context): array
    {
        if (!config('enterprise.ai.enabled')) {
            throw new RuntimeException('Assistant IA désactivé dans la configuration.');
        }

        $base = rtrim(config('enterprise.ai.base_url'), '/');
        $model = config('enterprise.ai.model');

        $response = Http::timeout(config('enterprise.ai.timeout'))
            ->post($base.'/api/chat', [
                'model' => $model,
                'stream' => false,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "Tu es l'assistant d'une application de gestion de flotte. Réponds en français, clairement. Utilise uniquement les indicateurs agrégés fournis. Ne prétends jamais avoir exécuté une action. Les calculs financiers et décisions doivent être validés par l'application et un administrateur. Contexte JSON : ".json_encode($context, JSON_UNESCAPED_UNICODE),
                    ],
                    ['role' => 'user', 'content' => $question],
                ],
            ])->throw();

        return [
            'answer' => data_get($response->json(), 'message.content', 'Aucune réponse du modèle.'),
            'provider' => 'ollama',
            'model' => $model,
        ];
    }
}
