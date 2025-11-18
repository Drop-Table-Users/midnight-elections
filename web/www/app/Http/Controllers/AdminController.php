<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    private function getApiBaseUrl()
    {
        return env('ELECTIONS_API_URL', 'http://localhost:3000');
    }

    /**
     * Display dashboard with all elections
     */
    public function index()
    {
        try {
            $elections = Election::with('candidates')->orderBy('created_at', 'desc')->get();
            return view('admin.dashboard', compact('elections'));
        } catch (\Exception $e) {
            Log::error('Failed to load elections: ' . $e->getMessage());
            return back()->with('error', 'Failed to load elections. Please try again.');
        }
    }

    /**
     * Show form to create new election
     */
    public function createElection()
    {
        return view('admin.elections.create');
    }

    /**
     * Store new election
     */
    public function storeElection(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title_en' => 'required|string|max:255',
                'title_sk' => 'required|string|max:255',
                'description_en' => 'nullable|string',
                'description_sk' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $election = Election::create([
                'title_en' => $request->title_en,
                'title_sk' => $request->title_sk,
                'description_en' => $request->description_en,
                'description_sk' => $request->description_sk,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('admin.elections.show', $election->id)
                ->with('success', 'Election created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create election: ' . $e->getMessage());
            return back()->with('error', 'Failed to create election. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show election details with candidates
     */
    public function showElection($id)
    {
        try {
            $election = Election::with('candidates')->findOrFail($id);
            return view('admin.elections.show', compact('election'));
        } catch (\Exception $e) {
            Log::error('Failed to load election: ' . $e->getMessage());
            return redirect()->route('admin.dashboard')
                ->with('error', 'Election not found.');
        }
    }

    /**
     * Show form to edit election
     */
    public function editElection($id)
    {
        try {
            $election = Election::findOrFail($id);
            return view('admin.elections.edit', compact('election'));
        } catch (\Exception $e) {
            Log::error('Failed to load election for editing: ' . $e->getMessage());
            return redirect()->route('admin.dashboard')
                ->with('error', 'Election not found.');
        }
    }

    /**
     * Update election
     */
    public function updateElection(Request $request, $id)
    {
        try {
            $election = Election::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title_en' => 'required|string|max:255',
                'title_sk' => 'required|string|max:255',
                'description_en' => 'nullable|string',
                'description_sk' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $election->update([
                'title_en' => $request->title_en,
                'title_sk' => $request->title_sk,
                'description_en' => $request->description_en,
                'description_sk' => $request->description_sk,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            return redirect()->route('admin.elections.show', $election->id)
                ->with('success', 'Election updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update election: ' . $e->getMessage());
            return back()->with('error', 'Failed to update election. Please try again.')
                ->withInput();
        }
    }

    /**
     * Delete election
     */
    public function destroyElection($id)
    {
        try {
            $election = Election::findOrFail($id);

            if ($election->isOpen()) {
                return back()->with('error', 'Cannot delete an open election.');
            }

            $election->delete();

            return redirect()->route('admin.dashboard')
                ->with('success', 'Election deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete election: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete election. Please try again.');
        }
    }

    /**
     * Deploy election to blockchain
     */
    public function deployElection($id)
    {
        try {
            $election = Election::findOrFail($id);

            if ($election->contract_address) {
                return back()->with('error', 'Election is already deployed to blockchain.');
            }

            $response = Http::timeout(30)->post("{$this->getApiBaseUrl()}/deploy", [
                'election_id' => $election->id,
                'title' => $election->title_en,
                'start_date' => $election->start_date->toIso8601String(),
                'end_date' => $election->end_date->toIso8601String(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $election->update([
                    'contract_address' => $data['contract_address'] ?? null,
                    'blockchain_election_id' => $data['election_id'] ?? null,
                ]);

                return back()->with('success', 'Election deployed to blockchain successfully.');
            } else {
                throw new \Exception('API response: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Failed to deploy election: ' . $e->getMessage());
            return back()->with('error', 'Failed to deploy election to blockchain. Error: ' . $e->getMessage());
        }
    }

    /**
     * Open election on blockchain
     */
    public function openElection($id)
    {
        try {
            $election = Election::findOrFail($id);

            if (!$election->contract_address) {
                return back()->with('error', 'Election must be deployed before opening.');
            }

            if ($election->isOpen()) {
                return back()->with('error', 'Election is already open.');
            }

            $response = Http::timeout(30)->post("{$this->getApiBaseUrl()}/open", [
                'election_id' => $election->blockchain_election_id,
                'contract_address' => $election->contract_address,
            ]);

            if ($response->successful()) {
                $election->update(['status' => 'open']);
                return back()->with('success', 'Election opened successfully.');
            } else {
                throw new \Exception('API response: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Failed to open election: ' . $e->getMessage());
            return back()->with('error', 'Failed to open election. Error: ' . $e->getMessage());
        }
    }

    /**
     * Close election on blockchain
     */
    public function closeElection($id)
    {
        try {
            $election = Election::findOrFail($id);

            if (!$election->isOpen()) {
                return back()->with('error', 'Only open elections can be closed.');
            }

            $response = Http::timeout(30)->post("{$this->getApiBaseUrl()}/close", [
                'election_id' => $election->blockchain_election_id,
                'contract_address' => $election->contract_address,
            ]);

            if ($response->successful()) {
                $election->update(['status' => 'closed']);
                return back()->with('success', 'Election closed successfully.');
            } else {
                throw new \Exception('API response: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Failed to close election: ' . $e->getMessage());
            return back()->with('error', 'Failed to close election. Error: ' . $e->getMessage());
        }
    }

    /**
     * Show form to add candidate to election
     */
    public function createCandidate($electionId)
    {
        try {
            $election = Election::findOrFail($electionId);
            return view('admin.candidates.create', compact('election'));
        } catch (\Exception $e) {
            Log::error('Failed to load election for candidate creation: ' . $e->getMessage());
            return redirect()->route('admin.dashboard')
                ->with('error', 'Election not found.');
        }
    }

    /**
     * Store new candidate
     */
    public function storeCandidate(Request $request, $electionId)
    {
        try {
            $election = Election::findOrFail($electionId);

            $validator = Validator::make($request->all(), [
                'name_en' => 'required|string|max:255',
                'name_sk' => 'required|string|max:255',
                'description_en' => 'nullable|string',
                'description_sk' => 'nullable|string',
                'display_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $maxOrder = $election->candidates()->max('display_order') ?? 0;

            $candidate = Candidate::create([
                'election_id' => $election->id,
                'name_en' => $request->name_en,
                'name_sk' => $request->name_sk,
                'description_en' => $request->description_en,
                'description_sk' => $request->description_sk,
                'display_order' => $request->display_order ?? ($maxOrder + 1),
            ]);

            return redirect()->route('admin.elections.show', $election->id)
                ->with('success', 'Candidate added successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create candidate: ' . $e->getMessage());
            return back()->with('error', 'Failed to add candidate. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show form to edit candidate
     */
    public function editCandidate($id)
    {
        try {
            $candidate = Candidate::with('election')->findOrFail($id);
            return view('admin.candidates.edit', compact('candidate'));
        } catch (\Exception $e) {
            Log::error('Failed to load candidate for editing: ' . $e->getMessage());
            return back()->with('error', 'Candidate not found.');
        }
    }

    /**
     * Update candidate
     */
    public function updateCandidate(Request $request, $id)
    {
        try {
            $candidate = Candidate::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name_en' => 'required|string|max:255',
                'name_sk' => 'required|string|max:255',
                'description_en' => 'nullable|string',
                'description_sk' => 'nullable|string',
                'display_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $candidate->update([
                'name_en' => $request->name_en,
                'name_sk' => $request->name_sk,
                'description_en' => $request->description_en,
                'description_sk' => $request->description_sk,
                'display_order' => $request->display_order ?? $candidate->display_order,
            ]);

            return redirect()->route('admin.elections.show', $candidate->election_id)
                ->with('success', 'Candidate updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update candidate: ' . $e->getMessage());
            return back()->with('error', 'Failed to update candidate. Please try again.')
                ->withInput();
        }
    }

    /**
     * Delete candidate
     */
    public function destroyCandidate($id)
    {
        try {
            $candidate = Candidate::findOrFail($id);
            $electionId = $candidate->election_id;

            $candidate->delete();

            return redirect()->route('admin.elections.show', $electionId)
                ->with('success', 'Candidate deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete candidate: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete candidate. Please try again.');
        }
    }

    /**
     * Register candidate on blockchain
     */
    public function registerCandidate($id)
    {
        try {
            $candidate = Candidate::with('election')->findOrFail($id);
            $election = $candidate->election;

            if (!$election->contract_address) {
                return back()->with('error', 'Election must be deployed before registering candidates.');
            }

            if ($candidate->blockchain_candidate_id) {
                return back()->with('error', 'Candidate is already registered on blockchain.');
            }

            $response = Http::timeout(30)->post("{$this->getApiBaseUrl()}/register", [
                'election_id' => $election->blockchain_election_id,
                'contract_address' => $election->contract_address,
                'candidate_id' => $candidate->id,
                'candidate_name' => $candidate->name_en,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $candidate->update([
                    'blockchain_candidate_id' => $data['candidate_id'] ?? null,
                ]);

                return back()->with('success', 'Candidate registered on blockchain successfully.');
            } else {
                throw new \Exception('API response: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Failed to register candidate: ' . $e->getMessage());
            return back()->with('error', 'Failed to register candidate on blockchain. Error: ' . $e->getMessage());
        }
    }

    /**
     * Switch the admin panel locale.
     */
    public function switchLocale($locale)
    {
        if (!in_array($locale, ['en', 'sk'])) {
            abort(404);
        }

        session(['admin_locale' => $locale]);
        app()->setLocale($locale);

        return redirect()->back();
    }
}
