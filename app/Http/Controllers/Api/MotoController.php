<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reparation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReparationController extends Controller
{
    public function index(Request $request)
    {
        $page=$request->integer('page',1);
        $perPage=min($request->integer('per_page',20),100);

        $key="reparations:{$page}:{$perPage}:{$request->moto_id}:{$request->type_reparation}";

        $data=Cache::remember($key,300,function() use($request,$perPage){

            return Reparation::query()

                ->select([
                    'id',
                    'moto_id',
                    'date_reparation',
                    'type_reparation',
                    'garage',
                    'montant'
                ])

                ->with('moto')

                ->filter($request)

                ->latest('date_reparation')

                ->paginate($perPage);

        });

        return $this->ok($data);
    }

    public function store(Request $request)
    {

        $data=$request->validate([

            'moto_id'=>'required|exists:motos,id',

            'date_reparation'=>'required|date',

            'type_reparation'=>'required|in:vidange,changement_pneus,chaine,batterie,embrayage,moteur,carburateur,freins,suspension,peinture,accident,revision_complete,renfort_cadre,electricite,echappement,amortisseur,roulement,pneumatique,autres',

            'description'=>'nullable|string',

            'garage'=>'nullable|string|max:255',

            'mecanicien'=>'nullable|string|max:255',

            'kilometrage'=>'nullable|integer|min:0',

            'pieces_remplacees'=>'nullable|string',

            'montant'=>'required|numeric|min:0',

            'photo_facture'=>'nullable|image|max:4096',

            'observations'=>'nullable|string',

        ]);

        $reparation = DB::transaction(function () use ($request, $data) {

            if ($request->hasFile('photo_facture')) {
                $data['photo_facture'] = $request->file('photo_facture')
                    ->store('factures/reparations', 'public');
            }
        
            $reparation = Reparation::create($data);
        
            $reparation->moto()->update([
                'statut' => 'en_reparation'
            ]);
        
            return $reparation;
        });
        
        Cache::flush();
        
        return $this->created(
            $reparation->load('moto')
        );

    }

    public function show(Reparation $reparation)
    {
        return $this->ok(

            $reparation->load('moto')

        );
    }

    public function update(Request $request,Reparation $reparation)
    {

        $data=$request->validate([

            'description'=>'nullable|string',

            'garage'=>'nullable|string|max:255',

            'mecanicien'=>'nullable|string|max:255',

            'kilometrage'=>'nullable|integer|min:0',

            'pieces_remplacees'=>'nullable|string',

            'montant'=>'nullable|numeric|min:0',

            'observations'=>'nullable|string',

        ]);

        $reparation->update($data);

        Cache::flush();

        return $this->ok(

            $reparation->fresh(),

            'Réparation mise à jour'

        );
    }

    public function destroy(Reparation $reparation)
    {

        if($reparation->photo_facture){

            Storage::disk('public')
                ->delete($reparation->photo_facture);

        }

        $reparation->delete();

        Cache::flush();

        return $this->ok(

            null,

            'Réparation supprimée'

        );

    }

}