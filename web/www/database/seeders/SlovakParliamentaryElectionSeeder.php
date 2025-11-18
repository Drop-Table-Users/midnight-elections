<?php

namespace Database\Seeders;

use App\Models\Election;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Database\Seeder;

class SlovakParliamentaryElectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user
        $adminUser = User::where('email', 'admin@sk-elections.com')->first();

        // Create the parliamentary election
        $election = Election::create([
            'title_en' => 'Slovak Parliamentary Elections 2025',
            'title_sk' => 'Parlamentné voľby SR 2025',
            'description_en' => 'Parliamentary elections to the National Council of the Slovak Republic',
            'description_sk' => 'Voľby do Národnej rady Slovenskej republiky',
            'start_date' => now()->addMonths(1),
            'end_date' => now()->addMonths(1)->addHours(14),
            'status' => 'draft',
            'created_by' => $adminUser?->id,
        ]);

        // Slovak political parties for parliamentary elections
        $parties = [
            [
                'name_en' => 'SMER - Social Democracy',
                'name_sk' => 'SMER - sociálna demokracia',
                'description_en' => 'Social democratic party led by Robert Fico',
                'description_sk' => 'Sociálnodemokratická strana pod vedením Roberta Fica',
                'display_order' => 1,
            ],
            [
                'name_en' => 'Progressive Slovakia',
                'name_sk' => 'Progresívne Slovensko',
                'description_en' => 'Liberal progressive party',
                'description_sk' => 'Liberálna progresívna strana',
                'display_order' => 2,
            ],
            [
                'name_en' => 'HLAS - Social Democracy',
                'name_sk' => 'HLAS - sociálna demokracia',
                'description_en' => 'Social democratic party led by Peter Pellegrini',
                'description_sk' => 'Sociálnodemokratická strana pod vedením Petra Pellegriniho',
                'display_order' => 3,
            ],
            [
                'name_en' => 'Christian Democratic Movement',
                'name_sk' => 'Kresťanskodemokratické hnutie',
                'description_en' => 'Christian democratic party',
                'description_sk' => 'Kresťanskodemokratická strana',
                'display_order' => 4,
            ],
            [
                'name_en' => 'Freedom and Solidarity',
                'name_sk' => 'Sloboda a Solidarita',
                'description_en' => 'Liberal conservative party',
                'description_sk' => 'Liberálno-konzervatívna strana',
                'display_order' => 5,
            ],
            [
                'name_en' => 'REPUBLIC',
                'name_sk' => 'REPUBLIKA',
                'description_en' => 'National conservative party',
                'description_sk' => 'Národno-konzervatívna strana',
                'display_order' => 6,
            ],
            [
                'name_en' => 'Slovak National Party',
                'name_sk' => 'Slovenská národná strana',
                'description_en' => 'Nationalist party',
                'description_sk' => 'Nacionalistická strana',
                'display_order' => 7,
            ],
            [
                'name_en' => 'We Are Family',
                'name_sk' => 'Sme rodina',
                'description_en' => 'Conservative populist party',
                'description_sk' => 'Konzervatívna populistická strana',
                'display_order' => 8,
            ],
            [
                'name_en' => 'Alliance',
                'name_sk' => 'Aliancia',
                'description_en' => 'Liberal party representing Hungarian minority',
                'description_sk' => 'Liberálna strana zastupujúca maďarskú menšinu',
                'display_order' => 9,
            ],
            [
                'name_en' => 'Democrats',
                'name_sk' => 'Demokrati',
                'description_en' => 'Centre-right democratic party',
                'description_sk' => 'Centristická demokratická strana',
                'display_order' => 10,
            ],
        ];

        foreach ($parties as $party) {
            Candidate::create([
                'election_id' => $election->id,
                'name_en' => $party['name_en'],
                'name_sk' => $party['name_sk'],
                'description_en' => $party['description_en'],
                'description_sk' => $party['description_sk'],
                'display_order' => $party['display_order'],
            ]);
        }

        $this->command->info('Slovak parliamentary election created with ' . count($parties) . ' parties!');
        $this->command->info('Election: ' . $election->title_sk);
    }
}
