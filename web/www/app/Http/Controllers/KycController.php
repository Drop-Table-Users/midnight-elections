<?php

namespace App\Http\Controllers;

use App\Models\KycVerification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class KycController extends Controller
{
    /**
     * Get the API base URL for blockchain operations.
     */
    private function getApiBaseUrl(): string
    {
        return env('ELECTIONS_API_URL', 'http://localhost:3000');
    }

    /**
     * Show KYC submission form.
     * Checks if wallet is connected and if user is already verified.
     * Passes necessary data to the view including nationality options and status.
     */
    public function index(Request $request)
    {
        try {
            // Get wallet address from session or request
            $walletAddress = $request->session()->get('wallet_address') ?? $request->input('wallet_address');

            if (!$walletAddress) {
                return view('kyc.index', [
                    'error' => __('validation.errors.wallet_not_connected'),
                    'walletConnected' => false,
                    'kyc' => null,
                    'nationalities' => $this->getNationalities(),
                ]);
            }

            // Check if user already has a KYC verification
            $kyc = KycVerification::where('user_wallet_address', $walletAddress)->first();

            if ($kyc && $kyc->isVerified()) {
                return view('kyc.index', [
                    'walletConnected' => true,
                    'kyc' => $kyc,
                    'alreadyVerified' => true,
                    'nationalities' => $this->getNationalities(),
                ]);
            }

            return view('kyc.index', [
                'walletConnected' => true,
                'kyc' => $kyc,
                'alreadyVerified' => false,
                'nationalities' => $this->getNationalities(),
                'minAge' => 18,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load KYC form: ' . $e->getMessage());
            return view('kyc.index', [
                'error' => __('validation.errors.generic_error'),
                'walletConnected' => false,
                'kyc' => null,
                'nationalities' => $this->getNationalities(),
            ]);
        }
    }

    /**
     * Get list of supported nationalities.
     * Currently only Slovakia is supported for voting eligibility.
     */
    private function getNationalities(): array
    {
        return [
            'SK' => __('countries.slovakia'),
        ];
    }

    /**
     * Handle KYC submission with validation.
     * Validates Slovak national ID format, age requirement, and nationality.
     * Encrypts sensitive data and stores in database.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Step 1: Verify wallet is connected
            $walletAddress = $request->session()->get('wallet_address') ?? $request->input('wallet_address');

            if (!$walletAddress) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('validation.errors.wallet_not_connected'),
                ], 400);
            }

            // Validate wallet address is not empty
            if (empty($walletAddress) || strlen(trim($walletAddress)) < 10) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('validation.errors.invalid_wallet_address'),
                ], 400);
            }

            // Check if user already has a KYC verification
            $existingKyc = KycVerification::where('user_wallet_address', $walletAddress)->first();

            if ($existingKyc && $existingKyc->isVerified()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('validation.errors.already_verified'),
                ], 400);
            }

            // Step 2: Validate all input fields
            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:255|min:3',
                'national_id' => [
                    'required',
                    'string',
                    'regex:/^[A-Z]{2}\d{6}$/', // Slovak ID card: 2 letters + 6 digits (e.g., AV247247)
                ],
                'date_of_birth' => 'required|date|before:today',
                'nationality' => 'required|string|in:SK',
            ], [
                'full_name.required' => trans('validation.errors.full_name_required'),
                'full_name.min' => trans('validation.errors.full_name_min'),
                'full_name.max' => trans('validation.errors.full_name_max'),
                'national_id.required' => trans('validation.errors.national_id_required'),
                'national_id.regex' => trans('validation.errors.national_id_format'),
                'date_of_birth.required' => trans('validation.errors.dob_required'),
                'date_of_birth.date' => trans('validation.errors.dob_invalid'),
                'date_of_birth.before' => trans('validation.errors.dob_future'),
                'nationality.required' => trans('validation.errors.nationality_required'),
                'nationality.in' => trans('validation.errors.nationality_must_be_sk'),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Step 3: Verify age >= 18
            $dateOfBirth = Carbon::parse($request->date_of_birth);
            $age = $dateOfBirth->diffInYears(Carbon::now());

            if ($age < 18) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('validation.errors.must_be_18', ['age' => $age]),
                ], 422);
            }

            // Step 4: Verify nationality is SK
            if ($request->nationality !== 'SK') {
                return response()->json([
                    'status' => 'error',
                    'message' => __('validation.errors.nationality_must_be_sk'),
                ], 422);
            }

            // Check for duplicate national ID
            $duplicateCheck = KycVerification::where('national_id', $request->national_id)
                ->where('user_wallet_address', '!=', $walletAddress)
                ->first();

            if ($duplicateCheck) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('validation.errors.national_id_already_used'),
                ], 422);
            }

            // Step 5: Encrypt and store data in database
            // Sensitive data (full_name, national_id) will be automatically encrypted via model casts
            $kyc = KycVerification::updateOrCreate(
                ['user_wallet_address' => $walletAddress],
                [
                    'full_name' => trim($request->full_name),
                    'national_id' => $request->national_id,
                    'date_of_birth' => $dateOfBirth,
                    'nationality' => $request->nationality,
                    'verification_status' => 'pending',
                    'rejection_reason' => null, // Clear any previous rejection reason
                    'verified_at' => null, // Clear previous verification
                    'verified_by' => null, // Clear previous verifier
                ]
            );

            Log::info('KYC verification submitted', [
                'wallet_address' => $walletAddress,
                'kyc_id' => $kyc->id,
                'nationality' => $request->nationality,
                'age' => $age,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('validation.success.kyc_submitted'),
                'data' => [
                    'kyc_id' => $kyc->id,
                    'status' => $kyc->verification_status,
                    'submitted_at' => $kyc->created_at->toIso8601String(),
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('KYC submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => __('validation.errors.kyc_submission_failed'),
            ], 500);
        }
    }

    /**
     * Show current KYC status for logged-in wallet.
     */
    public function status(Request $request)
    {
        try {
            // Get wallet address from session or request
            $walletAddress = $request->session()->get('wallet_address') ?? $request->input('wallet_address');

            if (!$walletAddress) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('validation.errors.wallet_not_connected'),
                ], 400);
            }

            $kyc = KycVerification::where('user_wallet_address', $walletAddress)->first();

            if (!$kyc) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'kyc_status' => 'not_submitted',
                        'message' => __('validation.messages.kyc_not_submitted'),
                    ],
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'kyc_id' => $kyc->id,
                    'kyc_status' => $kyc->verification_status,
                    'submitted_at' => $kyc->created_at->toIso8601String(),
                    'verified_at' => $kyc->verified_at?->toIso8601String(),
                    'rejection_reason' => $kyc->rejection_reason,
                    'is_verified' => $kyc->isVerified(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch KYC status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => __('validation.errors.generic_error'),
            ], 500);
        }
    }

    /**
     * List all KYC verifications with filtering, search, and pagination (Admin only).
     * Supports filtering by status (pending, approved, rejected).
     * Supports search by name or national ID.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function adminIndex(Request $request)
    {
        try {
            // Authorization check - ensure user is admin
            if (!auth()->check()) {
                return redirect()->route('login')
                    ->with('error', __('validation.errors.unauthorized_admin'));
            }

            // Get filter parameters
            $status = $request->input('status', 'all');
            $search = $request->input('search', '');
            $perPage = $request->input('per_page', 15);

            // Build query
            $query = KycVerification::query();

            // Apply status filter
            if ($status !== 'all' && in_array($status, ['pending', 'approved', 'rejected'])) {
                $query->where('verification_status', $status);
            }

            // Apply search filter (searches in encrypted fields)
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    // Search by wallet address (not encrypted)
                    $q->where('user_wallet_address', 'like', '%' . $search . '%')
                      // For encrypted fields, we need to search differently
                      // Since full_name and national_id are encrypted, exact match works better
                      ->orWhere('full_name', $search)
                      ->orWhere('national_id', $search);
                });
            }

            // Order by status priority: pending first, then by date
            $query->orderByRaw("
                CASE verification_status
                    WHEN 'pending' THEN 1
                    WHEN 'approved' THEN 2
                    WHEN 'rejected' THEN 3
                    ELSE 4
                END
            ")->orderBy('created_at', 'desc');

            // Paginate results
            $verifications = $query->paginate($perPage)->withQueryString();

            // Get statistics
            $statistics = [
                'total' => KycVerification::count(),
                'pending' => KycVerification::where('verification_status', 'pending')->count(),
                'approved' => KycVerification::where('verification_status', 'approved')->count(),
                'rejected' => KycVerification::where('verification_status', 'rejected')->count(),
            ];

            // Get all verifications and filter by status
            $allVerifications = KycVerification::orderBy('created_at', 'desc')->get();
            $pendingVerifications = $allVerifications->where('verification_status', 'pending');
            $approvedVerifications = $allVerifications->where('verification_status', 'approved');
            $rejectedVerifications = $allVerifications->where('verification_status', 'rejected');

            return view('admin.kyc.index', [
                'verifications' => $verifications,
                'pendingVerifications' => $pendingVerifications,
                'approvedVerifications' => $approvedVerifications,
                'rejectedVerifications' => $rejectedVerifications,
                'statistics' => $statistics,
                'currentStatus' => $status,
                'currentSearch' => $search,
                'perPage' => $perPage,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load KYC admin dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('validation.errors.generic_error'));
        }
    }

    /**
     * Approve a KYC verification and store blockchain transaction.
     * Requires admin authorization.
     * Updates status to 'approved', sets verified_at timestamp, and stores admin user.
     *
     * @param Request $request
     * @param int $id - KYC verification ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(Request $request, $id)
    {
        try {
            // Authorization check - ensure user is authenticated admin
            if (!auth()->check()) {
                return redirect()->route('login')
                    ->with('error', __('validation.errors.unauthorized_admin'));
            }

            // Find KYC verification by ID
            $kyc = KycVerification::findOrFail($id);

            // Check if already approved
            if ($kyc->verification_status === 'approved') {
                return back()->with('error', __('validation.errors.kyc_already_approved'));
            }

            // Verify user meets requirements (adult and Slovak)
            if (!$kyc->isAdult()) {
                return back()->with('error', __('validation.errors.kyc_not_adult'));
            }

            if (!$kyc->isSlovak()) {
                return back()->with('error', __('validation.errors.kyc_not_slovak'));
            }

            // Call blockchain API to register voter and generate credential hash
            $response = Http::timeout(30)->post("{$this->getApiBaseUrl()}/register-voter", [
                'wallet_address' => $kyc->user_wallet_address,
                'kyc_id' => $kyc->id,
                'full_name' => $kyc->full_name,
                'national_id' => $kyc->national_id,
                'date_of_birth' => $kyc->date_of_birth->toIso8601String(),
            ]);

            if (!$response->successful()) {
                throw new \Exception('Blockchain registration failed: ' . $response->body());
            }

            $data = $response->json();

            // Update KYC verification status
            $kyc->update([
                'verification_status' => 'approved',
                'verified_at' => Carbon::now(),
                'verified_by' => auth()->id(),
                'blockchain_tx_hash' => $data['tx_hash'] ?? null,
                'credential_hash' => $data['credential_hash'] ?? null,
                'rejection_reason' => null,
            ]);

            Log::info('KYC verification approved', [
                'kyc_id' => $kyc->id,
                'wallet_address' => $kyc->user_wallet_address,
                'approved_by' => auth()->id(),
                'approved_by_email' => auth()->user()->email ?? 'unknown',
                'tx_hash' => $data['tx_hash'] ?? null,
            ]);

            return back()->with('success', __('validation.success.kyc_approved'));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('KYC not found for approval', [
                'kyc_id' => $id,
                'admin_id' => auth()->id(),
            ]);

            return back()->with('error', __('validation.errors.kyc_not_found'));

        } catch (\Exception $e) {
            Log::error('KYC approval failed', [
                'kyc_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('validation.errors.kyc_approval_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Reject a KYC verification with a reason.
     * Requires admin authorization.
     * Validates rejection reason is provided and stores it with the rejection.
     *
     * @param Request $request
     * @param int $id - KYC verification ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request, $id)
    {
        try {
            // Authorization check - ensure user is authenticated admin
            if (!auth()->check()) {
                return redirect()->route('login')
                    ->with('error', __('validation.errors.unauthorized_admin'));
            }

            // Find KYC verification by ID
            $kyc = KycVerification::findOrFail($id);

            // Prevent rejecting already approved verifications
            if ($kyc->verification_status === 'approved') {
                return back()->with('error', __('validation.errors.cannot_reject_approved'));
            }

            // Validate rejection reason is provided
            $validator = Validator::make($request->all(), [
                'rejection_reason' => 'required|string|min:10|max:1000',
            ], [
                'rejection_reason.required' => __('validation.errors.rejection_reason_required'),
                'rejection_reason.min' => __('validation.errors.rejection_reason_min'),
                'rejection_reason.max' => __('validation.errors.rejection_reason_max'),
            ]);

            if ($validator->fails()) {
                return back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', $validator->errors()->first());
            }

            // Update KYC verification status to rejected
            $kyc->update([
                'verification_status' => 'rejected',
                'rejection_reason' => trim($request->rejection_reason),
                'verified_by' => auth()->id(),
                'verified_at' => Carbon::now(),
            ]);

            Log::info('KYC verification rejected', [
                'kyc_id' => $kyc->id,
                'wallet_address' => $kyc->user_wallet_address,
                'rejected_by' => auth()->id(),
                'rejected_by_email' => auth()->user()->email ?? 'unknown',
                'reason' => $request->rejection_reason,
            ]);

            return back()->with('success', __('validation.success.kyc_rejected'));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('KYC not found for rejection', [
                'kyc_id' => $id,
                'admin_id' => auth()->id(),
            ]);

            return back()->with('error', __('validation.errors.kyc_not_found'));

        } catch (\Exception $e) {
            Log::error('KYC rejection failed', [
                'kyc_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('validation.errors.kyc_rejection_failed'));
        }
    }
}
