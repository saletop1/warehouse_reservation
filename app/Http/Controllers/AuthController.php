<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        // Validate login credentials
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ], [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
        ]);

        // Attempt to authenticate user
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            // Log successful login
            Log::info('User logged in successfully', [
                'user_id' => Auth::id(),
                'email' => $request->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Redirect to intended URL or dashboard
            return redirect()->intended('/dashboard')
                ->with('success', 'Welcome back, ' . Auth::user()->name . '!');
        }

        // Log failed login attempt
        Log::warning('Failed login attempt', [
            'email' => $request->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Return error if authentication fails
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email')->withInput($request->except('password'));
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        // Get user info before logout
        $user = Auth::user();

        // Log logout activity
        if ($user) {
            Log::info('User logged out', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip()
            ]);
        }

        // Perform logout
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to login page
        return redirect('/')
            ->with('info', 'You have been logged out successfully.');
    }

    /**
     * Show the registration form (optional feature).
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application (optional feature).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        // Validate registration data
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Full name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        // Create new user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Log user registration
        Log::info('New user registered', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip()
        ]);

        // Auto-login after registration
        Auth::login($user);

        return redirect('/dashboard')
            ->with('success', 'Registration successful! Welcome to the system.');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPasswordForm()
    {
        // LANGKAH PENTING: Coba cek view mana yang tersedia
        if (view()->exists('auth.forgot-password')) {
            return view('auth.forgot-password');
        } elseif (view()->exists('auth.email')) {
            return view('auth.email');
        } else {
            // Fallback: tampilkan form sederhana
            return view('auth.passwords.email'); // Coba format Laravel default
        }
    }

    /**
     * Send reset link email
     */
    public function sendResetLinkEmail(Request $request)
    {
        // Validasi
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);
        
        // Kirim reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );
        
        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Show reset password form
     */
    public function showResetPasswordForm(Request $request, $token = null)
    {
        return view('auth.reset', [
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);
        
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
                
                $user->save();
                
                event(new PasswordReset($user));
            }
        );
        
        return $status == Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    /**
     * Show email verification notice
     */
    public function showVerifyEmail()
    {
        return view('auth.verify');
    }

    /**
     * Verify email
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        // Get the user by ID
        $user = User::findOrFail($id);
        
        // Check if the hash matches
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Invalid verification link.');
        }
        
        // Mark email as verified
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            
            Log::info('Email verified', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        }
        
        return redirect()->route('dashboard')
            ->with('success', 'Email verified successfully!');
    }

    /**
     * Send verification email
     */
    public function sendVerificationEmail(Request $request)
    {
        $user = $request->user();
        
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }
        
        $user->sendEmailVerificationNotification();
        
        Log::info('Verification email sent', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
        
        return back()->with('status', 'Verification link sent!');
    }
}