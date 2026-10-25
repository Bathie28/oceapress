<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Commande;
use App\Models\Employe;
use App\Models\Photo;
use App\Models\Restitution;
use App\Services\ChangerStatutCommande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreeDonneesTest;
use Tests\TestCase;

class PhotosRestitutionTest extends TestCase
{
    use RefreshDatabase, CreeDonneesTest;

    private function commandePreteAvecArticle(?Employe $employe = null): array
    {
        $employe ??= $this->creerEmploye();
        $commande = $this->creerCommande(null, $employe->pressing);
        $statuts = app(ChangerStatutCommande::class);
        $statuts->executer($commande->idCommande, Commande::STATUT_EN_TRAITEMENT, $employe->idEmploye);
        $statuts->executer($commande->idCommande, Commande::STATUT_PRETE, $employe->idEmploye);
        $article = Article::create([
            'typeArticle' => 'Chemise',
            'quantite' => 1,
            'idCommande' => $commande->idCommande,
        ]);

        return [$employe, $commande, $article];
    }

    private function jpeg(string $name = 'nom-original-confidentiel.jpg'): UploadedFile
    {
        $bytes = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/AP/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCcf/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8B/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABAf/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPxB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPxB//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9k=', true);

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    public function test_restitution_sans_photo_fonctionne(): void
    {
        $employe = $this->creerEmploye();
        [$employe, $commande, $article] = $this->commandePreteAvecArticle($employe);

        $this->actingAs($employe->utilisateur)
            ->post('/employe/commandes/'.$commande->idCommande.'/restitution', [
                'quantitesPresentees' => [$article->idArticle => 1],
            ])
            ->assertRedirect('/employe/commandes/'.$commande->idCommande);

        $this->assertSame(1, Restitution::where('idCommande', $commande->idCommande)->count());
        $this->assertSame(0, Photo::count());
    }

    public function test_photo_jpeg_est_stockee_sous_un_nom_aleatoire_hors_public_et_servie_aux_autorises(): void
    {
        Storage::fake('local');
        [$employe, $commande, $article] = $this->commandePreteAvecArticle();
        $upload = $this->jpeg();

        $this->actingAs($employe->utilisateur)
            ->post('/employe/commandes/'.$commande->idCommande.'/restitution', [
                'quantitesPresentees' => [$article->idArticle => 1],
                'photos' => [$article->idArticle => $upload],
            ])
            ->assertRedirect('/employe/commandes/'.$commande->idCommande);

        $photo = Photo::firstOrFail();
        $this->assertSame($article->idArticle, $photo->idArticle);
        $this->assertSame('restitution', $photo->type);
        $this->assertStringStartsWith('photos/', $photo->urlPhoto);
        $this->assertNotSame('nom-original-confidentiel.jpg', basename($photo->urlPhoto));
        Storage::disk('local')->assertExists($photo->urlPhoto);
        $this->assertFileDoesNotExist(public_path('storage/'.$photo->urlPhoto));
        $this->assertStringNotContainsString('nom-original-confidentiel.jpg', $photo->urlPhoto);
        $this->assertStringNotContainsString('nom-original-confidentiel.jpg', route('photos.show', $photo->idPhoto));

        $this->get(route('photos.show', $photo->idPhoto))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $this->actingAs($commande->client->utilisateur)
            ->get(route('photos.show', $photo->idPhoto))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $this->actingAs($employe->utilisateur)
            ->get('/employe/commandes/'.$commande->idCommande)
            ->assertOk()
            ->assertSee(route('photos.show', $photo->idPhoto));
        $this->actingAs($commande->client->utilisateur)
            ->get('/client/commandes/'.$commande->idCommande)
            ->assertOk()
            ->assertSee(route('photos.show', $photo->idPhoto));
    }

    public function test_contenu_texte_avec_extension_jpg_est_refuse_sans_ecriture(): void
    {
        Storage::fake('local');
        [$employe, $commande, $article] = $this->commandePreteAvecArticle();

        $this->actingAs($employe->utilisateur)
            ->from('/employe/commandes/'.$commande->idCommande.'/restitution')
            ->post('/employe/commandes/'.$commande->idCommande.'/restitution', [
                'quantitesPresentees' => [$article->idArticle => 1],
                'photos' => [$article->idArticle => UploadedFile::fake()->createWithContent('faux.jpg', 'ceci est du texte')],
            ])
            ->assertSessionHasErrors('photos.'.$article->idArticle);

        $this->assertSame(0, Photo::count());
        $this->assertSame(0, Restitution::count());
        $this->assertSame(0, count(Storage::disk('local')->allFiles('photos')));
    }

    public function test_photo_png_est_acceptee(): void
    {
        Storage::fake('local');
        [, , $article] = $this->commandePreteAvecArticle();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jXioAAAAASUVORK5CYII=', true);

        $photo = app(\App\Services\EnregistrerPhoto::class)->executer(
            UploadedFile::fake()->createWithContent('nom-original.png', $png),
            $article,
        );

        $this->assertSame('restitution', $photo->type);
        $this->assertSame('png', pathinfo($photo->urlPhoto, PATHINFO_EXTENSION));
        Storage::disk('local')->assertExists($photo->urlPhoto);
    }

    public function test_photo_de_plus_de_cinq_mo_est_refusee(): void
    {
        Storage::fake('local');
        [$employe, $commande, $article] = $this->commandePreteAvecArticle();

        $this->actingAs($employe->utilisateur)
            ->from('/employe/commandes/'.$commande->idCommande.'/restitution')
            ->post('/employe/commandes/'.$commande->idCommande.'/restitution', [
                'quantitesPresentees' => [$article->idArticle => 1],
                'photos' => [$article->idArticle => UploadedFile::fake()->create('trop-grand.jpg', 5121, 'image/jpeg')],
            ])
            ->assertSessionHasErrors('photos.'.$article->idArticle);

        $this->assertSame(0, Photo::count());
        $this->assertSame(0, Restitution::count());
        $this->assertSame(0, count(Storage::disk('local')->allFiles('photos')));
    }

    public function test_un_employe_d_un_autre_pressing_ne_peut_pas_telecharger_la_photo(): void
    {
        Storage::fake('local');
        [$employe, $commande, $article] = $this->commandePreteAvecArticle();
        $photo = app(\App\Services\EnregistrerPhoto::class)->executer($this->jpeg(), $article);
        $autreEmploye = $this->creerEmploye();

        $this->actingAs($autreEmploye->utilisateur)
            ->get(route('photos.show', $photo->idPhoto))
            ->assertNotFound();
    }

    public function test_un_client_ne_peut_pas_voir_la_photo_d_un_autre_client(): void
    {
        Storage::fake('local');
        [$employe, $commande, $article] = $this->commandePreteAvecArticle();
        $photo = app(\App\Services\EnregistrerPhoto::class)->executer($this->jpeg(), $article);
        $autreClient = $this->creerClient();

        $this->actingAs($autreClient->utilisateur)
            ->get(route('photos.show', $photo->idPhoto))
            ->assertNotFound();
    }
}
