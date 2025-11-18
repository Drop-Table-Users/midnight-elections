<?php

return [
    // Standard Laravel validation messages
    'required' => 'Pole :attribute je povinné.',
    'required_if' => 'Pole :attribute je povinné, keď :other je :value.',
    'string' => ':attribute musí byť reťazec.',
    'array' => ':attribute musí byť pole.',
    'in' => 'Vybrané :attribute je neplatné.',
    'max' => [
        'string' => ':attribute nesmie byť dlhšie ako :max znakov.',
    ],
    'min' => [
        'string' => ':attribute musí mať aspoň :min znakov.',
    ],

    // Custom validation messages
    'custom' => [
        'action' => [
            'required' => 'Akcia je povinná.',
            'in' => 'Akcia musí byť jedna z: open, close, register, vote.',
        ],
        'candidate_id' => [
            'required' => 'ID kandidáta je povinné pre registráciu.',
            'string' => 'ID kandidáta musí byť platný reťazec.',
        ],
        'ballot_data' => [
            'array' => 'Údaje hlasovania musia byť pole.',
        ],
    ],

    // Attribute names
    'attributes' => [
        'action' => 'akcia',
        'candidate_id' => 'ID kandidáta',
        'ballot_data' => 'údaje hlasovania',
    ],

    // Error messages
    'errors' => [
        'wallet_not_connected' => 'Peňaženka nie je pripojená.',
        'transaction_failed' => 'Spracovanie transakcie zlyhalo.',
        'invalid_candidate_id' => 'Neplatný formát ID kandidáta.',
        'election_not_open' => 'Voľby momentálne nie sú otvorené.',
        'already_registered' => 'Kandidát je už zaregistrovaný.',
        'already_voted' => 'V týchto voľbách ste už hlasovali.',
        'api_unavailable' => 'Elections API je momentálne nedostupné.',
        'voting_not_started' => 'Hlasovanie ešte nezačalo.',
        'voting_ended' => 'Hlasovanie už skončilo.',
        'election_or_candidate_not_found' => 'Voľby alebo kandidát nebol nájdený.',
        'blockchain_connection_error' => 'Chyba pri pripojení k blockchainovej sieti. Skúste to prosím neskôr.',
        'vote_processing_error' => 'Pri spracovaní vášho hlasu došlo k chybe. Skúste to prosím znova.',
    ],

    // Success messages
    'success' => [
        'election_opened' => 'Voľby boli úspešne otvorené.',
        'election_closed' => 'Voľby boli úspešne zatvorené.',
        'candidate_registered' => 'Kandidát bol úspešne zaregistrovaný.',
        'vote_cast' => 'Váš hlas bol úspešne odovzdaný.',
        'wallet_connected' => 'Peňaženka úspešne pripojená.',
        'wallet_disconnected' => 'Peňaženka úspešne odpojená.',
    ],
];
