<?php

namespace App\Console\Commands;

use App\Models\Pressing;
use App\Models\Tarif;
use App\Models\Utilisateur;
use App\Services\EnregistrerCommande;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreerComptesDemo extends Command
{
    protected $signature = 'demo:creer-comptes';

    protected $description = 'Crée des comptes et commandes de démonstration sur la base de test';

    public function handle(): int
    {
        if (config('database.connections.mysql.database') !== 'oceapress_test') {
            $this->output->writeln('Refusé : cette commande ne s’exécute que sur oceapress_test');

            return self::FAILURE;
        }

        if (Utilisateur::where('email', 'employe@demo.test')->exists()) {
            $this->info('Comptes déjà présents');

            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            $pressing = Pressing::create([
                'nom' => 'Pressing Démo',
                'adresse' => 'Dakar',
                'dateCreation' => now()->toDateString(),
                'statut' => 'ACTIF',
                'modeTarification' => 'PIECE',
            ]);
            Tarif::create(['idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise', 'formule' => 'LAVAGE', 'unite' => 'PIECE', 'prix' => 15.00]);
            Tarif::create(['idPressing' => $pressing->idPressing, 'typeArticle' => 'Pantalon', 'formule' => 'LAVAGE', 'unite' => 'PIECE', 'prix' => 25.00]);
            Tarif::create(['idPressing' => $pressing->idPressing, 'typeArticle' => 'Chemise', 'formule' => 'LAVAGE_REPASSAGE', 'unite' => 'PIECE', 'prix' => 20.00]);
            Tarif::create(['idPressing' => $pressing->idPressing, 'typeArticle' => 'Pantalon', 'formule' => 'LAVAGE_REPASSAGE', 'unite' => 'PIECE', 'prix' => 30.00]);
            Tarif::create(['idPressing' => $pressing->idPressing, 'typeArticle' => null, 'formule' => 'LAVAGE', 'unite' => 'KG', 'prix' => 8.00]);
            Tarif::create(['idPressing' => $pressing->idPressing, 'typeArticle' => null, 'formule' => 'LAVAGE_REPASSAGE', 'unite' => 'KG', 'prix' => 12.00]);

            $motDePasse = Hash::make('Demo2026!');
            $employe = Utilisateur::create([
                'nom' => 'Démo',
                'prenom' => 'Employé',
                'email' => 'employe@demo.test',
                'motDePasse' => $motDePasse,
            ]);
            DB::table('employe')->insert([
                'idEmploye' => $employe->idUtilisateur,
                'fonction' => 'Agent',
                'dateEmbauche' => now()->toDateString(),
                'idPressing' => $pressing->idPressing,
            ]);

            $client = Utilisateur::create([
                'nom' => 'Démo',
                'prenom' => 'Client',
                'email' => 'client@demo.test',
                'motDePasse' => $motDePasse,
            ]);
            DB::table('client')->insert([
                'idClient' => $client->idUtilisateur,
                'dateInscription' => now()->toDateString(),
            ]);

            $administrateur = Utilisateur::create([
                'nom' => 'Démo',
                'prenom' => 'Administrateur',
                'email' => 'admin@demo.test',
                'motDePasse' => $motDePasse,
            ]);
            DB::table('administrateur')->insert([
                'idAdministrateur' => $administrateur->idUtilisateur,
            ]);

            $donneesClient = [
                'nom' => 'Démo',
                'prenom' => 'Client',
                'email' => 'client@demo.test',
                'telephone' => '770000000',
            ];

            foreach ([1, 2] as $numeroCommande) {
                $commande = app(EnregistrerCommande::class)->executer(
                    $donneesClient,
                    [
                        ['typeArticle' => 'Chemise', 'couleur' => 'Blanc', 'description' => 'Coton'],
                        ['typeArticle' => 'Pantalon', 'couleur' => 'Bleu', 'description' => 'Jean'],
                    ],
                    $employe->idUtilisateur,
                    $pressing->idPressing,
                );

            }
        });

        $this->info('Comptes de démonstration créés.');

        return self::SUCCESS;
    }
}
