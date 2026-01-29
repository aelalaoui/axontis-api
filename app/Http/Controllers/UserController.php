<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        try {
            $query = User::query()->staff(); // Only staff users (not clients)

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Filter by role - validate the role first
            if ($request->has('role') && $request->role !== '') {
                try {
                    UserRole::from($request->role); // Validate role exists
                    $query->where('role', $request->role);
                } catch (\ValueError $e) {
                    // Invalid role filter, ignore it
                }
            }

            // Filter by status
            if ($request->has('status') && $request->status !== '') {
                $query->where('is_active', $request->status === 'active');
            }

            // Sort functionality
            $sortField = $request->input('sort', 'name');
            $sortDirection = $request->input('direction', 'asc');
            $query->orderBy($sortField, $sortDirection);

            $users = $query->paginate(15)->withQueryString();

            // Get available roles for filter (excluding client)
            $roles = collect(UserRole::cases())
                ->filter(fn($role) => $role !== UserRole::CLIENT)
                ->map(fn($role) => [
                    'value' => $role->value,
                    'label' => $role->label(),
                ])
                ->values();

            return Inertia::render('CRM/Users/Index', [
                'users' => $users,
                'roles' => $roles,
                'filters' => [
                    'search' => $request->search,
                    'role' => $request->role,
                    'status' => $request->status,
                    'sort' => $sortField,
                    'direction' => $sortDirection,
                ],
            ]);
        } catch (\ValueError $e) {
            // Log the error for debugging
            \Log::error('Invalid user role detected in database', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Redirect with error message
            return redirect()->route('dashboard')
                ->with('error', 'Une erreur est survenue lors du chargement des utilisateurs. Veuillez contacter l\'administrateur.');
        }
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        // Get available roles (excluding client)
        $roles = collect(UserRole::cases())
            ->filter(fn($role) => $role !== UserRole::CLIENT)
            ->map(fn($role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ])
            ->values();

        return Inertia::render('CRM/Users/Create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
        ]);

        // Prevent creating clients through this interface
        if ($validated['role'] === UserRole::CLIENT->value) {
            return back()->withErrors(['role' => 'Les clients ne peuvent pas être créés depuis cette interface.']);
        }

        // Generate temporary password and invitation token
        $temporaryPassword = Str::random(32);
        $invitationToken = Str::random(64);

        // Create user
        $user = User::create([
            'uuid' => Str::uuid()->toString(),
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($temporaryPassword),
            'role' => $validated['role'],
            'is_active' => true,
            'invitation_token' => Hash::make($invitationToken),
            'invitation_sent_at' => now(),
        ]);

        // Send invitation email
        $user->notify(new UserInvitation($invitationToken));

        return redirect()->route('crm.users.index')
            ->with('success', 'Utilisateur créé avec succès. Un email d\'invitation a été envoyé.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return Inertia::render('CRM/Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        // Get available roles (excluding client)
        $roles = collect(UserRole::cases())
            ->filter(fn($role) => $role !== UserRole::CLIENT)
            ->map(fn($role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ])
            ->values();

        return Inertia::render('CRM/Users/Edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
        ]);

        // Prevent changing to client role
        if ($validated['role'] === UserRole::CLIENT->value) {
            return back()->withErrors(['role' => 'Le rôle client ne peut pas être attribué depuis cette interface.']);
        }

        $user->update([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        return redirect()->route('crm.users.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Toggle the active status of the user.
     */
    public function toggleStatus(User $user)
    {
        // Prevent self-deactivation
        if ($user->id === auth()->id()) {
            return redirect()->route('crm.users.index')
                ->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'activé' : 'désactivé';

        return redirect()->route('crm.users.index')
            ->with('success', "Utilisateur {$status} avec succès.");
    }

    /**
     * Resend the invitation email.
     */
    public function resendInvitation(User $user)
    {
        // Check if user already has a password set (i.e., has accepted invitation)
        if ($user->email_verified_at) {
            return redirect()->route('crm.users.index')
                ->with('error', 'Cet utilisateur a déjà activé son compte.');
        }

        // Generate new invitation token
        $invitationToken = Str::random(64);

        $user->update([
            'invitation_token' => Hash::make($invitationToken),
            'invitation_sent_at' => now(),
        ]);

        // Send invitation email
        $user->notify(new UserInvitation($invitationToken));

        return redirect()->route('crm.users.index')
            ->with('success', 'Email d\'invitation renvoyé avec succès.');
    }

    /**
     * Show the password setup form.
     */
    public function showSetupPassword(Request $request)
    {
        return Inertia::render('Auth/SetupPassword', [
            'email' => $request->email,
            'token' => $request->token,
        ]);
    }

    /**
     * Store the new password for an invited user.
     */
    public function storePassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['token'], $user->invitation_token)) {
            return back()->withErrors(['token' => 'Le lien d\'invitation est invalide ou a expiré.']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'invitation_token' => null,
            'invitation_sent_at' => null,
        ]);

        return redirect()->route('login')
            ->with('success', 'Votre mot de passe a été configuré. Vous pouvez maintenant vous connecter.');
    }
}

