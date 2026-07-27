<?php

namespace Tests\Feature;

use App\Models\Moto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MotoTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateurAuthentifie(string $role = 'administrateur'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /** @test */
    public function un_utilisateur_non_authentifie_ne_peut_pas_lister_les_motos()
    {
        $response = $this->getJson('/api/motos');
        $response->assertStatus(401);
    }

    /** @test */
    public function un_utilisateur_authentifie_peut_creer_une_moto()
    {
        $user = $this->utilisateurAuthentifie();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/motos', [
            'immatriculation' => '1234 TBA',
            'marque' => 'Yamaha',
            'modele' => 'YBR 125',
            'type_vehicule' => 'moto',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.immatriculation', '1234 TBA')
            ->assertJsonPath('data.montant_versement_mensuel', '600000.00');

        $this->assertDatabaseHas('motos', ['immatriculation' => '1234 TBA']);
    }

    /** @test */
    public function on_ne_peut_pas_creer_une_moto_avec_une_immatriculation_dupliquee()
    {
        $user = $this->utilisateurAuthentifie();
        Moto::factory()->create(['immatriculation' => '5678 TBB']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/motos', [
            'immatriculation' => '5678 TBB',
            'marque' => 'Honda',
            'modele' => 'CG 125',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('immatriculation');
    }

    /** @test */
    public function on_peut_recuperer_le_bilan_financier_dune_moto()
    {
        $user = $this->utilisateurAuthentifie();
        $moto = Moto::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/motos/{$moto->id}/finances");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['revenus', 'depenses', 'benefice']]);
    }
}
