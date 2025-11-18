<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ElectionController extends Controller
{
    /**
     * Display the home page with active elections.
     */
    public function home()
    {
        $elections = Election::active()
            ->with('candidates')
            ->orderBy('start_date', 'desc')
            ->get();

        return view('home', [
            'elections' => $elections,
        ]);
    }

    /**
     * Display a specific election with its candidates.
     */
    public function show($id)
    {
        $election = Election::with('candidates')->findOrFail($id);

        return view('election', [
            'election' => $election,
        ]);
    }

    /**
     * Display the how to vote page.
     */
    public function howToVote()
    {
        return view('how-to-vote');
    }

    /**
     * Submit a vote for a candidate.
     */
    public function vote(Request $request, $electionId, $candidateId)
    {
        // Increase PHP execution time to 3 minutes for blockchain operations
        set_time_limit(180);

        try {
            // Find the election and candidate
            $election = Election::findOrFail($electionId);
            $candidate = Candidate::where('election_id', $electionId)
                ->where('id', $candidateId)
                ->firstOrFail();

            // Check if election is open
            if (!$election->isOpen()) {
                return response()->json([
                    'status' => 'error',
                    'message' => app()->getLocale() === 'sk'
                        ? 'Toto hlasovanie nie je akt�vne.'
                        : 'This election is not active.',
                ], 400);
            }

            // Check if election dates are valid
            if ($election->start_date > now()) {
                return response()->json([
                    'status' => 'error',
                    'message' => app()->getLocale() === 'sk'
                        ? 'Hlasovanie eate nezaalo.'
                        : 'Voting has not started yet.',
                ], 400);
            }

            if ($election->end_date < now()) {
                return response()->json([
                    'status' => 'error',
                    'message' => app()->getLocale() === 'sk'
                        ? 'Hlasovanie u~ skonilo.'
                        : 'Voting has ended.',
                ], 400);
            }

            // Call the Midnight blockchain API to submit the vote
            $timeout = (float) config('midnight.elections_api.timeout', 180.0);
            $client = new \GuzzleHttp\Client([
                'timeout' => $timeout,
                'connect_timeout' => 10.0,
            ]);
            $electionsApiUri = config('midnight.elections_api.base_uri', 'http://localhost:3000');

            $response = $client->post($electionsApiUri . '/vote', [
                'json' => [
                    'candidateId' => $candidate->blockchain_candidate_id,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return response()->json([
                'status' => 'success',
                'message' => __('validation.success.vote_cast'),
                'data' => $data,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('validation.errors.election_or_candidate_not_found'),
            ], 404);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Vote submission failed', [
                'error' => $e->getMessage(),
                'election_id' => $electionId,
                'candidate_id' => $candidateId,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => __('validation.errors.blockchain_connection_error'),
            ], 500);

        } catch (\Exception $e) {
            Log::error('Vote submission failed', [
                'error' => $e->getMessage(),
                'election_id' => $electionId,
                'candidate_id' => $candidateId,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => __('validation.errors.vote_processing_error'),
            ], 500);
        }
    }

    /**
     * Display election results from blockchain.
     */
    public function results($id)
    {
        $election = Election::with('candidates')->findOrFail($id);

        if (!$election->contract_address) {
            return back()->with('error', 'This election has not been deployed to blockchain yet.');
        }

        try {
            $electionsApiUri = config('midnight.elections_api.base_uri', 'http://localhost:3000');
            $response = Http::timeout(30)->get("{$electionsApiUri}/results/{$election->contract_address}");

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch results from blockchain');
            }

            $blockchainData = $response->json();

            // Map blockchain results to candidates
            $candidateResults = [];
            foreach ($blockchainData['results'] as $result) {
                $candidate = $election->candidates->firstWhere('blockchain_candidate_id', $result['candidateId']);
                if ($candidate) {
                    $candidateResults[] = [
                        'candidate' => $candidate,
                        'votes' => $result['votes'],
                    ];
                }
            }

            return view('results', [
                'election' => $election,
                'results' => $candidateResults,
                'totalVotes' => $blockchainData['totalVotes'],
                'contractAddress' => $blockchainData['contractAddress'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch election results', [
                'error' => $e->getMessage(),
                'election_id' => $id,
            ]);

            return back()->with('error', 'Failed to fetch election results from blockchain.');
        }
    }

    /**
     * Display vote verification page.
     */
    public function verifyPage($id)
    {
        $election = Election::findOrFail($id);

        if (!$election->contract_address) {
            return back()->with('error', 'This election has not been deployed to blockchain yet.');
        }

        return view('verify', [
            'election' => $election,
        ]);
    }

    /**
     * Verify if a vote was cast using credential hash.
     */
    public function verifyVote(Request $request, $id)
    {
        $election = Election::findOrFail($id);

        if (!$election->contract_address) {
            return response()->json([
                'status' => 'error',
                'message' => 'This election has not been deployed to blockchain yet.',
            ], 400);
        }

        $request->validate([
            'credential_hash' => 'required|string|min:10',
        ]);

        try {
            $electionsApiUri = config('midnight.elections_api.base_uri', 'http://localhost:3000');
            $response = Http::timeout(30)->post("{$electionsApiUri}/verify-vote", [
                'credential_hash' => $request->credential_hash,
                'contract_address' => $election->contract_address,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to verify vote on blockchain');
            }

            $data = $response->json();

            return response()->json([
                'status' => 'success',
                'voted' => $data['voted'],
                'message' => $data['message'],
            ]);

        } catch (\Exception $e) {
            Log::error('Vote verification failed', [
                'error' => $e->getMessage(),
                'election_id' => $id,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify vote on blockchain.',
            ], 500);
        }
    }

    /**
     * Switch the application locale.
     */
    public function switchLocale($locale)
    {
        if (!in_array($locale, ['en', 'sk'])) {
            abort(404);
        }

        session(['locale' => $locale]);
        app()->setLocale($locale);

        return redirect()->back();
    }
}
